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


$show_change_percent = showChangePercent($client, $esIndex, $esIndex2);

if ($show_change_percent) {
    // get sort
    $sort = isset($_GET['sort']) ? $_GET['sort'] : "change_percent_filesize";
    $sortorder = isset($_GET['sortorder']) ? $_GET['sortorder'] : "desc";

    // get show new dirs 
    $show_new_dirs = isset($_GET['show_new_dirs']) ? (int)$_GET['show_new_dirs'] : getCookie('show_new_dirs');
    ($show_new_dirs === "") ? $show_new_dirs = 1 : createCookie('show_new_dirs', $show_new_dirs);

    // get min change percent
    $min_change_percent = isset($_GET['min_change_percent']) ? (float)$_GET['min_change_percent'] : getCookie('min_change_percent');
    ($min_change_percent === "") ? $min_change_percent = 1.0 : createCookie('min_change_percent', $min_change_percent);

    // get mtime in ES format
    $mtime = getmtime($mtime);

    // get top 50 hot directories (most changed)
    $totaldirsize = 0;
    $totaldircount = 0;

    $results = [];
    $searchParams = [];

    // Setup search query
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = 'directory';


    if ($sortorder == 'desc') {
      // Setup search query for most changed directories
      $searchParams['body'] = [
          'size' => 50,
          '_source' => ['filename', 'path_parent', 'filesize', 'change_percent_filesize', 'items', 'items_files', 'items_subdirs',
          'change_percent_items', 'change_percent_items_files', 'change_percent_items_subdirs', 'last_modified'],
          'query' => [
              'bool' => [
                  'must' => [
                          'wildcard' => [ 'path_parent' => $path . '*' ]
                  ],
                  'filter' => [
                      'bool' => [
                        'must' => [
                          [ 'range' => [
                                $sort => [
                                    'gte' => $min_change_percent
                                ]
                          ] ],
                          [ 'range' => [
                                'filesize' => [
                                      'gte' => $filter
                                ]
                          ]

                      ] ],
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
              $sort => [
                  'order' => $sortorder
              ]
          ]
      ];
    } else {
      $searchParams['body'] = [
          'size' => 50,
          '_source' => ['filename', 'path_parent', 'filesize', 'change_percent_filesize', 'items', 'items_files', 'items_subdirs',
          'change_percent_items', 'change_percent_items_files', 'change_percent_items_subdirs', 'last_modified'],
          'query' => [
              'bool' => [
                  'must' => [
                          'wildcard' => [ 'path_parent' => $path . '*' ]
                  ],
                  'filter' => [
                      'bool' => [
                        'must' => [
                          [ 'range' => [
                                $sort => [
                                    'lte' => $min_change_percent * -1
                                ]
                          ] ],
                          [ 'range' => [
                                'filesize' => [
                                      'gte' => $filter
                                ]
                          ]

                      ] ],
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
              $sort => [
                  'order' => $sortorder
              ]
          ]
      ];
    }

    if (! $show_new_dirs) {
      $searchParams['body']['query']['bool']['must_not'] = [
                        'term' => [
                          'change_percent_filesize' => 100.0
                          ],
                        'term' => [
                          'change_percent_items' => 100.0
                          ]
                  ];
    }

    $queryResponse = $client->search($searchParams);

    $hotdirs = $queryResponse['hits']['hits'];

    foreach ($hotdirs as $key => $value) {
        $totaldirsize += $value['_source']['filesize'];
        $totaldircount += $value['_source']['items'];
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
  <title>diskover &mdash; Hot Dirs</title>
	<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
  <link rel="stylesheet" href="css/diskover.css" media="screen" />
  <link rel="stylesheet" href="css/diskover-hotdirs.css" media="screen" />
  <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
</head>
<body>
<?php include "nav.php"; ?>
<?php if (! $show_change_percent) { ?>
      <div class="container" id="nohotdirs" style="display:block; margin-top:70px;">
            <div class="row">
                <div class="alert alert-dismissible alert-info col-xs-8">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="glyphicon glyphicon-exclamation-sign"></i> <strong>Sorry, no hot directories found.</strong> Run diskover using --hotdirs to find hot directories.
                </div>
            </div>
        </div>
<?php } else { ?>
<div class="container" id="error" style="display:none; margin-top:70px;">
      <div class="row">
        <div class="alert alert-dismissible alert-danger col-xs-8">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, an error has occured :( </strong><a href="#" onclick="window.history.go(-1); return false;">Go back</a>.<br /><br />
                    <small><a href="#" onclick="document.getElementById('debugerror').style.display = 'block'; return false;"> show debug error</a><br />
                    <span id="debugerror" style="display:none;"></span></small>
        </div>
      </div>
    </div>
        <div class="container" id="warning" style="display:none; margin-top:70px;">
            <div class="row">
                <div class="alert alert-dismissible alert-info col-xs-8">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, no files found, all files too small (filtered) or worker bots are still calculating directory sizes.</strong> Choose a different path and try again or check if worker bots are still running in rq. <a href="#" onclick="window.history.go(-1); return false;">Go back</a>.
                </div>
            </div>
        </div>
        <div class="container" id="index2req" style="display:none; margin-top:70px;">
            <div class="row">
                <div class="alert alert-dismissible alert-info col-xs-8">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="glyphicon glyphicon-exclamation-sign"></i> <strong>No index2 selected.</strong> Go to the <a href="selectindices.php">select index</a> page to add a previous index for data comparison.
                </div>
            </div>
        </div>
        </div>
<div class="container-fluid" id="mainwindow" style="display:none;margin-top:70px;">
  <div class="row">
    <div class="col-xs-12">
    <div class="heatmap-wrapper" style="position:relative;" id="heatmap-wrapper">
    <form>
      <input type="hidden" title="radius" type="range" id="radius" value="25" min="1" max="100" />
      <input type="hidden" title="blur" type="range" id="blur" value="15" min="1" max="60" />
      <input type="hidden" title="max" type="range" id="maxs" value="" min="" max="" />
    </form>
      <canvas id="heatmap-overlay" style="position:absolute;"></canvas>
      <div id="heatmap-container" style="z-index:0;position:absolute;"></div>
    </div>
    </div>
  </div>
  <div class="row">
    <div class="col-xs-12">
        <div id="hotdirs">
            <div class="row">
                <div class="col-xs-5">
                  <span style="font-size:11px; color:gray;">Sort by</span> 
                      <div class="btn-group">
                          <button class="btn btn-default btn-sm button-sizechange-desc"> Size Change % <i class="glyphicon glyphicon-sort-by-attributes-alt"></i></button>
                          <button class="btn btn-default btn-sm button-sizechange-asc"> Size Change % <i class="glyphicon glyphicon-sort-by-attributes"></i></button>
                          <button class="btn btn-default btn-sm button-itemschange-desc"> Items Change % <i class="glyphicon glyphicon-sort-by-attributes-alt"></i></button>
                          <button class="btn btn-default btn-sm button-itemschange-asc"> Items Change % <i class="glyphicon glyphicon-sort-by-attributes"></i></button>
                      </div>
                </div>
                <div class="col-xs-7 pull-right text-right" id="chart-buttons">
                    <button type="submit" id="reload" class="btn btn-default btn-sm" title="reload"><i class="glyphicon glyphicon-refresh"></i> </button>
                        <div class="btn-group">
                            <button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="dropdown">Min Size Filter
                            <span class="caret"></span></button>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo build_url('filter', 1); ?>">1 Bytes (default)</a></li>
                                <li><a href="<?php echo build_url('filter', 1024); ?>">1 KB</a></li>
                                <li><a href="<?php echo build_url('filter', 8192); ?>">8 KB</a></li>
                                <li><a href="<?php echo build_url('filter', 65536); ?>">64 KB</a></li>
                                <li><a href="<?php echo build_url('filter', 262144); ?>">256 KB</a></li>
                                <li><a href="<?php echo build_url('filter', 524288); ?>">512 KB</a></li>
                                <li><a href="<?php echo build_url('filter', 1048576); ?>">1 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 2097152); ?>">2 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 5242880); ?>">5 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 10485760); ?>">10 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 26214400); ?>">25 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 52428800); ?>">50 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 104857600); ?>">100 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 209715200); ?>">200 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 524288000); ?>">500 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 1073741824); ?>">1 GB</a></li>
                            </ul>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="dropdown">Mtime Filter
                            <span class="caret"></span></button>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo build_url('mtime', '0'); ?>">0 (default)</a></li>
                                <li><a href="<?php echo build_url('mtime', '1d'); ?>">1 day</a></li>
                                <li><a href="<?php echo build_url('mtime', '1w'); ?>">1 week</a></li>
                                <li><a href="<?php echo build_url('mtime', '1m'); ?>">1 month</a></li>
                                <li><a href="<?php echo build_url('mtime', '3m'); ?>">3 months</a></li>
                                <li><a href="<?php echo build_url('mtime', '6m'); ?>">6 months</a></li>
                                <li><a href="<?php echo build_url('mtime', '1y'); ?>">1 year</a></li>
                                <li><a href="<?php echo build_url('mtime', '2y'); ?>">2 years</a></li>
                                <li><a href="<?php echo build_url('mtime', '3y'); ?>">3 years</a></li>
                                <li><a href="<?php echo build_url('mtime', '5y'); ?>">5 years</a></li>
                            </ul>
                        </div>
                    <div class="btn-group">
                        <button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="dropdown">Min Change % +/-
                        <span class="caret"></span></button>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo build_url('min_change_percent', 0.01); ?>">0.01</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 0.1); ?>">0.1</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 0.25); ?>">0.25</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 0.5); ?>">0.5</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 0.75); ?>">0.75</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 1.0); ?>">1.0 (default)</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 1.25); ?>">1.25</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 1.5); ?>">1.5</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 1.75); ?>">1.75</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 2.0); ?>">2.0</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 3.0); ?>">3.0</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 5.0); ?>">5.0</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 10.0); ?>">10.0</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 15.0); ?>">15.0</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 25.0); ?>">25.0</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 50.0); ?>">50.0</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 75.0); ?>">75.0</a></li>
                            <li><a href="<?php echo build_url('min_change_percent', 100.0); ?>">100.0</a></li>
                        </ul>
                    </div>
                    <span style="font-size:11px; color:gray;">Maxdepth:</span>
                    <div class="btn-group">
                        <button class="btn btn-default btn-sm" id="depth1">1</button>
                        <button class="btn btn-default btn-sm" id="depth2">2</button>
                        <button class="btn btn-default btn-sm" id="depth3">3</button>
                        <button class="btn btn-default btn-sm" id="depth4">4</button>
                        <button class="btn btn-default btn-sm" id="depth5">5</button>
                    </div>
                    <span style="font-size:11px; color:gray;">Show new dirs </span><span style="position:relative; top:8px;"><label class="switch"><input id="shownewdirs" name="shownewdirs" type="checkbox"><span class="slider round"></span></label></span>
                    <div id="statustext" class="statustext">
                        <i class="glyphicon glyphicon-filter"></i> Filters: <span id="statusfilters"></span><span id="statusminchangepercent"></span>
                        &nbsp;&nbsp;<i class="glyphicon glyphicon-info-sign"></i> filters affect all analytics pages
                    </div>
                </div>
    		</div>
        <div class="row">
        <div class="col-xs-12">
            <form class="form-inline" id="path-container">
              <div class="form-group">
                <input type="text" size="90" class="form-control input-sm" style="color:#66C266!important;font-weight:bold;" name="pathinput" id="pathinput" value="">
              </div>
              <button title="change path" type="submit" id="changepath" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-circle-arrow-right"></i> Go</button>
                            <button title="<?php echo getParentDir($path); ?>" type="button" class="btn btn-primary btn-sm" onclick="window.location.href='<?php echo build_url('path', getParentDir($path)); ?>'; return false;"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</button>
                        </form>
        </div>
            </div>
        <br />
            <?php if (count($hotdirs) > 0) { ?>
            <table class="table table-striped table-hover table-condensed" style="font-size:12px;">
              <thead>
                <tr>
                  <th class="text-nowrap">#</th>
                  <th class="text-nowrap">Name</th>
                  <th class="text-nowrap">Size / Prev</th>
                  <th>%</th>
                  <th class="text-nowrap">Change %</th>
                  <th class="text-nowrap">Items / Prev</th>
                  <th>%</th>
                  <th class="text-nowrap">Change %</th>
                  <th class="text-nowrap">Items (files)</th>
                  <th class="text-nowrap">Change %</th>
                  <th class="text-nowrap">Items (subdirs)</th>
                  <th class="text-nowrap">Change %</th>
                  <th class="text-nowrap">Modified (utc)</th>
                  <th class="text-nowrap">Path</th>
              </tr>
            </thead>
            <tbody>
                  <?php
                      $n = 1;
                      foreach ($hotdirs as $key => $value) {
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
                            <td class="text-nowrap"><span style="font-weight:bold;color:#D20915;"><?php echo formatBytes($value['_source']['filesize']); ?> / <?php $fileinfo_index2 = get_index2_fileinfo($client, $esIndex2, $value['_source']['path_parent'], $value['_source']['filename']); echo formatBytes($fileinfo_index2[0]); ?></span></td>
                            <td width="5%"><div class="text-right percent" style="width:<?php echo number_format(($value['_source']['filesize'] / $totaldirsize) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['_source']['filesize'] / $totaldirsize) * 100, 2); ?>%</small></span></td>
                            <td width="5%"><?php if ($value['_source']['change_percent_filesize'] != 0) { ?><div class="text-right <?php echo $value['_source']['change_percent_filesize'] > 0 ? 'percent-red' : 'percent-green'; ?>" style="width:<?php echo ($value['_source']['change_percent_filesize'] > 100) ? 100 : abs(number_format($value['_source']['change_percent_filesize'], 2)); ?>%;"></div>&nbsp;<span style="font-size: 9px; color:<?php echo $value['_source']['change_percent_filesize'] > 0 ? "red" : "#29FE2F"; ?>;">(<?php echo $value['_source']['change_percent_filesize'] > 0 ? '<i class="glyphicon glyphicon-chevron-up"></i>+' : '<i class="glyphicon glyphicon-chevron-down"></i>'; ?><?php echo $value['_source']['change_percent_filesize']; ?>%)</span><?php } ?></td>
                            <td class="text-nowrap"><?php echo $value['_source']['items'] . ' / ' . $fileinfo_index2[1]; ?></td>
                            <td width="5%"><div class="text-right percent" style="width:<?php echo number_format(($value['_source']['items'] / $totaldircount) * 100, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo number_format(($value['_source']['items'] / $totaldircount) * 100, 2); ?>%</small></span></td>
                            <td width="5%"><?php if ($value['_source']['change_percent_items'] != 0) { ?><div class="text-right <?php echo $value['_source']['change_percent_items'] > 0 ? 'percent-red' : 'percent-green'; ?>" style="width:<?php echo ($value['_source']['change_percent_items'] > 100) ? 100 : abs(number_format($value['_source']['change_percent_items'], 2)); ?>%;"></div>&nbsp;<span style="font-size: 9px; color:<?php echo $value['_source']['change_percent_items'] > 0 ? "red" : "#29FE2F"; ?>;">(<?php echo $value['_source']['change_percent_items'] > 0 ? '<i class="glyphicon glyphicon-chevron-up"></i>+' : '<i class="glyphicon glyphicon-chevron-down"></i>'; ?><?php echo $value['_source']['change_percent_items']; ?>%)</span><?php } ?></td>
                            <td class="text-nowrap"><?php echo $value['_source']['items_files']; ?></td>
                            <td width="5%"><?php if ($value['_source']['change_percent_items_files'] != 0) { ?><div class="text-right <?php echo $value['_source']['change_percent_items_files'] > 0 ? 'percent-red' : 'percent-green'; ?>" style="width:<?php echo ($value['_source']['change_percent_items_files'] > 100) ? 100 : abs(number_format($value['_source']['change_percent_items_files'], 2)); ?>%;"></div>&nbsp;<span style="font-size: 9px; color:<?php echo $value['_source']['change_percent_items_files'] > 0 ? "red" : "#29FE2F"; ?>;">(<?php echo $value['_source']['change_percent_items_files'] > 0 ? '<i class="glyphicon glyphicon-chevron-up"></i>+' : '<i class="glyphicon glyphicon-chevron-down"></i>'; ?><?php echo $value['_source']['change_percent_items_files']; ?>%)</span><?php } ?></td>
                            <td class="text-nowrap"><?php echo $value['_source']['items_subdirs']; ?></td>
                            <td width="5%"><?php if ($value['_source']['change_percent_items_subdirs'] != 0) { ?><div class="text-right <?php echo $value['_source']['change_percent_items_subdirs'] > 0 ? 'percent-red' : 'percent-green'; ?>" style="width:<?php echo ($value['_source']['change_percent_items_subdirs'] > 100) ? 100 : abs(number_format($value['_source']['change_percent_items_subdirs'], 2)); ?>%;"></div>&nbsp;<span style="font-size: 9px; color:<?php echo $value['_source']['change_percent_items_subdirs'] > 0 ? "red" : "#29FE2F"; ?>;">(<?php echo $value['_source']['change_percent_items_subdirs'] > 0 ? '<i class="glyphicon glyphicon-chevron-up"></i>+' : '<i class="glyphicon glyphicon-chevron-down"></i>'; ?><?php echo $value['_source']['change_percent_items_subdirs']; ?>%)</span><?php } ?></td>
                            <td class="text-nowrap darken"><?php echo $value['_source']['last_modified']; ?></td>
                            <td class="path darken"><a href="<?php echo build_url('path', $value['_source']['path_parent']); ?>"><?php echo $value['_source']['path_parent']; ?></a></td>
                        </tr>
                    <?php $n++; } ?>
               </tbody>
          </table>
      <?php } else { ?>
      <div class="col-xs-6">
          <div class="alert alert-dismissible alert-info">
              <button type="button" class="close" data-dismiss="alert">&times;</button><i class="glyphicon glyphicon-info-sign"></i> No hotdirs in this path, try changing paths, sort order or filters.
          </div>
        </div>
      <?php } ?>
        </div>
      </div>
  </div>
</div>
<?php } ?>
<script language="javascript" src="js/jquery.min.js"></script>
<script language="javascript" src="js/bootstrap.min.js"></script>
<script language="javascript" src="js/diskover.js"></script>
<script language="javascript" src="js/hotdirs.js"></script>
<script language="javascript" src="js/d3.v3.min.js"></script>
<script language="javascript" src="js/d3-queue.v3.min.js"></script>
<script language="javascript" src="js/spin.min.js"></script>
<script language="javascript" src="js/d3.tip.v0.6.3.js"></script>
<script language="javascript" src="js/simpleheat.js"></script>
<script language="javascript" src="js/heatmap-hotdirs.js"></script>
</body>
</html>
