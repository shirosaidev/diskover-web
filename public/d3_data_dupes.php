<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Diskover.php";
require "d3_inc.php";


// Get search results from Elasticsearch for duplicate files

// find all the files with dupe_md5 values that are not empty ""
$md5s = [];
$results = [];
$searchParams = [];
$totalMd5Count = 0;
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';
$searchParams['size'] = 1000;
// Scroll parameter alive time
$searchParams['scroll'] = "1m";

$searchParams['body'] = [
    '_source' => ['dupe_md5'],
        'query' => [
            'query_string' => [
            'query' => 'dupe_md5:(NOT "")'
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

// grab the md5's and put into md5s list
foreach ($results as $arr) {
    $md5s[] = $arr['_source']['dupe_md5'];
}

// just get unique md5s
$md5s_unique = array_unique($md5s);

// find files that match each md5
$md5s_files = [];
$results = [];
$searchParams = [];
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

foreach ($md5s_unique as $key => $value) {
    $searchParams['body'] = [
        'size' => 100,
        '_source' => ['filename', 'path_parent'],
            'query' => [
                'match' => [
                'dupe_md5' => $value
            ]
        ]
    ];
    $queryResponse = $client->search($searchParams);
    $results = $queryResponse['hits']['hits'];

    $md5s_files[$value] = [];
    foreach($results as $k => $v) {
        $md5s_files[$value][] = $v['_source']['path_parent'] . '/' . $v['_source']['filename'];
    }
}


// get total file sizes for each md5
$results = [];
$searchParams = [];
$md5_counts = [];
$totalFilesize = 0;

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

// Execute the search
foreach ($md5s_unique as $key => $value) {
    $searchParams['body'] = [
       'size' => 0,
       'query' => [
         'match' => [
           'dupe_md5' => $value
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

    // Get total count of files for md5
    $md5_counts[$value] = $queryResponse['hits']['total'];
    $md5_sizes[$value] = $queryResponse['aggregations']['total_size']['value'];
    $totalFilesize += $md5_sizes[$value];
}

// build data array for d3
foreach($md5s_unique as $key => $value) {
    // only include md5's > 0.1 of total size or total dupe count
    if (($md5_sizes[$value] / $totalFilesize * 100) > 0.1) {
        $data[] = [
            "label" => $value,
            "size" => $md5_sizes[$value],
            "count" => $md5_counts[$value],
            "files" => $md5s_files[$value]
        ];
    }
}

echo json_encode($data);
