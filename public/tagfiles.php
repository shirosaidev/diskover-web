<?php
/*
Copyright (C) Chris Park 2017-2019
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Diskover.php";


$docsupdated = 0;

function get_all_docs($client, $index, $path, $doctype, $recursive) {
    $docs = [];
    $searchParams['body'] = [];
    $searchParams['index'] = $index;
    $searchParams['size'] = 1000;
    $searchParams['type'] = $doctype;
    // Scroll parameter alive time
    $searchParams['scroll'] = "1m";
    // diff query if root path /
    if ($path === '/') {
        $query = 'path_parent: \/ OR path_parent: \/*\/*';
    } else {
        // escape special characters
        $path = escape_chars($path);
        if ($recursive) {
            $query = '(path_parent:' . $path . ' OR path_parent:' . $path . '\/*)';
        } else {
            $query = '(path_parent:' . $path . ' AND NOT path_parent:' . $path . '\/*)';
        }
    }
    $searchParams['body'] = [
        'query' => [
            'query_string' => [
            'query' => $query,
            'analyze_wildcard' => true
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
            $docs[] = $hit;
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

    return $docs;
}


function multi_tag($client, $result, $doctype, $recursive) {
    $docsupdated = 0;
    $path = $result['_source']['path_parent'] . '/' . $result['_source']['filename'];
    if ($path === "//") { $path = "/"; }  // root
    $tag = $result['_source']['tag'];
    $tag_custom = $result['_source']['tag_custom'];
    $docs = get_all_docs($client, $_POST['docindex'], $path, $doctype, $recursive);
    // update tags for all matching docs (bulk update)
    $multi_params = ['body' => []];
    // refresh index so we see results right away when page reloads
    $multi_params['refresh'] = true;
    foreach ($docs as $doc) {
        $multi_params['body'][] = [
            'update' => [
                '_index' => $doc['_index'],
                '_type' => $doc['_type'],
                '_id' => $doc['_id']
            ]
        ];
        $doc['_source']['tag'] = $tag;
        $doc['_source']['tag_custom'] = $tag_custom;
        $multi_params['body'][] = [
            'doc' => $doc['_source']
        ];
        
        $docsupdated += 1;
        
        // stop and make api call every 1000 docs
        if ($docsupdated % 1000 == 0) {
            $result = $client->bulk($multi_params);
            unset($result);
            unset($multi_params);
            $multi_params = ['body' => []];
        }

    }
    // update any remaining docs
    $result = $client->bulk($multi_params);

    return $docsupdated;
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
            $docsupdated = multi_tag($client, $result, 'directory', true);
        } elseif ($_POST['tag'] === "tagall_files_recurs") {
            $docsupdated = multi_tag($client, $result, 'file', true);
        } elseif ($_POST['tag'] === "tagall_subdirs_norecurs") {
            $docsupdated = multi_tag($client, $result, 'directory', false);
        } elseif ($_POST['tag'] === "tagall_files_norecurs") {
            $docsupdated = multi_tag($client, $result, 'file', false);
        } elseif ($_POST['tag'] === "tagall_ids_onpage") {
            // copy tag and tag_custom to all ids on page
            $tag = $result['_source']['tag'];
            $tag_custom = $result['_source']['tag_custom'];
            $ids = $_POST['idsonpage'];
            $multi_params = ['body' => []];
            $multi_params['refresh'] = true;
            foreach ($ids as $id) {
                $params = [];
                $params['id'] = $id['id'];
                $params['index'] = $esIndex;
                $params['type'] = $id['type'];
                $result = $client->get($params);
                unset($params);
                $result['_source']['tag'] = $tag;
                $result['_source']['tag_custom'] = $tag_custom;
                $multi_params['body'][] = [
                    'update' => [
                        '_index' => $esIndex,
                        '_type' => $id['type'],
                        '_id' => $id['id']
                    ]
                ];
                $multi_params['body'][] = [
                    'doc' => $result['_source']
                ];

                $docsupdated += 1;

                // stop and make api call every 1000 docs
                if ($docsupdated % 1000 == 0) {
                    $result = $client->bulk($multi_params);
                    unset($result);
                    unset($multi_params);
                    $multi_params = ['body' => []];
                }
            }
            // update any remaining docs
            $result = $client->bulk($multi_params);
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
