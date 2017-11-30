<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// redirect to select indices page if no index cookie
$esIndex = getenv('APP_ES_INDEX') ?: getCookie('index');
if (!$esIndex) {
    header("location:selectindices.php");
    exit();
}
$esIndex2 = getenv('APP_ES_INDEX2') ?: getCookie('index2');

require __DIR__ . "/d3_inc.php";


function top50dirs($client, $index, $path, $filter, $mtime, $depth, $maxdepth) {
    $items = [];
    $subdirs = [];
    if ($depth == $maxdepth) {
        return $items;
    }

    // get directories in current path (not recursive)
    $subdirs = get_sub_dirs($client, $index, $path);

    // loop through all subdirs and add to items array
    foreach ($subdirs as $d) {
        // get dir total size, file count, last modified time
        $dirinfo = get_dir_info($client, $index, $d, $filter, $mtime);

        // continue if directory is empty
        if ($dirinfo[0] == 0 || $dirinfo[1] == 0) {
            continue;
        }
        $items[] = [
            "name" => $d,
            "size" => $dirinfo[0],
            "count" => $dirinfo[1],
            "modified" => $dirinfo[2],
            "children" => top50dirs($client, $index, $d, $filter, $mtime, $depth+=1, $maxdepth)
        ];
        $depth-=1;
    }
    return $items;
}

// get top 50 directories
$totaldirsize = 0;
$totaldircount = 0;
$data = top50dirs($client, $esIndex, $path, $filter, $mtime, 0, 2);
$largestdirs = [];
foreach ($data as $arr) {
    if (isset($arr['count'])) {
        $largestdirs[$arr['name']] = [$arr['size'], $arr['count'], $arr['modified']];
        $totaldirsize += $arr['size'];
        $totaldircount += $arr['count'];
    }
    if (isset($arr['children'])) {
        foreach ($arr['children'] as $arr1) {
            if (isset($arr1['count'])) {
                $largestdirs[$arr1['name']] = [$arr1['size'], $arr1['count'], $arr1['modified']];
                $totaldirsize += $arr1['size'];
                $totaldircount += $arr1['count'];
            }
        }
    }
}
arsort($largestdirs);
$largestdirs = array_slice($largestdirs, 0, 50);


// Get search results from Elasticsearch for top 50 largest files
$results = [];
$searchParams = [];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';


// Setup search query for largest files
$path = addcslashes($path, '+-&&||!(){}[]^"~*?:\/ ');
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
        'filesize' => [
            'order' => 'desc'
        ]
    ]
];
$queryResponse = $client->search($searchParams);

$largestfiles = $queryResponse['hits']['hits'];

