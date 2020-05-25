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

// get mtime in ES format
$mtime = getmtime($mtime);

// escape characters in path
$path_escaped = escape_chars($path);

// get top 50 type
if (!isset($_REQUEST['top50type'])) {
    $top50type = 'Largest';
} else {
    $top50type = $_REQUEST['top50type'];
}

// determine sort order
if ($_REQUEST['top50type'] == 'Oldest') {
    $order = 'asc';
} else {
    $order = 'desc';
}

if ($top50type == 'Largest' || $top50type == 'Oldest' || $top50type == 'Newest') {
    // determine sort type
    if ($top50type == 'Largest') {
        $sorttype = 'filesize';
    } else {
        $sorttype = 'last_modified';
    }

    // get top 50 directories
    $totaldirsize = 0;
    $totaldircount = 0;

    $results = [];
    $searchParams = [];

    // Setup search query
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = 'directory';


    // Setup search query for directories
    $searchParams['body'] = [
        'size' => 50,
        '_source' => ['filename', 'path_parent', 'filesize', 'items', 'last_modified'],
        'query' => [
            'bool' => [
                'must' => [
                        'wildcard' => [ 'path_parent' => $path_escaped . '*' ]
                ],
                'must_not' => [
                        'match' => [ 'path_parent' => "/" ],
                        'match' => [ 'filename' => ""]
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
            $sorttype => [
                'order' => $order
            ]
        ]
    ];
    $queryResponse = $client->search($searchParams);

    $topdirs = $queryResponse['hits']['hits'];

    foreach ($topdirs as $key => $value) {
        $totaldirsize += $value['_source']['filesize'];
        $totaldircount += $value['_source']['items'];
    }

    // Get search results from Elasticsearch for top 50 files
    $results = [];
    $searchParams = [];

    // Setup search query
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = 'file';

    // Setup search query for files
    $searchParams['body'] = [
        'size' => 50,
        '_source' => ['filename', 'path_parent', 'filesize', 'last_modified'],
        'query' => [
            'bool' => [
                'must' => [
                        'wildcard' => [ 'path_parent' => $path_escaped . '*' ]
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
            $sorttype => [
                'order' => $order
            ]
        ]
    ];
    $queryResponse = $client->search($searchParams);

    $topfiles = $queryResponse['hits']['hits'];

    // calculate total file size
    $totalfilesize = 0;
    foreach ($topfiles as $key => $value) {
        $totalfilesize += $value['_source']['filesize'];
    }
} elseif ($top50type == 'Users' || $top50type == 'Groups') {

    if ($top50type == 'Users') {
        $sortby = 'owner';
    } elseif ($top50type == 'Groups') {
        $sortby = 'group';
    }

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
                        'wildcard' => [ 'path_parent' => $path_escaped . '*' ]
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
                'top_consumers' => [
                    'terms' => [
                        'field' => $sortby,
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

    $results = $queryResponse['aggregations']['top_consumers']['buckets'];

    foreach ($results as $result) {
        $topconsumers[] = [
                    "name" => $result['key'],
                    "filecount" => $result['doc_count'],
                    "filesize" => $result['file_size']['value']
                    ];
    }

    // calculate total file size and count
    $totalusersize = 0;
    $totalusercount = 0;
    foreach ($topconsumers as $key => $value) {
        $totalusersize += $value['filesize'];
        $totalusercount += $value['filecount'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-148814293-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'UA-148814293-1');
  </script>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>diskover &mdash; Top 50</title>
	<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
  <link rel="stylesheet" href="css/diskover.css" media="screen" />
  <link rel="stylesheet" href="css/diskover-top50.css" media="screen" />
  <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
</head>
<body>
<?php include "nav.php"; ?>
<div class="container-fluid" style="margin-top:70px;">
  <div class="row">
    <div class="col-xs-12">
        <div id="top50files">
            <div class="row">
    			<div class="col-xs-12">
                    <h2 style="display: inline;"><i class="glyphicon glyphicon-scale"></i> Top 50 <?php echo $top50type; if ($top50type == 'Largest' || $top50type == 'Oldest' || $top50type == 'Newest') { ?> Files</h2>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="top50Switch('directory');">Switch to directories</a>&nbsp;&nbsp;&nbsp;&nbsp; <?php } ?>
                    <div class="btn-group">
                        <button class="btn btn-default button-largest <?php if ($top50type == 'Largest') { echo "active"; } ?>"> Largest</button>
                        <button class="btn btn-default button-oldest <?php if ($top50type == 'Oldest') { echo "active"; } ?>"> Oldest</button>
                        <button class="btn btn-default button-newest <?php if ($top50type == 'Newest') { echo "active"; } ?>"> Newest</button>
                        <button class="btn btn-default button-users <?php if ($top50type == 'Users') { echo "active"; } ?>"> Users</button>
                        <button class="btn btn-default button-groups <?php if ($top50type == 'Groups') { echo "active"; } ?>"> Groups</button>
                    </div>
                    <span style="font-size:10px; color:gray;"><i class="glyphicon glyphicon-info-sign"></i> filters on filetree page affect this page</span>
                    <br />
                    <h5 style="display: inline;"><span class="text-success bold"><?php echo stripslashes($path); ?></span></h5>
                    <span><a title="<?php echo getParentDir($path); ?>" class="btn btn-primary btn-sm" onclick="window.location.href='<?php echo build_url('path', getParentDir($path)); ?>';"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</a></span>
                </div>
            </div><br />
            <?php if ($top50type == 'Largest' || $top50type == 'Oldest' || $top50type == 'Newest') { ?>
            <table class="table table-striped table-hover table-condensed" style="font-size:12px;">
              <thead>
                <tr>
                  <th class="text-nowrap">#</th>
                  <th class="text-nowrap">Name</th>
                  <th class="text-nowrap">File Size</th>
                  <th>%  <span style="color:darkgray;font-size: 11px;"><i title="Percentage of total file size this page" class="glyphicon glyphicon-question-sign"></i></span></th>
                  <th class="text-nowrap">Modified (utc)</th>
                  <th class="text-nowrap">Path</th>
              </tr>
            </thead>
            <tbody>
                  <?php
                  $n = 1;
                  foreach ($topfiles as $key => $value) {
                    ?>
                    <tr><td class="darken" width="10"><?php echo $n; ?></td>
                        <td class="path"><a href="view.php?id=<?php echo $value['_id'] . '&amp;index=' . $value['_index'] . '&amp;doctype=file'; ?>"><i class="glyphicon glyphicon-file" style="color:#738291;font-size:13px;padding-right:3px;"></i> <?php echo $value['_source']['filename']; ?></a></td>
                        <td class="text-nowrap"><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value['_source']['filesize']); ?></span></td>
                        <td width="15%"><div class="percent" style="width:<?php echo number_format(($value['_source']['filesize'] / $totalfilesize) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['_source']['filesize'] / $totalfilesize) * 100, 2); ?>%</small></span></td>
                        <td class="text-nowrap darken"><?php echo $value['_source']['last_modified']; ?></td>
                        <td class="path darken"><a href="<?php echo build_url('path', $value['_source']['path_parent']); ?>"><?php echo $value['_source']['path_parent']; ?></a></td>
                    </tr>
                  <?php $n++; }
                   ?>
               </tbody>
          </table>
        </div>
        <div id="top50dirs" style="display:none;">
            <div class="row">
    			<div class="col-xs-12">
                  <h2 style="display: inline;"><i class="glyphicon glyphicon-scale"></i> Top 50 <?php echo $top50type; if ($top50type == 'Largest' || $top50type == 'Oldest' || $top50type == 'Newest') { ?> Directories</h2>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="top50Switch('file');">Switch to files</a>&nbsp;&nbsp;&nbsp;&nbsp; <?php } ?>
                    <div class="btn-group">
                        <button class="btn btn-default button-largest <?php if ($top50type == 'Largest') { echo "active"; } ?>"> Largest</button>
                        <button class="btn btn-default button-oldest <?php if ($top50type == 'Oldest') { echo "active"; } ?>"> Oldest</button>
                        <button class="btn btn-default button-newest <?php if ($top50type == 'Newest') { echo "active"; } ?>"> Newest</button>
                        <button class="btn btn-default button-users <?php if ($top50type == 'Users') { echo "active"; } ?>"> Users</button>
                        <button class="btn btn-default button-groups <?php if ($top50type == 'Groups') { echo "active"; } ?>"> Groups</button>
                    </div>
                    <br />
                    <h5 style="display: inline;"><span class="text-success bold"><?php echo stripslashes($path); ?></span></h5>
                    <span><a title="<?php echo getParentDir($path); ?>" class="btn btn-primary btn-sm" onclick="window.location.href='<?php echo build_url('path', getParentDir($path)); ?>';"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</a></span>
                </div>
    		</div><br />
            <?php if (count($topdirs) > 0) { ?>
            <table class="table table-striped table-hover table-condensed" style="font-size:12px;">
              <thead>
                <tr>
                  <th class="text-nowrap">#</th>
                  <th class="text-nowrap">Name</th>
                  <th class="text-nowrap">Size</th>
                  <th>%  <span style="color:darkgray;font-size: 11px;"><i title="Percentage of total file size this page" class="glyphicon glyphicon-question-sign"></i></span></th>
                  <th class="text-nowrap">Items</th>
                  <th>%</th>
                  <th class="text-nowrap">Modified (utc)</th>
                  <th class="text-nowrap">Path</th>
              </tr>
            </thead>
            <tbody>
                  <?php
                      $n = 1;
                      foreach ($topdirs as $key => $value) {
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
                            <td class="path"><a href="<?php echo build_url('path', $fullpath); ?>&amp;doctype=directory"><i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;font-size:13px;padding-right:3px;"></i> <?php echo $filename; ?></a></td>
                            <td class="text-nowrap"><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value['_source']['filesize']); ?></span></td>
                            <td width="15%"><div class="text-right percent" style="max-width:100%; width:<?php echo number_format(($value['_source']['filesize'] / $totaldirsize) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['_source']['filesize'] / $totaldirsize) * 100, 2); ?>%</small></span></td>
                            <td class="text-nowrap"><?php echo $value['_source']['items']; ?></td>
                            <td width="15%"><div class="text-right percent" style="max-width:100%; width:<?php echo number_format(($value['_source']['items'] / $totaldircount) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['_source']['items'] / $totaldircount) * 100, 2); ?>%</small></span></td>
                            <td class="text-nowrap darken"><?php echo $value['_source']['last_modified']; ?></td>
                            <td class="path darken"><a href="<?php echo build_url('path', $value['_source']['path_parent']); ?>"><?php echo $value['_source']['path_parent']; ?></a></td>
                        </tr>
                    <?php $n++; } ?>
               </tbody>
          </table>
      <?php } else { ?>
          <div class="col-xs-6">
          <div class="alert alert-dismissible alert-info">
              <button type="button" class="close" data-dismiss="alert">&times;</button><i class="glyphicon glyphicon-info-sign"></i> No directories found. Try switching to files.
          </div>
        </div>
      <?php } ?>
      <?php } elseif ($top50type == 'Users' || $top50type == 'Groups') { ?>
          <table class="table table-striped table-hover table-condensed" style="font-size:12px;">
              <thead>
                <tr>
                  <th class="text-nowrap">#</th>
                  <th class="text-nowrap"><?php if ($top50type == 'Users') { echo 'Owner'; } else { echo 'Group'; } ?></th>
                  <th class="text-nowrap">Size</th>
                  <th>%</th>
                  <th class="text-nowrap">Items (files)</th>
                  <th>%</th>
              </tr>
            </thead>
            <tbody>
                  <?php
                  $n = 1;
                  foreach ($topconsumers as $key => $value) {
                    ?>
                    <tr><td class="darken" width="10"><?php echo $n; ?></td>
                        <td><i class="glyphicon glyphicon-user" style="color:#D19866; font-size:13px; padding-right:3px;"></i> <a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&<?php if ($top50type == "Users") { echo 'owner'; } else { echo 'group'; } ?>=<?php echo $value['name']; ?>"><?php echo $value['name']; ?></a></td>
                        <td><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value['filesize']); ?></span></td>
                        <td width="15%"><div class="percent" style="width:<?php echo number_format(($value['filesize'] / $totalusersize) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['filesize'] / $totalusersize) * 100, 2); ?>%</small></span></td>
                        <td><?php echo $value['filecount']; ?></td>
                        <td width="15%"><div class="percent" style="width:<?php echo number_format(($value['filecount'] / $totalusercount) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['filecount'] / $totalusercount) * 100, 2); ?>%</small></span></td>
                    </tr>
                  <?php $n++; }
                   ?>
               </tbody>
          </table>
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
