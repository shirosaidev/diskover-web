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


// get mtime in ES format
$mtime = getmtime($mtime);

// get indexname
$index = $_GET['index'];

// create list to hold file/directory data from ES
$data = [];

// get dir total size and file count for index
$dirinfo = get_dir_info($client, $index, $path, $filter, $mtime);

// append index info to data list
$data[] = [
    "name" => $path,
    "size" => $dirinfo[0],
    "count" => $dirinfo[1],
    "count_files" => $dirinfo[2],
    "count_subdirs" => $dirinfo[3],
    "modified" => $dirinfo[4],
    "type" => 'directory',
    "children" => walk_tree($client, $index, $path, $filter, $mtime, $depth=0, $maxdepth, $use_count, $show_files)
];

// output json encoded data for d3
echo json_encode($data);
