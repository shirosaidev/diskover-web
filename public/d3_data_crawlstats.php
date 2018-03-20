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


// Get search results from Elasticsearch for crawl stats showing the
// directories that took the most amount of time to crawl
$num = 50; // number of items to get
$results = [];
$searchParams = [];
$slowestcrawlers = [];
$sizes = [];
$filecount = [];
$directorycount = [];
$paths = [];
$dirnames = [];
$crawltimes = [];

$searchParams['index'] = $esIndex;
$searchParams['type'] = 'crawlstat';
$searchParams['body'] = [
    '_source' => ['path', 'crawl_time'],
    'size' => $num,
    'query' => [
        'match_all' => (object) []
     ],
     'sort' => [
         'crawl_time' => [
             'order' => 'desc'
         ]
     ]
];
$queryResponse = $client->search($searchParams);
foreach ($queryResponse['hits']['hits'] as $key => $value) {
    if ($value['_source']['path'] !== $path) {
        $elapsed = number_format($value['_source']['crawl_time'], 3);
        $slowestcrawlers[] = ['path' => $value['_source']['path'], 'crawltime' => (float)$elapsed];
        $dirnames[] = basename($value['_source']['path']);
        $paths[] = $value['_source']['path'];
        $crawltimes[] = (float)$elapsed;
    }
}

// grab the total items for each directory
$searchParams['type'] = 'directory';
foreach ($slowestcrawlers as $key => $value) {
    $searchParams['body'] = [
    '_source' => ['filesize', 'items'],
     'size' => 1,
     'query' => [
       'query_string' => [
         'query' => 'path_parent:' . escape_chars(dirname($value['path'])) . ' AND filename:' . escape_chars(basename($value['path']))
       ]
     ]
    ];
    $queryResponse = $client->search($searchParams);
    $slowestcrawlers[$key]['filesize'] = $queryResponse['hits']['hits'][0]['_source']['filesize'];
    $slowestcrawlers[$key]['items'] = $queryResponse['hits']['hits'][0]['_source']['items'];
    $sizes[] = $queryResponse['hits']['hits'][0]['_source']['filesize'];
    $items[] = $queryResponse['hits']['hits'][0]['_source']['items'];
}

// grab the total sub directories for each directory
$searchParams['type'] = 'directory';
foreach ($slowestcrawlers as $key => $value) {
    $searchParams['body'] = [
    '_source' => [],
     'size' => 0,
     'query' => [
       'query_string' => [
         'query' => 'path_parent:' . escape_chars($value['path']) . ' OR path_parent:' . escape_chars($value['path']) . '\/*',
         'analyze_wildcard' => 'true'
       ]
     ]
    ];
    $queryResponse = $client->search($searchParams);
    $directorycount[] = $queryResponse['hits']['total'];
    $slowestcrawlers[$key]['directorycount'] = $queryResponse['hits']['total'];
}
// grab the total files for each directory
$searchParams['type'] = 'file';
foreach ($slowestcrawlers as $key => $value) {
    $searchParams['body'] = [
    '_source' => [],
     'size' => 0,
     'query' => [
       'query_string' => [
         'query' => 'path_parent:' . escape_chars($value['path']) . ' OR path_parent:' . escape_chars($value['path']) . '\/*',
         'analyze_wildcard' => 'true'
       ]
     ]
    ];
    $queryResponse = $client->search($searchParams);
    $filecount[] = $queryResponse['hits']['total'];
    $slowestcrawlers[$key]['filecount'] = $queryResponse['hits']['total'];
}

$data = [
    "slowestcrawlers" => $slowestcrawlers,
    "filecount" => $filecount,
    "directorycount" => $directorycount,
    "dirnames" => $dirnames,
    "paths" => $paths,
    "crawltimes" => $crawltimes,
    "sizes" => $sizes,
    "items" => $items
];

echo json_encode($data);

?>
