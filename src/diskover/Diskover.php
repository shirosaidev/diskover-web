<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */
 
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


// get and change url variable for sorting search results table
function sortURL($sort) {
    $query = $_GET;
    $sortorder = ['asc', 'desc'];
    $sortorder_icons = ['glyphicon-chevron-up', 'glyphicon-chevron-down'];

    foreach ($sortorder as $key => $value) {
        # set class for sort arrow
        if (($_GET['sort'] == $sort && $_GET['sortorder'] == $value) || ($_GET['sort2'] == $sort && $_GET['sortorder2'] == $value)) {
            $class = 'sortarrow-'.$value.'-active';
        } elseif ((getCookie('sort') == $sort && getCookie('sortorder') == $value) || (getCookie('sort2') == $sort && getCookie('sortorder2') == $value)) {
            $class = 'sortarrow-'.$value.'-active';
        } else {
            $class = '';
        }
        # build link for arrow
        # sort 1 set, set sort 2
        if ((isset($_GET['sort']) || getCookie('sort')) && (!isset($_GET['sort2']) && !getCookie('sort2')) && ($_GET['sort'] != $sort && getCookie('sort') != $sort)) {
            $query['sort2'] = $sort;
            $query['sortorder2'] = $value;
            $query_result = http_build_query($query);
            $arrows .= "<a href=\"".$_SERVER['PHP_SELF']."?".$query_result."\" onclick=\"setCookie('sort2', '".$sort."'); setCookie('sortorder2', '".$value."');\"><i class=\"glyphicon ".$sortorder_icons[$key]." sortarrow-".$value." ".$class."\"></i></a>";
        } elseif ((isset($_GET['sort2']) || getCookie('sort2')) && (!isset($_GET['sort']) && !getCookie('sort')) && ($_GET['sort2'] != $sort && getCookie('sort2') != $sort)) {  # sort 2 set, set sort 1
            $query['sort'] = $sort;
            $query['sortorder'] = $value;
            $query_result = http_build_query($query);
            $arrows .= "<a href=\"".$_SERVER['PHP_SELF']."?".$query_result."\" onclick=\"setCookie('sort', '".$sort."'); setCookie('sortorder', '".$value."');\"><i class=\"glyphicon ".$sortorder_icons[$key]." sortarrow-".$value." ".$class."\"></i></a>";
        } elseif ((isset($_GET['sort']) || getCookie('sort')) && ($_GET['sort'] == $sort || getCookie('sort') == $sort) && ($_GET['sortorder'] != $value && getCookie('sortorder') != $value)) {
            $query['sort'] = $sort;
            $query['sortorder'] = $value;
            $query_result = http_build_query($query);
            $arrows .= "<a href=\"".$_SERVER['PHP_SELF']."?".$query_result."\" onclick=\"setCookie('sort', '".$sort."'); setCookie('sortorder', '".$value."');\"><i class=\"glyphicon ".$sortorder_icons[$key]." sortarrow-".$value." ".$class."\"></i></a>";
        } elseif ((isset($_GET['sort']) || getCookie('sort')) && ($_GET['sort'] == $sort || getCookie('sort') == $sort) && ($_GET['sortorder'] == $value || getCookie('sortorder') == $value)) {
            $query['sort'] = null;
            $query['sortorder'] = null;
            $query_result = http_build_query($query);
            $arrows .= "<a href=\"".$_SERVER['PHP_SELF']."?".$query_result."\" onclick=\"deleteCookie('sort'); deleteCookie('sortorder');\"><i class=\"glyphicon ".$sortorder_icons[$key]." sortarrow-".$value." ".$class."\"></i></a>";
        } elseif ((isset($_GET['sort2']) || getCookie('sort2')) && ($_GET['sort2'] == $sort || getCookie('sort2') == $sort) && ($_GET['sortorder2'] != $value && getCookie('sortorder2') != $value)) {
            $query['sort2'] = $sort;
            $query['sortorder2'] = $value;
            $query_result = http_build_query($query);
            $arrows .= "<a href=\"".$_SERVER['PHP_SELF']."?".$query_result."\" onclick=\"setCookie('sort2', '".$sort."'); setCookie('sortorder2', '".$value."');\"><i class=\"glyphicon ".$sortorder_icons[$key]." sortarrow-".$value." ".$class."\"></i></a>";
        } elseif ((isset($_GET['sort2']) || getCookie('sort2')) && ($_GET['sort2'] == $sort || getCookie('sort2') == $sort) && ($_GET['sortorder2'] == $value || getCookie('sortorder2') == $value)) {
            $query['sort2'] = null;
            $query['sortorder2'] = null;
            $query_result = http_build_query($query);
            $arrows .= "<a href=\"".$_SERVER['PHP_SELF']."?".$query_result."\" onclick=\"deleteCookie('sort2'); deleteCookie('sortorder2');\"><i class=\"glyphicon ".$sortorder_icons[$key]." sortarrow-".$value." ".$class."\"></i></a>";
        } else {
            $query['sort'] = $sort;
            $query['sortorder'] = $value;
            $query_result = http_build_query($query);
            $arrows .= "<a href=\"".$_SERVER['PHP_SELF']."?".$query_result."\" onclick=\"setCookie('sort', '".$sort."'); setCookie('sortorder', '".$value."');\"><i class=\"glyphicon ".$sortorder_icons[$key]." sortarrow-".$value." ".$class."\"></i></a>";
        }
    }

    return "<span class=\"sortarrow-container\">".$arrows."</span>";
}
