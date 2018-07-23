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


// Get search results from Elasticsearch for last modified file count and directory count date range past year
$results = [];
$searchParams = [];
$LastModDateRangeD3Data = [];

$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

$searchParams['body'] = [
   'size' => 0,
    'aggs' => [
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => [
             [ 'to' => 'now',
             'from' => 'now-2w/w' ],
             [ 'to' => 'now-2w/w',
             'from' => 'now-4w/w' ],
             [ 'to' => 'now-4w/w',
             'from' => 'now-6w/w' ],
             [ 'to' => 'now-6w/w',
             'from' => 'now-8w/w' ],
             [ 'to' => 'now-8w/w',
             'from' => 'now-10w/w' ],
             [ 'to' => 'now-10w/w',
             'from' => 'now-12w/w' ],
             [ 'to' => 'now-12w/w',
             'from' => 'now-14w/w' ],
             [ 'to' => 'now-14w/w',
             'from' => 'now-16w/w' ],
             [ 'to' => 'now-16w/w',
             'from' => 'now-18w/w' ],
             [ 'to' => 'now-18w/w',
             'from' => 'now-20w/w' ],
             [ 'to' => 'now-20w/w',
             'from' => 'now-22w/w' ],
             [ 'to' => 'now-22w/w',
             'from' => 'now-24w/w' ],
             [ 'to' => 'now-24w/w',
             'from' => 'now-26w/w' ],
             [ 'to' => 'now-26w/w',
             'from' => 'now-28w/w' ],
             [ 'to' => 'now-28w/w',
             'from' => 'now-30w/w' ],
             [ 'to' => 'now-30w/w',
             'from' => 'now-32w/w' ],
             [ 'to' => 'now-32w/w',
             'from' => 'now-34w/w' ],
             [ 'to' => 'now-34w/w',
             'from' => 'now-36w/w' ],
             [ 'to' => 'now-36w/w',
             'from' => 'now-38w/w' ],
             [ 'to' => 'now-38w/w',
             'from' => 'now-40w/w' ],
             [ 'to' => 'now-40w/w',
             'from' => 'now-42w/w' ],
             [ 'to' => 'now-42w/w',
             'from' => 'now-44w/w' ],
             [ 'to' => 'now-44w/w',
             'from' => 'now-46w/w' ],
             [ 'to' => 'now-46w/w',
             'from' => 'now-48w/w' ],
             [ 'to' => 'now-48w/w',
             'from' => 'now-50w/w' ],
             [ 'to' => 'now-50w/w',
             'from' => 'now-52w/w' ]
          ],
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
    'aggs' => [
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => [
             [ 'to' => 'now',
             'from' => 'now-2w/w' ],
             [ 'to' => 'now-2w/w',
             'from' => 'now-4w/w' ],
             [ 'to' => 'now-4w/w',
             'from' => 'now-6w/w' ],
             [ 'to' => 'now-6w/w',
             'from' => 'now-8w/w' ],
             [ 'to' => 'now-8w/w',
             'from' => 'now-10w/w' ],
             [ 'to' => 'now-10w/w',
             'from' => 'now-12w/w' ],
             [ 'to' => 'now-12w/w',
             'from' => 'now-14w/w' ],
             [ 'to' => 'now-14w/w',
             'from' => 'now-16w/w' ],
             [ 'to' => 'now-16w/w',
             'from' => 'now-18w/w' ],
             [ 'to' => 'now-18w/w',
             'from' => 'now-20w/w' ],
             [ 'to' => 'now-20w/w',
             'from' => 'now-22w/w' ],
             [ 'to' => 'now-22w/w',
             'from' => 'now-24w/w' ],
             [ 'to' => 'now-24w/w',
             'from' => 'now-26w/w' ],
             [ 'to' => 'now-26w/w',
             'from' => 'now-28w/w' ],
             [ 'to' => 'now-28w/w',
             'from' => 'now-30w/w' ],
             [ 'to' => 'now-30w/w',
             'from' => 'now-32w/w' ],
             [ 'to' => 'now-32w/w',
             'from' => 'now-34w/w' ],
             [ 'to' => 'now-34w/w',
             'from' => 'now-36w/w' ],
             [ 'to' => 'now-36w/w',
             'from' => 'now-38w/w' ],
             [ 'to' => 'now-38w/w',
             'from' => 'now-40w/w' ],
             [ 'to' => 'now-40w/w',
             'from' => 'now-42w/w' ],
             [ 'to' => 'now-42w/w',
             'from' => 'now-44w/w' ],
             [ 'to' => 'now-44w/w',
             'from' => 'now-46w/w' ],
             [ 'to' => 'now-46w/w',
             'from' => 'now-48w/w' ],
             [ 'to' => 'now-48w/w',
             'from' => 'now-50w/w' ],
             [ 'to' => 'now-50w/w',
             'from' => 'now-52w/w' ]
          ],
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
    $LastModDateRangeD3Data[] = [ 'date' => $date, 'file' => $fileLastModDateRange[$key]['doc_count'], 'directory' => $value['doc_count'] ];
}


// Get search results from Elasticsearch for file size and directory size date range past year
$results = [];
$searchParams = [];
$SizeDateRangeD3Data = [];

$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

$searchParams['body'] = [
   'size' => 0,
    'aggs' => [
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => [
             [ 'to' => 'now',
             'from' => 'now-2w/w' ],
             [ 'to' => 'now-2w/w',
             'from' => 'now-4w/w' ],
             [ 'to' => 'now-4w/w',
             'from' => 'now-6w/w' ],
             [ 'to' => 'now-6w/w',
             'from' => 'now-8w/w' ],
             [ 'to' => 'now-8w/w',
             'from' => 'now-10w/w' ],
             [ 'to' => 'now-10w/w',
             'from' => 'now-12w/w' ],
             [ 'to' => 'now-12w/w',
             'from' => 'now-14w/w' ],
             [ 'to' => 'now-14w/w',
             'from' => 'now-16w/w' ],
             [ 'to' => 'now-16w/w',
             'from' => 'now-18w/w' ],
             [ 'to' => 'now-18w/w',
             'from' => 'now-20w/w' ],
             [ 'to' => 'now-20w/w',
             'from' => 'now-22w/w' ],
             [ 'to' => 'now-22w/w',
             'from' => 'now-24w/w' ],
             [ 'to' => 'now-24w/w',
             'from' => 'now-26w/w' ],
             [ 'to' => 'now-26w/w',
             'from' => 'now-28w/w' ],
             [ 'to' => 'now-28w/w',
             'from' => 'now-30w/w' ],
             [ 'to' => 'now-30w/w',
             'from' => 'now-32w/w' ],
             [ 'to' => 'now-32w/w',
             'from' => 'now-34w/w' ],
             [ 'to' => 'now-34w/w',
             'from' => 'now-36w/w' ],
             [ 'to' => 'now-36w/w',
             'from' => 'now-38w/w' ],
             [ 'to' => 'now-38w/w',
             'from' => 'now-40w/w' ],
             [ 'to' => 'now-40w/w',
             'from' => 'now-42w/w' ],
             [ 'to' => 'now-42w/w',
             'from' => 'now-44w/w' ],
             [ 'to' => 'now-44w/w',
             'from' => 'now-46w/w' ],
             [ 'to' => 'now-46w/w',
             'from' => 'now-48w/w' ],
             [ 'to' => 'now-48w/w',
             'from' => 'now-50w/w' ],
             [ 'to' => 'now-50w/w',
             'from' => 'now-52w/w' ]
          ],
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
    'aggs' => [
      'range' => [
        'date_range' => [
          'field' => 'last_modified',
          'format' => 'yyy-MM-dd',
          'ranges' => [
             [ 'to' => 'now',
             'from' => 'now-2w/w' ],
             [ 'to' => 'now-2w/w',
             'from' => 'now-4w/w' ],
             [ 'to' => 'now-4w/w',
             'from' => 'now-6w/w' ],
             [ 'to' => 'now-6w/w',
             'from' => 'now-8w/w' ],
             [ 'to' => 'now-8w/w',
             'from' => 'now-10w/w' ],
             [ 'to' => 'now-10w/w',
             'from' => 'now-12w/w' ],
             [ 'to' => 'now-12w/w',
             'from' => 'now-14w/w' ],
             [ 'to' => 'now-14w/w',
             'from' => 'now-16w/w' ],
             [ 'to' => 'now-16w/w',
             'from' => 'now-18w/w' ],
             [ 'to' => 'now-18w/w',
             'from' => 'now-20w/w' ],
             [ 'to' => 'now-20w/w',
             'from' => 'now-22w/w' ],
             [ 'to' => 'now-22w/w',
             'from' => 'now-24w/w' ],
             [ 'to' => 'now-24w/w',
             'from' => 'now-26w/w' ],
             [ 'to' => 'now-26w/w',
             'from' => 'now-28w/w' ],
             [ 'to' => 'now-28w/w',
             'from' => 'now-30w/w' ],
             [ 'to' => 'now-30w/w',
             'from' => 'now-32w/w' ],
             [ 'to' => 'now-32w/w',
             'from' => 'now-34w/w' ],
             [ 'to' => 'now-34w/w',
             'from' => 'now-36w/w' ],
             [ 'to' => 'now-36w/w',
             'from' => 'now-38w/w' ],
             [ 'to' => 'now-38w/w',
             'from' => 'now-40w/w' ],
             [ 'to' => 'now-40w/w',
             'from' => 'now-42w/w' ],
             [ 'to' => 'now-42w/w',
             'from' => 'now-44w/w' ],
             [ 'to' => 'now-44w/w',
             'from' => 'now-46w/w' ],
             [ 'to' => 'now-46w/w',
             'from' => 'now-48w/w' ],
             [ 'to' => 'now-48w/w',
             'from' => 'now-50w/w' ],
             [ 'to' => 'now-50w/w',
             'from' => 'now-52w/w' ]
          ],
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
    $SizeDateRangeD3Data[] = [ 'date' => $date, 'file' => $fileSizeDateRange[$key]['total_size'], 'directory' => $value['total_size'] ];
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
            #mtimechart {
                top: 10px;
                position: relative;
            }

            #mtimechart svg {
                width: 1400px;
                height: 300px;
            }

            #sizechart {
                top: 30px;
                position: relative;
            }

            #sizechart svg {
                width: 1400px;
                height: 300px;
            }

            text {
                fill: gray;
                font-size: 10px;
                text-anchor: middle;
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

            .line {
              fill: none;
              stroke-width: 1px;
            }
        </style>
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container-fluid" id="mainwindow" style="margin-top: 70px;">
            <div class="row">
                <div class="col-xs-12 text-center">
                    <h2>Time Change</h2>
                </div>
            </div>
			<div class="row">
                <div class="col-xs-12">
                      <div id="chart-container" class="text-center">
                          <div id="mtimechart" class="text-center"></div>
                          <div id="sizechart" class="text-center"></div>
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
            // chart1 mtime
            var data_raw = <?php echo json_encode($LastModDateRangeD3Data); ?>;

            var margin = { top: 20, right: 100, bottom: 40, left: 100 };
            var height = 300 - margin.top - margin.bottom;
            var width = 1400 - margin.left - margin.right;

            var svg = d3.select("#mtimechart").append("svg")
                .attr("width",width + margin.left + margin.right)
                .attr("height",height + margin.top + margin.bottom)
              .append("g")
                .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

            // setup scales - the domain is specified inside of the function called when we load the data
            var xScale = d3.time.scale().range([0, width]);
            var yScale = d3.scale.linear().range([height, 0]);
            var color = d3.scale.category10();

            // setup the axes
            var xAxis = d3.svg.axis().scale(xScale).orient("bottom");
            var yAxis = d3.svg.axis().scale(yScale).orient("left");

            // create function to parse dates into date objects
            var parseDate = d3.time.format("%Y-%m-%d").parse;
            var formatDate = d3.time.format("%Y-%m-%d");
            var bisectDate = d3.bisector(function(d) { return d.date; }).left;

            // set the line attributes
            var line = d3.svg.line()
              .interpolate("basis")
              .x(function(d) { return xScale(d.date); })
              .y(function(d) { return yScale(d.count); });

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
            var doctypes = color.domain().map(function(type) {
              return {
                type: type,
                values: data.map(function(d){
                  return {date: d.date, count: d[type]};
                })
              };
            });

            // add domain ranges to the x and y scales
            xScale.domain([
              d3.min(doctypes, function(c) { return d3.min(c.values, function(v) { return v.date; }); }),
              d3.max(doctypes, function(c) { return d3.max(c.values, function(v) { return v.date; }); })
            ]);
            yScale.domain([
              0,
              // d3.min(doctypes, function(c) { return d3.min(c.values, function(v) { return v.count; }); }),
              d3.max(doctypes, function(c) { return d3.max(c.values, function(v) { return v.count; }); })
            ]);

            // add the x axis
            svg.append("g")
              .attr("class", "x axis")
              .attr("transform", "translate(0," + height + ")")
              .call(xAxis);

            // add the y axis
            svg.append("g")
                .attr("class", "y axis")
                .call(yAxis)
              .append("text")
                .attr("transform","rotate(-90)")
                .attr("y",-60)
                .attr("dy",".71em")
                .style("text-anchor","end")
                .text("Modified Count");
            
            // add the line groups
            var doctype = svg.selectAll(".doctypeXYZ")
                .data(doctypes)
              .enter().append("g")
                .attr("class","doctypeXYZ");

            // add the doctype count paths
            doctype.append("path")
              .attr("class","line")
              .attr("id",function(d,i){ return "id" + i; })
              .attr("d", function(d) {
                return line(d.values); 
              })
              .style("stroke", function(d) { return color(d.type); });


            // add the doctype labels at the right edge of chart
            var maxLen = data.length;
            doctype.append("text")
              .datum(function(d) { 
                return {type: d.type, value: d.values[maxLen - 1]}; 
              })
              .attr("transform", function(d) { 
                return "translate(" + xScale(d.value.date) + "," + yScale(d.value.count) + ")"; 
              })
              .attr("id",function(d,i){ return "text_id" + i; })
              .attr("x", 3)
              .attr("dy", ".35em")
              .text(function(d) { return d.type; })
              .on("mouseover",function(d,i) { 
                for (j=0; j < 6; j++) {
                  if (i !== j) {
                    d3.select("#id"+j).style("opacity",0.1);
                    d3.select("#text_id"+j).style("opacity",0.2);
                  }
                };
              })
              .on("mouseout", function(d,i) {
                for (j=0; j < 6; j++) {
                  d3.select("#id"+j).style("opacity",1);
                  d3.select("#text_id"+j).style("opacity",1);
                };
              });
    </script>

    <script>
            // chart2 size
            var data2_raw = <?php echo json_encode($SizeDateRangeD3Data); ?>;

            var margin2 = { top: 20, right: 100, bottom: 40, left: 100 };
            var height2 = 300 - margin2.top - margin2.bottom;
            var width2 = 1400 - margin2.left - margin2.right;

            var svg2 = d3.select("#sizechart").append("svg")
                .attr("width",width2 + margin2.left + margin2.right)
                .attr("height",height2 + margin2.top + margin2.bottom)
              .append("g")
                .attr("transform", "translate(" + margin2.left + "," + margin2.top + ")");

            // setup scales - the domain is specified inside of the function called when we load the data
            var xScale2 = d3.time.scale().range([0, width2]);
            var yScale2 = d3.scale.linear().range([height2, 0]);
            var color2 = d3.scale.category10();

            // setup the axes
            var xAxis2 = d3.svg.axis().scale(xScale2).orient("bottom");
            var yAxis2 = d3.svg.axis().scale(yScale2).orient("left");

            // create function to parse dates into date objects
            var parseDate = d3.time.format("%Y-%m-%d").parse;
            var formatDate = d3.time.format("%Y-%m-%d");
            var bisectDate = d3.bisector(function(d) { return d.date; }).left;

            // set the line attributes
            var line2 = d3.svg.line()
              .interpolate("basis")
              .x(function(d) { return xScale2(d.date); })
              .y(function(d) { return yScale2(d.size); });

            // import data and create chart

            var data2 = data2_raw.map(function(d) {
              return {
                date: parseDate(d.date),
                file: +d.file.value / 1024 / 1024,
                directory: +d.directory.value / 1024 / 1024
              };
            });
            
            // sort data ascending - needed to get correct bisector results
            data2.sort(function(a,b) {
              return a.date - b.date;
            });

            // color domain
            //color2.domain(d3.keys(data2[0]).filter(function(key) { return key !== "date"; }));

            // create doctypes array with object for each doctype (file/directory) containing all data
            var doctypes2 = color.domain().map(function(type) {
              return {
                type: type,
                values: data2.map(function(d){
                  return {date: d.date, size: d[type]};
                })
              };
            });

            // add domain ranges to the x and y scales
            xScale2.domain([
              d3.min(doctypes2, function(c) { return d3.min(c.values, function(v) { return v.date; }); }),
              d3.max(doctypes2, function(c) { return d3.max(c.values, function(v) { return v.date; }); })
            ]);
            yScale2.domain([
              0,
              // d3.min(doctypes, function(c) { return d3.min(c.values, function(v) { return v.size; }); }),
              d3.max(doctypes2, function(c) { return d3.max(c.values, function(v) { return v.size; }); })
            ]);

            // add the x axis
            svg2.append("g")
              .attr("class", "x axis")
              .attr("transform", "translate(0," + height2 + ")")
              .call(xAxis2);

            // add the y axis
            svg2.append("g")
                .attr("class", "y axis")
                .call(yAxis2)
              .append("text")
                .attr("transform","rotate(-90)")
                .attr("y",-60)
                .attr("dy",".71em")
                .style("text-anchor","end")
                .text("Modified Size (MB)");
            
            // add the line groups
            var doctype2 = svg2.selectAll(".doctypeXYZ")
                .data(doctypes2)
              .enter().append("g")
                .attr("class","doctypeXYZ");

            // add the doctype count paths
            doctype2.append("path")
              .attr("class","line")
              .attr("id",function(d,i){ return "id2" + i; })
              .attr("d", function(d) {
                return line2(d.values); 
              })
              .style("stroke", function(d) { return color2(d.type); });


            // add the doctype labels at the right edge of chart
            var maxLen = data2.length;
            doctype2.append("text")
              .datum(function(d) { 
                return {type: d.type, value: d.values[maxLen - 1]}; 
              })
              .attr("transform", function(d) { 
                return "translate(" + xScale2(d.value.date) + "," + yScale2(d.value.size) + ")"; 
              })
              .attr("id",function(d,i){ return "text_id2" + i; })
              .attr("x", 3)
              .attr("dy", ".35em")
              .text(function(d) { return d.type; })
              .on("mouseover",function(d,i) { 
                for (j=0; j < 6; j++) {
                  if (i !== j) {
                    d3.select("#id2"+j).style("opacity",0.1);
                    d3.select("#text_id2"+j).style("opacity",0.2);
                  }
                };
              })
              .on("mouseout", function(d,i) {
                for (j=0; j < 6; j++) {
                  d3.select("#id2"+j).style("opacity",1);
                  d3.select("#text_id2"+j).style("opacity",1);
                };
              });
    </script>

	</body>

	</html>
