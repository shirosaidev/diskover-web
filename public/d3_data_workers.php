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


// Get search results from Elasticsearch for worker bot usage
$results = [];
$searchParams = [];

$worker_usage = [];
$workers = [];

// Setup search query
// get all the worker names
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
    // Execute the search
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = 'file';
    $searchParams['body'] = [
     'size' => 0,
     'query' => [
       'match' => [
         'worker_name' => $value
       ]
     ]
    ];
    // Send search query to Elasticsearch
    $queryResponseFile = $client->search($searchParams);

    // Execute the search
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = 'directory';
    // Execute the search
    $searchParams['body'] = [
     'size' => 0,
     'query' => [
       'match' => [
         'worker_name' => $value
       ]
     ]
    ];

    // Send search query to Elasticsearch
    $queryResponseDir = $client->search($searchParams);

    if ($queryResponseFile['hits']['total'] || $queryResponseDir['hits']['total'] > 0) {
        $worker_usage[] = [ 'worker_name' => $value, 'file' => $queryResponseFile['hits']['total'], 'directory' => $queryResponseDir['hits']['total'] ];
    }
}

echo json_encode($worker_usage);

?>
