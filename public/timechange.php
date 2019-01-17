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

// escape characters in path
$path_escaped = escape_chars($path);

$monthrangeDays = [
             [ 'from' => 'now-1d/d', 'to' => 'now/d' ],
             [ 'from' => 'now-2d/d', 'to' => 'now-1d/d' ],
             [ 'from' => 'now-3d/d', 'to' => 'now-2d/d' ],
             [ 'from' => 'now-4d/d', 'to' => 'now-3d/d' ],
             [ 'from' => 'now-5d/d', 'to' => 'now-4d/d' ],
             [ 'from' => 'now-6d/d', 'to' => 'now-5d/d' ],
             [ 'from' => 'now-7d/d', 'to' => 'now-6d/d' ],
             [ 'from' => 'now-8d/d', 'to' => 'now-7d/d' ],
             [ 'from' => 'now-9d/d', 'to' => 'now-8d/d' ],
             [ 'from' => 'now-10d/d', 'to' => 'now-9d/d' ],
             [ 'from' => 'now-11d/d', 'to' => 'now-10d/d' ],
             [ 'from' => 'now-12d/d', 'to' => 'now-11d/d' ],
             [ 'from' => 'now-13d/d', 'to' => 'now-12d/d' ],
             [ 'from' => 'now-14d/d', 'to' => 'now-13d/d' ],
             [ 'from' => 'now-15d/d', 'to' => 'now-14d/d' ],
             [ 'from' => 'now-16d/d', 'to' => 'now-15d/d' ],
             [ 'from' => 'now-17d/d', 'to' => 'now-16d/d' ],
             [ 'from' => 'now-18d/d', 'to' => 'now-17d/d' ],
             [ 'from' => 'now-19d/d', 'to' => 'now-18d/d' ],
             [ 'from' => 'now-20d/d', 'to' => 'now-19d/d' ],
             [ 'from' => 'now-21d/d', 'to' => 'now-20d/d' ],
             [ 'from' => 'now-22d/d', 'to' => 'now-21d/d' ],
             [ 'from' => 'now-23d/d', 'to' => 'now-22d/d' ],
             [ 'from' => 'now-24d/d', 'to' => 'now-23d/d' ],
             [ 'from' => 'now-25d/d', 'to' => 'now-24d/d' ],
             [ 'from' => 'now-26d/d', 'to' => 'now-25d/d' ],
             [ 'from' => 'now-27d/d', 'to' => 'now-26d/d' ],
             [ 'from' => 'now-28d/d', 'to' => 'now-27d/d' ],
             [ 'from' => 'now-29d/d', 'to' => 'now-28d/d' ],
             [ 'from' => 'now-30d/d', 'to' => 'now-29d/d' ]
          ];

