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
    setCookie('index', $esIndex);
} else {
    // get index from env var or cookie
    $esIndex = getenv('APP_ES_INDEX') ?: getCookie('index');
}
// check for index2 in url
if (isset($_GET['index2'])) {
    $esIndex2 = $_GET['index2'];
} else {
    $esIndex2 = getenv('APP_ES_INDEX2') ?: getCookie('index2');
}

require "d3_inc.php";

$path = $_GET['path'] ?: getCookie('path');
// check if no path (grab one from ES)
if (empty($path)) {
    $path = get_es_path($client, $esIndex);
    createCookie('path', $path);
} elseif ($path !== "/") {
    // remove any trailing slash
    $path = rtrim($path, '/');
}
$filter = (int)$_GET['filter'] ?: Constants::FILTER; // file size
$mtime = $_GET['mtime'] ?: Constants::MTIME; // file mtime
// get mtime in ES format
$mtime = getmtime($mtime);
$maxdepth = (int)$_GET['maxdepth'] ?: Constants::MAXDEPTH; // maxdepth
// get use_count
$use_count = (int)$_GET['use_count'] ?: Constants::USE_COUNT; // use count
$use_count = ($use_count === 0) ? false : true;
settype($use_count, 'bool');


// create list of indices
$indices = [$esIndex, $esIndex2];

// create list to hold file/directory data from ES
$data = [];

// get dir total size and file count for each index
foreach ($indices as $key => $value) {
    $dirinfo = get_dir_info($client, $value, $path, $filter, $mtime);

    // check for error
    if ($dirinfo[0] === 0) {
        echo "Error: diskover index " . $value . " has no data";
        exit;
    }

    // append each index info to data list
    $data[] = [
        "name" => $path, //basename($path),
        "size" => $dirinfo[0],
        "count" => $dirinfo[1],
        "children" => walk_tree($client, $value, $path, $filter, $mtime, $depth=0, $maxdepth, $use_count)
    ];
}

// output json encoded data for d3
echo json_encode($data);
