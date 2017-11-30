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

// Get search results from Elasticsearch for top 50 users
$results = [];
$searchParams = [];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

$searchParams['body'] = [
    'size' => 0,
    'size' => 0,
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
        'aggs' => [
            'top_users' => [
                'terms' => [
                    'field' => 'owner',
                    'order' => [
                        'file_size' => 'desc'
                    ],
                    'size' => 50
                ],
                'aggs' => [
                    'file_size' => [
                        'sum' => [
                            'field' => 'filesize'
                        ]
                    ]
                ]
            ]
        ]
    ];
$queryResponse = $client->search($searchParams);

$results = $queryResponse['aggregations']['top_users']['buckets'];

foreach ($results as $result) {
    $topusers[] = [
                "owner" => $result['key'],
                "filecount" => $result['doc_count'],
                "filesize" => $result['file_size']['value']
                ];
}

// calculate total file size and file count
$totalfilesize = 0;
$totalfilecount = 0;
foreach ($topusers as $key => $value) {
    $totalfilesize += $value['filesize'];
    $totalfilecount += $value['filecount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>diskover &mdash; Top 50 Users</title>
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
        <div id="top50users">
            <div class="row">
    			<div class="col-xs-12">
    				<h1 style="display: inline;"><i class="glyphicon glyphicon-scale"></i> Top 50 Users</h1>&nbsp;&nbsp;&nbsp;&nbsp;<h5 style="display: inline;">Path: <span class="text-success"><?php echo stripslashes($path); ?></span></h5>&nbsp;&nbsp;&nbsp;&nbsp;
                    <div class="btn-group">
                        <button class="btn btn-default button-largest"> Largest</button>
                        <button class="btn btn-default button-oldest"> Oldest</button>
                        <button class="btn btn-default button-newest"> Newest</button>
                        <button class="btn btn-default button-user active"> Users</button>
                    </div>
                    <span style="font-size:10px; color:gray;">*filters on filetree page affect this page</span>
                </div>
    		</div><br />
            <table class="table table-striped table-hover table-condensed" style="font-size:12px;">
              <thead>
                <tr>
                  <th class="text-nowrap">#</th>
                  <th class="text-nowrap">Owner</th>
                  <th class="text-nowrap">Size</th>
                  <th>%</th>
                  <th class="text-nowrap">Items</th>
                  <th>%</th>
              </tr>
            </thead>
            <tbody>
                  <?php
                  $n = 1;
                  foreach ($topusers as $key => $value) {
                    ?>
                    <tr><td width="10"><?php echo $n; ?></td>
                        <td><i class="glyphicon glyphicon-user" style="color:#D19866; font-size:13px;"></i> <a href="/advanced.php?submitted=true&p=1&owner=<?php echo $value['owner']; ?>"><?php echo $value['owner']; ?></a></td>
                        <td><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value['filesize']); ?></span></td>
                        <td width="20%"><div class="percent" style="width:<?php echo number_format(($value['filesize'] / $totalfilesize) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['filesize'] / $totalfilesize) * 100, 2); ?>%</small></span></td>
                        <td><?php echo $value['filecount']; ?></td>
                        <td width="20%"><div class="percent" style="width:<?php echo number_format(($value['filecount'] / $totalfilecount) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['filecount'] / $totalfilecount) * 100, 2); ?>%</small></span></td>
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
