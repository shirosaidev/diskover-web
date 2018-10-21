<?php
/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

session_start();
use diskover\Constants;
use Elasticsearch\ClientBuilder;

error_reporting(E_ALL ^ E_NOTICE);

// diskover-web version
$VERSION = '1.5.0-rc18';


function connectES() {
  // Connect to Elasticsearch node
  $esPort = getenv('APP_ES_PORT') ?: Constants::ES_PORT;
  $esIndex = getenv('APP_ES_INDEX') ?: getCookie('index');
  $esIndex2 = getenv('APP_ES_INDEX2') ?: getCookie('index2');
  if (Constants::AWS) {
    // using AWS
    if (Constants::AWS_HTTPS) {
        $scheme = 'https';
    } else {
        $scheme = 'http';
    }
    $hosts = [
      [ 'host' => Constants::ES_HOST, 'port' => $esPort, 'scheme' => $scheme ]
    ];
  } else {
  $hosts = [
      [ 'host' => Constants::ES_HOST, 'port' => $esPort, 
      'user' => Constants::ES_USER, 'pass' => Constants::ES_PASS ]
    ];
  }

  $client = ClientBuilder::create()->setHosts($hosts)->build();

  // Check if diskover index exists in Elasticsearch
  $params = ['index' => $esIndex];
  $bool_index = $client->indices()->exists($params);
  $params = ['index' => $esIndex2];
  $bool_index2 = $client->indices()->exists($params);
  if ((!$bool_index || !$bool_index2) && basename($_SERVER['PHP_SELF']) !== 'selectindices.php' && explode('/', $_SERVER['PHP_SELF'])[1] !== 'api.php') {
      deleteCookie('index');
      deleteCookie('index2');
      header("Location: /selectindices.php");
      exit();
  }

  return $client;
}


function get_es_path($client, $index) {
    // try to get a top level path from ES

    $searchParams['body'] = [];

    // Setup search query
    $searchParams['index'] = $index;
    $searchParams['type']  = "diskspace";

    // number of results to return
    $searchParams['size'] = 1;

    $searchParams['body'] = [
        '_source' => ["path"],
           'query' => [
               'match_all' => (object) []
        ]
    ];

    // Send search query to Elasticsearch and get results
    $queryResponse = $client->search($searchParams);

    // Get directories
    $results = $queryResponse['hits']['hits'];

    // set path to first path found
    $path = $results[0]['_source']['path'];

    // set session var
    $_SESSION['rootpath'] = $path;

    return $path;
}


// return time in ES format
function getmtime($mtime) {
    // default 0 days mtime filter
    if (empty($mtime) || $mtime === "now" || $mtime === 0) {
        $mtime = 'now';
    } elseif ($mtime === "today") {
        $mtime = 'now/d';
    } elseif ($mtime === "tomorrow") {
        $mtime = 'now+1d/d';
    } elseif ($mtime === "yesterday") {
        $mtime = 'now-1d/d';
    } elseif ($mtime === "1d") {
        $mtime = 'now-1d/d';
    } elseif ($mtime === "1w") {
        $mtime = 'now-1w/d';
    } elseif ($mtime === "1m") {
        $mtime = 'now-1M/d';
    } elseif ($mtime === "3m") {
        $mtime = 'now-3M/d';
    } elseif ($mtime === "6m") {
        $mtime = 'now-6M/d';
    } elseif ($mtime === "1y") {
        $mtime = 'now-1y/d';
    } elseif ($mtime === "2y") {
        $mtime = 'now-2y/d';
    } elseif ($mtime === "3y") {
        $mtime = 'now-3y/d';
    } elseif ($mtime === "5y") {
        $mtime = 'now-5y/d';
    } elseif ($mtime === "10y") {
        $mtime = 'now-10y/d';
    }
    return $mtime;
}


