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
$crawlfinished = (sizeof($queryResponse['hits']['hits']) > 0) ? true : false;

// get first crawl index time
$searchParams['type']  = 'directory,file';
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

// get total crawl elapsed time (cumulative)
$searchParams['type']  = 'directory';
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

// Get total elapsed time (in seconds) of crawl(s)
$crawlelapsedtime = $queryResponse['aggregations']['total_elapsed']['value'];

?>

	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>diskover &mdash; Crawl Stats</title>
		<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="css/diskover.css" media="screen" />
        <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
        <style>
            #crawlstatschart1 {
                top: 10px;
                position: relative;
            }
            #crawlstatschart2 {
                top: 10px;
                position: relative;
            }
            #crawlstatschart1 svg {
                width: 1400px;
                height: 600px;
            }
            #crawlstatschart2 svg {
                width: 1400px;
                height: 200px;
            }
            .stack rect {
                opacity: .8
            }
            .stack rect:hover {
                opacity: .7
            }

            circle {
                opacity: .8;
            }
            circle:hover {
                opacity: .7;
            }

            text {
                fill: gray;
                font-size: 10px;
                pointer-events: none;
                text-anchor: middle;
            }

            .bubble {
                fill: lightgray;
                font-size: 10px;
                pointer-events: none;
                text-anchor: middle;
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
              stroke: steelblue;
              pointer-events: none;
              shape-rendering: crispEdges;
              opacity: .6;
            }
        </style>
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container-fluid" id="mainwindow" style="margin-top: 70px;">
            <div class="row">
                <div class="col-xs-12 text-center">
                    <h2>Crawl Stats</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 text-right">
                    <span style="margin-right:30px;"><small><i class="glyphicon glyphicon-repeat"></i> Auto refresh <a href="#_self" id="autorefresh_on" onclick="autorefresh(3000);">on</a> <a href="#_self" id="autorefresh_off" onclick="autorefresh(0);">off</a></small></span>
                </div>
            </div>
			<div class="row">
                <div class="col-xs-12">
                      <div id="crawlstatschart-container" class="text-center">
                          <div id="crawlstatschart2" class="text-center"></div>
                          <div id="crawlstatschart1" class="text-center"></div>
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

        // global data vars for d3
        var data;
        var sizes; // x
        var items; // y
        var crawltimes; // r
        var dirnames; // text bubble
        var paths; // text tip

        var indexname = '<?php echo $esIndex; ?>';

        // init d3 charts

        var margin = {top: 40, right: 20, bottom: 300, left: 70},
        width = 1400 - margin.left - margin.right,
        height = 600 - margin.top - margin.bottom;

        var svg = d3.select("#crawlstatschart1").append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g")
            .attr("transform",
                  "translate(" + margin.left + "," + margin.top + ")");

        var margin2 = {top: 30, right: 20, bottom: 20, left: 70},
        width2 = 1400 - margin2.left - margin2.right,
        height2 = 200 - margin2.top - margin2.bottom;

        var svg2 = d3.select("#crawlstatschart2").append("svg")
            .attr("width", width2 + margin2.left + margin2.right)
            .attr("height", height2 + margin2.top + margin2.bottom)
            .append("g")
            .attr("transform",
                "translate(" + margin2.left + "," + margin2.top + ")");


        function getjsondata(refreshcharts) {
            // config references
            var chartConfig = {
                target: 'mainwindow',
                data_url: 'd3_data_crawlstats.php?index=' + indexname
            };

            // loader settings
            var opts = {
                lines: 12, // The number of lines to draw
                length: 6, // The length of each line
                width: 3, // The line thickness
                radius: 7, // The radius of the inner circle
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
                data = dataset.slowestcrawlers;
                sizes = dataset.sizes; // x
                items = dataset.items; // y
                crawltimes = dataset.crawltimes; // r
                dirnames = dataset.dirnames; // text bubble
                paths = dataset.paths; // text tip

                if (refreshcharts === false) {
                    // stop spin.js loader
                    spinner.stop();
                } else {
                    svg.selectAll("*").remove();
                    svg2.selectAll("*").remove();
                }

                // load charts
                loadchart1()
                loadchart2()
            });
        }

        function loadchart1() {
            // bar stack
            var xData = ['crawltime']; // stack

            var x = d3.scale.ordinal()
                .rangeRoundBands([0, width], .35);

            var y = d3.scale.linear()
                .rangeRound([height, 0]);

            var color = d3.scale.category20c();
            var color2 = d3.scale.category20b();

            var xAxis = d3.svg.axis()
                    .scale(x)
                    .orient("bottom");

            var yAxis = d3.svg.axis()
                .scale(y)
                .orient("left")
                .ticks(10);

            var dataIntermediate=xData.map(function (c){
                return data.map(function(d) {
                    return {x: d.path, y: d[c], items: d.items, filesize: d.filesize};
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
                .data(dataStackLayout);

            layer
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
                    if (d3.event.pageY > window.innerHeight - 50) {
                        // change tip for bottom of screen
                        return tip
                            .style("top", (d3.event.pageY - 40) + "px")
                            .style("left", (d3.event.pageX + 10) + "px");
                    } else if (d3.event.pageX > window.innerWidth - 200) {
                        // change tip for right side of screen
                        return tip
                            .style("top", (d3.event.pageY + 10) + "px")
                            .style("left", (d3.event.pageX - 200) + "px");
                    } else {
                        return tip
                            .style("top", (d3.event.pageY - 10) + "px")
                            .style("left", (d3.event.pageX + 10) + "px");
                    }
                });

            layer.transition().duration(250)
                .attr("y", function (d, i) {
                    return height - y(d.y + d.y0);
                })
                .attr("height", function (d) {
                    return y(d.y0) - y(d.y + d.y0);
                });

            layer.exit()
                .remove()

            // line
            var line = d3.svg.line()
                .x(function (d,i) {
                    return x(paths[i]);
                })
                .y(function (d,i) {
                    return y(crawltimes[i]) - 20;
                })
                .interpolate("bundle");

            svg.append('path')
                .datum(crawltimes)
                .attr("d", line)
                .attr("class", "line")
                .attr("fill", "none")
                .attr("stroke", "steelblue")
                .attr("stroke-linejoin", "round")
                .attr("stroke-linecap", "round")
                .attr("stroke-width", 1.5);

            // axis
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
                  .text("Crawl time (sec)");

            // scatterplot
            var x2 = d3.scale.linear()
                .domain([d3.min(crawltimes), d3.max(crawltimes)])
                .range([width-20, 20 ]);
            var y2 = d3.scale.linear()
              .domain([d3.min(items), d3.max(items)])
              .range([ height-20, 20 ]);
            var r = d3.scale.linear()
              .domain([d3.min(items), d3.max(items)])
              .range([5, 35]);

            var g = svg.append("svg:g")

            g.selectAll('scatterplot')
                .data(items)
                .enter().append("svg:circle")
                .attr("cy", function (d,i) { return y2(d); } )
                .attr("cx", function (d,i) { return x2(crawltimes[i]); } )
                .attr("r", function(d,i){ return r(items[i]);})
                .style("fill", function(d, i){return color2(i);})
                .on("mouseover", function(d,i) {
                    tip2.show(i);
                })
                .on("mouseout", function(d,i) {
                    tip2.hide(i);
                })
                .on('mousemove', function() {
                    if (d3.event.pageY > window.innerHeight - 50) {
                        // change tip for bottom of screen
                        return tip2
                            .style("top", (d3.event.pageY - 40) + "px")
                            .style("left", (d3.event.pageX + 10) + "px");
                    } else if (d3.event.pageX > window.innerWidth - 200) {
                        // change tip for right side of screen
                        return tip2
                            .style("top", (d3.event.pageY + 10) + "px")
                            .style("left", (d3.event.pageX - 200) + "px");
                    } else {
                        return tip2
                            .style("top", (d3.event.pageY - 10) + "px")
                            .style("left", (d3.event.pageX + 10) + "px");
                    }
                });

            g.selectAll('scatterplot')
                .data(items)
                .enter().append("text")
                .attr('class', 'bubble')
                .attr("y", function (d,i) { return y2(d); })
                .attr("x", function (d,i) { return x2(crawltimes[i]); })
                .attr("dx", function(d,i){ return -r(items[i]);})
                .text(function(d, i){return dirnames[i];});

            // tooltips
            var tip = d3.tip()
                .attr('class', 'd3-tip')
                .html(function(d) {
                    return "<span style='font-size:12px;color:white;'>" + d.x + "</span><br>\
                    <span style='font-size:12px; color:red;'>crawl time: " + d3.round(d.y * 100 / 100, 3) + " sec</span><br>\
                    <span style='font-size:12px; color:red;'>items: " + d.items + " ("+format(d.filesize)+")</span>";
                });

            svg.call(tip);

            var tip2 = d3.tip()
                .attr('class', 'd3-tip')
                .html(function(d) {
                    return "<span style='font-size:12px;color:white;'>" + paths[d] + "</span><br>\
                    <span style='font-size:12px; color:red;'>crawl time: " + d3.round(crawltimes[d] * 100 / 100, 3) + " sec</span><br>\
                    <span style='font-size:12px; color:red;'>items: " + items[d] + " ("+format(sizes[d])+")</span>";
                });

            svg.call(tip2);

            d3.select("#crawlstatschart1").append("div")
                .attr("class", "tooltip")
                .style("opacity", 0);
        }

        function loadchart2() {

            // bar stack
            var xData = ['filecount','directorycount']; // stack

            var x = d3.scale.ordinal()
                .rangeRoundBands([0, width2], .35);

            var y = d3.scale.linear()
                .rangeRound([height2, 0]);

            var color = d3.scale.category20b();
            var color2 = d3.scale.category20();

            var xAxis = d3.svg.axis()
                    .scale(x)
                    .orient("bottom");

            var yAxis = d3.svg.axis()
                .scale(y)
                .orient("left")
                .ticks(10);

            var dataIntermediate=xData.map(function (c){
                return data.map(function(d) {
                    return {x: d.path, y: d[c], items: d.items, filesize: d.filesize};
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

            var layer = svg2.selectAll(".stack")
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
                    if (d3.event.pageY > window.innerHeight - 50) {
                        // change tip for bottom of screen
                        return tip
                            .style("top", (d3.event.pageY - 40) + "px")
                            .style("left", (d3.event.pageX + 10) + "px");
                    } else if (d3.event.pageX > window.innerWidth - 200) {
                        // change tip for right side of screen
                        return tip
                            .style("top", (d3.event.pageY + 10) + "px")
                            .style("left", (d3.event.pageX - 200) + "px");
                    } else {
                        return tip
                            .style("top", (d3.event.pageY - 10) + "px")
                            .style("left", (d3.event.pageX + 10) + "px");
                    }
                });

            // line
            /*var line = d3.svg.line()
                .x(function (d,i) {
                    return x(paths[i]);
                })
                .y(function (d,i) {
                    return y(crawltimes[i]) - 20;
                })
                .interpolate("bundle");

            svg2.append('path')
                .datum(crawltimes)
                .attr("d", line)
                .attr("class", "line")
                .attr("fill", "none")
                .attr("stroke", "steelblue")
                .attr("stroke-linejoin", "round")
                .attr("stroke-linecap", "round")
                .attr("stroke-width", 1.5);*/

            // axis
            svg2.append("g")
                .attr("class", "axis")
                .attr("transform", "translate(0," + height2 + ")")
                .call(xAxis)
                .selectAll("text")
                  .attr("transform", "rotate(-90)")
                  .attr("y", 0)
                  .attr("x", 0)
                  .attr("dx", "-.8em")
                  .attr("dy", ".15em")
                  .style("text-anchor", "end")
                  .style("opacity", 0);

            svg2.append("g")
                .attr("class", "axis")
                .attr("transform", "translate(0,0)")
                .call(yAxis)
                .append("text")
                  .attr("transform", "rotate(-90)")
                  .attr("y", 0)
                  .attr("x", 0)
                  .attr("dy", ".71em")
                  .style("text-anchor", "end")
                  .text("Items (file/dir)");

            // tooltips
            var tip = d3.tip()
                .attr('class', 'd3-tip')
                .html(function(d) {
                    var t = (d.y0===0) ? "files" : "dirs"
                    return "<span style='font-size:12px;color:white;'>" + d.x + "</span><br>\
                    <span style='font-size:12px; color:red;'>"+t+": " + d.y + "</span><br>\
                    <span style='font-size:12px; color:red;'>items: " + d.items + " ("+format(d.filesize)+")</span>";
                });

            svg2.call(tip);

            d3.select("#crawlstatschart2").append("div")
                .attr("class", "tooltip")
                .style("opacity", 0);

        }

        // auto refresh crawl stats charts
        var crawlfinished = '<?php echo $crawlfinished ? "true" : "false"; ?>';
        // load d3 data
        getjsondata(false);
        // auto refresh
        var auto_refresh;
        if (crawlfinished === 'false') {
            autorefresh(3000);
        } else {  // crewl is finished so disable interval
            autorefresh(0);
        }
        function autorefresh(worker_refreshtime) {
            if (worker_refreshtime == 0) {
                clearInterval(auto_refresh);
                $('#autorefresh_off').attr('style', 'color: #33A0D4 !important');
                $('#autorefresh_on').attr('style', 'color: #FFF !important');
            } else {
                auto_refresh = setInterval(
                    function () {
                        //d3.selectAll(".d3-tip").remove();
                        // fetch new d3 data
                        getjsondata(true);
                    }, worker_refreshtime); // refresh every 3 sec
                    $('#autorefresh_on').attr('style', 'color: #33A0D4 !important');
                    $('#autorefresh_off').attr('style', 'color: #FFF !important');
            }
        };
        </script>
	</body>

	</html>
