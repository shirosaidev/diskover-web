<?php
/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

/* diskover REST API v1
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Diskover.php";


// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'),true);

// get the URL query of the request
$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$query = parse_url($url, PHP_URL_QUERY);

// split path request into endpoint array
$endpoint = [];
foreach ($request as $r) {
	$endpoint[] = $r;
}

// call endpoint based on HTTP method
switch ($method) {
  case 'GET':
		get($endpoint, $query);
		break;
  case 'PUT':
		put($endpoint, $input);
    	break;
  case 'POST':
		error('method not supported');
  case 'DELETE':
		error('method not supported');
}

function error($message, $type='error') {
	header('Content-Type: application/json');
	echo json_encode([$type => $message], JSON_PRETTY_PRINT);
	http_response_code(404);
	die();
}

function put($endpoint, $input) {
	// Connect to Elasticsearch
	$client = connectES();

	// Setup search query
	$searchParams['index'] = $endpoint[0]; // which index to search

	$files = [];
	$files = $input['files'];
	$path_parent = $input['path_parent'];
	$tag = $input['tag'];
	$tag_custom = $input['tag_custom'];
    $recursive = $input['recursive'];
    $tagfiles = $input['tagfiles'];

	switch ($endpoint) {
		// tag directory doc and items in directory
		case $endpoint[1] == 'tagdir':
			$numitems = 0;

            // first let's get the directory doc id
            $results = [];
			$queryResponse = [];

            $searchParams['size'] = 1;

            // doc type
			$searchParams['type'] = 'directory';

			// esccape special characters
            $pp = addcslashes(dirname($path_parent), '<>+-&|!(){}[]^"~*?:/= @\'$.#\\');
			$f = addcslashes(basename($path_parent), '<>+-&|!(){}[]^"~*?:/= @\'$.#\\');
				
            $searchParams['body'] = [
                    '_source' => [],
				 	'query' => [
				   		'query_string' => [
                            'query' => 'path_parent:' . $pp .' AND filename:' . $f
				   		]
				 	]
			 ];

			try {
				// Send search query to Elasticsearch
				$queryResponse = $client->search($searchParams);
			}
			catch (Exception $e) {
				error('Message: ' . $e);
				echo "0\r\n";
			}

			// check if directory found
			if (!$queryResponse['hits']['hits']) {
				echo "path_parent not found: " . $path_parent . "\r\n";
				die();
			}

            // store the directory doc data
            $directory_hit = $queryResponse['hits']['hits'][0];

            // add directory doc data to results
            $results[] = $directory_hit;

            // now let's get all the doc id's in the directory

            if ($recursive === "true" || $tagfiles === "true") {
                $queryResponse = [];

    			// Scroll parameter alive time
    			$searchParams['scroll'] = "1m";

    			// scroll size
				$searchParams['size'] = 1000;
				
				$pp = addcslashes($path_parent, '+-&|!(){}[]^"~*?:\/ ');

                if ($recursive === "true") {
                    $type = ($tagfiles === "true") ? 'file,directory' : 'directory';
                    // doc type
        			$searchParams['type'] = $type;
                    $searchParams['body'] = [
                            '_source' => [],
        				 	'query' => [
        				   		'query_string' => [
                                    'query' => 'path_parent:' . $pp . '*',
                                    'analyze_wildcard' => 'true'
        				   		]
        				 	]
        			 ];
                } elseif ($tagfiles === "true") {
        			$searchParams['type'] = 'file';
                    $searchParams['body'] = [
                            '_source' => [],
        				 	'query' => [
        				   		'query_string' => [
                                    'query' => 'path_parent:' . $pp,
                                    'analyze_wildcard' => 'true'
        				   		]
        				 	]
        			 ];
                }

    			try {
    				// Send search query to Elasticsearch
    				$queryResponse = $client->search($searchParams);
    			}
    			catch (Exception $e) {
    				error('Message: ' . $e);
    				echo "0\r\n";
    			}

    			// set total hits
    			$total = $queryResponse['hits']['total'];

    			// Get the first scroll_id
    			$scroll_id = $queryResponse['_scroll_id'];

    			$i = 1;
    			// Loop through all the pages of results
    			while ($i <= ceil($total/$searchParams['size'])) {
    				// add files in directory to results array
                    foreach ($queryResponse['hits']['hits'] as $hit) {
                        $results[] = $hit;
                    }

    				// Execute a Scroll request and repeat
    				$queryResponse = $client->scroll([
    					"scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
    					"scroll" => "1m"           // and the same timeout window
    				]);

    				// Get the scroll_id for next page of results
    				$scroll_id = $queryResponse['_scroll_id'];
    				$i += 1;
    			}
            }

			// loop through all the items in results and update tag

			foreach ($results as $r) {
				$searchParams = [];
				$queryResponse = [];

				// get id and index of file
				$id = $r['_id'];
				$index = $r['_index'];
                $type = $r['_type'];

				$searchParams = array();
				$searchParams['id'] = $id;
				$searchParams['index'] = $index;
				$searchParams['type'] = $type;

				try {
					$queryResponse = $client->get($searchParams);
				}
				catch (Exception $e) {
					error('Message: ' . $e);
					echo "0\r\n";
				}

				if (isset($tag_custom)) {
					$queryResponse['_source']['tag_custom'] = $tag_custom;
				}
                if (isset($tag)) {
					$queryResponse['_source']['tag'] = $tag;
				}

				$searchParams['body']['doc'] = $queryResponse['_source'];

				try {
					$queryResponse = $client->update($searchParams);
					$numitems += 1;
				}
				catch (Exception $e) {
					error('Message: ' . $e);
					$numitems -= 1;

				}

		  	}
			// print number of docs updated
			echo $numitems . "\r\n";
			break;

		// tag files
		case $endpoint[1] == 'tagfile':
			$numfiles = 0;

			// update existing tag field with new value
			foreach ($files as $f) {
				$queryResponse = [];
                $searchParams['type'] = 'file';
				$path_parent = dirname($f);
				$filename = basename($f);

				$searchParams['body'] = [
				 	'query' => [
				   		'query_string' => [
							'query' => 'path_parent:"' . $path_parent . '" AND filename:"' . $filename . '"'
						]
				 	]
			  	];

				try {
					// Send search query to Elasticsearch
					$queryResponse = $client->search($searchParams);
				}
				catch (Exception $e) {
					error('Message: ' . $e);
					echo "0\r\n";
				}

				// check if any files found
				if (!$queryResponse['hits']['hits']) {
					echo "file not found: " . $f . "\r\n";
					continue;
				}

				// get id and index of file
				$id = $queryResponse['hits']['hits'][0]['_id'];
				$index = $queryResponse['hits']['hits'][0]['_index'];

				$searchParams = array();
				$searchParams['id'] = $id;
				$searchParams['index'] = $index;
				$searchParams['type'] = 'file';

				try {
					$queryResponse = $client->get($searchParams);
				}
				catch (Exception $e) {
					error('Message: ' . $e);
					echo "0\r\n";
				}

				if (isset($tag_custom)) {
					$queryResponse['_source']['tag_custom'] = $tag_custom;
				}
                if (isset($tag)) {
					$queryResponse['_source']['tag'] = $tag;
				}

				$searchParams['body']['doc'] = $queryResponse['_source'];

				try {
					$queryResponse = $client->update($searchParams);
					$numfiles += 1;
				}
				catch (Exception $e) {
					error('Message: ' . $e);
					$numfiles -= 1;

				}

		  	}
			// print number of files updated
			echo $numfiles . "\r\n";
			break;

		default:
			echo "0\r\n";
	}
}

function get($endpoint, $query) {
	// Connect to Elasticsearch
	$client = connectES();

	// Setup search query
	$searchParams['index'] = $endpoint[0]; // which index to search
    parse_str($query, $output);
	$searchParams['type'] = $output['type'];  // which type within the index to search


	switch ($endpoint) {

		case $endpoint[1] == 'tagcount':

			if (isset($output['tag']) || isset($output['tag_custom'])) {
				$tag = (isset($output['tag'])) ? $output['tag'] : "";
				($tag === "untagged") ? $tag = "" : $tag;
				$tag_custom = (isset($output['tag_custom'])) ? $output['tag_custom'] : "";
				$searchParams['body'] = [
					'size' => 0,
					'query' => [
						'query_string' => [
							'query' => 'tag:"' . $tag . '" AND tag_custom:"' . $tag_custom . '"'
						]
					]
				];

    			// Get search results from Elasticsearch for tag
    			$tagCount = 0;

    			try {
    				// Send search query to Elasticsearch
    				$queryResponse = $client->search($searchParams);
    			}

    			catch (Exception $e) {
    				error('Message: ' . $e);
    			}

    			// Get total for tag
    			$tagCount = $queryResponse['hits']['total'];

    			// print results
    			header('Content-Type: application/json');
    			echo json_encode($tagCount, JSON_PRETTY_PRINT);
				break;
				
            } else {
                // Get search results from Elasticsearch for tags
    			$tagCounts = ['untagged' => 0, 'delete' => 0, 'archive' => 0, 'keep' => 0];

                foreach ($tagCounts as $tag => $value) {
                    $t = ($tag === "untagged") ? "" : $tag;
    				$searchParams['body'] = [
    					'size' => 0,
    				 	'query' => [
    				   		'match' => [
    					 		'tag' => $t
    				   		]
    				 	]
    			  	];

    				try {
    					// Send search query to Elasticsearch
    					$queryResponse = $client->search($searchParams);
    				}

    				catch (Exception $e) {
    					error('Message: ' . $e);
    				}

    			  	// Get total for tag
    			  	$tagCounts[$tag] = $queryResponse['hits']['total'];
    			}

                // Grab all the custom tags from file and add to tagCounts
                $customtags = get_custom_tags();
                $tagCountsCustom = [];
                foreach ($customtags as $tag) {
                    $tagCountsCustom[$tag[0]] = 0;
                }

                foreach ($tagCountsCustom as $tag => $value) {
    				$searchParams['body'] = [
    					'size' => 0,
    				 	'query' => [
    				   		'match' => [
    					 		'tag_custom' => $tag
    				   		]
    				 	]
    			  	];

    				try {
    					// Send search query to Elasticsearch
    					$queryResponse = $client->search($searchParams);
    				}

    				catch (Exception $e) {
    					error('Message: ' . $e);
    				}

    			  	// Get total for tag
    			  	$tagCountsCustom[$tag] = $queryResponse['hits']['total'];
    			}

                $tagCountsAll = [];
                $tagCountsAll['tag'] = $tagCounts;
                $tagCountsAll['tag_custom'] = $tagCountsCustom;

    			// print results
    			header('Content-Type: application/json');
                echo json_encode($tagCountsAll, JSON_PRETTY_PRINT);
    			break;
            }

		case $endpoint[1] == 'tagsize':

            if (isset($output['tag']) || isset($output['tag_custom'])) {
				$tag = (isset($output['tag'])) ? $output['tag'] : "";
				($tag === "untagged") ? $tag = "" : $tag;
				$tag_custom = (isset($output['tag_custom'])) ? $output['tag_custom'] : "";
				$searchParams['body'] = [
					'size' => 0,
					'query' => [
						'query_string' => [
						'query' => 'tag:"' . $tag . '" AND tag_custom:"' . $tag_custom . '"'
						]
					],
					'aggs' => [
						'total_size' => [
							'sum' => [
								'field' => 'filesize'
							]
						]
					]
				];

                // Get search results from Elasticsearch for tag
                $tagSize = 0;

                try {
                    // Send search query to Elasticsearch
                    $queryResponse = $client->search($searchParams);
                }

                catch (Exception $e) {
                    error('Message: ' . $e);
                }

                // Get total for tag
                $tagSize = $queryResponse['aggregations']['total_size']['value'];

                // print results
                header('Content-Type: application/json');
                echo json_encode($tagSize, JSON_PRETTY_PRINT);
				break;
				
            } else {
                // Get search results from Elasticsearch for tags
                $tagSizes = ['untagged' => 0, 'delete' => 0, 'archive' => 0, 'keep' => 0];

                foreach ($tagSizes as $tag => $value) {
                    ($tag === "untagged") ? $t = "" : $t = $tag;
                    $searchParams['body'] = [
    					'size' => 0,
    					'query' => [
    				   		'match' => [
    						'tag' => $t
    				  		]
    					],
    				  	'aggs' => [
    						'total_size' => [
    					  		'sum' => [
    								'field' => 'filesize'
    					  		]
    						]
    				  	]
    			  	];

                    try {
                        // Send search query to Elasticsearch
                        $queryResponse = $client->search($searchParams);
                    }

                    catch (Exception $e) {
                        error('Message: ' . $e);
                    }

                    // Get total size of all files with tag
    			  	$tagSizes[$tag] = $queryResponse['aggregations']['total_size']['value'];
                }

                // Grab all the custom tags from file and add to tagSizesCustom
                $customtags = get_custom_tags();
                $tagSizesCustom = [];
                foreach ($customtags as $tag) {
                    $tagSizesCustom[$tag[0]] = 0;
                }

                foreach ($tagSizesCustom as $tag => $value) {
                    $searchParams['body'] = [
    					'size' => 0,
    					'query' => [
    				   		'match' => [
    						'tag_custom' => $tag
    				  		]
    					],
    				  	'aggs' => [
    						'total_size' => [
    					  		'sum' => [
    								'field' => 'filesize'
    					  		]
    						]
    				  	]
    			  	];

                    try {
                        // Send search query to Elasticsearch
                        $queryResponse = $client->search($searchParams);
                    }

                    catch (Exception $e) {
                        error('Message: ' . $e);
                    }

                    // Get total size of all files with tag
    			  	$tagSizesCustom[$tag] = $queryResponse['aggregations']['total_size']['value'];
                }

                $tagSizesAll = [];
                $tagSizesAll['tag'] = $tagSizes;
                $tagSizesAll['tag_custom'] = $tagSizesCustom;

                // print results
                header('Content-Type: application/json');
                echo json_encode($tagSizesAll, JSON_PRETTY_PRINT);
                break;
            }

		case $endpoint[1] == 'tags':

			// Scroll parameter alive time
			$searchParams['scroll'] = "1m";

			// scroll size
			$searchParams['size'] = (isset($output['size']) ? $output['size'] : 1000);

			// page number of results to print
			$page = (isset($output['page']) ? $output['page'] : 1);

			if ((!isset($output['tag']) || empty($output['tag'])) && (!isset($output['tag_custom']) || empty($output['tag_custom']))) {
				$searchParams['body'] = [
					'query' => [
						'query_string' => [
							'query' => 'tag:"" AND tag_custom:""'
						]
					]
				];
			} else {
				$tag = (isset($output['tag'])) ? $output['tag'] : "";
				($tag === "untagged") ? $tag = "" : $tag;
				$tag_custom = (isset($output['tag_custom'])) ? $output['tag_custom'] : "";
				$searchParams['body'] = [
					'query' => [
						'query_string' => [
							'query' => 'tag:"' . $tag . '" AND tag_custom:"' . $tag_custom . '"'
						]
					]
				];
			}

			// Send search query to Elasticsearch and get scroll id and first page of results
			try {
				// Send search query to Elasticsearch
				$queryResponse = $client->search($searchParams);
			}
			catch (Exception $e) {
				error('Message: ' . $e);
			}

			// set total hits
			$total = $queryResponse['hits']['total'];

			// Get the first scroll_id
			$scroll_id = $queryResponse['_scroll_id'];

			$i = 1;
			$results = [];
			// Loop through all the pages of results
			while ($i <= ceil($total/$searchParams['size'])) {
				// check if we have the results for the page we are on
				if ($i == $page) {
					// Get files for tag
					$results[$i] = $queryResponse['hits']['hits'];
					// end loop
					break;
				}

				// Execute a Scroll request and repeat
				$queryResponse = $client->scroll([
					"scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
					"scroll" => "1m"           // and the same timeout window
				]);

				// Get the scroll_id for next page of results
				$scroll_id = $queryResponse['_scroll_id'];
				$i += 1;
			}

			// print results
			header('Content-Type: application/json');
			if ($results[$page]) {
				echo json_encode($results[$page], JSON_PRETTY_PRINT);
			} else {
				error('no files found');
			}
			break;

		case $endpoint[1] == 'dupes':

			// Scroll parameter alive time
			$searchParams['scroll'] = "1m";

            $searchParams['type'] = "file";

			// scroll size
			$searchParams['size'] = (isset($output['size']) ? $output['size'] : 1000);

			// page number of results to print
			$page = (isset($output['page']) ? $output['page'] : 1);

			$searchParams['body'] = [
					'query' => [
          				'query_string' => [
                            'query' => 'dupe_md5:(NOT "")'
          				]
        			],
        			'sort' => [
          				'dupe_md5'
         			]
			];

			// Send search query to Elasticsearch and get scroll id and first page of results
			try {
				// Send search query to Elasticsearch
				$queryResponse = $client->search($searchParams);
			}

			catch (Exception $e) {
				error('Message: ' . $e);
			}

			// set total hits
			$total = $queryResponse['hits']['total'];

			// Get the first scroll_id
			$scroll_id = $queryResponse['_scroll_id'];

			$i = 1;
			$results = [];
			// Loop through all the pages of results
			while ($i <= ceil($total/$searchParams['size'])) {
				// check if we have the results for the page we are on
				if ($i == $page) {
					// Get files for tag
					$results[$i] = $queryResponse['hits']['hits'];
					// end loop
					break;
				}

				// Execute a Scroll request and repeat
				$queryResponse = $client->scroll([
					"scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
					"scroll" => "1m"           // and the same timeout window
				]);

				// Get the scroll_id for next page of results
				$scroll_id = $queryResponse['_scroll_id'];
				$i += 1;
			}

			// print results
			header('Content-Type: application/json');
			if ($results[$page]) {
				echo json_encode($results[$page], JSON_PRETTY_PRINT);
			} else {
				error('no files found');
			}
			break;

		case $endpoint[1] == 'dupessize':
			$totalFilesize = 0;

			$searchParams['body'] = [
				'size' => 0,
				'query' => [
					'query_string' => [
					'query' => 'dupe_md5:(NOT "")'
					]
				],
				'aggs' => [
					'total_size' => [
						'sum' => [
							'field' => 'filesize'
						]
					]
				]
			];

			try {
			// Send search query to Elasticsearch
			$queryResponse = $client->search($searchParams);
			}

			catch (Exception $e) {
				error('Message: ' . $e);
			}

			// Get total size of all files with tag
			$totalFilesize = $queryResponse['aggregations']['total_size']['value'];

			// print results
			header('Content-Type: application/json');
			echo json_encode($totalFilesize, JSON_PRETTY_PRINT);
			break;

		case $endpoint[0] == 'list':
			$indices = $client->cat()->indices([]);
			$diskover_indices = [];
			for($i=0;$i<count($indices);$i++) {
				if (strpos($indices[$i]['index'], 'diskover') !== false) {
					$diskover_indices[] = $indices[$i];
				}
			}
		    for($i=0;$i<count($diskover_indices);$i++) {
				// Get search results from Elasticsearch for duplicate files
				$results = [];
				$searchParams = [];
				// Setup search query
				$searchParams['index'] = $diskover_indices[$i]['index'];
				$searchParams['type']  = 'file';
				// Setup search query for dupes count
				$searchParams['body'] = [
				'size' => 0,
					'aggs' => [
					'total_size' => [
					'sum' => [
					'field' => 'filesize'
					]
					]
					],
					'query' => [
					'query_string' => [
					'query' => 'dupe_md5:(NOT "")',
					'analyze_wildcard' => 'true'
					]
					]
				];
				$queryResponse = $client->search($searchParams);
				// Get total count of duplicate files
				$diskover_indices[$i]['dupes'] = $queryResponse['hits']['total'];
				// Get total size of all duplicate files
				$diskover_indices[$i]['dupessize'] = $queryResponse['aggregations']['total_size']['value'];
				// Get search results from Elasticsearch for number of files
				$results = [];
				$searchParams = [];
				// Setup search query
				$searchParams['index'] = $diskover_indices[$i]['index'];
				$searchParams['type']  = "file";
				$searchParams['body'] = [
					'size' => 0,
					'query' => [
					'match_all' => (object) []
					]
				];
				$queryResponse = $client->search($searchParams);
				// Get total count of files
				$diskover_indices[$i]['totalfiles'] = $queryResponse['hits']['total'];
				// Get search results from Elasticsearch for number of directories
				$results = [];
				$searchParams = [];
				// Setup search query
				$searchParams['index'] = $diskover_indices[$i]['index'];
				$searchParams['type']  = "directory";
				$searchParams['body'] = [
					'size' => 0,
					'query' => [
					'match_all' => (object) []
					]
				];
				$queryResponse = $client->search($searchParams);
				// Get total count of directories
				$diskover_indices[$i]['totaldirs'] = $queryResponse['hits']['total'];
				// Get search results from Elasticsearch for hardlink files
				$results = [];
				$searchParams = [];
				$totalHardlinkFiles = 0;
				$totalFilesizeHardlinkFiles = 0;
				// Setup search query
				$searchParams['index'] = $diskover_indices[$i]['index'];
				$searchParams['type']  = 'file';
				// Setup search query for hardlink count
				$searchParams['body'] = [
				'size' => 0,
					'aggs' => [
					'total_size' => [
					'sum' => [
					'field' => 'filesize'
					]
					]
					],
					'query' => [
					'query_string' => [
					'query' => 'hardlinks:>1'
					]
					]
				];
				$queryResponse = $client->search($searchParams);
				// Get total count of hardlink files
				$diskover_indices[$i]['totalHardlinkFiles'] = $queryResponse['hits']['total'];

				// Get search results from Elasticsearch for disk space info
				$results = [];
				$searchParams = [];
				// Setup search query
				$searchParams['index'] = $diskover_indices[$i]['index'];
				$searchParams['type']  = "diskspace";
				$searchParams['body'] = [
				    'size' => 100,
				    'query' => [
					'match_all' => (object) []
				     ],
				     'sort' => [
					 'indexing_date' => [
					     'order' => 'asc'
					 ]
				     ]
				];
				$queryResponse = $client->search($searchParams);
				// Get disk space info from queryResponse
				$diskover_indices[$i]['path'] = $queryResponse['hits']['hits'][0]['_source']['path'];

			   	// Get total worker crawl cumulative time (in seconds) 
				$searchParams['type'] = 'worker';
				$searchParams['body'] = [
				   'size' => 0,
				    'aggs' => [
				      'total_elapsed' => [
					'sum' => [
					  'field' => 'crawl_time'
					]
				      ]
				    ],
				    'query' => [
					    'match_all' => (object) []
				     ]
				];
				$queryResponse = $client->search($searchParams);
				$diskover_indices[$i]['workerCrawlTime'] = round($queryResponse['aggregations']['total_elapsed']['value'], 6);

				// Get total elapsed time (in seconds) of all crawls (not inc dir calc time)
				$searchParams['type'] = 'crawlstat';
				$searchParams['body'] = [
					'size' => 100,
					'query' => [
						'match' => [
							'state' => 'finished_crawl'
						]
					 ]
				];
				$queryResponse = $client->search($searchParams);
				$crawlelapsedtime = 0;
				foreach ($queryResponse['hits']['hits'] as $key => $value) {
					$crawlelapsedtime += $value['_source']['crawl_time'];
				}
				$queryResponse = $client->search($searchParams);
				$diskover_indices[$i]['elapsedCrawlTime'] = $crawlelapsedtime;
			}
			
			header('Content-Type: application/json');
			echo json_encode($diskover_indices, JSON_PRETTY_PRINT);
		break;

		case $endpoint[1] == 'search':
			// Scroll parameter alive time
			$searchParams['scroll'] = "1m";

			//$searchParams['type'] = "file,directory";

			// scroll size
			$searchParams['size'] = 1000;

			$searchParams['body'] = [
					'query' => [
						'query_string' => [
							'query' => $output['query'],
							'analyze_wildcard' => 'true'
						]
					]
			];

			// Send search query to Elasticsearch and get scroll id and first page of results
			try {
				// Send search query to Elasticsearch
				$queryResponse = $client->search($searchParams);
			}

			catch (Exception $e) {
				error('Message: ' . $e);
			}

			// set total hits
			$total = $queryResponse['hits']['total'];

			// Get the first scroll_id
			$scroll_id = $queryResponse['_scroll_id'];

			$i = 1;
			$results = [];
			// Loop through all the pages of results and store in results array
			while ($i <= ceil($total/$searchParams['size'])) {
				foreach ($queryResponse['hits']['hits'] as $hit) {
					$results[] = $hit;
				}

				// Execute a Scroll request and repeat
				$queryResponse = $client->scroll([
					"scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
					"scroll" => "1m"           // and the same timeout window
				]);

				// Get the scroll_id for next page of results
				$scroll_id = $queryResponse['_scroll_id'];
				$i += 1;
			}

			// print results
			header('Content-Type: application/json');
			if ($results) {
				echo json_encode($results, JSON_PRETTY_PRINT);
			} else {
				error('no docs found');
			}
		break;

		default:
			header('Content-Type: application/json');
			$json = [
						'version' => 'diskover REST API v1',
						'message' => 'endpoint not found'
					];
			echo json_encode($json, JSON_PRETTY_PRINT);
	}
}
