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


// Get search results from Elasticsearch for worker usage
$results = [];
$searchParams = [];

// Setup search query
$worker_usage = [];
$workers = [];

// get all the worker info
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'worker';
$searchParams['body'] = [
    '_source' => ['worker_name'],
    'size' => 100,
    'query' => [
        'match_all' => (object) []
    ]
];
// Send search query to Elasticsearch
$queryResponse = $client->search($searchParams);

foreach ($queryResponse['hits']['hits'] as $key => $value) {
    $workers[] = $value['_source']['worker_name'];
}
$workers = array_unique($workers);

foreach ($workers as $key => $value) {
    $searchParams['index'] = $esIndex;
    $searchParams['type'] = 'worker';
    $searchParams['body'] = [
       'size' => 0,
        'aggs' => [
          'total_files' => [
            'sum' => [
              'field' => 'file_count'
            ]
          ],
          'total_dirs' => [
            'sum' => [
              'field' => 'dir_count'
            ]
          ]
        ],
        'query' => [
          'match' => [
            'worker_name' => $value
          ]
        ]
    ];
    $queryResponse = $client->search($searchParams);
    $totalfilecount = $queryResponse['aggregations']['total_files']['value'];
    $totaldircount = $queryResponse['aggregations']['total_dirs']['value'];

    if ($totalfilecount > 0 || $totaldircount > 0) {
        $worker_usage[] = [ 'worker_name' => $value, 'file' => $totalfilecount, 'directory' => $totaldircount ];
    }
}

echo json_encode($worker_usage);

?>
