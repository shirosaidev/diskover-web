<?php
/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Auth.php";
require "../src/diskover/Diskover.php";
require "d3_inc.php";

// check if crawl has finished
// Get search results from Elasticsearch for index stats and to see if crawl finished
// return boolean if crawl finished (true)
$results = [];
$searchParams = [];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'crawlstat';

$searchParams['body'] = [
    'size' => 1,
    'query' => [
            'match' => [
                'state' => 'finished_dircalc'
            ]
     ]
];
$queryResponse = $client->search($searchParams);

// determine if crawl is finished by checking if there is state "finished_dircalc" which only gets added at end of crawl
if (sizeof($queryResponse['hits']['hits']) > 0) {
    $crawlfinished = true;
    $searchParams['body'] = [
    'size' => 1,
    'query' => [
            'match' => [
                'state' => 'finished_crawl'
            ]
     ]
    ];
    $queryResponse = $client->search($searchParams);
    // Get total elapsed time (in seconds) of crawl (not inc dir calc time)
    $crawlelapsedtime = $queryResponse['hits']['hits'][0]['_source']['crawl_time'];
} else {
    $crawlfinished = false;
}

$searchParams['type']  = 'directory,file';

// get first crawl index time
$searchParams['body'] = [
    '_source' => ['indexing_date'],
    'size' => 1,
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

$firstcrawltime = $queryResponse['hits']['hits'][0]['_source']['indexing_date'];

// get last crawl index time
$searchParams['body'] = [
    '_source' => ['indexing_date'],
    'size' => 1,
    'query' => [
            'match_all' => (object) []
     ],
     'sort' => [
         'indexing_date' => [
             'order' => 'desc'
         ]
     ]
];
$queryResponse = $client->search($searchParams);

$lastcrawltime = $queryResponse['hits']['hits'][0]['_source']['indexing_date'];

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

$crawlcumulativetime = $queryResponse['aggregations']['total_elapsed']['value'];

// Get total worker bulk cumulative time (in seconds) 
$searchParams['type'] = 'worker';
$searchParams['body'] = [
   'size' => 0,
    'aggs' => [
      'total_elapsed' => [
        'sum' => [
          'field' => 'bulk_time'
        ]
      ]
    ],
    'query' => [
            'match_all' => (object) []
     ]
];
$queryResponse = $client->search($searchParams);

$bulkcumulativetime = $queryResponse['aggregations']['total_elapsed']['value'];


// Get total number of workers
$workers = [];

// get all the worker info
$searchParams['type']  = 'worker';
$searchParams['body'] = [
    '_source' => ['worker_name'],
    'size' => 100,
    'query' => [
        'match_all' => (object) []
    ]
];
// Send search query to Elasticsearch
$queryResponse = $client->search($searchParams);

foreach ($queryResponse['hits']['hits'] as $key => $value) {
    $workers[] = $value['_source']['worker_name'];
}
$workers = array_unique($workers);
$numworkers = sizeof($workers);


// Get search results from Elasticsearch for tags
$results = [];
$searchParams = [];

$totalFilesize = ['untagged' => 0, 'delete' => 0, 'archive' => 0, 'keep' => 0];
$totalFilesizeAll = 0;

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

// Execute the search
foreach ($totalFilesize as $tag => $value) {
    if ($tag === "untagged") { $t = ""; } else { $t = $tag; }
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

    // Send search query to Elasticsearch
    $queryResponse = $client->search($searchParams);

    // Get total size of all files with tag
    $totalFilesize[$tag] = $queryResponse['aggregations']['total_size']['value'];
    $totalFilesizeAll += $totalFilesize[$tag];
}

$results = [];
$searchParams = [];
$tagCounts = ['untagged' => 0, 'delete' => 0, 'archive' => 0, 'keep' => 0];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file,directory';

// Execute the search
foreach ($tagCounts as $tag => $value) {
    if ($tag === "untagged") { $t = ""; } else { $t = $tag; }
    $searchParams['body'] = [
       'size' => 0,
       'query' => [
         'match' => [
           'tag' => $t
         ]
       ]
    ];

    // Send search query to Elasticsearch
    $queryResponse = $client->search($searchParams);

    // Get total for tag
    $tagCounts[$tag] = $queryResponse['hits']['total'];
}

// Get search results from Elasticsearch for duplicate files
$results = [];
$searchParams = [];
$totalDupes = 0;
$totalFilesizeDupes = 0;

// Setup search query
$searchParams['index'] = $esIndex;
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
$totalDupes = $queryResponse['hits']['total'];

// Get total size of all duplicate files
$totalFilesizeDupes = $queryResponse['aggregations']['total_size']['value'];

// Get search results from Elasticsearch for number of files
$results = [];
$searchParams = [];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = "file";

$searchParams['body'] = [
    'size' => 0,
    'query' => [
        'match_all' => (object) []
     ]
];
$queryResponse = $client->search($searchParams);

// Get total count of files
$totalfiles = $queryResponse['hits']['total'];


// Get search results from Elasticsearch for number of directories
$results = [];
$searchParams = [];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = "directory";

$searchParams['body'] = [
    'size' => 0,
    'query' => [
        'match_all' => (object) []
     ]
];
$queryResponse = $client->search($searchParams);

// Get total count of directories
$totaldirs = $queryResponse['hits']['total'];

// Get search results from Elasticsearch for hardlink files
$results = [];
$searchParams = [];
$totalHardlinkFiles = 0;
$totalFilesizeHardlinkFiles = 0;

// Setup search query
$searchParams['index'] = $esIndex;
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
$totalHardlinkFiles = $queryResponse['hits']['total'];

// Get total size of all hardlink files
$totalFilesizeHardlinkFiles = $queryResponse['aggregations']['total_size']['value'];


// Get search results from Elasticsearch for disk space info
$results = [];
$searchParams = [];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = "diskspace";

$searchParams['body'] = [
    'size' => 1,
    'query' => [
        'match_all' => (object) []
     ]
];
$queryResponse = $client->search($searchParams);

// Get disk space info from queryResponse
$diskspace_path = $queryResponse['hits']['hits'][0]['_source']['path'];
$diskspace_total = $queryResponse['hits']['hits'][0]['_source']['total'];
$diskspace_free = $queryResponse['hits']['hits'][0]['_source']['free'];
$diskspace_available = $queryResponse['hits']['hits'][0]['_source']['available'];
$diskspace_used = $queryResponse['hits']['hits'][0]['_source']['used'];
$diskspace_date = $queryResponse['hits']['hits'][0]['_source']['indexing_date'];

if ($esIndex2 != "") {
    // Get search results from Elasticsearch for disk space info from index2
    $results = [];
    $searchParams = [];

    // Setup search query
    $searchParams['index'] = $esIndex2;
    $searchParams['type']  = "diskspace";

    $searchParams['body'] = [
        'size' => 1,
        'query' => [
            'match_all' => (object) []
         ]
    ];
    $queryResponse = $client->search($searchParams);

    // Get disk space info from queryResponse
    $diskspace2_path = $queryResponse['hits']['hits'][0]['_source']['path'];
    $diskspace2_total = $queryResponse['hits']['hits'][0]['_source']['total'];
    $diskspace2_free = $queryResponse['hits']['hits'][0]['_source']['free'];
    $diskspace2_available = $queryResponse['hits']['hits'][0]['_source']['available'];
    $diskspace2_used = $queryResponse['hits']['hits'][0]['_source']['used'];
    $diskspace2_date = $queryResponse['hits']['hits'][0]['_source']['indexing_date'];
}

if (!$s3_index && !$qumulo_index) {
    // Get recommended file delete size/count
    $file_recommended_delete_size = 0;
    $file_recommended_delete_count = 0;

    $results = [];
    $searchParams = [];

    // Setup search query
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = "file";

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
            'query' => 'last_modified:{* TO now-6M} AND last_access:{* TO now-6M}'
          ]
        ]
    ];
    $queryResponse = $client->search($searchParams);

    // Get total count of recommended files to remove
    $file_recommended_delete_count = $queryResponse['hits']['total'];

    // Get total size of allrecommended files to remove
    $file_recommended_delete_size = $queryResponse['aggregations']['total_size']['value'];
}

