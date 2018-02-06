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

// get dir total size and file count
$dirinfo = get_dir_info($client, $esIndex, $path, $filter, $mtime);

// check for error
if ($dirinfo[0] === 0) {
    echo "Error: diskover index " . $esIndex . " has no data";
    exit;
}

$data = [
    "name" => $path,
    "size" => $dirinfo[0],
    "count" => $dirinfo[1],
    "children" => walk_tree($client, $esIndex, $path, $filter, $mtime, $depth=0, $maxdepth=1, $use_count)
];

echo json_encode($data);
