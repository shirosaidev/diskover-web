<?php
session_start();
use diskover\Constants;
use Elasticsearch\ClientBuilder;

error_reporting(E_ALL ^ E_NOTICE);


function connectES() {
  // Connect to Elasticsearch node
  $esPort = getenv('APP_ES_PORT') ?: Constants::ES_PORT;
  $esIndex = getenv('APP_ES_INDEX') ?: getCookie('index');
  $esIndex2 = getenv('APP_ES_INDEX2') ?: getCookie('index2');
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

  // Check if diskover index exists in Elasticsearch
  $params = ['index' => $esIndex];
  $bool_index = $client->indices()->exists($params);
  $params = ['index' => $esIndex2];
  $bool_index2 = $client->indices()->exists($params);
  if (!$bool_index || !$bool_index2) {
      deleteCookie('index');
      deleteCookie('index2');
      header("Location: /selectindices.php");
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
    $c = (isset($_COOKIE[$cname])) ? $_COOKIE[$cname] : '';
	return $c;
}

function deleteCookie($cname) {
	setcookie($cname, "", time() - 3600);
}

// saved search query functions
function saveSearchQuery($req) {
    $req === "" ? $req = "*" : "";
    if (!isset($_SESSION['savedsearches'])) {
        $_SESSION['savedsearches'] = [];
    } else {
        $json = $_SESSION['savedsearches'];
        $savedsearches = json_decode($json, true);
    }
    $savedsearches[] = $req;
    $json = json_encode($savedsearches);
    $_SESSION['savedsearches'] = $json;
}

function getSavedSearchQuery() {
    if (!isset($_SESSION['savedsearches'])) {
        return false;
    }
    $json = $_SESSION['savedsearches'];
    $savedsearches = json_decode($json, true);
    $savedsearches = array_reverse($savedsearches);
    $savedsearches = array_slice($savedsearches, 0, 10);
    return $savedsearches;
}

function changePercent($a, $b) {
    return (($a - $b) / $b) * 100;
}

function getParentDir($p) {
    if (strlen($p) > strlen($_SESSION['rootpath'])) {
        return dirname($p);
    } else {
        return $_SESSION['rootpath'];
    }
}

function secondsToTime($seconds) {
    $seconds = (int)$seconds;
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%hh:%im:%ss');
}
