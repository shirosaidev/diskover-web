<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Diskover.php";

// check for index in url
if (isset($_GET['index'])) {
    $esIndex = $_GET['index'];
    setCookie('index', $esIndex);
} else {
    // get index from env var or cookie
    $esIndex = getenv('APP_ES_INDEX') ?: getCookie('index');
    // redirect to select indices page if no index cookie
    if (!$esIndex) {
        header("location:selectindices.php");
        exit();
    }
}
// check for index2 in url
if (isset($_GET['index2'])) {
    $esIndex2 = $_GET['index2'];
    setCookie('index2', $esIndex2);
} else {
    $esIndex2 = getenv('APP_ES_INDEX2') ?: getCookie('index2');
}

require "d3_inc.php";

$path = $_GET['path'] ?: getCookie('path');
// check if no path (grab one from ES)
if (empty($path)) {
    $path = get_es_path($client, $esIndex);
    createCookie('path', $path);
} elseif ($path !== "/") {
    // remove any trailing slash
    $path = rtrim($path, '/');
}
$filter = (int)$_GET['filter'] ?: Constants::FILTER; // file size
$mtime = $_GET['mtime'] ?: Constants::MTIME; // file mtime
// get mtime in ES format
$mtime = getmtime($mtime);
$maxdepth = (int)$_GET['maxdepth'] ?: Constants::MAXDEPTH; // maxdepth

// get top 50 directories
$totaldirsize = 0;
$totaldircount = 0;

$results = [];
$searchParams = [];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'directory';

