<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// Connect to Elasticsearch
$client = connectES();

// Get all files in sub directories from Elasticsearch
function get_files($client, $path, $filter) {
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
          'query' => 'path_parent:' . $path . '* AND filesize:>' . $filter
        ]
      ]
  ];

  // Send search query to Elasticsearch and get scroll id and first page of results
  $queryResponse = $client->search($searchParams);

  // set total hits
  $total = $queryResponse['hits']['total'];

  // check if too many files
  if ($total > 100000) {
    echo json_encode([ "warning" => "too many files, choose a different path" ]);
    exit;
  }
  // check if no files
  if ($total == 0) {
    echo json_encode([ "info" => "no files found, choose a different path" ]);
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

// Get total directory size from Elasticsearch
function get_dir_size($client, $path) {
  $totalsize = 0;
  $searchParams['body'] = [];

  // Setup search query
  $searchParams['index'] = Constants::ES_INDEX; // which index to search
  $searchParams['type']  = Constants::ES_TYPE;  // which type within the index to search

  $path = addslashes($path);
  $path = addcslashes($path, '/ .+*?[^]($){}~');
  $searchParams['body'] = [
     'size' => 0,
     'query' => [
       'query_string' => [
         'analyze_wildcard' => 'true',
         'query' => 'path_parent:' . $path . '*'
       ]
     ],
      'aggs' => [
        'dir_size' => [
          'sum' => [
            'field' => 'filesize'
          ]
        ]
      ]
  ];

  // Send search query to Elasticsearch
  $queryResponse = $client->search($searchParams);

  // Get total size of directory and all subdirs
  $totalsize = $queryResponse['aggregations']['dir_size']['value'];

  return $totalsize;
}

function get_files_by_file_size($client, $files, $path, $type) {
  $items = [];
  $dirs = [];
  $subdirs = [];

  // find all subdirs under path
  $path1 = addslashes($path);
  $path1 = addcslashes($path1, '/.+*?[^]($)');
  foreach($files as $key => $value) {
    if (preg_match("/" . $path1 . "\//", $value['directory'])) {
      // if we find a matching directory, add it to dirs array
      if (!in_array($value['directory'], $dirs)) {
        $dirs[] = $value['directory'];
      }
    }
    if ($type == "files") {
      // add files to items if directory is same as path
      if ($value['directory'] == $path) {
        $items[] = [
                "name" => $value['name'],
                "size" => $value['size']
              ];
      }
    }
  }
  // create array containing all subdirs of current path
  $depth = count(explode("/", $path));
  foreach ($dirs as $d) {
    $arr = explode("/", $d);
    if (!in_array($arr[$depth], $subdirs)) {
      $subdirs[] = $arr[$depth];
    }
  }

  // loop through all subdirs and add to items array
  foreach ($subdirs as $d) {
    $newpath = $path."/".$d;

    $items[] = [
            "name" => $d,
            "size" => get_dir_size($client, $newpath),
            "children" => get_files_by_file_size($client, $files, $newpath, $type)
          ];
  }

  return $items;
}

$path = $_GET['path'];
$type = $_GET['type'];
$filter = $_GET['filter'];

// default 1 MB min file size filter so sunburst not too heavy
if (empty($filter)) {
  $filter = 1048576;
}

$files = [];
// get list containing directory, name, size keys of all files
$files = get_files($client, $path, $filter);

$data = [
            "name" => $path,
            "size" => get_dir_size($client, $path),
            "children" => get_files_by_file_size($client, $files, $path, $type)
          ];

echo json_encode($data);

?>
