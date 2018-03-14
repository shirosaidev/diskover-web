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

// get mtime in ES format
$mtime = getmtime($mtime);

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
    "children" => get_file_mtime($client, $esIndex, $path, $filter, $mtime)
];

echo json_encode($data);
