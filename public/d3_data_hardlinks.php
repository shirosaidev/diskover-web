<?php
/*
Copyright (C) Chris Park 2017-2019
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Diskover.php";
require "d3_inc.php";


$minhardlinks = $_GET['minhardlinks'];

// get mtime in ES format
$mtime = getmtime($mtime);

// Get search results from Elasticsearch for harlinks

// find all the files with hardlinks >= minhardlinks
$results = [];
$searchParams = [];
$totalHardLinkCount = 0;
$searchParams['index'] = $esIndex;
$searchParams['type'] = 'file';

$searchParams['body'] = [
    'size' => 0,
    '_source' => ['hardlinks','inode'],
    'query' => [
          'bool' => [
            'must' => [
                  'wildcard' => [ 'path_parent' => $path . '*' ]
              ],
              'filter' => [
                  'range' => [
                      'filesize' => [
                            'gte' => $filter
                      ]
                  ],
                  'range' => [
                        'hardlinks' => [
                            'gte' => $minhardlinks
                        ]
                  ]
              ],
              'should' => [
                  'range' => [
                      'last_modified' => [
                          'lte' => $mtime
                      ]
                  ]
              ]
          ]
      ],
      'aggs' => [
          'top_hardlinks' => [
              'terms' => [
                  'field' => 'inode',
                  'size' => 50
              ]
          ]
      ]
  ];

$queryResponse = $client->search($searchParams);

// Get top hardlinks
$results = $queryResponse['aggregations']['top_hardlinks']['buckets'];

$inodes_unique = [];
foreach ($results as $result) {
    $inodes_unique[] = $result['key'];
}

if (sizeof($inodes_unique) === 0) {
  echo "No Elasticsearch results";
  die;
}

// find files that match each inode
$inodes_files = [];
$inodes_paths = [];
$results = [];
$searchParams = [];
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

foreach ($inodes_unique as $key => $value) {
    $searchParams['body'] = [
        'size' => 50,
        '_source' => ['filename', 'path_parent'],
            'query' => [
              'bool' => [
                'must' => [
                      'match' => [ 'inode' => $value ]
                  ],
                  'filter' => [
                      'range' => [
                          'filesize' => [
                                'gte' => $filter
                          ]
                      ]
                  ],
                  'should' => [
                      'range' => [
                          'last_modified' => [
                              'lte' => $mtime
                          ]
                      ]
                  ]
              ]
          ]
    ];
    $queryResponse = $client->search($searchParams);
    $results = $queryResponse['hits']['hits'];

    // remove key and continue if count of results < min hardlinks
    if (sizeof($results) < $minhardlinks) {
        unset($inodes_unique[$key]);
        continue;
    }

    $inodes_files[$value] = [];
    foreach($results as $k => $v) {
        $inodes_files[$value][] = $v['_source']['path_parent'] . '/' . $v['_source']['filename'];
        $arr = array_filter(explode('/', $v['_source']['path_parent']));
        $inodes_paths[] = '/'.implode('/',$arr);
        while((array_pop($arr) and !empty($arr))){
            $inodes_paths[] = '/'.implode('/',$arr);
        };
    }
}

// just get unique paths
$inodes_paths_unique = array_unique($inodes_paths);


// get total file sizes for each inode
$results = [];
$searchParams = [];
$inode_counts = [];
$totalFilesize = 0;

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

// Execute the search
foreach ($inodes_unique as $key => $value) {
    $searchParams['body'] = [
       'size' => 0,
       'query' => [
         'match' => [
           'inode' => $value
         ]
     ],
      'aggs' => [
        'total_size' => [
          'sum' => [
            'field' => 'filesize'
          ]
        ]
      ]
    ];

    // Send search query to Elasticsearch
    $queryResponse = $client->search($searchParams);

    // Get total count of files for inode
    $inode_counts[$value] = $queryResponse['hits']['total'];
    $inode_sizes[$value] = $queryResponse['aggregations']['total_size']['value'];
    $totalFilesize += $inode_sizes[$value];
}


// build data array for d3
foreach($inodes_unique as $key => $value) {
    $data[0][] = [
        "label" => $value,
        "count" => $inode_counts[$value],
        "files" => $inodes_files[$value],
        "size" => $inode_sizes[$value]
    ];
}

foreach($inodes_files as $key => $value) {
    foreach($value as $k => $v) {
      $data[1][] = [
          "source" => $v,
          "target" => dirname($v),
          "inode" => $key,
          "count" => $inode_counts[$key]
      ];
    }
}

foreach($inodes_paths_unique as $key => $value) {
      $data[1][] = [
          "source" => $value,
          "target" => dirname($value)
      ];
}


echo json_encode($data);
