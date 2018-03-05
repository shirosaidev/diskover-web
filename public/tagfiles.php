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


$docsupdated = 0;

function tagall_docs($client, $index, $path, $tag, $tag_custom, $doctype, $recursive) {
    $doc_ids = [];
    $results = [];
    $searchParams['body'] = [];
    $searchParams['index'] = $index;
    $searchParams['type']  = $doctype;
    $searchParams['size'] = 1000;
    // Scroll parameter alive time
    $searchParams['scroll'] = "1m";
    // diff query if root path /
    if ($path === '/') {
        $query = 'path_parent: \/ OR path_parent: \/*\/*';
    } else {
        // escape special characters
        $path = addcslashes($path, '+-&|!(){}[]^"~*?:\/ ');
        if ($recursive) {
            $query = 'path_parent: ' . $path . ' OR path_parent: ' . $path . '\/*';
        } else {
            $query = 'path_parent: ' . $path . ' NOT path_parent: ' . $path . '\/*';
        }
    }
    $searchParams['body'] = [
        '_source' => [],
            'query' => [
                'query_string' => [
                'query' => $query
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

    // grab the es doc id's and put into dir_ids
    foreach ($results as $arr) {
        $doc_ids[] = $arr['_id'];
    }
    return $doc_ids;
}

// submit form data

// Connect to Elasticsearch
$client = connectES();

// check for post data from form
if (isset($_POST)) {
    $doctype = $_POST['doctype'];
    $params = array();
    // refresh index so we see results right away when page reloads
    $params['refresh'] = true;
    $params['id'] = $_POST['id'];
    $params['index'] = $esIndex;
    $params['type'] = $doctype;
    $result = $client->get($params);
    // tag
    if ($_POST['tag']) {
        // clear tag
        if ($_POST['tag'] === "null") {
            $result['_source']['tag'] = "";
            $params['body']['doc'] = $result['_source'];
            $result = $client->update($params);
            $docsupdated += 1;
        } elseif ($_POST['tag'] === "tagall_subdirs_recurs") {
            // apply tag and tag_custom to all subdirs recursively
            $path = $result['_source']['path_parent'] . '/' . $result['_source']['filename'];
            if ($path === "//") { $path = "/"; }  // root
            $tag = $result['_source']['tag'];
            $tag_custom = $result['_source']['tag_custom'];
            // tag all subdirs in ES index
            $dir_ids = tagall_docs($client, $esIndex, $path, $tag, $tag_custom, 'directory', true);
            // update tags for all matching id's
            foreach ($dir_ids as $id) {
                $params = array();
                // refresh index so we see results right away when page reloads
                $params['refresh'] = true;
                $params['id'] = $id;
                $params['index'] = $esIndex;
                $params['type'] = 'directory';
                $result = $client->get($params);
                $result['_source']['tag'] = $tag;
                $result['_source']['tag_custom'] = $tag_custom;
                $params['body']['doc'] = $result['_source'];
                $result = $client->update($params);
                $docsupdated += 1;
            }
        } elseif ($_POST['tag'] === "tagall_files_recurs") {
            // apply tag and tag_custom to all files recursively
            $path = $result['_source']['path_parent'] . '/' . $result['_source']['filename'];
            if ($path === "//") { $path = "/"; }  // root
            $tag = $result['_source']['tag'];
            $tag_custom = $result['_source']['tag_custom'];
            // tag all subdirs in ES index
            $file_ids = tagall_docs($client, $esIndex, $path, $tag, $tag_custom, 'file', true);
            // update tags for all matching id's
            foreach ($file_ids as $id) {
                $params = array();
                // refresh index so we see results right away when page reloads
                $params['refresh'] = true;
                $params['id'] = $id;
                $params['index'] = $esIndex;
                $params['type'] = 'file';
                $result = $client->get($params);
                $result['_source']['tag'] = $tag;
                $result['_source']['tag_custom'] = $tag_custom;
                $params['body']['doc'] = $result['_source'];
                $result = $client->update($params);
                $docsupdated += 1;
            }
        } elseif ($_POST['tag'] === "tagall_ids_onpage") {
            // copy tag and tag_custom to all ids on page
            $tag = $result['_source']['tag'];
            $tag_custom = $result['_source']['tag_custom'];
            // tag all ids on page in ES index
            $ids = $_POST['idsonpage'];
            // update tags for all matching id's
            foreach ($ids as $id) {
                $params = array();
                // refresh index so we see results right away when page reloads
                $params['refresh'] = true;
                $params['id'] = $id['id'];
                $params['index'] = $esIndex;
                $params['type'] = $id['type'];
                $result = $client->get($params);
                $result['_source']['tag'] = $tag;
                $result['_source']['tag_custom'] = $tag_custom;
                $params['body']['doc'] = $result['_source'];
                $result = $client->update($params);
                $docsupdated += 1;
            }
        } else {
            $result['_source']['tag'] = $_POST['tag'];
            $params['body']['doc'] = $result['_source'];
            $result = $client->update($params);
            $docsupdated += 1;
        }
    }
    // custom tag
    if ($_POST['tag_custom']) {
        // clear tag
        if ($_POST['tag_custom'] == "null") {
            $result['_source']['tag_custom'] = "";
        } else {
            $result['_source']['tag_custom'] = $_POST['tag_custom'];
        }
        $params['body']['doc'] = $result['_source'];
        $result = $client->update($params);
        $docsupdated += 1;
    }
    // new custom tag
    if ($_POST['tagtext']) {
        // save the text contents
        add_custom_tag($_POST['tagtext']);
        $result['_source']['tag_custom'] = trim(explode('|', $_POST['tagtext'])[0]);
        $params['body']['doc'] = $result['_source'];
        $result = $client->update($params);
        $docsupdated += 1;
    }
} else {
    die("no post data");
}
echo $docsupdated;
?>