// calculate total file size
$totalfilesize = 0;
foreach ($largestfiles as $key => $value) {
    $totalfilesize += $value['_source']['filesize'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>diskover &mdash; Top 50 Largest</title>
  <!--<link rel="stylesheet" href="/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" href="/css/bootstrap-theme.min.css" media="screen" />-->
	<link rel="stylesheet" href="/css/bootswatch.min.css" media="screen" />
  <link rel="stylesheet" href="/css/diskover.css" media="screen" />
	<style>
    .percent {
        background-color: #D20915;
        opacity: .9;
        border: 1px solid black;
        display: inline-block;
        height: 14px;
        left: 0px;
        bottom: -2px;
        position: relative;
        z-index: 0;
        overflow: hidden;
    }
	</style>
</head>
<body>
<?php include __DIR__ . "/nav.php"; ?>
<div class="container-fluid">
  <div class="row">
    <div class="col-xs-12">
        <div id="top50files">
            <div class="row">
    			<div class="col-xs-12">
    				<h1 style="display: inline;"><i class="glyphicon glyphicon-scale"></i> Top 50 Largest Files</h1>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="top50Switch('directory');">Switch to directories</a></h1>&nbsp;&nbsp;&nbsp;&nbsp;<h5 style="display: inline;"><span class="text-success bold"><?php echo stripslashes($path); ?></span></h5>&nbsp;&nbsp;&nbsp;&nbsp;
                    <div class="btn-group">
                        <button class="btn btn-default button-largest active"> Largest</button>
                        <button class="btn btn-default button-oldest"> Oldest</button>
                        <button class="btn btn-default button-newest"> Newest</button>
                        <button class="btn btn-default button-user"> Users</button>
                    </div>
                    <span style="font-size:10px; color:gray;">*filters on filetree page affect this page</span>
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
                  foreach ($largestfiles as $key => $value) {
                    ?>
                    <tr><td width="10"><?php echo $n; ?></td>
                        <td class="path"><i class="glyphicon glyphicon-file" style="color:#738291;font-size:13px;"></i> <a href="/view.php?id=<?php echo $value['_id'] . '&amp;index=' . $value['_index'] . '&amp;doctype=file'; ?>"><?php echo $value['_source']['filename']; ?></a></td>
                        <td class="text-nowrap"><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value['_source']['filesize']); ?></span></td>
                        <td width="20%"><div class="percent" style="width:<?php echo number_format(($value['_source']['filesize'] / $totalfilesize) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['_source']['filesize'] / $totalfilesize) * 100, 2); ?>%</small></span></td>
                        <td class="text-nowrap"><?php echo $value['_source']['last_modified']; ?></td>
                        <td class="path"><a href="/top50.php?path=<?php echo $value['_source']['path_parent']; ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=<?php echo $_GET['mtime']; ?>"><?php echo $value['_source']['path_parent']; ?></a></td>
                    </tr>
                  <?php $n++; }
                   ?>
               </tbody>
          </table>
        </div>
        <div id="top50dirs" style="display:none;">
            <div class="row">
    			<div class="col-xs-12">
    				<h1 style="display: inline;"><i class="glyphicon glyphicon-scale"></i> Top 50 Largest Directories</h1>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="top50Switch('file');">Switch to files</a></h1>&nbsp;&nbsp;&nbsp;&nbsp;<h5 style="display: inline;"><span class="text-success bold"><?php echo stripslashes($path); ?></span></h5>&nbsp;&nbsp;&nbsp;&nbsp;
                    <div class="btn-group">
                        <button class="btn btn-default button-largest active"> Largest</button>
                        <button class="btn btn-default button-oldest"> Oldest</button>
                        <button class="btn btn-default button-newest"> Newest</button>
                        <button class="btn btn-default button-user"> Users</button>
                    </div>
                </div>
    		</div><br />
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
                  foreach ($largestdirs as $key => $value) {
                    ?>
                    <tr><td width="10"><?php echo $n; ?></td>
                        <td class="path"><i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;font-size:13px;"></i> <a href="/top50.php?path=<?php echo $key; ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=<?php echo $_GET['mtime']; ?>"><?php echo basename($key); ?></a></td>
                        <td class="text-nowrap"><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value[0]); ?></span></td>
                        <td width="20%"><div class="text-right percent" style="width:<?php echo number_format(($value[0] / $totaldirsize) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value[0] / $totaldirsize) * 100, 2); ?>%</small></span></td>
                        <td class="path"><?php echo $value[1]; ?></td>
                        <td width="20%"><div class="text-right percent" style="width:<?php echo number_format(($value[1] / $totaldircount) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value[1] / $totaldircount) * 100, 2); ?>%</small></span></td>
                        <td class="path"><?php echo $value[2]; ?></td>
                        <td class="path"><a href="/top50.php?path=<?php echo dirname($key); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=<?php echo $_GET['mtime']; ?>"><?php echo dirname($key); ?></a></td>
                    </tr>
                  <?php $n++; }
                   ?>
               </tbody>
          </table>
        </div>
      </div>
  </div>
</div>
<script language="javascript" src="/js/jquery.min.js"></script>
<script language="javascript" src="/js/bootstrap.min.js"></script>
<script language="javascript" src="/js/diskover.js"></script>
<!-- top 50 switcher -->
<script>
    function top50Switch(a) {
        if (a == 'directory') {
            document.getElementById('top50files').style.display = 'none';
            document.getElementById('top50dirs').style.display = 'block';
        } else {
            document.getElementById('top50dirs').style.display = 'none';
            document.getElementById('top50files').style.display = 'block';
        }
    }
</script>
<!-- buttons -->
<script>
    var path = $_GET('path');
    var filter = $_GET('filter');
    var mtime = $_GET('mtime');
    $(".button-largest").click(function () {
        window.location.href = '/top50.php?path=' + path + '&filter='  + filter + '&mtime=' + mtime;
    });
    $(".button-oldest").click(function () {
        window.location.href = '/top50_oldest.php?path=' + path + '&filter='  + filter + '&mtime=' + mtime;
    });
    $(".button-newest").click(function () {
        window.location.href = '/top50_newest.php?path=' + path + '&filter='  + filter + '&mtime=' + mtime;
    });
    $(".button-user").click(function () {
        window.location.href = '/top50_users.php?path=' + path + '&filter='  + filter + '&mtime=' + mtime;
    });
</script>
</body>
</html>