// Setup search query for largest files
$searchParams['body'] = [
    'size' => 50,
    '_source' => ['filename', 'path_parent', 'filesize', 'items', 'last_modified'],
    'query' => [
        'bool' => [
            'must' => [
                    'wildcard' => [ 'path_parent' => $path . '*' ]
            ],
            'filter' => [
                'bool' => [
                    'must' => [
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
            ]
        ]
    ],
    'sort' => [
        'last_modified' => [
            'order' => 'asc'
        ]
    ]
];
$queryResponse = $client->search($searchParams);

$oldestdirs = $queryResponse['hits']['hits'];

foreach ($oldestdirs as $key => $value) {
    $totaldirsize += $value['_source']['filesize'];
    $totaldircount += $value['_source']['items'];
}

// Get search results from Elasticsearch for top 50 oldest files
$results = [];
$searchParams = [];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';


// Setup search query for largest files
$searchParams['body'] = [
    'size' => 50,
    '_source' => ['filename', 'path_parent', 'filesize', 'last_modified'],
    'query' => [
        'bool' => [
            'must' => [
                    'wildcard' => [ 'path_parent' => $path . '*' ]
            ],
            'filter' => [
                'bool' => [
                    'must' => [
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
            ]
        ]
    ],
    'sort' => [
        'last_modified' => [
            'order' => 'asc'
        ]
    ]
];
$queryResponse = $client->search($searchParams);

$oldestfiles = $queryResponse['hits']['hits'];

// calculate total file size
$totalfilesize = 0;
foreach ($oldestfiles as $key => $value) {
    $totalfilesize += $value['_source']['filesize'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>diskover &mdash; Top 50 Oldest</title>
	<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
  <link rel="stylesheet" href="css/diskover.css" media="screen" />
  <link rel="stylesheet" href="css/diskover-top50.css" media="screen" />
</head>
<body>
<?php include "nav.php"; ?>
<div class="container-fluid" style="margin-top:70px;">
  <div class="row">
    <div class="col-xs-12">
        <div id="top50files">
            <div class="row">
    			<div class="col-xs-12">
    				<h2 style="display: inline;"><i class="glyphicon glyphicon-scale"></i> Top 50 Oldest Files</h2>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="top50Switch('directory');">Switch to directories</a>&nbsp;&nbsp;&nbsp;&nbsp;
                    <div class="btn-group">
                        <button class="btn btn-default button-largest"> Largest</button>
                        <button class="btn btn-default button-oldest active"> Oldest</button>
                        <button class="btn btn-default button-newest"> Newest</button>
                        <button class="btn btn-default button-user"> Users</button>
                    </div>
                    <span style="font-size:10px; color:gray;">*filters on filetree page affect this page</span>
                    <br />
                    <h5 style="display: inline;"><span class="text-success bold"><?php echo stripslashes($path); ?></span></h5>
                    <span><a title="<?php echo getParentDir($path); ?>" class="btn btn-primary btn-sm" onclick="window.location.href='top50_oldest.php?path=<?php echo getParentDir($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>';"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</a></span>
                </div>
    		</div><br />
            <table class="table table-striped table-hover table-condensed" style="font-size:12px;">
              <thead>
                <tr>
                  <th class="text-nowrap">#</th>
                  <th class="text-nowrap">Name</th>
                  <th class="text-nowrap">File Size</th>
                  <th>%</th>
                  <th class="text-nowrap">Modified (utc)</th>
                  <th class="text-nowrap">Path</th>
              </tr>
            </thead>
            <tbody>
                  <?php
                  $n = 1;
                  foreach ($oldestfiles as $key => $value) {
                    ?>
                    <tr><td class="darken" width="10"><?php echo $n; ?></td>
                        <td class="path"><a href="view.php?id=<?php echo $value['_id'] . '&amp;index=' . $value['_index'] . '&amp;doctype=file'; ?>"><i class="glyphicon glyphicon-file" style="color:#738291;font-size:13px;"></i> <?php echo $value['_source']['filename']; ?></a></td>
                        <td class="text-nowrap"><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value['_source']['filesize']); ?></span></td>
                        <td width="15%"><div class="percent" style="width:<?php echo number_format(($value['_source']['filesize'] / $totalfilesize) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['_source']['filesize'] / $totalfilesize) * 100, 2); ?>%</small></span></td>
                        <td class="text-nowrap darken"><?php echo $value['_source']['last_modified']; ?></td>
                        <td class="path darken"><a href="top50.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo $value['_source']['path_parent']; ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=<?php echo $_GET['mtime']; ?>"><?php echo $value['_source']['path_parent']; ?></a></td>
                    </tr>
                  <?php $n++; }
                   ?>
               </tbody>
          </table>
        </div>
        <div id="top50dirs" style="display:none;">
            <div class="row">
    			<div class="col-xs-12">
    				<h2 style="display: inline;"><i class="glyphicon glyphicon-scale"></i> Top 50 Oldest Directories</h2>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="top50Switch('file');">Switch to files</a>&nbsp;&nbsp;&nbsp;&nbsp;
                    <div class="btn-group">
                        <button class="btn btn-default button-largest"> Largest</button>
                        <button class="btn btn-default button-oldest active"> Oldest</button>
                        <button class="btn btn-default button-newest"> Newest</button>
                        <button class="btn btn-default button-user"> Users</button>
                    </div>
                    <br />
                    <h5 style="display: inline;"><span class="text-success bold"><?php echo stripslashes($path); ?></span></h5>
                    <span><a title="<?php echo getParentDir($path); ?>" class="btn btn-primary btn-sm" onclick="window.location.href='top50_oldest.php?path=<?php echo getParentDir($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>';"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</a></span>
                </div>
    		</div><br />
            <?php if (count($oldestdirs) > 0) { ?>
            <table class="table table-striped table-hover table-condensed" style="font-size:12px;">
              <thead>
                <tr>
                  <th class="text-nowrap">#</th>
                  <th class="text-nowrap">Name</th>
                  <th class="text-nowrap">Size</th>
                  <th>%</th>
                  <th class="text-nowrap">Items</th>
                  <th>%</th>
                  <th class="text-nowrap">Modified (utc)</th>
                  <th class="text-nowrap">Path</th>
              </tr>
            </thead>
            <tbody>
                  <?php
                      $n = 1;
                      foreach ($oldestdirs as $key => $value) {
                          // set fullpath, parentpath and filename and check for root /
                          if ($path === "/" && $value['_source']['path_parent'] === "/") {
                              $fullpath = '/' . $value['_source']['filename'];
                              $parentpath = $value['_source']['path_parent'];
                              if ($value['_source']['filename'] === "") { // root /
                                  $filename = '/';
                              } else {
                                  $filename = $value['_source']['filename'];
                              }
                          } else {
                              $fullpath = $value['_source']['path_parent'] . '/' . $value['_source']['filename'];
                              $parentpath = $value['_source']['path_parent'];
                              $filename = $value['_source']['filename'];
                          }
                        ?>
                        <tr><td class="darken" width="10"><?php echo $n; ?></td>
                            <td class="path"><a href="top50_oldest.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;doctype=directory"><i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;font-size:13px;"></i> <?php echo $filename; ?></a></td>
                            <td class="text-nowrap"><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value['_source']['filesize']); ?></span></td>
                            <td width="15%"><div class="text-right percent" style="width:<?php echo number_format(($value['_source']['filesize'] / $totaldirsize) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['_source']['filesize'] / $totaldirsize) * 100, 2); ?>%</small></span></td>
                            <td class="text-nowrap"><?php echo $value['_source']['items']; ?></td>
                            <td width="15%"><div class="text-right percent" style="width:<?php echo number_format(($value['_source']['items'] / $totaldircount) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['_source']['items'] / $totaldircount) * 100, 2); ?>%</small></span></td>
                            <td class="text-nowrap darken"><?php echo $value['_source']['last_modified']; ?></td>
                            <td class="path darken"><a href="top50.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo $value['_source']['path_parent']; ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=<?php echo $_GET['mtime']; ?>"><?php echo $value['_source']['path_parent']; ?></a></td>
                        </tr>
                    <?php $n++; } ?>
               </tbody>
          </table>
      <?php } else { ?>
          <div class="col-xs-7">
          <div class="alert alert-dismissible alert-info">
              <button type="button" class="close" data-dismiss="alert">&times;</button><i class="glyphicon glyphicon-info-sign"></i> No directories found or directory size has not been calculated.
          </div>
        </div>
      <?php } ?>
        </div>
      </div>
  </div>
</div>
<script language="javascript" src="js/jquery.min.js"></script>
<script language="javascript" src="js/bootstrap.min.js"></script>
<script language="javascript" src="js/diskover.js"></script>
<script language="javascript" src="js/top50.js"></script>
</body>
</html>
