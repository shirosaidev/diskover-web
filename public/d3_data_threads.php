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
require "vars_inc.php";


// Get search results from Elasticsearch for thread usage
$results = [];
$searchParams = [];

// Setup search query
$thread_usage = [];

# show up to 40 threads in chart
for ($i=0; $i < 40; $i++) {
    // Execute the search
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = 'file';
    $searchParams['body'] = [
     'size' => 0,
     'query' => [
       'match' => [
         'indexing_thread' => $i
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
         'indexing_thread' => $i
       ]
     ]
    ];

    // Send search query to Elasticsearch
    $queryResponseDir = $client->search($searchParams);
    if ($queryResponseFile['hits']['total'] || $queryResponseDir['hits']['total'] > 0) {
        $thread_usage[$i] = [ 'thread' => $i, 'file' => $queryResponseFile['hits']['total'], 'directory' => $queryResponseDir['hits']['total'] ];
    }
}

echo json_encode($thread_usage);

?>
