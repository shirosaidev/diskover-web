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
require "vars_inc.php";


// Get search results from Elasticsearch
$results = [];

// Connect to Elasticsearch
$client = connectES();

// curent page
$p = $_REQUEST['p'];

// type of export
$export = $_REQUEST['export'];

// doctype of export
$export_type = $_REQUEST['export_type'];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = $export_type;

// Scroll parameter alive time
$searchParams['scroll'] = "1m";

// search size (number of results to return per page)
if (!empty($_REQUEST['resultsize'])) {
    $searchParams['size'] = $_REQUEST['resultsize'];
    createCookie("resultsize", $_REQUEST['resultsize']);
} elseif (getCookie("resultsize") != "") {
    $searchParams['size'] = getCookie("resultsize");
} else {
    $searchParams['size'] = Constants::SEARCH_RESULTS;
}

// match all if search field empty
if (empty($_REQUEST['q'])) {
    $searchParams['body'] = [
        'query' => [
            'match_all' => (object) []
        ]
    ];
    // match what's in the search field
} else {
    $searchParams['body'] = [
        'query' => [
            'query_string' => [
                'query' => $_REQUEST['q'],
                'analyze_wildcard' => 'true'
            ]
        ]
    ];
}

// Sort search results
$searchParams = sortSearchResults($_REQUEST, $searchParams);

try {
    // Send search query to Elasticsearch and get scroll id and first page of results
    $queryResponse = $client->search($searchParams);
} catch (Exception $e) {
    //echo 'Message: ' .$e->getMessage();
}

// set total hits
$total = $queryResponse['hits']['total'];

// Get the first scroll_id
$scroll_id = $queryResponse['_scroll_id'];

if ((string)$p === "all") {
    // Loop through all the pages of results
    while (count($queryResponse['hits']['hits']) > 0) {

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
    }
} else {
    $i = 1;
    // Loop through all the pages of results
    while ($i <= ceil($total/$searchParams['size'])) {

    // check if we have the results for the page we are on
        if ($i == $p) {
            // Get results
            foreach ($queryResponse['hits']['hits'] as $hit) {
                $results[] = $hit;
            }
            // end loop
            break;
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
}

function array2csv(array &$array)
{
   if (count($array) == 0) {
     return null;
   }
   ob_start();
   $df = fopen("php://output", 'w');
   fputcsv($df, array_keys(reset($array)));
   foreach ($array as $row) {
      fputcsv($df, $row);
   }
   fclose($df);
   return ob_get_clean();
}

// separate doc source by file and directory (separate exports since different fields)
$results_source_directory = [];
$results_source_file = [];

foreach ($results as $arr) {
    if ($arr['_type'] == "file") {
        $results_source_file[] = $arr['_source'];
    } else {
        $results_source_directory[] = $arr['_source'];
    }
}

// output results
// disable caching
$now = gmdate("D, d M Y H:i:s");
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
header("Last-Modified: {$now} GMT");
// force download
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
// disposition / encoding on response body
header("Content-Disposition: attachment;filename=diskover_export_{$export_type}.{$export}");
header("Content-Transfer-Encoding: binary");

// check if we are exporting file or directory
if ($export_type == 'file') {
    if (count($results_source_file) > 0) {
        if ($export == "json") {
            echo json_encode($results_source_file);
        } elseif ($export == "csv") {
            echo array2csv($results_source_file);
        }
    }
} else {
    if (count($results_source_directory) > 0) {
        if ($export == "json") {
            echo json_encode($results_source_directory);
        } elseif ($export == "csv") {
            echo array2csv($results_source_directory);
        }
    }
}
?>
