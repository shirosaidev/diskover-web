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


// Get search results from Elasticsearch if the user searched for something
$results = [];

if (isset($_GET)) {
    // get request string from predict_search
    $request = predict_search($_REQUEST['q']);

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
                    'fields' => ['filename^5','path_parent','extension'],
                    'default_operator' => 'OR',
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
    if (!empty($results) && count($results)>0) {
        foreach($results as $arr) {
            $files[] = [ $arr['_type'], $arr['_source']['filename'], $arr['_source']['path_parent'] ];
        }
    }

} else {
    die("no get data");
}

echo '<span style="color:#666;font-size:10px;line-height:1.2em;display:block;">' . $request . '</span>';
echo '<span class="pull-right" style="font-size:11px;color:#ccc;"><strong>' . $total . ' items found</strong></span><br />';

if (!empty($results) && count($results) === 0) {
    echo "<i class=\"glyphicon glyphicon-eye-close\"></i> No results found";
} else {
    // replace any words and characters that we don't want to highlight in red with a space
    $keywords_clean = preg_replace('/\bOR\b|\bAND\b|\bNOT\b|\(|\)|\[|\]|\*|\\|\/|_|-|\'s|&|(\w+):/i', ' ', $request);

    // find and replace any keyword in file with highlighted red html keyword
    foreach($files as $key => $value) {
        // find all words in keywords_clean and add to matches
        preg_match_all('/(\w+)/i', $keywords_clean, $matches);
        if($matches) {
            $reg_exp = '/\b(' . implode('|', $matches[0]) . ')\b/i';
            $file_highlighted = preg_replace($reg_exp, '<span style="font-weight:bolder;color:red!important;">$0</span>', $value[1]);
        }

        if ($value[0] == 'file') {
            // output html with keyword highlights for each file
            echo '<i class="glyphicon glyphicon-file" style="color:#555;display:inline-block;line-height:2.1em;margin:0 auto;"></i> <a href="simple.php?submitted=true&p=1&q=path_parent:' . rawurlencode(escape_chars($value[2])) .' AND filename: ' . rawurlencode(escape_chars($value[1])) . '">' . $file_highlighted . '</a>&nbsp;&nbsp;<span class="searchpath"><a href="simple.php?submitted=true&p=1&q=path_parent:' . rawurlencode(escape_chars($value[2])) . '">' . $value[2] . '</a></span><br />';
        } else {  // directory
            $parentpath = "";
            if ($value[2] === '/') {
                $parentpath = rawurlencode(escape_chars($value[2] . $value[1]));
            } else {
                $parentpath = rawurlencode(escape_chars($value[2] . '/' . $value[1]));
            }
            echo '<i class="glyphicon glyphicon-folder-close" style="color:#555;display:inline-block;line-height:2.1em;margin:0 auto;"></i> <a href="simple.php?submitted=true&p=1&q=path_parent:' . $parentpath . '">' . $file_highlighted . '</a>&nbsp;&nbsp;<span class="searchpath"><a href="simple.php?submitted=true&p=1&q=path_parent:' . rawurlencode(escape_chars($value[2])) . '">' . $value[2] . '</a></span><br />';
        }
    }
}
?>
