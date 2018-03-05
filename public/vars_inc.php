<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

use diskover\Constants;

// sets important vars and cookies for index, index2, path

// check for index in url
if (isset($_GET['index'])) {
    $esIndex = $_GET['index'];
    setCookie('index', $esIndex);
} else {
    // get index from env var or cookie
    $esIndex = !empty(getenv('APP_ES_INDEX')) ? getenv('APP_ES_INDEX') : getCookie('index');
    // redirect to select indices page if no index cookie
    if (empty($esIndex)) {
        header("location:selectindices.php");
        exit();
    }
}
// check for index2 in url
if (isset($_GET['index2'])) {
    $esIndex2 = $_GET['index2'];
    setCookie('index2', $esIndex2);
} else {
    $esIndex2 = !empty(getenv('APP_ES_INDEX2')) ? getenv('APP_ES_INDEX2') : getCookie('index2');
}

// set path
$path = isset($_GET['path']) ? $_GET['path'] : getCookie('path');
// check if no path (grab one from ES)
if (empty($path)) {
    $path = get_es_path($client, $esIndex);
    createCookie('path', $path);
}
// remove any trailing slash (unless root)
if ($path !== "/") {
    $path = rtrim($path, '/');
}