$yearrangeWeeks = [
             [ 'from' => 'now-7d/d', 'to' => 'now/d' ],
             [ 'from' => 'now-14d/d', 'to' => 'now-7d/d' ],
             [ 'from' => 'now-21d/d', 'to' => 'now-14d/d' ],
             [ 'from' => 'now-28d/d', 'to' => 'now-21d/d' ],
             [ 'from' => 'now-35d/d', 'to' => 'now-28d/d' ],
             [ 'from' => 'now-42d/d', 'to' => 'now-35d/d' ],
             [ 'from' => 'now-49d/d', 'to' => 'now-42d/d' ],
             [ 'from' => 'now-56d/d', 'to' => 'now-49d/d' ],
             [ 'from' => 'now-63d/d', 'to' => 'now-56d/d' ],
             [ 'from' => 'now-70d/d', 'to' => 'now-63d/d' ],
             [ 'from' => 'now-77d/d', 'to' => 'now-70d/d' ],
             [ 'from' => 'now-84d/d', 'to' => 'now-77d/d' ],
             [ 'from' => 'now-91d/d', 'to' => 'now-84d/d' ],
             [ 'from' => 'now-98d/d', 'to' => 'now-91d/d' ],
             [ 'from' => 'now-105d/d', 'to' => 'now-98d/d' ],
             [ 'from' => 'now-112d/d', 'to' => 'now-105d/d' ],
             [ 'from' => 'now-119d/d', 'to' => 'now-112d/d' ],
             [ 'from' => 'now-126d/d', 'to' => 'now-119d/d' ],
             [ 'from' => 'now-133d/d', 'to' => 'now-126d/d' ],
             [ 'from' => 'now-140d/d', 'to' => 'now-133d/d' ],
             [ 'from' => 'now-147d/d', 'to' => 'now-140d/d' ],
             [ 'from' => 'now-154d/d', 'to' => 'now-147d/d' ],
             [ 'from' => 'now-161d/d', 'to' => 'now-154d/d' ],
             [ 'from' => 'now-168d/d', 'to' => 'now-161d/d' ],
             [ 'from' => 'now-175d/d', 'to' => 'now-168d/d' ],
             [ 'from' => 'now-182d/d', 'to' => 'now-175d/d' ],
             [ 'from' => 'now-189d/d', 'to' => 'now-182d/d' ],
             [ 'from' => 'now-196d/d', 'to' => 'now-189d/d' ],
             [ 'from' => 'now-203d/d', 'to' => 'now-196d/d' ],
             [ 'from' => 'now-210d/d', 'to' => 'now-203d/d' ],
             [ 'from' => 'now-217d/d', 'to' => 'now-210d/d' ],
             [ 'from' => 'now-224d/d', 'to' => 'now-217d/d' ],
             [ 'from' => 'now-231d/d', 'to' => 'now-224d/d' ],
             [ 'from' => 'now-238d/d', 'to' => 'now-231d/d' ],
             [ 'from' => 'now-245d/d', 'to' => 'now-238d/d' ],
             [ 'from' => 'now-252d/d', 'to' => 'now-245d/d' ],
             [ 'from' => 'now-259d/d', 'to' => 'now-252d/d' ],
             [ 'from' => 'now-266d/d', 'to' => 'now-259d/d' ],
             [ 'from' => 'now-273d/d', 'to' => 'now-266d/d' ],
             [ 'from' => 'now-280d/d', 'to' => 'now-273d/d' ],
             [ 'from' => 'now-287d/d', 'to' => 'now-280d/d' ],
             [ 'from' => 'now-294d/d', 'to' => 'now-287d/d' ],
             [ 'from' => 'now-301d/d', 'to' => 'now-294d/d' ],
             [ 'from' => 'now-308d/d', 'to' => 'now-301d/d' ],
             [ 'from' => 'now-315d/d', 'to' => 'now-308d/d' ],
             [ 'from' => 'now-322d/d', 'to' => 'now-315d/d' ],
             [ 'from' => 'now-329d/d', 'to' => 'now-322d/d' ],
             [ 'from' => 'now-336d/d', 'to' => 'now-329d/d' ],
             [ 'from' => 'now-343d/d', 'to' => 'now-336d/d' ],
             [ 'from' => 'now-350d/d', 'to' => 'now-343d/d' ],
             [ 'from' => 'now-357d/d', 'to' => 'now-350d/d' ],
             [ 'from' => 'now-364d/d', 'to' => 'now-357d/d' ]
          ];

// Get search results from Elasticsearch for last modified file count and directory count date range past year
$results = [];
$searchParams = [];
$LastModDateRangeD3DataYear = [];

$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

$searchParams['body'] = [
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
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => $yearrangeWeeks,
          'keyed' => 'true'
        ]
      ]
    ]
];
$queryResponse = $client->search($searchParams);

// Get last modified count for past year
$fileLastModDateRange = $queryResponse['aggregations']['range']['buckets'];

$searchParams['index'] = $esIndex;
$searchParams['type']  = 'directory';

$searchParams['body'] = [
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
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => $yearrangeWeeks,
          'keyed' => 'true'
        ]
      ]
    ]
];
$queryResponse = $client->search($searchParams);

// Get last modified count for past year
$dirLastModDateRange = $queryResponse['aggregations']['range']['buckets'];
// add to data array for d3
foreach ($dirLastModDateRange as $key => $value) {
    $date = explode("-", $key);
    $date = $date[0] . "-" . $date[1] . "-" . $date[2];
    $LastModDateRangeD3DataYear[] = [ 'date' => $date, 'file' => $fileLastModDateRange[$key]['doc_count'], 'directory' => $value['doc_count'] ];
}


// Get search results from Elasticsearch for file size and directory size date range past year
$results = [];
$searchParams = [];
$SizeDateRangeD3DataYear = [];

$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

$searchParams['body'] = [
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
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => $yearrangeWeeks,
          'keyed' => 'true'
        ],
        'aggs' => [
        'total_size' => [
            'sum' => [
                'field' => 'filesize'
            ]
        ]
      ]
      ]
    ]
];
$queryResponse = $client->search($searchParams);

