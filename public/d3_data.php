<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// Connect to Elasticsearch
$client = connectES();

// Get search results from Elasticsearch
function es($client, $path) {
  $items = [];
  $searchParams['body'] = [];

  // Setup search query
  $searchParams['index'] = Constants::ES_INDEX; // which index to search
  $searchParams['type']  = Constants::ES_TYPE;  // which type within the index to search

  // Scroll parameter alive time
  $searchParams['scroll'] = "1m";

  // number of results to return per page
  $searchParams['size'] = 100;

  $path = addslashes($path);
  $path = addcslashes($path, '/ .+*?[^]($)');
  $searchParams['body'] = [
      'query' => [
        'query_string' => [
          'analyze_wildcard' => 'true',
          'query' => 'path_parent:' . $path . '*'
        ]
      ]
  ];

  // Send search query to Elasticsearch and get scroll id and first page of results
  $queryResponse = $client->search($searchParams);

  // set total hits
  $total = $queryResponse['hits']['total'];

  if ($total > 10000) {
    echo json_encode([ "warning" => "too many files, choose a different path" ]);
    exit;
  }

  // Get the first scroll_id
  $scroll_id = $queryResponse['_scroll_id'];

  $i = 1;
  // Loop through all the pages of results
  while ($i <= ceil($total/$searchParams['size'])) {

    // Get results
    $results = $queryResponse['hits']['hits'];
    foreach ($results as $result) {
      $items[] = [
                  "directory" => $result['_source']['path_parent'],
                  "name" => $result['_source']['filename'],
                  "size" => $result['_source']['filesize']
                ];
    }

    // Execute a Scroll request and repeat
    $queryResponse = $client->scroll([
            "scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
            "scroll" => "1m"           // and the same timeout window
        ]
    );

    // Get the scroll_id for next page of results
    $scroll_id = $queryResponse['_scroll_id'];
    $i += 1;
  }
  // clear scroll api
  $client->clearScroll([
        "scroll_id" => $scroll_id
      ]
  );

  return $items;
}

function get_files($client, $base_dir) {
  $items = [];
  $items = es($client, $base_dir);
  return $items;
}

function get_dir_size($base_dir, $files) {
  $total_size = 0;
  $files2 = [];
  $base_dir = addslashes($base_dir);
  $base_dir = addcslashes($base_dir, '/.+*?[^]($)');
  foreach($files as $key => $value) {
    if (preg_match('/^' . $base_dir . '/', $value['directory'])) {
      $files2[] = $value;
    }
  }
  foreach ($files2 as $key => $value) {
    $total_size += $value['size'];
  }

  return $total_size;
}

function get_files_by_file_size($path, $files, $type) {
  $items = [];
  $files2 = [];
  $dirs = [];
  $subdirs = [];
  $dir = addslashes($path);
  $dir = addcslashes($dir, '/.+*?[^]($)');
  foreach($files as $key => $value) {
    if (preg_match('/^' . $dir . '\//', $value['directory'])) {
      if (!in_array($value['directory'], $dirs)) {
        $dirs[] = $value['directory'];
      }
    }
    if ($type == "files") {
      if ($value['directory'] == $path) {
        $files2[] = $value;
      }
    }
  }
  $depth = count(explode("/", $path));
  foreach ($dirs as $d) {
    $arr = explode("/", $d);
    if (!in_array($arr[$depth], $subdirs)) {
      $subdirs[] = $arr[$depth];
    }
  }
  if ($type == "files") {
    foreach ($files2 as $f) {
      $items[] = [
              "name" => $f['name'],
              "size" => $f['size']
            ];
    }
  }
  foreach ($subdirs as $d) {
    $newpath = $path."/".$d;
    $items[] = [
            "name" => $d,
            "size" => get_dir_size($newpath, $files),
            "children" => get_files_by_file_size($newpath, $files, $type)
          ];
  }

  return $items;
}

$path = $_GET['path'];
$type = $_GET['type'];

$files = [];
$files = get_files($client, $path);

$data = [
            "name" => $path,
            "size" => get_dir_size($path, $files),
            "children" => get_files_by_file_size($path, $files, $type)
          ];

echo json_encode($data);

?>
