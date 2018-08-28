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


// Get search results from Elasticsearch for crawl stats showing the
// directories that took the most amount of time to crawl
$num = 50; // number of items to get
$results = [];
$searchParams = [];
$slowestcrawlers = [];
$sizes = [];
$items = [];
$filecount = [];
$directorycount = [];
$paths = [];
$dirnames = [];
$crawltimes = [];

$searchParams['index'] = $esIndex;
$searchParams['type'] = 'directory';
$searchParams['body'] = [
    '_source' => ['path_parent', 'filename', 'crawl_time', 'items', 'items_files', 'items_subdirs', 'filesize'],
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
    $fullpath = $value['_source']['path_parent'] . '/' . $value['_source']['filename'];
    if ($fullpath !== $_SESSION['rootpath']) {
        $elapsed = number_format($value['_source']['crawl_time'], 3);
        $slowestcrawlers[] = ['path' => $fullpath, 'crawltime' => (float)$elapsed, 'filesize' => $value['_source']['filesize'], 'items' => $value['_source']['items'], 'directorycount' => $value['_source']['items_subdirs'], 'filecount' => $value['_source']['items_files']];
        $directorycount[] = $value['_source']['items_subdirs'];
        $filecount[] = $value['_source']['items_files'];
        $sizes[] = $value['_source']['filesize'];
        $items[] = $value['_source']['items'];
        $dirnames[] = basename($fullpath);
        $paths[] = $fullpath;
        $crawltimes[] = (float)$elapsed;
    }
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