// Get directory filesize for past month
$fileSizeDateRange = $queryResponse['aggregations']['range']['buckets'];

$searchParams['index'] = $esIndex;
$searchParams['type']  = 'directory';

$searchParams['body'] = [
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
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => $yearrangeWeeks,
          'keyed' => 'true'
        ],
        'aggs' => [
        'total_size' => [
            'sum' => [
                'field' => 'filesize'
            ]
        ]
      ]
      ]
    ]
];
$queryResponse = $client->search($searchParams);

// Get total size for past year
$dirSizeDateRange = $queryResponse['aggregations']['range']['buckets'];
// add to data array for d3
foreach ($dirSizeDateRange as $key => $value) {
    $date = explode("-", $key);
    $date = $date[0] . "-" . $date[1] . "-" . $date[2];
    $SizeDateRangeD3DataYear[] = [ 'date' => $date, 'file' => $fileSizeDateRange[$key]['total_size']['value'], 'directory' => $value['total_size']['value'] ];
}


// Get search results from Elasticsearch for last modified file count and directory count date range past month
$results = [];
$searchParams = [];
$LastModDateRangeD3DataMonth = [];

$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

$searchParams['body'] = [
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
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => $monthrangeDays,
          'keyed' => 'true'
        ]
      ]
    ]
];
$queryResponse = $client->search($searchParams);

// Get last modified count for past year
$fileLastModDateRange = $queryResponse['aggregations']['range']['buckets'];

$searchParams['index'] = $esIndex;
$searchParams['type']  = 'directory';

$searchParams['body'] = [
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
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => $monthrangeDays,
          'keyed' => 'true'
        ]
      ]
    ]
];
$queryResponse = $client->search($searchParams);

// Get last modified count for past month
$dirLastModDateRange = $queryResponse['aggregations']['range']['buckets'];
// add to data array for d3
foreach ($dirLastModDateRange as $key => $value) {
    $date = explode("-", $key);
    $date = $date[0] . "-" . $date[1] . "-" . $date[2];
    $LastModDateRangeD3DataMonth[] = [ 'date' => $date, 'file' => $fileLastModDateRange[$key]['doc_count'], 'directory' => $value['doc_count'] ];
}


// Get search results from Elasticsearch for file size and directory size date range past month
$results = [];
$searchParams = [];
$SizeDateRangeD3DataMonth = [];

$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

$searchParams['body'] = [
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
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => $monthrangeDays,
          'keyed' => 'true'
        ],
        'aggs' => [
        'total_size' => [
            'sum' => [
                'field' => 'filesize'
            ]
        ]
      ]
      ]
    ]
];
$queryResponse = $client->search($searchParams);

// Get directory filesize for past year
$fileSizeDateRange = $queryResponse['aggregations']['range']['buckets'];

$searchParams['index'] = $esIndex;
$searchParams['type']  = 'directory';

$searchParams['body'] = [
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
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => $monthrangeDays,
          'keyed' => 'true'
        ],
        'aggs' => [
        'total_size' => [
            'sum' => [
                'field' => 'filesize'
            ]
        ]
      ]
      ]
    ]
];
$queryResponse = $client->search($searchParams);

// Get total size for past month
$dirSizeDateRange = $queryResponse['aggregations']['range']['buckets'];
// add to data array for d3
foreach ($dirSizeDateRange as $key => $value) {
    $date = explode("-", $key);
    $date = $date[0] . "-" . $date[1] . "-" . $date[2];
    $SizeDateRangeD3DataMonth[] = [ 'date' => $date, 'file' => $fileSizeDateRange[$key]['total_size']['value'], 'directory' => $value['total_size']['value'] ];
}

