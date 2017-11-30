<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;

error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// get index cookies
$esIndex = getenv('APP_ES_INDEX') ?: getCookie('index');
$esIndex2 = getenv('APP_ES_INDEX2') ?: getCookie('index2');

require __DIR__ . "/d3_inc.php";

// create list of indices
$indices = [$esIndex, $esIndex2];

// create list to hold file/directory data from ES
$data = [];

// get dir total size and file count for each index
foreach ($indices as $key => $value) {
    $dirinfo = get_dir_info($client, $value, $path, $filter, $mtime);

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
