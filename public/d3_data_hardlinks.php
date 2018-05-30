<?php
/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Diskover.php";
require "d3_inc.php";


$minhardlinks = $_GET['minhardlinks'];

// Get search results from Elasticsearch for harlinks

// find all the files with hardlinks >= minhardlinks
$inodes = [];
$results = [];
$searchParams = [];
$totalHardLinkCount = 0;
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';
$searchParams['size'] = 1000;
// Scroll parameter alive time
$searchParams['scroll'] = "1m";

$searchParams['body'] = [
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
          'sort' => [
              'hardlinks' => [
                  'order' => 'desc'
              ]
          ]
];
$queryResponse = $client->search($searchParams);

// set total hits
$total = $queryResponse['hits']['total'];

// Get the first scroll_id
$scroll_id = $queryResponse['_scroll_id'];

$i = 1;
// Loop through all the pages of results
while ($i <= ceil($total/$searchParams['size'])) {
    // Get results
    foreach ($queryResponse['hits']['hits'] as $hit) {
        $results[] = $hit;
    }

    // Execute a Scroll request and repeat
    $queryResponse = $client->scroll(
    [
        "scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
        "scroll" => "1m"           // and the same timeout window
    ]
);

    // Get the scroll_id for next page of results
    $scroll_id = $queryResponse['_scroll_id'];
    $i += 1;
}

// grab the inodes and put into inodes list
foreach ($results as $arr) {
    $inodes[] = $arr['_source']['inode'];
}

// just get unique inodes
$inodes_unique = array_unique($inodes);

// find files that match each inode
$inodes_files = [];
$inodes_sizes = [];
$inodes_paths = [];
$results = [];
$searchParams = [];
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

foreach ($inodes_unique as $key => $value) {
    $searchParams['body'] = [
        'size' => 100,
        '_source' => ['filename', 'path_parent', 'filesize'],
            'query' => [
                'match' => [
                'inode' => $value
            ]
        ]
    ];
    $queryResponse = $client->search($searchParams);
    $results = $queryResponse['hits']['hits'];

    $inodes_files[$value] = [];
    foreach($results as $k => $v) {
        $inodes_files[$value][] = $v['_source']['path_parent'] . '/' . $v['_source']['filename'];
        $arr = array_filter(explode('/', $v['_source']['path_parent']));
        $inodes_paths[] = '/'.implode('/',$arr);
        while((array_pop($arr) and !empty($arr))){
            $inodes_paths[] = '/'.implode('/',$arr);
        };
    }
    $inodes_sizes[$value] = $v['_source']['filesize'];
}

// just get unique paths
$inodes_paths_unique = array_unique($inodes_paths);

// build data array for d3
foreach($inodes_unique as $key => $value) {
    $data[0][] = [
        "label" => $value,
        "count" => sizeof($inodes_files[$value]),
        "files" => $inodes_files[$value],
        "size" => $inodes_sizes[$value]
    ];
}

foreach($inodes_files as $key => $value) {
    foreach($value as $k => $v) {
      $data[1][] = [
          "source" => $v,
          "target" => dirname($v),
          "inode" => $key,
          "count" => sizeof($value)
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