?>

	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>diskover &mdash; Time Change</title>
		<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="css/diskover.css" media="screen" />
        <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
        <style>
            #chart-container-month {
                display: inline-block;
                margin-top: 20px;
            }

            #mtimechart-month {
                position: relative;
                display: inline;
            }

            #mtimechart-month svg {
                width: 650px;
                height: 300px;
            }

            #sizechart-month {
                position: relative;
                display: inline;
            }

            #sizechart-month svg {
                width: 650px;
                height: 300px;
            }

            #chart-container-year {
                display: inline-block;
                margin-top: 20px;
            }

            #mtimechart-year {
                position: relative;
                display: inline;
            }

            #mtimechart-year svg {
                width: 650px;
                height: 300px;
            }

            #sizechart-year {
                position: relative;
                display: inline;
            }

            #sizechart-year svg {
                width: 650px;
                height: 300px;
            }

            text {
                fill: gray;
                font-size: 10px;
            }

            .hovertext {
                fill: red;
                font-size: 11px;
                font-weight: bold;
            }

            .axis {
                font: 10px sans-serif;
                fill: gray;
            }
            .axis path,
            .axis line {
              fill: none;
              stroke: #555;
              shape-rendering: crispEdges;
            }

            .path,
            .line {
              fill: none;
              stroke-width: 1px;
              stroke: steelblue;
              pointer-events: none;
              shape-rendering: crispEdges;
              opacity: .9;
            }

            .area_mtimechart-month,
            .area_sizechart-month,
            .area_mtimechart-year,
            .area_sizechart-year { 
                opacity: .6;
            }
        </style>
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container-fluid" id="mainwindow" style="margin-top: 70px;">
            <div class="row">
                <div class="col-xs-12 text-center">
                    <h2>Time Change</h2>
                    <div class="row">
                    		<div class="col-xs-12 text-center">
                    			<form class="form-horizontal" id="changemindupes">
                    			<div class="form-group form-inline">
                    			     <span style="font-size:10px; color:gray; margin-left:20px;"><i class="glyphicon glyphicon-info-sign"></i> filters on filetree page affect this page<br />
                                     <h5 style="display: inline;"><span class="text-success bold"><?php echo stripslashes($path); ?></span></h5>
                                    <span style="margin-right:20px;"><a title="<?php echo getParentDir($path); ?>" class="btn btn-primary btn-sm" onclick="window.location.href='<?php echo build_url('path', getParentDir($path)); ?>';"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</a></span>
                    			</div>
                    			</form>
                    		</div>
                    	</div>
                </div>
            </div>
			<div class="row">
                <div class="col-xs-12 text-center">
                      <div id="chart-container-month" class="text-center" style="margin-top: 10px;">
                          <div class="well"><p>File and directory total counts/sizes which have been modified over the past month, aggregated daily</p><br />
                          <div id="mtimechart-month" class="text-center"></div>
                          <div id="sizechart-month" class="text-center"></div>
                      </div>
                      </div>
                </div>
			</div>
            <div class="row">
                <div class="col-xs-12 text-center">
                      <div id="chart-container-year" class="text-center" style="margin-top: 10px;">
                          <div class="well"><p>File and directory total counts/sizes which have been modified over the past year, aggregated weekly</p><br />
                          <div id="mtimechart-year" class="text-center"></div>
                          <div id="sizechart-year" class="text-center"></div>
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

        <script>

          function draw_chart(jsondata, divid, charttype) {
            var data_raw = jsondata;

            var margin = { top: 20, right: 80, bottom: 60, left: 70 };
            var height = 300 - margin.top - margin.bottom;
            var width = 650 - margin.left - margin.right;

            var svg = d3.select("#" + divid).append("svg")
                .attr("width",width + margin.left + margin.right)
                .attr("height",height + margin.top + margin.bottom)
              .append("g")
                .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

            // setup scales - the domain is specified inside of the function called when we load the data
            var xScale = d3.time.scale().range([0, width]);
            var yScale = d3.scale.linear().range([height, 0]);
            var xScale_line = d3.time.scale().range([0, width]);
            var yScale_line = d3.scale.linear().range([height, 0]);
            var color = d3.scale.ordinal()
                .domain(["directory", "file"])
                .range(["#738290", "#89CAE3"]);

            // setup the axes
            var xAxis = d3.svg.axis().scale(xScale)
                .orient("bottom").ticks(10)
                .tickFormat(d3.time.format("%Y-%m-%d"));
            var yAxis = d3.svg.axis().scale(yScale).orient("left");

            // create function to parse dates into date objects
            var parseDate = d3.time.format("%Y-%m-%d").parse;
            var formatDate = d3.time.format("%Y-%m-%d");
            var bisectDate = d3.bisector(function(d) { return d.date; }).left;

            // set the area attributes
            var area = d3.svg.area()
              .interpolate("basis")
              .x(function(d) { return xScale(d.date); })
              .y0(function(d) { return yScale(d.y0); })
              .y1(function(d) { return yScale(d.y0 + d.y); });

            var stack = d3.layout.stack()
                .values(function(d) { return d.values; });

            var line = d3.svg.line()
              .interpolate("basis")
              .x(function(d) { return xScale_line(d.date); })
              .y(function(d) { return yScale_line(d.value); });

            // import data and create chart

            var data = data_raw.map(function(d) {
              return {
                date: parseDate(d.date),
                file: +d.file,
                directory: +d.directory
              };
            });
            
            // sort data ascending - needed to get correct bisector results
            data.sort(function(a,b) {
              return a.date - b.date;
            });

            // color domain
            color.domain(d3.keys(data[0]).filter(function(key) { return key !== "date"; }));

            // create doctypes array with object for each doctype (file/directory) containing all data
            var doctypes = stack(color.domain().map(function(type) {
              return {
                type: type,
                values: data.map(function(d){
                  if (charttype === "count") {
                    return {
                      date: d.date, 
                      y: +d[type]
                    };
                  } else {
                    return {
                      date: d.date, 
                      y: +d[type] / 1024 / 1024 / 1024
                    };
                  }
                })
              };
            }));

            var doctypes_line = color.domain().map(function(type) {
              return {
                type: type,
                values: data.map(function(d){
                  return {
                    date: d.date, 
                    value: +d[type]
                  };
                })
              };
            });

            // Find the value of the date with highest total value
            var maxDateVal = d3.max(data, function(d){
              var vals = d3.keys(d).map(
                function(key){ 
                  return key !== "date" ? d[key] : 0 });
              return d3.sum(vals);
            });

            // add domain ranges to the x and y scales
            xScale.domain([
              d3.min(doctypes, function(c) { return d3.min(c.values, function(v) { return v.date; }); }),
              d3.max(doctypes, function(c) { return d3.max(c.values, function(v) { return v.date; }); })
            ]);
            yScale.domain([
              0,
              // d3.min(doctypes, function(c) { return d3.min(c.values, function(v) { return v.y; }); }),
              d3.max(doctypes, function(c) { return d3.max(c.values, function(v) { return v.y; }); })
            ]);
            xScale_line.domain([
              d3.min(doctypes_line, function(c) { return d3.min(c.values, function(v) { return v.date; }); }),
              d3.max(doctypes_line, function(c) { return d3.max(c.values, function(v) { return v.date; }); })
            ]);
            yScale_line.domain([
              0,
              // d3.min(doctypes, function(c) { return d3.min(c.values, function(v) { return v.value; }); }),
              d3.max(doctypes_line, function(c) { return d3.max(c.values, function(v) { return v.value; }); })
            ]);

            // add the x axis
            svg.append("g")
                .attr("class", "x axis")
                .attr("transform", "translate(0," + height + ")")
                .call(xAxis)
              .selectAll("text")
                .attr("y", 7)
                .attr("x", -9)
                .attr("dy", ".35em")
                .attr("transform", "rotate(-45)")
                .style("text-anchor", "end");

            // add the y axis
            svg.append("g")
                .attr("class", "y axis")
                .call(yAxis)
              .append("text")
                .attr("transform","rotate(-90)")
                .attr("y",10)
                .attr("dy",".71em")
                .style("text-anchor","end")
                .text(function() { if (charttype === "count") { return "Modified Count"; } else { return "Modified Size (GB)"; } });
            
            // add the area groups
            var doctype = svg.selectAll(".doctype")
                .data(doctypes)
              .enter().append("g")
                .attr("class","doctype" + "_" + divid);

            // add the line groups
            var doctype_line = svg.selectAll(".doctype-line")
                .data(doctypes_line)
              .enter().append("g")
                .attr("class","doctype-line" + "_" + divid);

            // add the doctype count paths
            doctype.append("path")
                .attr("class", "area" + "_" + divid)
                .attr("d", function(d) {
                return area(d.values); 
              })
              .style("fill", function(d) { return color(d.type); });

            doctype_line.append("path")
                .attr("class", 'line' + '_' + divid)
                .attr("d", function(d) {
                return line(d.values); 
              })
              .style("opacity", 0);

            var legend = svg.selectAll(".legend" + "_" + divid)
              .data(color.domain()).enter()
              .append("g")
              .attr("class","legend" + "_" + divid)
              .attr("transform", "translate(" + (width +20) + "," + 0+ ")");

            legend.append("rect")
              .attr("x", 0) 
              .attr("y", function(d, i) { return 20 * i; })
              .attr("width", 10)
              .attr("height", 10)
              .style("fill", function(d, i) {
                return color(i);}); 
           
            legend.append("text")
              .attr("x", 20) 
              .attr("dy", "0.75em")
              .attr("y", function(d, i) { return 20 * i; })
              .text(function(d) {return d});
              
            legend.append("text")
              .attr("x",0) 
              //.attr("dy", "0.75em")
              .attr("y",-10)
              .text("Type");

            var mouseG = svg.append("g")
              .attr("class", "mouse-over-effects" + "_" + divid);

            mouseG.append("path") // this is the gray vertical line to follow mouse
              .attr("class", "mouse-line" + "_" + divid)
              .style("stroke", "#555")
              .style("stroke-width", "1px")
              .style("opacity", "0");
              
            var lines = document.getElementsByClassName('line' + '_' + divid);

            var mousePerLine = mouseG.selectAll('.mouse-per-line' + '_' + divid)
              .data(doctypes_line)
              .enter()
              .append("g")
              .attr("class", "mouse-per-line" + "_" + divid);

            mousePerLine.append("circle")
              .attr("r", 7)
              .style("stroke", function(d) {
                return color(d.type);
              })
              .style("fill", "none")
              .style("stroke-width", "1px")
              .style("opacity", "0");

            mousePerLine.append("text")
              .attr("class", "hovertext")
              .attr("transform", "translate(10,3)");

            mouseG.append('svg:rect') // append a rect to catch mouse movements on canvas
              .attr('width', width) // can't catch mouse events on a g element
              .attr('height', height)
              .attr('fill', 'none')
              .attr('pointer-events', 'all')
              .on('mouseout', function() { // on mouse out hide line, circles and text
                d3.select(".mouse-line" + "_" + divid)
                  .style("opacity", "0");
                d3.selectAll(".mouse-per-line" + "_" + divid + " circle")
                  .style("opacity", "0");
                d3.selectAll(".mouse-per-line" + "_" + divid + " text")
                  .style("opacity", "0");
              })
              .on('mouseover', function() { // on mouse in show line, circles and text
                d3.select(".mouse-line" + "_" + divid)
                  .style("opacity", "1");
                d3.selectAll(".mouse-per-line" + "_" + divid + " circle")
                  .style("opacity", "1");
                d3.selectAll(".mouse-per-line" + "_" + divid + " text")
                  .style("opacity", "1");
              })
              .on('mousemove', function() { // mouse moving over canvas
                var mouse = d3.mouse(this);
                d3.select(".mouse-line" + "_" + divid)
                  .attr("d", function() {
                    var d = "M" + mouse[0] + "," + height;
                    d += " " + mouse[0] + "," + 0;
                    return d;
                  });

                d3.selectAll(".mouse-per-line" + "_" + divid)
                  .attr("transform", function(d, i) {
                    console.log(width/mouse[0])
                    var xDate = xScale_line.invert(mouse[0]),
                        bisect = d3.bisector(function(d) { return d.date; }).right;
                        idx = bisect(d.values, xDate);
                    
                    var beginning = 0,
                        end = lines[i].getTotalLength(),
                        target = null;

                    while (true){
                      target = Math.floor((beginning + end) / 2);
                      pos = lines[i].getPointAtLength(target);
                      if ((target === end || target === beginning) && pos.x !== mouse[0]) {
                          break;
                      }
                      if (pos.x > mouse[0])      end = target;
                      else if (pos.x < mouse[0]) beginning = target;
                      else break; //position found
                    }
                    
                    d3.select(this).select('text')
                      .text(function() { if (charttype === "count") { return yScale_line.invert(pos.y).toFixed(0); } else { return format(yScale_line.invert(pos.y), 1); } });
                      
                    return "translate(" + mouse[0] + "," + pos.y +")";
                  });
              });

            }

    var jsondata = <?php echo json_encode($LastModDateRangeD3DataMonth); ?>;
    draw_chart(jsondata, 'mtimechart-month', 'count')
    jsondata = <?php echo json_encode($SizeDateRangeD3DataMonth); ?>;
    draw_chart(jsondata, 'sizechart-month', 'size')
    jsondata = <?php echo json_encode($LastModDateRangeD3DataYear); ?>;
    draw_chart(jsondata, 'mtimechart-year', 'count')
    jsondata = <?php echo json_encode($SizeDateRangeD3DataYear); ?>;
    draw_chart(jsondata, 'sizechart-year', 'size')
            
    </script>

	</body>

	</html>