if ($s3_index) {
    // Get s3 bucket names
    $buckets = [];

    $results = [];
    $searchParams = [];

    // Setup search query
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = "directory";

    $searchParams['body'] = [
       'size' => 100,
        'query' => [
          'query_string' => [
            'query' => 'path_parent:\/s3'
          ]
        ]
    ];
    $queryResponse = $client->search($searchParams);

    // Get total count of buckets
    $bucketcount = $queryResponse['hits']['total'];

    $buckets = $queryResponse['hits']['hits'];
}

$estime = number_format(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 6);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>diskover &mdash; Dashboard</title>
  <link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
  <link rel="stylesheet" href="css/diskover.css" media="screen" />
  <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
  <style>
      .darken {
          color: gray !important;
      }
      .darken a {
          color: gray !important;
      }
      .darken a:hover {
          color: gray !important;
      }
      .arc text {
          font: 10px sans-serif;
          text-anchor: middle;
      }
      .arc path {
          stroke: #0B0C0E;
      }
      #diskspacechart rect {
          fill: #BD1B00;
          stroke: black;
      }
      #diskspacechart text {
          font-size: 10px;
          fill: white;
          font-weight: bold;
      }
      #diskspacechart {
          height: 22px;
          width: 400px;
          border:1px solid #000;
          background-color: #7EB26D;
      }
      #diskspacechart-indexed rect {
          fill: #DA722C;
          stroke: black;
      }
      #diskspacechart-indexed text {
          font-size: 8px;
          fill: white;
      }
      #diskspacechart-indexed {
          height: 18px;
          width: 400px;
          border:1px solid #000;
          background-color: #282C34;
          margin-bottom: 10px;
      }
      .axis {
          font: 10px sans-serif;
          fill: #ccc;
      }
      .axis path,
      .axis line {
        fill: none;
        stroke: #555;
        shape-rendering: crispEdges;
      }
      #workerchart {
          width: 700px;
          height: 350px;
          position: relative;
      }
      #workerchart rect {
          stroke: black;
          cursor: pointer;
      }
      .d3-tip {
          font-size: 11px;
          line-height: 1;
          font-weight: bold;
          padding: 12px;
          background: rgba(25, 25, 25, 0.8);
          color: #fff;
          border-radius: 2px;
          pointer-events: none;
          word-break: break-all;
          word-wrap: break-word;
      }
      .line {
          fill: none;
          stroke-width: 1px;
      }
      .tick line {
          stroke: #ccc;
          stroke-width: 1px;
      }
  </style>
