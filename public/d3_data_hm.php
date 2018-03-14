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
        "children" => walk_tree($client, $value, $path, $filter, $mtime, $depth=0, $maxdepth, $use_count, $show_files)
    ];
}

// output json encoded data for d3
echo json_encode($data);
