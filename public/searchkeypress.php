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

// check for index in url
if (isset($_GET['index'])) {
    $esIndex = $_GET['index'];
} else {
    // get index from env var or cookie
    $esIndex = getenv('APP_ES_INDEX') ?: getCookie('index');
}

// Get search results from Elasticsearch if the user searched for something
$results = [];

if (isset($_GET)) {
    // Grab all the smart searches from file
    $smartsearches = get_smartsearches();

    // check for path input
    if (strpos($_REQUEST['q'], '/') !== false && strpos($_REQUEST['q'], 'path_parent') === false) {
        $request = escape_chars($_REQUEST['q']);
        $request = 'path_parent:' . $request . '*';
    } elseif (strpos($_REQUEST['q'], '!') === 0) {  # ! smart search keyword
        if ($_REQUEST['q'] === '!') {
            echo '<span class="text-info"><i class="glyphicon glyphicon-share-alt"></i> Enter in a smart search name like <strong>!tmp</strong> or <strong>!doc</strong> or <strong>!img</strong>.</span>';
            echo '<br /><span class="text-info">Smart searches:</span><br />';
            foreach($smartsearches as $arr) {
                 echo '<strong>' . $arr[0] . '</strong>&nbsp;&nbsp;';
            }
            die();
        } elseif (preg_match('/^\!(\w+)/', $_REQUEST['q']) !== false) {
            // check if requested smart search is in smartsearches array
            $inarray = false;
            foreach($smartsearches as $arr) {
                if(in_array($_REQUEST['q'], $arr)) {
                    $inarray = true;
                    $smartsearch_query = $arr[1];
                }
            }
            if ($inarray) {
                $request = $smartsearch_query;
            } else {
                echo '<span class="text-info"><i class="glyphicon glyphicon-exclamation-sign"></i> No matching smart search name.</span>';
                echo '<br /><span class="text-info">Smart searches:</span><br />';
                foreach($smartsearches as $arr) {
                     echo '<strong>' . $arr[0] . '</strong>&nbsp;&nbsp;';
                }
                die();
            }
        }
    } else {
        $request = $_REQUEST['q'];
    }

    // Connect to Elasticsearch
    $client = connectES();

    // Setup search query
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = ($_REQUEST['doctype']) ? $_REQUEST['doctype'] : 'file,directory';

    // search size (number of results to return
    $searchParams['size'] = 10;

    // match all if search field empty
    if (empty($_REQUEST['q'])) {
        $searchParams['body'] = [
            '_source' => ['filename', 'path_parent'],
            'query' => [
                'match_all' => (object) []
            ]
        ];
        // match what's in the search field
    } else {
        $searchParams['body'] = [
            '_source' => ['filename', 'path_parent'],
            'query' => [
                'query_string' => [
                    'query' => $request,
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
    if(!$total) $total = 0;
    $results = $queryResponse['hits']['hits'];

    $files = [];
    // format for output
    if (count($results)>0) {
        foreach($results as $arr) {
            $files[] = [ $arr['_type'], $arr['_source']['filename'], $arr['_source']['path_parent'] ];
        }
    }

} else {
    die("no get data");
}

echo '<span style="color:#666;font-size:11px;">' . $request . '</span>';
echo '<span class="pull-right" style="font-size:11px;color:#ccc;"><strong>' . $total . ' items found</strong></span><br />';

if (count($results) === 0) {
    echo "<i class=\"glyphicon glyphicon-eye-close\"></i> No results found";
} else {
    // replace any words and characters that we don't want to highlight in red with a space
    $keywords_clean = preg_replace('/OR|AND|NOT|\(|\)|\[|\]|\*|\\|\/|(w+):/i', ' ', $request);

    // find and replace any keyword in file with highlighted red html keyword
    foreach($files as $key => $value) {
        // find all words in keywords_clean and add to matches
        preg_match_all('/\w+/', $keywords_clean, $matches);
        if($matches) {
            $reg_exp = '/\b(' . implode('|', $matches[0]) . ')\b/i';
            $file_highlighted = preg_replace($reg_exp, '<span style="font-weight:bolder;color:red!important;">$0</span>', $value[1]);
        }

        if ($value[0] == 'file') {
            echo '<i class="glyphicon glyphicon-file" style="color:#555;"></i> ';
        } else {
            echo '<i class="glyphicon glyphicon-folder-close" style="color:#555;"></i> ';
        }
        // output html with keyword highlights for each file
        echo '<a href="simple.php?submitted=true&p=1&q=&quot;' . rawurlencode($value[1]) . '&quot;">' . $file_highlighted . '</a>&nbsp;&nbsp;<span class="searchpath"><a href="simple.php?submitted=true&p=1&q=&quot;' . rawurlencode($value[2]) . '&quot;">' . $value[2] . '</a></span><br />';
    }
}
?>
