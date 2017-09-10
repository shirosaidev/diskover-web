<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// Connect to Elasticsearch
$client = connectES();

// Get total directory size and count from Elasticsearch (recursive)
function get_dir_info($client, $path, $filter) {
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
	$dirinfo = [$totalsize, $totalcount];

  return $dirinfo;
}

function get_files($client, $path, $filter) {
	// gets all the files in the current directory (path)
	$items = [];
  $searchParams['body'] = [];

  // Setup search query
  $searchParams['index'] = Constants::ES_INDEX; // which index to search
  $searchParams['type']  = Constants::ES_TYPE;  // which type within the index to search

  // max number of results to return
  $searchParams['size'] = 100;

	$path = addcslashes($path,'+-&&||!(){}[]^"~*?:\/ ');
	$searchParams['body'] = [
				'query' => [
					'query_string' => [
						'query' => 'path_parent: "' . $path . '" AND filesize: >' . $filter
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
						"name" => $result['_source']['filename'],
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

	// number of results to return
	$searchParams['size'] = 100;
	
	$path = addcslashes($path,'+-&&||!(){}[]^"~*?:\/ ');
	$searchParams['body'] = [
	'query' => [
		'query_string' => [
			'query' => 'path: ' . $path . '\/* NOT path: ' . $path . '\/*\/*'
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

function walk_tree($client, $path, $filter, $getfiles, $depth, $maxdepth) {
  $items = [];
  $subdirs = [];
	if ($depth == $maxdepth) return $items;
	// get files in current path (not recursive)
  if ($getfiles) {
		$items = get_files($client, $path, $filter);
	}
	// get directories in current path (not recursive)
	$subdirs = get_sub_dirs($client, $path);
  // get depth of path
  //$depth = count(explode("/", $path));
  // loop through all subdirs and add to items array
  foreach ($subdirs as $d) {
    // get dir total size and file count
    $dirinfo = get_dir_info($client, $d, $filter);
    // continue if directory is empty
		if ($dirinfo[0] == 0 || $dirinfo[1] == 0) continue;
    $items[] = [
            "name" => basename($d),
            "size" => $dirinfo[0],
            "count" => $dirinfo[1],
            "children" => walk_tree($client, $d, $filter, $getfiles, $depth+=1, $maxdepth)
          ];
		$depth-=1;
	}
  return $items;
}

$path = $_GET['path'];
$filter = $_GET['filter'];
$maxdepth = $_GET['maxdepth'];

// default 1 MB min file size filter
if (empty($filter)) {
  $filter = 1048576;
}

// default 3 max directory depth
if (empty($maxdepth)) {
  $maxdepth = 3;
}

// check if root or no path (grab one from ES)
if (empty($path) || $path == '/'){
	$path = get_es_path($client);
}


// get dir total size and file count
$dirinfo = get_dir_info($client, $path, $filter);

// check for error
if ($dirinfo[0] == 0) {
	echo json_encode([ "error" => "nothing found" ]);
  exit;
}

$data = [
            "name" => $path,
            "size" => $dirinfo[0],
            "count" => $dirinfo[1],
            "children" => walk_tree($client, $path, $filter, $getfiles=true, $depth=0, $maxdepth)
          ];

echo json_encode($data);

?>
