<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Auth.php";
require "../src/diskover/Diskover.php";
require "d3_inc.php";
require "vars_inc.php";


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
	<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
  <link rel="stylesheet" href="css/diskover.css" media="screen" />
  <link rel="stylesheet" href="css/diskover-top50.css" media="screen" />
</head>
<body>
<?php include "nav.php"; ?>
<div class="container-fluid" style="margin-top:70px;">
  <div class="row">
    <div class="col-xs-12">
        <div id="top50users">
            <div class="row">
    			<div class="col-xs-12">
    				<h2 style="display: inline;"><i class="glyphicon glyphicon-scale"></i> Top 50 Users</h2>&nbsp;&nbsp;&nbsp;&nbsp;
                    <div class="btn-group">
                        <button class="btn btn-default button-largest"> Largest</button>
                        <button class="btn btn-default button-oldest"> Oldest</button>
                        <button class="btn btn-default button-newest"> Newest</button>
                        <button class="btn btn-default button-user active"> Users</button>
                    </div>
                    <span style="font-size:10px; color:gray;"><i class="glyphicon glyphicon-info-sign"></i> filters on filetree page affect this page</span>
                    <br />
                    <h5 style="display: inline;"><span class="text-success bold"><?php echo stripslashes($path); ?></span></h5>
                    <span><a title="<?php echo getParentDir($path); ?>" class="btn btn-primary btn-sm" onclick="window.location.href='<?php echo build_url('path', getParentDir($path)); ?>';"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</a></span>
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
                    <tr><td class="darken" width="10"><?php echo $n; ?></td>
                        <td><i class="glyphicon glyphicon-user" style="color:#D19866; font-size:13px; padding-right:3px;"></i> <a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&owner=<?php echo $value['owner']; ?>"><?php echo $value['owner']; ?></a></td>
                        <td><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value['filesize']); ?></span></td>
                        <td width="15%"><div class="percent" style="width:<?php echo number_format(($value['filesize'] / $totalfilesize) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['filesize'] / $totalfilesize) * 100, 2); ?>%</small></span></td>
                        <td><?php echo $value['filecount']; ?></td>
                        <td width="15%"><div class="percent" style="width:<?php echo number_format(($value['filecount'] / $totalfilecount) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['filecount'] / $totalfilecount) * 100, 2); ?>%</small></span></td>
                    </tr>
                  <?php $n++; }
                   ?>
               </tbody>
          </table>
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