// update url param with new value and return url
function build_url($param, $val) {
    parse_str($_SERVER['QUERY_STRING'], $queries);
    // defaults
    $queries['index'] = isset($_GET['index']) ? $_GET['index'] : getCookie('index');
    $queries['index2'] = isset($_GET['index2']) ? $_GET['index2'] : getCookie('index2');
    $queries['path'] = isset($_GET['path']) ? $_GET['path'] : getCookie('path');
    $queries['filter'] = isset($_GET['filter']) ? $_GET['filter'] : getCookie('filter');
    $queries['mtime'] = isset($_GET['mtime']) ? $_GET['mtime'] : getCookie('mtime');
    $queries['use_count'] = isset($_GET['use_count']) ? $_GET['use_count'] : getCookie('use_count');
    $queries['show_files'] = isset($_GET['show_files']) ? $_GET['show_files'] : getCookie('show_files');
    $queries['path'] = isset($_GET['path']) ? $_GET['path'] : getCookie('path');
    // set new param
    $queries[$param] = $val;
    $q = http_build_query($queries, null, '&', PHP_QUERY_RFC3986);
    $url = $_SERVER['PHP_SELF'] . "?" . $q;
    return $url;
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

// convert human readable file size format to bytes
function convertToBytes($num, $unit) {
  if ($num == 0) return 0;
  if ($unit == "") return $num;
  if ($unit == "bytes") {
      return $num;
  } elseif ($unit == "KB") {
      return $num * 1024;
  } elseif ($unit == "MB") {
      return $num * 1024 * 1024;
  } elseif ($unit == "GB") {
      return $num * 1024 * 1024 * 1024;
  }
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
    $sec = number_format($seconds, 3);
    $milliseconds = explode('.', $sec)[1];
    $seconds = (int)$seconds;
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    $time = $dtF->diff($dtT)->format('%ad:%hh:%im:%s');
    $time = $time . '.' . $milliseconds . 's';
    return $time;
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

// escape special characters
function escape_chars($text) {
   $chr = '<>+-&|!(){}[]^"~*?:/= @\'$.#\\';
   return addcslashes($text, $chr);
}

// get custom tags from customtags.txt
function get_custom_tags() {
    $f = fopen("customtags.txt", "r") or die("Unable to open customtags.txt! Check if exists and permissions.");
    $t = [];
    // grab each line (tag)
    while(!feof($f)) {
        $l = trim(fgets($f));
        if ($l === "") {
            continue;
        }
        // hex color for tag separated by pipe |
        $t[] = explode('|', $l);
    }
    fclose($f);
    return $t;
}

// get extra fields from extrafields.txt
function get_extra_fields() {
    $f = fopen("extrafields.txt", "r") or die("Unable to open extrafields.txt! Check if exists and permissions.");
    $ef = [];
    // grab each line (field)
    while(!feof($f)) {
        $l = trim(fgets($f));
        if ($l === "") {
            continue;
        }
        // field desc for field separated by pipe |
        $fn = explode('|', $l)[0];
        $fd = explode('|', $l)[1];
        $ef[$fn] = $fd;
    }
    fclose($f);
    return $ef;
}

// get smart searhches from smartsearches.txt
function get_smartsearches() {
    $f = fopen("smartsearches.txt", "r") or die("Unable to open smartsearches.txt! Check if exists and permissions.");
    $ss = [];
    // grab each line (smart search string)
    while(!feof($f)) {
        $l = trim(fgets($f));
        if ($l === "") {
            continue;
        }
        // es search query for smart search separated by pipe |
        $ss[] = explode('|', $l);
    }
    fclose($f);
    return $ss;
}

// add custom tag to customtags.txt
function add_custom_tag($t) {
    $tag = trim(explode('|', $t)[0]);
    $color = trim(explode('|', $t)[1]);
    if ($color == "") {
        // default hex color for tags
        $color = "#CCC";
    }
    $f = fopen("customtags.txt", "a") or die("Unable to open customtags.txt! Check if exists and permissions.");
    fwrite($f, $tag . '|' . $color . PHP_EOL);
    fclose($f);
}

// get custom tag color from customtags.txt
function get_custom_tag_color($t) {
    $c = "#ccc";
    $f = fopen("customtags.txt", "r") or die("Unable to open customtags.txt! Check if exists and permissions.");
    // grab each line (tag)
    while(!feof($f)) {
        $l = trim(fgets($f));
        if ($l === "") {
            continue;
        }
        // hex color for tag separated by pipe |
        $tag = explode('|', $l)[0];
        $color = explode('|', $l)[1];
        if ($t == $tag) {
            $c = $color;
            break;
        }
    }
    fclose($f);
    return $c;
}

// get index2 file info for comparison with index
function get_index2_fileinfo($client, $index, $path_parent, $filename) {
    $searchParams = [];
    $searchParams['index'] = $index;
    $searchParams['type']  = 'file,directory';
    $path_parent = escape_chars($path_parent);
    $filename = escape_chars($filename);
    $searchParams['body'] = [
       'size' => 1,
       '_source' => ['filesize', 'items', 'items_files', 'items_subdirs'],
       'query' => [
           'query_string' => [
               'query' => 'filename:' . $filename . ' AND path_parent:' . $path_parent
           ]
        ]
    ];
    $queryResponse = $client->search($searchParams);
    if (empty($queryResponse['hits']['hits'][0]['_source'])) {
        return [ 0, 0, 0, 0 ];
    }
    $filesize = $queryResponse['hits']['hits'][0]['_source']['filesize'];
    if ($queryResponse['hits']['hits'][0]['_type'] == 'directory') {
        $items = $queryResponse['hits']['hits'][0]['_source']['items'];
        $items_files = $queryResponse['hits']['hits'][0]['_source']['items_files'];
        $items_subdirs = $queryResponse['hits']['hits'][0]['_source']['items_subdirs'];
        $arr = [ $filesize, $items, $items_files, $items_subdirs ];
    } else {
        $arr = [ $filesize ];
    }
    return $arr;
}

// sort search results and set cookies for search pages
function sortSearchResults($request, $searchParams) {
    if (!$request['sort'] && !$request['sort2'] && !getCookie("sort") && !getCookie("sort2")) {
        $searchParams['body']['sort'] = [ 'path_parent' => [ 'order' => 'asc' ], 'filename' => 'asc' ];
    } else {
        $searchParams['body']['sort'] = [];
        $sortarr = ['sort', 'sort2'];
        $sortorderarr = ['sortorder', 'sortorder2'];
        foreach($sortarr as $key => $value) {
            if ($request[$value] && !$request[$sortorderarr[$key]]) {
                // check if we are sorting by tag
                if ($request[$value] == "tag") {
                    array_push($searchParams['body']['sort'], ['tag']);
                    array_push($searchParams['body']['sort'], ['tag_custom']);
                    createCookie($value, $request[$value]);
                } else {
                    $searchParams['body']['sort'] = $request[$value];
                    createCookie($value, $request[$value]);
                }
            } elseif ($request[$value] && $request[$sortorderarr[$key]]) {
                // check if we are sorting by tag
                if ($request[$value] == "tag") {
                    array_push($searchParams['body']['sort'], [ 'tag' => [ 'order' => $request[$sortorderarr[$key]] ] ]);
                    array_push($searchParams['body']['sort'], [ 'tag_custom' => [ 'order' => $request[$sortorderarr[$key]] ] ]);
                    createCookie($value, $request[$value]);
                } else {
                    array_push($searchParams['body']['sort'], [ $request[$value] => [ 'order' => $request[$sortorderarr[$key]] ] ]);
                    createCookie($value, $request[$value]);
                    createCookie($sortorderarr[$key], $request[$sortorderarr[$key]]);
                }
            } elseif (getCookie($value) && !getCookie($sortorderarr[$key])) {
                // check if we are sorting by tag
                if (getCookie($value) == "tag") {
                    array_push($searchParams['body']['sort'], ['tag']);
                    array_push($searchParams['body']['sort'], ['tag_custom']);
                } else {
                    $searchParams['body']['sort'] = getCookie($value);
                }
            } elseif (getCookie($value) && getCookie($sortorderarr[$key])) {
                // check if we are sorting by tag
                if (getCookie($value) == "tag") {
                    array_push($searchParams['body']['sort'], ['tag' => [ 'order' => getCookie($sortorderarr[$key]) ] ]);
                    array_push($searchParams['body']['sort'], ['tag_custom' => [ 'order' => getCookie($sortorderarr[$key]) ] ]);
                } else {
                    array_push($searchParams['body']['sort'], [ getCookie($value) => [ 'order' => getCookie($sortorderarr[$key]) ] ]);
                }
            }
        }
    }
    return $searchParams;
}

// predict search request and handle smartsearch requests
function predict_search($q) {
    // remove any extra white space
    $q = trim($q);

    // Grab all the smart searches from file
    $smartsearches = get_smartsearches();

    // check for escape character to disable smartsearch
    if (strpos($q, '\\') === 0 || strpos($q, '!\\') === 0) {
        $request = preg_replace('/^\\\|!\\\/', '', $q);
    // check for path input
    } elseif (strpos($q, '/') === 0 && strpos($q, 'path_parent') === false) {
        // check for escaped paths
        if (strpos($q, '\/') !== false) {
            $request = $q;
        } else {
            $request = escape_chars($q);
        }
        if (preg_match('/\.(\w)$|\.(\w){1,4}$/', $request)) {
            $request = rtrim($request, '\\');
            $filearr = explode('.', basename($request));
            $request = 'path_parent:' . dirname($request) . ' AND filename:' . basename($request) . '* AND extension:' . end($filearr) . '*';
        } elseif (preg_match('/\*$/', $request)) {
            $request = 'path_parent:' . rtrim($request, '\*') . ' OR path_parent:' . rtrim($request, '\*') . '\/*';
        } else {
            $request = rtrim($request, '\/*');
            $request = 'path_parent:' . $request . '* NOT path_parent:' . $request . '\/*';
        }
    } elseif (strpos($q, '*') === 0) {  # wildcard search keyword
        $request = $q;
    } elseif (strpos($q, '!') === 0) {  # ! smart search keyword
        if ($q === '!') {
            echo '<span class="text-info"><i class="glyphicon glyphicon-share-alt"></i> Enter in a smart search name like <strong>!tmp</strong> or <strong>!doc</strong> or <strong>!img</strong>. Disable by pressing \.</span>';
            echo '<br /><span class="text-info">Smart searches:</span><br />';
            foreach($smartsearches as $arr) {
                 echo '<strong><a href="simple.php?p=1&submitted=true&q='.$arr[1].'">' . $arr[0] . '</a></strong>&nbsp;&nbsp;';
            }
            die();
        } elseif (preg_match('/^\!(\w+)/', $q) !== false) {
            // check if requested smart search is in smartsearches array
            $inarray = false;
            foreach($smartsearches as $arr) {
                if(in_array($q, $arr)) {
                    $inarray = true;
                    $smartsearch_query = $arr[1];
                }
            }
            if ($inarray) {
                $request = $smartsearch_query;
            } else {
                echo '<span class="text-info"><i class="glyphicon glyphicon-exclamation-sign"></i> No matching smart search name.</span>';
                echo '<br /><span class="text-info">Smart searches:</span><br />';
                foreach($smartsearches as $arr) {
                     echo '<strong>' . $arr[0] . '</strong>&nbsp;&nbsp;';
                }
                die();
            }
        }
    } elseif (preg_match('/(\w+):/i', $q) == false && !empty($q)) {  # ES fields, ie last_modified:
        $request = "";
        $keyword_clean = preg_replace('/\bOR\b|\bAND\b|\bNOT\b|\bthe\b|\bof\b|\(|\)|\[|\]|\*|\/|\'s|&|(\w+):/i', '', $q);
        $keyword_arr = explode(' ', $keyword_clean);
        $keyword_arr_ext = [];
        $keyword_arr_path = [];
        foreach ($keyword_arr as $key => $value) {
            if ($value == "") continue;
            // check if .ext extension and make extension lowercase/uppercase version
            if (strpos($value, '.') === 0) {
                $keyword_arr_ext[] = strtoupper($value);
                $keyword_arr_ext[] = strtolower($value);
            // Add first letter upercase/lowercase and all lowercase/uppercase versions of keyword
            } else {
                $keyword_arr_path[] = strtoupper($value);
                $keyword_arr_path[] = strtolower($value);
                $keyword_arr_path[] = ucfirst($value);
                $keyword_arr_path[] = (count($keyword_arr) > 1 && $value !== "") ? 'AND' : '';
            }
        }
        // create es request
        if (count($keyword_arr_path) > 0) {
            $request .= '(';
            foreach (['filename', 'path_parent'] as $field) {
                $request .= '(' . $field .':(';
                $i = 1;
                $n = count($keyword_arr_path);
                foreach ($keyword_arr_path as $key => $value) {
                    if ($i == $n) continue;
                    if ($value == "AND") {
                        $request .= ') AND '. $field .':(';
                    } else {
                        $request .= '*' . escape_chars($value) . '* ';
                    }
                    $i++;
                }
                $request .= ')) ';
            }
            $request .= ') ';
        }
        if (count($keyword_arr_ext) > 0) {
            if (count($keyword_arr_path) > 0) {
                $request .= ' AND extension:(';
            }
            foreach ($keyword_arr_ext as $key => $value) {
                    $request .= '' . escape_chars(str_replace('.', '', $value)) . '* ';
            }
            if (count($keyword_arr_path) > 0) {
                $request .= ')';
            }
        }
    } else {
        $request = $q;
    }

    return $request;
}



function showChangePercent($client, $index, $index2) {
    if ($index2 == "") {
        return false;
    }
    $searchParams['index'] = $index;
    $searchParams['type']  = 'directory';
    $searchParams['body'] = [
                'size' => 1,
                'query' => [
                    'match_all' => (object) []
                ]
            ];
    $queryResponse = $client->search($searchParams);
    $result_source = $queryResponse['hits']['hits'][0]['_source'];
    //print_r($result_source); die();
    if ($result_source['change_percent_filesize'] >= 0 && $result_source['change_percent_filesize'] !== "") {
        return true;
    } else {
        return false;
    }
}


function getAvgHardlinks($client, $esIndex, $path, $filter, $mtime) {
    // find avg hardlinks
    $searchParams = [];
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = 'file';
    $searchParams['size'] = 1;

    $searchParams['body'] = [
        '_source' => ['hardlinks'],
            'query' => [
                  'bool' => [
                    'must' => [
                          'wildcard' => [ 'path_parent' => $path . '*' ]
                      ],
                      'filter' => [
                          'range' => [
                              'filesize' => [
                                    'gte' => $filter
                              ]
                          ],
                          'range' => [
                            'hardlinks' => [
                                'gt' => 1
                            ]
                      ]
                      ],
                      'should' => [
                          'range' => [
                              'last_modified' => [
                                  'lte' => $mtime
                              ]
                          ]
                      ]
                  ]
              ],
              'sort' => [
                  'hardlinks' => [
                      'order' => 'desc'
                  ]
              ]
    ];
    $queryResponse = $client->search($searchParams);

    $maxhardlinks = $queryResponse['hits']['hits'][0]['_source']['hardlinks'];

    $searchParams['body'] = [
        '_source' => ['hardlinks'],
            'query' => [
                  'bool' => [
                    'must' => [
                          'wildcard' => [ 'path_parent' => $path . '*' ]
                      ],
                      'filter' => [
                          'range' => [
                              'filesize' => [
                                    'gte' => $filter
                              ]
                          ],
                          'range' => [
                            'hardlinks' => [
                                'gt' => 1
                            ]
                      ]
                      ],
                      'should' => [
                          'range' => [
                              'last_modified' => [
                                  'lte' => $mtime
                              ]
                          ]
                      ]
                  ]
              ],
              'sort' => [
                  'hardlinks' => [
                      'order' => 'asc'
                  ]
              ]
    ];
    $queryResponse = $client->search($searchParams);
    $minhardlinks = $queryResponse['hits']['hits'][0]['_source']['hardlinks'];

    $avg = round(($maxhardlinks+$minhardlinks)/2, 0);

    if ($avg == 0) {
        $avg = 2;
    }

    return $avg;
}


function getAvgDupes($client, $esIndex, $path, $filter, $mtime) {
    // find avg dupes
    $searchParams = [];
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = 'file';
    $searchParams['size'] = 1;

    $searchParams['body'] = [
        '_source' => ['md5_sum'],
            'query' => [
                  'bool' => [
                    'must' => [
                          'wildcard' => [ 'path_parent' => $path . '*' ]
                      ],
                      'must_not' => [
                          'match' => [ 'dupe_md5' => '' ]
                      ],
                      'filter' => [
                          'range' => [
                              'filesize' => [
                                    'gte' => $filter
                              ]
                          ]
                      ],
                      'should' => [
                          'range' => [
                              'last_modified' => [
                                  'lte' => $mtime
                              ]
                          ]
                      ]
                  ]
              ],
              'aggs' => [
                  'top-dupe_md5' => [
                      'terms' => [
                        "field" => 'dupe_md5',
                        'size' => 1
                      ],
                      'aggs' => [
                          'top-md5s' => [
                              'top_hits' => [
                                "size" => 1
                              ]
                          ]
                      ]

                ]
            ]
    ];
    $queryResponse = $client->search($searchParams);

    $maxdupes = $queryResponse['aggregations']['top-dupe_md5']['buckets'][0]['doc_count'];

    $searchParams['body'] = [
        '_source' => ['md5_sum'],
            'query' => [
                  'bool' => [
                    'must' => [
                          'wildcard' => [ 'path_parent' => $path . '*' ]
                      ],
                      'must_not' => [
                          'match' => [ 'dupe_md5' => '' ]
                      ],
                      'filter' => [
                          'range' => [
                              'filesize' => [
                                    'gte' => $filter
                              ]
                          ]
                      ],
                      'should' => [
                          'range' => [
                              'last_modified' => [
                                  'lte' => $mtime
                              ]
                          ]
                      ]
                  ]
              ],
              'aggs' => [
                  'top-dupe_md5' => [
                      'terms' => [
                        "field" => 'dupe_md5',
                        'size' => 1,
                        'order' => [
                            'top_hit' => 'asc'
                        ]
                      ],
                      'aggs' => [
                          'top-md5s' => [
                              'top_hits' => [
                                "size" => 1
                              ]
                          ],
                          'top_hit' => [
                              'max' => [
                                'script' => [
                                    'source' => '_score'
                                ]

                              ]
                          ]
                      ]

                ]
            ]
    ];
    $queryResponse = $client->search($searchParams);

    $mindupes = $queryResponse['aggregations']['top-dupe_md5']['buckets'][0]['doc_count'];

    $avg = round(($maxdupes+$mindupes)/2, 0);

    if ($avg == 0) {
        $avg = 2;
    }

    return $avg;
}


// Connect to Elasticsearch
$client = connectES();

// sets important vars and cookies for index, index2, path, etc

// check for index in url
if (isset($_GET['index'])) {
    $esIndex = $_GET['index'];
    createCookie('index', $esIndex);
} else {
    // get index from env var or cookie
    $esIndex = (!empty(getenv('APP_ES_INDEX'))) ? getenv('APP_ES_INDEX') : getCookie('index');
    // redirect to select indices page if no index cookie
    if (empty($esIndex) && basename($_SERVER['PHP_SELF']) !== 'selectindices.php' && explode('/', $_SERVER['PHP_SELF'])[1] !== 'api.php') {
        header("location:selectindices.php");
        exit();
    }
}

if (basename($_SERVER['PHP_SELF']) !== 'selectindices.php') {
    // check for Qumulo index
    if (strpos($esIndex, 'diskover_qumulo-') !== false) {
        createCookie('qumulo', 1);
        $qumulo_index = 1;
    } else {
        createCookie('qumulo', 0);
        $qumulo_index = 0;
    }
    // check for AWS S3 index
    if (strpos($esIndex, 'diskover_s3-') !== false) {
        createCookie('s3', 1);
        $s3_index = 1;
    } else {
        createCookie('s3', 0);
        $s3_index = 0;
    }
    // check for index2 in url
    if (isset($_GET['index2'])) {
        $esIndex2 = $_GET['index2'];
        createCookie('index2', $esIndex2);
    } else {
        $esIndex2 = (!empty(getenv('APP_ES_INDEX2'))) ? getenv('APP_ES_INDEX2') : getCookie('index2');
    }

    // set path
    $path = (isset($_GET['path'])) ? $_GET['path'] : getCookie('path');
    // check if no path grab from session and then if still can't find grab from ES
    if (empty($path)) {
        $path = $_SESSION['rootpath'];
        if (empty($path)) {
            $path = get_es_path($client, $esIndex);
        }
        createCookie('path', $path);
        $_SESSION['rootpath'] = $path;
    }
    // remove any trailing slash (unless root)
    if ($path !== "/") {
        $path = rtrim($path, '/');
    }

    // set analytics vars
    $filter = (isset($_GET['filter'])) ? (int)$_GET['filter'] : getCookie('filter'); // filesize filter
    if ($filter === "") {
        $filter = Constants::FILTER;
        createCookie('filter', $filter);
    }
    $mtime = (isset($_GET['mtime'])) ? (string)$_GET['mtime'] : getCookie('mtime'); // mtime
    if ($mtime === "") {
        $mtime = Constants::MTIME;
        createCookie('mtime', $mtime);
    }
    // get use_count
    $use_count = (isset($_GET['use_count'])) ? (int)$_GET['use_count'] : getCookie('use_count'); // use count
    if ($use_count === "") {
        $use_count = Constants::USE_COUNT;
        createCookie('use_count', $use_count);
    }
    // get show_files
    $show_files = (isset($_GET['show_files'])) ? (int)$_GET['show_files'] : getCookie('show_files'); // show files
    if ($show_files === "") {
        $show_files = Constants::SHOW_FILES;
        createCookie('show_files', $show_files);
    }
    $maxdepth = (isset($_GET['maxdepth'])) ? (int)$_GET['maxdepth'] : getCookie('maxdepth'); // maxdepth
    if ($maxdepth === "") {
        $maxdepth = Constants::MAXDEPTH;
        createCookie('maxdepth', $maxdepth);
    }
    if (!$s3_index) {
        $minhardlinks = (isset($_GET['minhardlinks'])) ? (int)$_GET['minhardlinks'] : (int)getCookie('minhardlinks'); // minhardlinks
        if ($minhardlinks === "" || $minhardlinks === 0) {
            $minhardlinks = getAvgHardlinks($client, $esIndex, $path, $filter, $mtime);
            createCookie('minhardlinks', $minhardlinks);
        }
        $mindupes = (isset($_GET['mindupes'])) ? (int)$_GET['mindupes'] : (int)getCookie('mindupes'); // mindupes
        if ($mindupes === "" || $mindupes === 0) {
            $mindupes = getAvgDupes($client, $esIndex, $path, $filter, $mtime);
            createCookie('mindupes', $mindupes);
        }
    }

}
