<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// Connect to Elasticsearch
$client = connectES();

// Get all files in sub directories from Elasticsearch (walk tree)
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

  //$path = addslashes($path);
  //$path = addcslashes($path, '/ .+*?[^]($)');
  $searchParams['body'] = [
      'query' => [
        'query_string' => [
          //'analyze_wildcard' => 'true',
          'query' => '"' . $path . '" AND filesize: >' . $filter
        ]
      ],
			'sort' => [
				'filesize' => [
					'order' => 'desc'
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

// Get total directory size and file count from Elasticsearch
function get_dir_info($client, $path, $filter) {
  $totalsize = 0;
  $totalcount = 0;
  $searchParams['body'] = [];

  // Setup search query
  $searchParams['index'] = Constants::ES_INDEX; // which index to search
  $searchParams['type']  = Constants::ES_TYPE;  // which type within the index to search

  $searchParams['body'] = [
     'size' => 0,
     'query' => [
       'query_string' => [
         'analyze_wildcard' => 'true',
         'query' => '"' . $path . '" AND filesize: >' . $filter
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

  // Get total count of directory and all subdirs
  $totalcount = $queryResponse['hits']['total'];

  // Get total size of directory and all subdirs
  $totalsize = $queryResponse['aggregations']['dir_size']['value'];

  // Create dirinfo list with size and count
	$dirinfo = ['size'=>$totalsize,'count'=>$totalcount];

  return $dirinfo;
}

function get_dirs($files) {
  $dirs = [];
  // get all the directories
  foreach($files as $key => $value) {
    if (!in_array($value['directory'], $dirs)) {
      $dirs[] = $value['directory'];
    }
  }
  sort($dirs);
  return $dirs;
}

function get_sub_dirs($dirs, $path, $depth) {
  $subdirs = [];
	// escape slashes and special characters
	$path = addslashes($path);
  $path = addcslashes($path, '/.+*?[^]($)');
  // create array containing all subdirs
  foreach ($dirs as $d) {
    if (preg_match("/" . $path . "\//", $d)) {
      $arr = explode("/", $d);
      if (!in_array($arr[$depth], $subdirs)) {
        $subdirs[] = $arr[$depth];
      }
    }
  }
  sort($subdirs);
  return $subdirs;
}

function walk_tree($client, $files, $dirs, $path, $filter, $getfiles=true, $level=0) {
  $items = [];
  $subdirs = [];

  if ($getfiles) {
    foreach ($files as $key => $value) {
      // add files to items if directory is same as path
      if ($value['directory'] == $path) {
        $items[] = [
                "name" => $value['name'],
                "size" => $value['size']
              ];
      }
    }
  }

  // get depth of path
  $depth = count(explode("/", $path));

  // get all subdirs for current depth
  $subdirs = get_sub_dirs($dirs, $path, $depth);

  // loop through all subdirs and add to items array
  foreach ($subdirs as $d) {

    $newpath = $path."/".$d;

    // get dir total size and file count
    $dirinfo = get_dir_info($client, $newpath, $filter);

    // continue if get_dir_info returned no results
		if ($dirinfo['size'] == 0 && $dirinfo['count'] == 0) continue;

    $items[] = [
            "name" => $d,
            "size" => $dirinfo['size'],
            "count" => $dirinfo['count'],
            "children" => walk_tree($client, $files, $dirs, $newpath, $filter, $getfiles=true, $level+=1)
          ];
  }

  return $items;
}

$path = $_GET['path'];
$getfiles = $_GET['getfiles'];
$filter = $_GET['filter'];

// default 1 MB min file size filter so sunburst not too heavy
if (empty($filter)) {
  $filter = 1048576;
}

if ($path == "/") {
	$path = "";
	$rootpath = "/";
} else {
	$rootpath = $path;
}

$files = [];
// get list containing directory, name, size keys of all files
$files = get_files($client, $path, $filter);

$dirs = [];
// get just the dirs from files array
$dirs = get_dirs($files);

// get dir total size and file count
$dirinfo = get_dir_info($client, $path, $filter);

$data = [
            "name" => $rootpath,
            "size" => $dirinfo['size'],
            "count" => $dirinfo['count'],
            "children" => walk_tree($client, $files, $dirs, $path, $filter, $getfiles=true)
          ];

echo json_encode($data);

?>
