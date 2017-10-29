<?php

use diskover\Constants;
use Elasticsearch\ClientBuilder;

error_reporting(E_ALL ^ E_NOTICE);

function connectES() {
  // Connect to Elasticsearch node
  $esPort = getenv('APP_ES_PORT') ?: Constants::ES_PORT;
  $esIndex = getenv('APP_ES_INDEX') ?: Constants::ES_INDEX;
  if (Constants::AWS == false) {
		$hosts = [
      [
    'host' => Constants::ES_HOST,
    'port' => $esPort,
    'user' => Constants::ES_USER,
    'pass' => Constants::ES_PASS
      ]
  	];
	} else { // using AWS
		$hosts = [
      [
    'host' => Constants::ES_HOST,
    'port' => $esPort
      ]
  ];
	}

  $client = ClientBuilder::create()->setHosts($hosts)->build();

  // Check connection to Elasticsearch
  try {
    $params = [
      'index' => $esIndex,
      'type' => Constants::ES_TYPE,
      'id' => 1,
      'client' => [ 'ignore' => [400, 404, 500] ]
    ];
    $client->get($params);
  } catch(Exception $e) {
    echo 'Error connecting to Elasticsearch: ',  $e->getMessage(), "\n";
  }

  return $client;
}

// human readable file size format function
function formatBytes($bytes, $precision = 2) {
  if ($bytes == 0) {
    return "0 Bytes";
  }
  $base = log($bytes) / log(1024);
  $suffix = array("Bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB")[floor($base)];

  return round(pow(1024, $base - floor($base)), $precision) . " " . $suffix;
}

// cookie functions
function createCookie($cname, $cvalue) {
	setcookie($cname, $cvalue, 0, "/");
}

function getCookie($cname) {
	return $_COOKIE[$cname];
}

function deleteCookie($cname) {
	setcookie($cname, "", time() - 3600);
}

// saved search query functions
function saveSearchQuery($req) {
    if (!getcookie('savedsearches')) {
        $savedsearches = [];
    } else {
        $json = getcookie('savedsearches');
        $savedsearches = json_decode($json, true);
    }
    $savedsearches[] = $req;
    $json = json_encode($savedsearches);
    setcookie('savedsearches', $json);
}

function getSavedSearchQuery() {
    $json = getcookie('savedsearches');
    $savedsearches = json_decode($json, true);
    $savedsearches = array_reverse($savedsearches);
    $savedsearches = array_slice($savedsearches, 0, 10);
    return $savedsearches;
}
