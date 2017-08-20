<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

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
	$searchParams['index'] = Constants::ES_INDEX; // which index to search
	$searchParams['type']  = Constants::ES_TYPE;  // which type within the index to search

	$files = [];
	$files = $input['files'];
	$path_parent = $input['path_parent'];
	$tag = $input['tag'];
	$tag_custom = $input['tag_custom'];
	
	switch ($endpoint) {
		// tag all files in directory
		case $endpoint[0] == 'tagdir':
			$numfiles = 0;
			
			// first let's get all the file id's
			
			$searchParams = [];
			$queryResponse = [];
			
			// Scroll parameter alive time
			$searchParams['scroll'] = "1m";

			// number of results to return per page
			$searchParams['size'] = 100;
			
			$searchParams['body'] = [
				 	'query' => [
				   		'match' => [
					 		'path_parent' => $path_parent
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
				echo "path_parent not found: " . $path_parent . "\r\n";
				die();
			}
			
			// set total hits
			$total = $queryResponse['hits']['total'];

			// Get the first scroll_id
			$scroll_id = $queryResponse['_scroll_id'];

			$i = 1;
			$results = [];
			// Loop through all the pages of results
			while ($i <= ceil($total/$searchParams['size'])) {
				// add files in directory to results array
				$results = $queryResponse['hits']['hits'];

				// Execute a Scroll request and repeat
				$queryResponse = $client->scroll([
					"scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
					"scroll" => "1m"           // and the same timeout window
				]);

				// Get the scroll_id for next page of results
				$scroll_id = $queryResponse['_scroll_id'];
				$i += 1;
			}
			
			// loop through all the files in results and update tag
			
			foreach ($results as $r) {
				$searchParams = [];
				$queryResponse = [];
				
				// get id and index of file
				$id = $r['_id'];
				$index = $r['_index'];
				
				$searchParams = array();
				$searchParams['id'] = $id;
				$searchParams['index'] = $index;
				$searchParams['type'] = Constants::ES_TYPE;
				
				try {
					$queryResponse = $client->get($searchParams);
				}
				catch (Exception $e) {
					error('Message: ' . $e);
					echo "0\r\n";
				}
				
				if ($tag_custom) {
					$queryResponse['_source']['tag_custom'] = $tag_custom;
				} elseif ($tag) {
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
		
		// tag files
		case $endpoint[0] == 'tagfiles':
			$numfiles = 0;
			
			// update existing tag field with new value
			foreach ($files as $f) {
				$searchParams = [];
				$queryResponse = [];			
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
				$searchParams['type'] = Constants::ES_TYPE;
				
				try {
					$queryResponse = $client->get($searchParams);
				}
				catch (Exception $e) {
					error('Message: ' . $e);
					echo "0\r\n";
				}
				
				if ($tag_custom) {
					$queryResponse['_source']['tag_custom'] = $tag_custom;
				} elseif ($tag) {
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
	$searchParams['index'] = Constants::ES_INDEX; // which index to search
	$searchParams['type']  = Constants::ES_TYPE;  // which type within the index to search
	
	switch ($endpoint) {
		case $endpoint[0] == 'tagcounts':
			// Get search results from Elasticsearch for tags
			$tagCounts = ['untagged' => 0, 'delete' => 0, 'archive' => 0, 'keep' => 0];

			foreach ($tagCounts as $tag => $value) {
				$searchParams['body'] = [
					'size' => 0,
				 	'query' => [
				   		'match' => [
					 		'tag' => $tag
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
			// print results
			header('Content-Type: application/json');
			echo json_encode($tagCounts, JSON_PRETTY_PRINT);
			break;

		case $endpoint[0] == 'tagcount':
			$tag = $query || error('missing tag');
			parse_str($query, $output);
			// custom tag
			if ($output['custom']) {
				$tag = $output['custom'];
				$searchParams['body'] = [
					'size' => 0,
					'query' => [
						'match' => [
							'tag_custom' => $tag
						]
					]
				];
			} else {
				$searchParams['body'] = [
					'size' => 0,
					'query' => [
						'match' => [
							'tag' => $tag
						]
					]
				];
			}
			
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

		case $endpoint[0] == 'tagsizes':
			// Get search results from Elasticsearch for tags
			$totalFilesize = ['untagged' => 0, 'delete' => 0, 'archive' => 0, 'keep' => 0];

			foreach ($totalFilesize as $tag => $value) {
				$searchParams['body'] = [
					'size' => 0,
					'query' => [
				   		'match' => [
						'tag' => $tag
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
			  	$totalFilesize[$tag] = $queryResponse['aggregations']['total_size']['value'];
			}
			// print results
			header('Content-Type: application/json');
			echo json_encode($totalFilesize, JSON_PRETTY_PRINT);
			break;

		case $endpoint[0] == 'tagsize':
			$tag = $query || error('missing tag');
			parse_str($query, $output);
			// custom tag
			if ($output['custom']) {
				$tag = $output['custom'];
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
			} else {
				$searchParams['body'] = [
					'size' => 0,
					'query' => [
						'match' => [
							'tag' => $tag
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
			}
			
			// Get search results from Elasticsearch for tag
			$totalFilesize = 0;

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
			
		case $endpoint[0] == 'tagfiles':
			$tag = $query || error('missing tag');
			parse_str($query, $output);
			
			// Scroll parameter alive time
			$searchParams['scroll'] = "1m";

			// number of results to return per page
			$searchParams['size'] = 100;
			
			// custom tag
			if ($output['custom']) {
				$tag = $output['custom'];
				$searchParams['body'] = [
					'query' => [
						'match' => [
							'tag_custom' => $tag
						]
					]
				];
			} else {
				$searchParams['body'] = [
					'query' => [
						'match' => [
							'tag' => $tag
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
				// Get files for tag
				$results = $queryResponse['hits']['hits'];

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
				error('no files found', 'message');
			}
			break;

		case $endpoint[0] == 'dupes':
			$tag = $query;
			
			// Scroll parameter alive time
			$searchParams['scroll'] = "1m";

			// number of results to return per page
			$searchParams['size'] = 100;
			
			$searchParams['body'] = [
					'query' => [
          				'match' => [
            				'is_dupe' => 'true'
          				]
        			],
        			'sort' => [
          				'filehash'
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
				// Get files for tag
				$results = $queryResponse['hits']['hits'];

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
				error('no files found');
			}
			break;

		case $endpoint[0] == 'dupessize':
			$totalFilesize = 0;

			$searchParams['body'] = [
				'size' => 0,
				'query' => [
					'match' => [
					'is_dupe' => 'true'
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

		default:
			header('Content-Type: application/json');
			$json = [
						'version' => 'diskover REST API v1',
						'message' => 'endpoint not found'
					];
			echo json_encode($json, JSON_PRETTY_PRINT);
	}
}