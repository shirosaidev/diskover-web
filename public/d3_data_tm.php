<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// Connect to Elasticsearch
$client = connectES();

// Get total directory size and count from Elasticsearch (recursive)
function get_dir_info($client, $path, $filter, $mtime) {
  $totalsize = 0;
	$totalcount = 0;
  $searchParams['body'] = [];

  // Setup search query
  $searchParams['index'] = Constants::ES_INDEX; // which index to search
  $searchParams['type']  = Constants::ES_TYPE;  // which type within the index to search

	$path = addcslashes($path,'+-&&||!(){}[]^"~*?:\/ ');
  $searchParams['body'] = [
     'size' => 0,
     'query' => [
       'query_string' => [
         'query' => '"' . $path . '" AND filesize: >' . $filter . ' AND last_modified: {* TO ' . $mtime . '}'
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
	$dirinfo = [$totalsize, $totalcount];

  return $dirinfo;
}

function get_files($client, $path, $filter, $mtime) {
	// gets all the files in the current directory (path)
	$items = [];
  $searchParams['body'] = [];

  // Setup search query
  $searchParams['index'] = Constants::ES_INDEX; // which index to search
  $searchParams['type']  = Constants::ES_TYPE;  // which type within the index to search

  // search size
  $searchParams['size'] = 100;

	$path = addcslashes($path,'+-&&||!(){}[]^"~*?:\/ ');
	$searchParams['body'] = [
				'_source' => ["path_parent","filename","filesize"],
				'query' => [
					'query_string' => [
						'query' => 'path_parent: "' . $path . '" AND filesize: >' . $filter . ' AND last_modified: {* TO ' . $mtime . '}'
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

 	// Get files
	$results = $queryResponse['hits']['hits'];

	// Add files to items array
	foreach($results as $result) {
		$items[] = [
						"name" => $result['_source']['path_parent'] . '/' . $result['_source']['filename'],
						"size" => $result['_source']['filesize']
					];
	}

	return $items;
}

function get_es_path($client) {
	// try to get a top level path from ES
	
	$searchParams['body'] = [];

	// Setup search query
	$searchParams['index'] = Constants::ES_INDEX; // which index to search
	$searchParams['type']  = "directory"; //Constants::ES_TYPE;  // which type within the index to search

	// number of results to return
	$searchParams['size'] = 10;
	
	$searchParams['body'] = [
	'_source' => ["path"],
	'query' => [
			'match_all' => (object) []
	],
	'sort' => [
			'path' => [
			'order' => 'asc'
			]
	]
	];

	// Send search query to Elasticsearch and get results
	$queryResponse = $client->search($searchParams);

	// Get directories
	$results = $queryResponse['hits']['hits'];
	
	// set path to first path found
	$path = $results[0]['_source']['path'];

	return $path;
}

function get_sub_dirs($client, $path) {
	$dirs = [];
	
	$searchParams['body'] = [];

	// Setup search query
	$searchParams['index'] = Constants::ES_INDEX; // which index to search
	$searchParams['type']  = "directory"; //Constants::ES_TYPE;  // which type within the index to search

	// search size
	$searchParams['size'] = 100;
	
	// diff query if root path /
	if ($path == '/') {
		$query = 'path: \/* NOT path: \/*\/* NOT path: \/';
	} else {
		// escape special characters
		$path = addcslashes($path,'+-&&||!(){}[]^"~*?:\/ ');
		$query = 'path: ' . $path . '\/* NOT path: ' . $path . '\/*\/*';
	}
	
	$searchParams['body'] = [
	'_source' => ["path"],
	'query' => [
		'query_string' => [
			'query' => $query
		]
	],
	'sort' => [
			'path' => [
			'order' => 'asc'
			]
	]
	];

	// Send search query to Elasticsearch and get results
	$queryResponse = $client->search($searchParams);

	// Get directories
	$results = $queryResponse['hits']['hits'];
	
	foreach ($results as $arr) {
		$dirs[] = $arr['_source']['path'];
	}
	
	return $dirs;

}

function walk_tree($client, $path, $filter, $mtime, $depth, $maxdepth) {
  $items = [];
  $subdirs = [];
	if ($depth == $maxdepth) return $items;
	// get files in current path (not recursive)
  $items = get_files($client, $path, $filter, $mtime);
	// get directories in current path (not recursive)
	$subdirs = get_sub_dirs($client, $path);
  // get depth of path
  //$depth = count(explode("/", $path));
  // loop through all subdirs and add to items array
  foreach ($subdirs as $d) {
    // get dir total size and file count
    $dirinfo = get_dir_info($client, $d, $filter, $mtime);
    // continue if directory is empty
		if ($dirinfo[0] == 0 || $dirinfo[1] == 0) continue;
    $items[] = [
            "name" => $d, //basename($d),
            "size" => $dirinfo[0],
            //"count" => $dirinfo[1],
            "children" => walk_tree($client, $d, $filter, $mtime, $depth+=1, $maxdepth)
          ];
		$depth-=1;
	}
  return $items;
}

$path = $_GET['path'];
$filter = $_GET['filter']; // file size
$mtime = $_GET['mtime']; // file mtime
$maxdepth = $_GET['maxdepth'];

// default 1 MB min file size filter
if (empty($filter)) {
  $filter = 1048576;
}

// default 0 days mtime filter
if (empty($mtime) || $mtime == 0) {
  $mtime = gmdate("Y-m-d\TH:i:s", strtotime("now"));
} elseif ($mtime == "1m") {
	$mtime = gmdate("Y-m-d\TH:i:s", strtotime("-1 month"));
} elseif ($mtime == "3m") {
	$mtime = gmdate("Y-m-d\TH:i:s", strtotime("-3 months"));
} elseif ($mtime == "6m") {
	$mtime = gmdate("Y-m-d\TH:i:s", strtotime("-6 months"));
} elseif ($mtime == "1y") {
	$mtime = gmdate("Y-m-d\TH:i:s", strtotime("-12 months"));
}

// default 5 max directory depth
if (empty($maxdepth)) {
  $maxdepth = 5;
}

// check if no path (grab one from ES)
if (empty($path)) {
	$path = get_es_path($client);
}

// get dir total size and file count
$dirinfo = get_dir_info($client, $path, $filter, $mtime);

// check for error
if ($dirinfo[0] == 0) {
	echo json_encode([ "error" => "nothing found" ]);
  exit;
}

$data = [
            "name" => $path, //basename($path),
            "size" => $dirinfo[0],
            //"count" => $dirinfo[1],
            "children" => walk_tree($client, $path, $filter, $mtime, $depth=0, $maxdepth)
          ];

echo json_encode($data);

?>