</head>
<body>
<?php include "nav.php"; ?>
<div class="container-fluid" style="margin-top:70px;">
  <div class="row">
    <div class="col-xs-6">
      <?php if (!$crawlfinished) { ?>
      <div class="alert alert-dismissible alert-danger">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong><i class="glyphicon glyphicon-exclamation-sign"></i> Worker bots still building index!</strong> Some pages will not load until worker bots have finished crawling and calculating directory sizes. Check worker bots in rq or rq-dashboard. <a href="dashboard.php?<?php echo $_SERVER['QUERY_STRING']; ?>">Reload</a>.
      </div>
      <?php } ?>
      <div class="jumbotron">
        <h1><i class="glyphicon glyphicon-hdd"></i> Space Savings</h1>
        <p>You could save <span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($totalFilesizeAll); ?></span> of disk space if you delete or archive all your files. 
            <?php if (!$s3_index && !$qumulo_index) { ?>diskover found <span style="font-weight:bold;color:#D20915;"><?php echo number_format($file_recommended_delete_count) ?></span> (<span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($file_recommended_delete_size) ?></span>) <a href="advanced.php?index=<?php echo $esIndex ?>&amp;index2=<?php echo $esIndex2 ?>&amp;submitted=true&amp;p=1&amp;last_mod_time_high=now-6M&amp;last_access_time_high=now-6M&amp;doctype=file">recommended files</a> to remove.<br /><span style="font-size:12px;color:#666;"><i class="glyphicon glyphicon-info-sign"></i> Does not account for hardlinks. Recommended files is based on >6M mtime &amp; atime.</span><?php } ?></p>
        <p><span class="label label-default"><i class="glyphicon glyphicon-file" style="color:#738291;font-weight:bold;"></i> <span style="color:lightgray">Files</span> <?php echo number_format($totalfiles); ?></span> &nbsp;&nbsp; <span class="label label-default"><i class="glyphicon glyphicon-folder-close" style="color:skyblue;font-weight:bold;"></i> <span style="color:lightgray">Directories</span> <?php echo number_format($totaldirs); ?></span> &nbsp;&nbsp;
            <?php if (!$s3_index) { ?><span class="label label-default"><i class="glyphicon glyphicon-duplicate" style="color:#738291;font-weight:bold;"></i> <span style="color:lightgray">Dupes</span> <?php echo number_format($totalDupes); ?> (<?php echo formatBytes($totalFilesizeDupes); ?>)</span> &nbsp;&nbsp; <span class="label label-default"><i class="glyphicon glyphicon-link" style="color:#738291;font-weight:bold;"></i> <span style="color:lightgray">Hardlink files</span> <?php echo number_format($totalHardlinkFiles); ?> (<?php echo formatBytes($totalFilesizeHardlinkFiles); ?>)</span><?php } ?></p>
      </div>
      <div class="panel panel-default chartbox">
        <div class="panel-heading"><h3 class="panel-title" style="display:inline;"><i class="glyphicon glyphicon-dashboard"></i> Crawl Stats</h3><small>&nbsp;&nbsp;&nbsp;&nbsp;<a href="crawlstats.php?<?php echo $_SERVER['QUERY_STRING']; ?>">View more</a></small></div>
        <div class="panel-body">
            <ul class="list-group">
              <li class="list-group-item">
                <span class="badge"><?php echo $esIndex; ?></span>
                <i class="glyphicon glyphicon-list-alt"></i> Index
              </li>
              <li class="list-group-item">
                <span class="badge"><?php echo $firstcrawltime; ?> UTC</span>
                <i class="glyphicon glyphicon-calendar"></i> Started at
              </li>
              <?php if ($crawlfinished) { ?>
              <li class="list-group-item">
                <span class="badge"><?php echo $lastcrawltime; ?> UTC</span>
                <i class="glyphicon glyphicon-flag"></i> Finished at
              </li>
              <li class="list-group-item">
                <span class="badge"><?php echo secondsToTime($crawlelapsedtime); ?></span>
                <i class="glyphicon glyphicon-time"></i> Elapsed time
              </li>
              <li class="list-group-item">
                <span class="badge"><?php echo secondsToTime($crawlcumulativetime); ?></span>
                <i class="glyphicon glyphicon-time"></i> Total crawl time (cumulative)
              </li>
              <li class="list-group-item">
                <span class="badge"><?php echo secondsToTime($bulkcumulativetime); ?></span>
                <i class="glyphicon glyphicon-time"></i> Total bulk update time (cumulative)
              </li>
              <li class="list-group-item">
                <span class="badge"><?php echo number_format($crawlelapsedtime/$totalfiles*1000, 6) . ' / ' . number_format($crawlelapsedtime/$totaldirs*1000, 6); ?></span>
                <i class="glyphicon glyphicon-dashboard"></i> Elapsed time per file/directory (average ms)
              </li>
            </ul>
            <?php } else { ?>
                    <p><strong><i class="glyphicon glyphicon-tasks text-danger"></i> Crawl is still running. <a href="dashboard.php?<?php echo $_SERVER['QUERY_STRING']; ?>">Reload</a> to see updated results.</strong><small> (Last updated: <?php echo (new \DateTime())->format('Y-m-d\TH:i:s T'); ?>)</small></p>
                <?php } ?>
                <p><small><span style="color:#666"><i class="glyphicon glyphicon-info-sign"></i> Started at time is first crawl and finished at time is last crawl. Elapsed time is how long it took to crawl the tree and scrape meta. Total crawl time and bulk update time is the cumulative time for all worker bots.</span></small></p>
          </div>
        </div>
      <div class="panel panel-success chartbox">
      <div class="panel-heading">
          <h3 class="panel-title" style="display:inline"><i class="glyphicon glyphicon-tasks"></i> Crawl Worker Bot Usage</h3>&nbsp;&nbsp;&nbsp;&nbsp;<span style="display:inline"><small>Auto refresh <a href="#_self" id="autorefresh_on" onclick="autorefresh(3000);">on</a> <a href="#_self" id="autorefresh_off" onclick="autorefresh(0);">off</a></small></span>
      </div>
      <div class="panel-body">
        <div id="workerchart" class="text-center" style="display: block; margin: auto;"></div>
      </div>
    </div>
      <?php
      if ($totalDupes === 0 && $s3_index != '1') {
      ?>
      <div class="alert alert-dismissible alert-info">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <h4><i class="glyphicon glyphicon-duplicate"></i> No dupe files found.</h4>
        <p>Run diskover with the --finddupes flag after crawl finishes to check for duplicate files.</p>
      </div>
      <?php
      }
      ?>
      <?php
      if ($totalDupes > 0 && $s3_index != '1') {
      ?>
      <div class="alert alert-dismissible alert-info">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <h4><i class="glyphicon glyphicon-duplicate"></i> Duplicate files!</h4>
        <p>It looks like you have <a href="simple.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;q=dupe_md5:(NOT &quot;&quot;)&amp;doctype=file" class="alert-link">duplicate files</a>, tag the copies for deletion to save space.</p>
      </div>
      <?php
      }
      ?>
      <?php
      if ($tagCounts['untagged'] > 0) {
      ?>
      <div class="alert alert-dismissible alert-info">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <h4><i class="glyphicon glyphicon-tags"></i> Untagged files!</h4>
        <p>It looks like you have <a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=&quot;&quot;" class="alert-link">untagged files</a>, time to start tagging and free up some space.</p>
      </div>
      <?php
      }
      ?>
      <?php
      if ($tagCounts['untagged'] == 0 AND $totalFilesize['delete'] > 0 AND $totalFilesize['archive'] > 0 AND $totalFilesize['keep'] > 0 ) {
      ?>
      <div class="alert alert-dismissible alert-info">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="glyphicon glyphicon-thumbs-up"></i> <strong>Good job!</strong> It looks like all files have been tagged.
      </div>
      <?php
      }
      ?>
    </div>
    <div class="col-xs-6">
        <div class="panel panel-default">
          <?php if ($s3_index) { ?>
          <div class="panel-heading"><h3 class="panel-title" style="display:inline;"><i class="glyphicon glyphicon-cloud" style="color:#FD9827;"></i> S3 Overview</h3></div>
          <div class="panel-body">
            <p>Buckets: <span class="text-success"><strong><?php $i = 0; while ( $i < sizeof($buckets) ) { { echo '<i class="glyphicon glyphicon-cloud-upload" style="color:#FD9827;"></i> ' . $buckets[$i]['_source']['filename']; if ($i<sizeof($buckets)-1) { echo '&nbsp; '; }; $i++; } } ?></strong></span><br />
            Bucket Count: <span class="text-success"><strong><?php echo $bucketcount; ?></strong></span><br />
            diskover S3 root path: <span class="text-success"><strong><?php echo $diskspace_path; ?></strong></span><br />
            Total Buckets Size: <span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($totalFilesizeAll); ?></span></p>
          <?php } else { ?>
          <div class="panel-heading"><h3 class="panel-title" style="display:inline;"><i class="glyphicon glyphicon-eye-open"></i> Disk Space Overview</h3></div>
          <div class="panel-body">
          <p>Path: <span style class="text-success"><strong><?php echo $diskspace_path; ?></strong></span></p>
          <div id="diskspacechart"></div>
          <div id="diskspacechart-indexed"></div>
          <?php
          if ($esIndex2 != "") {
              $diskspace_used_change = number_format(changePercent($diskspace_used, $diskspace2_used), 2);
              $diskspace_free_change = number_format(changePercent($diskspace_free, $diskspace2_free), 2);
              $diskspace_available_change = number_format(changePercent($diskspace_available, $diskspace2_available), 2);
          }
          ?>
            <span class="label label-default">Total <?php echo formatBytes($diskspace_total); ?></span>&nbsp;&nbsp;
            <span class="label label-default">Used <?php echo formatBytes($diskspace_used); ?> <?php if ($esIndex2 != "") { ?><small><span style="color:gray;"><?php echo formatBytes($diskspace2_used); ?></span> <span style="color:<?php echo $diskspace_used_change > 0 ? "red" : "#29FE2F"; ?>;">(<?php echo $diskspace_used_change >= 0 ? '<i class="glyphicon glyphicon-chevron-up"></i> +' : '<i class="glyphicon glyphicon-chevron-down"></i>'; ?><?php echo $diskspace_used_change;  ?>%)</span></small></span><?php } ?></span>&nbsp;&nbsp;
            <span class="label label-default">Free <?php echo formatBytes($diskspace_free); ?><span> <?php if ($esIndex2 != "") { ?><small><span style="color:gray;"><?php echo formatBytes($diskspace2_free); ?></span> <span style="color:<?php echo $diskspace_free_change >= 0 ? "#29FE2F" : "red"; ?>;">(<?php echo $diskspace_free_change >= 0 ? '<i class="glyphicon glyphicon-chevron-up"></i> +' : '<i class="glyphicon glyphicon-chevron-down"></i>'; ?><?php echo $diskspace_free_change; ?>%)</span></small><?php } ?></span></span>&nbsp;&nbsp;
            <span class="label label-default">Available <?php echo formatBytes($diskspace_available); ?><span> <?php if ($esIndex2 != "") { ?><small><span style="color:gray;"><?php echo formatBytes($diskspace2_available); ?></span> <span style="color:<?php echo $diskspace_available_change >= 0 ? "#29FE2F" : "red"; ?>;">(<?php echo $diskspace_available_change > 0 ? '<i class="glyphicon glyphicon-chevron-up"></i> +' : '<i class="glyphicon glyphicon-chevron-down"></i>'; ?><?php echo $diskspace_available_change; ?>%)</span></small><?php } ?></span></span>
            <?php if ($diskspace_total > 0) { if ((($diskspace_used / $diskspace_total) * 100) >= 80 && (($diskspace_used / $diskspace_total) * 100) < 90) { ?>
            <br /><span class="label label-warning"><i class="glyphicon glyphicon-warning-sign"></i> Used disk space is above 80%</span>
            <?php } else if ((($diskspace_used / $diskspace_total) * 100) >= 90) { ?>
            <br /><span class="label label-danger"><i class="glyphicon glyphicon-warning-sign"></i> Used disk space is above 90%</span>
            <?php } } else { echo "<p class=\"text-warning\">No data in Elasticsearch index... try again later...</p>"; } ?>
        <?php } ?>
        </div>
        </div>
        <div class="row">
      <div class="col-xs-6">
        <div class="panel panel-default chartbox">
        <div class="panel-heading">
            <h3 class="panel-title" style="display:inline;"><i class="glyphicon glyphicon-tag"></i> Tag Counts</h3><small>&nbsp;&nbsp;&nbsp;&nbsp;<a href="tags.php?<?php echo $_SERVER['QUERY_STRING']; ?>">View all</a></small>
        </div>
        <div class="panel-body">
            <div id="tagcountchart" class="text-center"></div>
            <div class="chartbox">
              <span class="label" style="background-color:#666666;"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=">untagged <?php echo $tagCounts['untagged']; ?></a></span>
              <span class="label" style="background-color:#F69327"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=delete">delete <?php echo $tagCounts['delete']; ?></a></span>
              <span class="label" style="background-color:#65C165"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=archive">archive <?php echo $tagCounts['archive']; ?></a></span>
              <span class="label" style="background-color:#52A3BB"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=keep">keep <?php echo $tagCounts['keep']; ?></a></span>
          </div>
        </div>
        </div>
      </div>
        <div class="col-xs-6">
            <div class="panel panel-default chartbox">
            <div class="panel-heading">
                <h3 class="panel-title" style="display:inline;"><i class="glyphicon glyphicon-hdd"></i> Total File Sizes</h3><small>&nbsp;&nbsp;&nbsp;&nbsp;<a href="tags.php?<?php echo $_SERVER['QUERY_STRING']; ?>">View all</a></small>
            </div>
        <div class="panel-body">
            <div id="filesizechart" class="text-center"></div>
            <div class="chartbox">
              <span class="label" style="background-color:#666666;"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=">untagged <?php echo formatBytes($totalFilesize['untagged']); ?></a></span>
              <span class="label" style="background-color:#F69327"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=delete">delete <?php echo formatBytes($totalFilesize['delete']); ?></a></span>
              <span class="label" style="background-color:#65C165"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=archive">archive <?php echo formatBytes($totalFilesize['archive']); ?></a></span>
              <span class="label" style="background-color:#52A3BB"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=keep">keep <?php echo formatBytes($totalFilesize['keep']); ?></a></span>
          </div>
        </div>
        </div>
    </div>
    </div>
        <div class="panel panel-info chartbox">
            <div class="panel-heading">
                <h3 style="display: inline;" class="panel-title"><i class="glyphicon glyphicon-scale"></i> Top 10 Largest Files</h3><small>&nbsp;&nbsp;&nbsp;&nbsp;<a href="top50.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;path=<?php echo $diskspace_path; ?>">Top 50</a></small>
            </div>
            <div class="panel-body">
            <table class="table table-striped table-hover table-condensed" style="font-size:12px;">
              <thead>
                <tr>
                  <th class="text-nowrap">#</th>
                  <th class="text-nowrap">Name</th>
                  <th class="text-nowrap">File Size</th>
                  <th class="text-nowrap">Modified (utc)</th>
                  <th class="text-nowrap">Path</th>
              </tr>
            </thead>
            <tbody>
                  <?php
                  // Get search results from Elasticsearch for top 10 largest files
                  $results = [];
                  $searchParams = [];

                  // Setup search query
                  $searchParams['index'] = $esIndex;
                  $searchParams['type']  = 'file';


                  // Setup search query for largest files
                  $searchParams['body'] = [
                      'size' => 10,
                      '_source' => ['filename', 'path_parent', 'filesize', 'last_modified'],
                      'query' => [
                          'match_all' => (object) []
                      ],
                      'sort' => [
                          'filesize' => [
                              'order' => 'desc'
                          ]
                      ]
                  ];
                  $queryResponse = $client->search($searchParams);

                  $largestfiles = $queryResponse['hits']['hits'];
                  $n = 1;
                  foreach ($largestfiles as $key => $value) {
                    ?>
                    <tr><td class="darken"><?php echo $n; ?></td>
                        <td class="path"><a href="view.php?id=<?php echo $value['_id'] . '&amp;index=' . $value['_index'] . '&amp;doctype=file'; ?>"><i class="glyphicon glyphicon-file" style="color:#738291;font-size:13px;padding-right:3px;"></i> <?php echo $value['_source']['filename']; ?></a></td>
                        <td class="text-nowrap darken"><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value['_source']['filesize']); ?></span></td>
                        <td class="text-nowrap darken"><?php echo $value['_source']['last_modified']; ?></td>
                        <td class="path darken"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;path_parent=<?php echo $value['_source']['path_parent']; ?>&amp;doctype=file"><?php echo $value['_source']['path_parent']; ?></a></td>
                    </tr>
                  <?php $n++; }
                   ?>
               </tbody>
          </table>
        </div>
        </div>
        <div class="panel panel-info chartbox">
            <div class="panel-heading">
                <h3 style="display: inline;" class="panel-title"><i class="glyphicon glyphicon-scale"></i> Top 10 Largest Directories</h3><small>&nbsp;&nbsp;&nbsp;&nbsp;<a href="top50.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;path=<?php echo $diskspace_path; ?>">Top 50</a></small>
            </div>
            <div class="panel-body">
            <table class="table table-striped table-hover table-condensed" style="font-size:12px;">
              <thead>
                <tr>
                  <th class="text-nowrap">#</th>
                  <th class="text-nowrap">Name</th>
                  <th class="text-nowrap">File Size</th>
                  <th class="text-nowrap">Modified (utc)</th>
                  <th class="text-nowrap">Path</th>
              </tr>
            </thead>
            <tbody>
                  <?php
                  // Get search results from Elasticsearch for top 10 largest directories
                  $results = [];
                  $searchParams = [];

                  // Setup search query
                  $searchParams['index'] = $esIndex;
                  $searchParams['type']  = 'directory';

                  // Setup search query for largest files
                  $searchParams['body'] = [
                      'size' => 10,
                      '_source' => ['filename', 'path_parent', 'filesize', 'last_modified'],
                      'query' => [
                        'bool' => [
                            'must' => [
                                    'wildcard' => [ 'path_parent' => $diskspace_path . '*' ]
                            ],
                            'must_not' => [
                                    'match' => [ 'path_parent' => "/" ],
                                    'match' => [ 'filename' => ""]
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

                  $largestdirs = $queryResponse['hits']['hits'];
                  $n = 1;
                  foreach ($largestdirs as $key => $value) {
                    ?>
                    <tr><td class="darken"><?php echo $n; ?></td>
                        <td class="path"><a href="view.php?id=<?php echo $value['_id'] . '&amp;index=' . $value['_index'] . '&amp;doctype=directory'; ?>"><i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;font-size:13px;padding-right:3px;"></i> <?php if ($value['_source']['filename'] === '' && $value['_source']['path_parent'] === '/') { echo '/'; } else { echo $value['_source']['filename']; } ?></a></td>
                        <td class="text-nowrap darken"><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value['_source']['filesize']); ?></span></td>
                        <td class="text-nowrap darken"><?php echo $value['_source']['last_modified']; ?></td>
                        <td class="path darken"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;path_parent=<?php echo $value['_source']['path_parent']; ?>"><?php echo $value['_source']['path_parent']; ?></a></td>
                    </tr>
                  <?php $n++; }
                   ?>
               </tbody>
          </table>
        </div>
        </div>
      </div>
  </div>
</div>
<script language="javascript" src="js/jquery.min.js"></script>
<script language="javascript" src="js/bootstrap.min.js"></script>
<script language="javascript" src="js/diskover.js"></script>
<script language="javascript" src="js/d3.v3.min.js"></script>
<script language="javascript" src="js/spin.min.js"></script>
<script language="javascript" src="js/d3.tip.v0.6.3.js"></script>
<!-- d3 charts -->
<script>
    var count_untagged = <?php echo $tagCounts['untagged'] ?>;
    var count_delete = <?php echo $tagCounts['delete'] ?>;
    var count_archive = <?php echo $tagCounts['archive'] ?>;
    var count_keep = <?php echo $tagCounts['keep'] ?>;

    var dataset = [{
        label: 'untagged',
        count: count_untagged
    }, {
        label: 'delete',
        count: count_delete
    }, {
        label: 'archive',
        count: count_archive
    }, {
        label: 'keep',
        count: count_keep
    }];

    var width = 200;
    var height = 200;
    var radius = Math.min(width, height) / 2;

    var color = d3.scale.ordinal()
        .range(["#666666", "#F69327", "#65C165", "#52A3BB"]);

    var svg = d3.select("#tagcountchart")
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .append('g')
        .attr('transform', 'translate(' + width / 2 + ',' + height / 2 + ')');

    var pie = d3.layout.pie()
        .value(function(d) {
            return d.count;
        })
        .sort(null);

    var path = d3.svg.arc()
        .outerRadius(radius - 10)
        .innerRadius(40);

    var label = d3.svg.arc()
        .outerRadius(radius - 40)
        .innerRadius(radius - 40);

    var arc = svg.selectAll('.arc')
        .data(pie(dataset))
        .enter().append('g')
        .attr('class', 'arc');

    arc.append('path')
        .attr('d', path)
        .attr('fill', function(d) {
            return color(d.data.label);
        });

    arc.append('text')
        .attr("transform", function(d) {
            return "translate(" + label.centroid(d) + ")";
        })
        .attr("dy", "0.35em")
        .text(function(d) {
            return d.data.label;
        });
</script>

<script>
    var size_untagged = <?php echo $totalFilesize['untagged'] ?>;
    var size_delete = <?php echo $totalFilesize['delete'] ?>;
    var size_archive = <?php echo $totalFilesize['archive'] ?>;
    var size_keep = <?php echo $totalFilesize['keep'] ?>;

    var dataset = [{
        label: 'untagged',
        size: size_untagged
    }, {
        label: 'delete',
        size: size_delete
    }, {
        label: 'archive',
        size: size_archive
    }, {
        label: 'keep',
        size: size_keep
    }];

    var width = 200;
    var height = 200;
    var radius = Math.min(width, height) / 2;

    var color = d3.scale.ordinal()
        //.range(["#98abc5", "#8a89a6", "#7b6888", "#6b486b", "#a05d56", "#d0743c", "#ff8c00"]);
    .range(["#666666", "#F69327", "#65C165", "#52A3BB"]);

    var svg = d3.select("#filesizechart")
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .append('g')
        .attr('transform', 'translate(' + width / 2 + ',' + height / 2 + ')');

    var pie = d3.layout.pie()
        .value(function(d) {
            return d.size;
        })
        .sort(null);

    var path = d3.svg.arc()
        .outerRadius(radius - 10)
        .innerRadius(40);

    var label = d3.svg.arc()
        .outerRadius(radius - 40)
        .innerRadius(radius - 40);

    var arc = svg.selectAll('.arc')
        .data(pie(dataset))
        .enter().append('g')
        .attr('class', 'arc');

    arc.append('path')
        .attr('d', path)
        .attr('fill', function(d) {
            return color(d.data.label);
        });

    arc.append('text')
        .attr("transform", function(d) {
            return "translate(" + label.centroid(d) + ")";
        })
        .attr("dy", "0.35em")
        .text(function(d) {
            return d.data.label;
        });
</script>
<script>
    var size_total = <?php echo $diskspace_total; ?>;
    var size_used = <?php echo $diskspace_used; ?>;
    var size_free = <?php echo $diskspace_free; ?>;
    var size_available = <?php echo $diskspace_available; ?>;

    var height = 20,
        maxBarWidth = 400;

    var svg = d3.select("#diskspacechart")
        .append('svg')
        .attr('width', maxBarWidth)
        .attr('height', height)
        .append('g');

    var bar = svg.selectAll('.bar')
        .data([size_used])
        .enter().append('g')
        .attr('class', 'bar');

    bar.append('rect')
        .attr('height', height)
        .attr('class', 'bar')
        .attr('width', function(d) {
            percent = parseInt(d / size_total * 100) + "%";
            return percent;
        });

    var label = svg.selectAll(".label")
        .data([size_used])
        .enter()
        .append('text')
        .attr('transform', 'translate(' + maxBarWidth / 2 + ',0)')
        .attr("dy", "1.35em")
        .attr('class', 'label')
        .attr('text-anchor', 'middle')
        .text(function(d) {
            percent = d3.round(d / size_total * 100, 2) + "%";
            return percent + ' used';
        });

</script>
<script>
    var size_used = <?php echo $diskspace_used; ?>;
    var size_indexed = <?php echo $totalFilesizeAll; ?>;

    var height = 16,
        maxBarWidth = 400;

    var svg = d3.select("#diskspacechart-indexed")
        .append('svg')
        .attr('width', maxBarWidth)
        .attr('height', height)
        .append('g');

    var bar = svg.selectAll('.bar')
        .data([size_indexed])
        .enter().append('g')
        .attr('class', 'bar');

    bar.append('rect')
        .attr('height', height)
        .attr('class', 'bar')
        .attr('width', function(d) {
            percent = parseInt(d / size_used * 100);
            if (percent > 100) {
                percent = 100;
            }
            return percent + "%";
        });

    var label = svg.selectAll(".label")
        .data([size_indexed])
        .enter()
        .append('text')
        .attr('transform', 'translate(' + maxBarWidth / 2 + ',0)')
        .attr("dy", "1.3em")
        .attr('class', 'label')
        .attr('text-anchor', 'middle')
        .text(function(d) {
            percent = d3.round(d / size_used * 100, 2);
            if (percent > 100) {
                percent = 100;
            }
            return percent + '% indexed';
        });

</script>

<script>
    var data;
    var indexname = '<?php echo $esIndex; ?>';

    // init worker chart
    var margin = {top: 20, right: 20, bottom: 120, left: 70},
    width = 700 - margin.left - margin.right,
    height = 350 - margin.top - margin.bottom;

    var svg = d3.select("#workerchart").append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
      .append("g")
        .attr("transform",
              "translate(" + margin.left + "," + margin.top + ")");

    function getjsondata(refreshcharts) {
        // config references
        var chartConfig = {
            target: 'workerchart',
            data_url: 'd3_data_workers.php?index=' + indexname
        };

        // loader settings
        var opts = {
            lines: 12, // The number of lines to draw
            length: 5, // The length of each line
            width: 3, // The line thickness
            radius: 6, // The radius of the inner circle
            color: '#EE3124', // #rgb or #rrggbb or array of colors
            speed: 1.9, // Rounds per second
            trail: 40, // Afterglow percentage
            className: 'spinner', // The CSS class to assign to the spinner
        };

        // loader settings
        var target = document.getElementById(chartConfig.target);
        if (refreshcharts === false) {
            // trigger loader
            var spinner = new Spinner(opts).spin(target);
        }

        // load json data from Elasticsearch
        d3.json(chartConfig.data_url, function(error, dataset) {

            // update global data vars
            data = dataset;

            if (refreshcharts === false) {
                // stop spin.js loader
                spinner.stop();
            } else {
                svg.selectAll("*").remove();
            }
            // load chart
            loadworkerchart()
        });
    }

    function loadworkerchart() {

        var xData = ['file', 'directory'];

        var x = d3.scale.ordinal()
            .rangeRoundBands([0, width], .35);

        var y = d3.scale.linear()
            .rangeRound([height, 0]);

        var color = d3.scale.category20b();

        var xAxis = d3.svg.axis()
            .scale(x)
            .orient("bottom");

        var yAxis = d3.svg.axis()
            .scale(y)
            .orient("left")
            .ticks(10);

        var dataIntermediate=xData.map(function (c){
            return data.map(function(d) {
                return {x: d.worker_name, y: d[c]};
            });
        });

        var dataStackLayout = d3.layout.stack()(dataIntermediate);

        x.domain(dataStackLayout[0].map(function(d) {
            return d.x;
        }));

        y.domain([0,
            d3.max(dataStackLayout[dataStackLayout.length - 1],
                function (d) { return d.y0 + d.y;})
            ])
            .nice();

        var layer = svg.selectAll(".stack")
            .data(dataStackLayout)
            .enter().append("g")
            .attr("class", "stack")
            .style("fill", function (d, i) {
                return color(i);
            });

        layer.selectAll("rect")
            .data(function (d) {
                return d;
            })
            .enter().append("rect")
            .attr("x", function (d) {
                return x(d.x);
            })
            .attr("y", function (d) {
                return y(d.y + d.y0);
            })
            .attr("height", function (d) {
                return y(d.y0) - y(d.y + d.y0);
            })
            .attr("width", x.rangeBand())
            .on("mouseover", function(d) {
                tip.show(d);
            })
            .on("mouseout", function(d) {
                tip.hide(d);
            })
            .on('mousemove', function() {
                return tip
                  .style("top", (d3.event.pageY - 10) + "px")
                  .style("left", (d3.event.pageX + 10) + "px");
            })
            .on('click', function(d) {
                var t = (d.y0===0) ? "file" : "directory";
                window.open('simple.php?submitted=true&p=1&q=worker_name:' + d.x + '&doctype=' + t,'_blank');
            });

        svg.append("g")
            .attr("class", "axis")
            .attr("transform", "translate(0," + height + ")")
            .call(xAxis)
            .selectAll("text")
              .attr("transform", "rotate(-45)")
                .attr("y", 10)
                .attr("x", 0)
                .attr("dx", "-.8em")
                .attr("dy", ".15em")
                .style("text-anchor", "end");

        svg.append("g")
            .attr("class", "axis")
            .attr("transform", "translate(0,0)")
            .call(yAxis)
            .append("text")
              .attr("transform", "rotate(-90)")
              .attr("y", 0)
              .attr("x", 0)
              .attr("dy", ".71em")
              .style("text-anchor", "end")
              .text("Queue items");

        // tooltips
        var tip = d3.tip()
            .attr('class', 'd3-tip')
            .html(function(d) {
              var t = (d.y0===0) ? "files" : "dirs"
                return "<span style='font-size:12px;color:white;'>" + d.x + "</span><br>\
                <span style='font-size:12px; color:red;'>"+t+": " + d.y + "</span><br>";
            });

        svg.call(tip);

        d3.select("#workerchart").append("div")
            .attr("class", "tooltip")
            .style("opacity", 0);
    }

    var crawlfinished = '<?php echo $crawlfinished ? "true" : "false"; ?>';
    // get json data for workers chart and load vis
    getjsondata(false);
    // auto refresh the workers chart
    var auto_refresh;
    if (crawlfinished === 'false') {
        autorefresh(3000);
    } else {  // crewl is finished so disable interval
        autorefresh(0);
    }
    function autorefresh(worker_refreshtime) {
        if (worker_refreshtime == 0) {
            clearInterval(auto_refresh);
            $('#autorefresh_off').attr('style', 'color: #000 !important');
            $('#autorefresh_on').attr('style', 'color: #FFF !important');
        } else {
            auto_refresh = setInterval(
                function () {
                    // reload data for workers chart
                    getjsondata(true);
                }, worker_refreshtime); // refresh every 3 sec
                $('#autorefresh_on').attr('style', 'color: #000 !important');
                $('#autorefresh_off').attr('style', 'color: #FFF !important');
        }
    };
</script>
<hr>
<p style="text-align:center; font-size:11px; color:#555;">
<?php
$time = number_format(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 6);
echo "ES Process Time: {$estime}, Process Time: {$time}";
?>
</p>

<div id="statsModal" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Send anonymous stats to the diskover developer</h4>
      </div>
      <div class="modal-body">
        <p>Allow usage statistics to be sent to the diskover developer to help improve the product. You can change this later from admin page.</p>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="sendstats" onclick="sendStats();">
            <label class="form-check-label" for="sendstats">Allow limited anonymous usage stats</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>

<div id="supportModal" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Support the development of diskover</h4>
      </div>
      <div class="modal-body">
        <p><i class="glyphicon glyphicon-bullhorn"></i> Welcome to diskover-web! If you are using diskover in a commercial environment or just want to help advance the software, please become a patron on <a target="_blank" href="https://www.patreon.com/diskover">Patreon</a> or donate on <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CLF223XAS4W72" target="_blank">PayPal</a>. <span style="color:#D01020;"><i class="glyphicon glyphicon-heart-empty"></i></span></p>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="sponsoring" onclick="sponsoring();">
            <label class="form-check-label" for="sponsor">I'm already supporting</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>

<script>
// set cookie for sending anonymous stats
function sendStats() {
    if (document.getElementById('sendstats').checked) {
        setCookie('sendstats', 1);
    } else {
        setCookie('sendstats', 0);
    }
}
// set cookie for sponsoring
function sponsoring() {
    if (document.getElementById('sponsoring').checked) {
        setCookie('sponsoring', 1, 365);
    } else {
        setCookie('sponsoring', 0);
    }
}
$(window).on('load',function(){
    if (getCookie('sendstats') == '') {
      setCookie('sendstats', 1);
      document.getElementById('sendstats').checked = true;
      $('#statsModal').modal('show');
    }
    if (getCookie('support') == '' && getCookie('sponsoring') != 1) {
      setCookie('support', 1, 7);
      $('#supportModal').modal('show');
    }
});
</script>

<?php require "logform.php"; ?>

</body>
</html>
