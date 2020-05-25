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


// Grab all the smart searches from file
$smartsearches = get_smartsearches();

$totalFilesizeSmartSearches = [];
$totalCountSmartSearches = [];
$SmartSearchesQueries = [];
$totalCountOtherFiles = 0;
$totalFilesizeOtherFiles = 0;
$otherfiles_query = "";

foreach($smartsearches as $key => $value) {
    $totalFilesizeSmartSearches[$value[0]] = 0;
    $totalCountSmartSearches[$value[0]] = 0;
    $SmartSearchesQueries[$value[0]] = $value[1];
    $otherfiles_query .= "NOT (" . $value[1] . ") AND ";
}

// Get search results from Elasticsearch for all files
$results = [];
$searchParams = [];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

// Execute the search
$searchParams['body'] = [
 'size' => 0,
 'query' => [
     'match_all' => (object) []
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

// Get total count of all files
$totalCountOtherFiles = $queryResponse['hits']['total'];

// Get total size of all files
$totalFilesizeOtherFiles = $queryResponse['aggregations']['total_size']['value'];


// Get search results from Elasticsearch for smart searches
$results = [];
$searchParams = [];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'file';

// Execute the search
foreach ($SmartSearchesQueries as $key => $value) {
    $searchParams['body'] = [
     'size' => 0,
     'query' => [
       'query_string' => [
         'query' => $value
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

    // Get total count of all files from smart search es query
    $totalCountSmartSearches[$key] = $queryResponse['hits']['total'];

    // subtract smart search from total
    $totalCountOtherFiles -= $queryResponse['hits']['total'];

    // Get total size of all files from smart search es query
    $totalFilesizeSmartSearches[$key] = $queryResponse['aggregations']['total_size']['value'];

    // subtract smart search from total
    $totalFilesizeOtherFiles -= $queryResponse['aggregations']['total_size']['value'];
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
		<title>diskover &mdash; Smart Searches</title>
		<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="css/diskover.css" media="screen" />
        <link rel="stylesheet" href="css/diskover-smartsearches.css" media="screen" />
        <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container-fluid" id="mainwindow" style="margin-top: 70px;">
            <div class="row">
                <div class="col-xs-12 text-center">
                    <h2>Smart Searches</h2>
                </div>
            </div>
			<div class="row">
                <div class="col-xs-6">
                      <div id="sscountchart" class="text-center"></div>
                      <br /><hr />
                  </div>
                <div class="col-xs-6">
                    <div id="ssfilesizechart" class="text-center"></div>
                    <br /><hr />
              </div>
				</div>
                <div class="row">
                    <div class="col-xs-6">
                        <div class="chartbox text-center">
                            <span class="label" style="font-size:12px;background-color:#666666;"><a href="simple.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;q=<?php echo $otherfiles_query . '_type:file'; ?>">other files <?php echo $totalCountOtherFiles; ?></a></span>
                          <?php foreach($smartsearches as $key => $value) { ?>
                          <span class="label" id="<?php echo $value[0] . '_count' ?>" style="font-size:12px;"><a href="simple.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;q=<?php echo $value[1]; ?>"><?php echo $value[0]; ?> <?php echo $totalCountSmartSearches[$value[0]]; ?></a></span>
                          <?php } ?>
                      </div>
                  </div>
                  <div class="col-xs-6">
                      <div class="chartbox text-center" style="display:absolute;">
                          <span class="label" style="font-size:12px;background-color:#666666;"><a href="simple.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;q=<?php echo $otherfiles_query . '_type:file'; ?>">other files <?php echo formatBytes($totalFilesizeOtherFiles); ?></a></span>
                        <?php foreach($smartsearches as $key => $value) { ?>
                        <span class="label" id="<?php echo $value[0] . '_size' ?>" style="font-size:12px;"><a href="simple.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;q=<?php echo $value[1]; ?>"><?php echo $value[0]; ?> <?php echo formatBytes($totalFilesizeSmartSearches[$value[0]]); ?></a></span>
                        <?php } ?>
                    </div>
                </div>
              </div>
                <div class="row">
                    <div class="col-xs-12">
                        <br />
                        <form class="form-inline" name="showotherfilesform" id="showotherfilesform" method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                            <span style="font-size:11px; color:gray;">Show other files </span><span style="position:relative; top:8px;"><label class="switch"><input onchange="setCookie('showotherfiles', document.getElementById('showotherfiles').checked); $('#showotherfilesform').submit();" id="showotherfiles" name="showotherfiles" type="checkbox" <?php if (getCookie('ssshowotherfiles') === "true") { echo "checked"; } ?>><span class="slider round"></span></label></span>
                            &nbsp;&nbsp;&nbsp;&nbsp;<span><a href="admin.php">Edit smart searches</a></span>
                        </form>
                    </div>
                </div>
			</div>
		<script language="javascript" src="js/jquery.min.js"></script>
		<script language="javascript" src="js/bootstrap.min.js"></script>
		<script language="javascript" src="js/diskover.js"></script>
		<script language="javascript" src="js/d3.v3.min.js"></script>
		<script language="javascript" src="js/d3.tip.v0.6.3.js"></script>

        <!-- show other files toggle -->
            <script>
                if (getCookie('showotherfiles') === "true") {
                    $('#showotherfiles').prop('checked', true);
                }
            </script>
        <!-- d3 charts -->
        	<script>
                var count_otherfiles = <?php echo $totalCountOtherFiles; ?>;
                <?php foreach($smartsearches as $key => $value) {
                ?>
                var count_ss_<?php echo $key ?> = <?php echo $totalCountSmartSearches[$value[0]] ?>;
                <?php } ?>

                var showotherfiles = document.getElementById('showotherfiles').checked;

                var dataset = [];
                if (showotherfiles) {
                    dataset.push({
            			label: 'other files',
            			count: count_otherfiles
            		});
                }

                dataset.push(
                <?php foreach($smartsearches as $key => $value) {
                ?>
                { label: '<?php echo $value[0] ?>', count: <?php echo $totalCountSmartSearches[$value[0]] ?> },
                <?php } ?>
                );

                var totalcount = d3.sum(dataset, function(d) {
                    return d.count;
                });

        		var width = 640;
        		var height = 500;
        		var radius = Math.min(width, height) / 2;

        		var color = d3.scale.category20b();

        		var svg = d3.select("#sscountchart")
        			.append('svg')
        			.attr('width', width)
        			.attr('height', height)
        			.append('g')
        			.attr('transform', 'translate(' + width / 2 + ',' + height / 2 + ')');

                var tip = d3.tip()
                    .attr('class', 'd3-tip')
                    .html(function(d) {
                        var percent = (d.value / totalcount * 100).toFixed(1) + '%';
                        return "<span style='font-size:12px;color:white;'>" + d.data.label + "</span><br><span style='font-size:12px; color:red;'>" + d.value + " (" + percent + ")</span>";
                    });

                svg.call(tip);

                d3.select("#sscountchart").append("div")
                    .attr("class", "tooltip")
                    .style("opacity", 0);

        		var pie = d3.layout.pie()
        			.value(function(d) {
        				return d.count;
        			})
        			.sort(null);

        		var path = d3.svg.arc()
        			.outerRadius(radius - 10)
        			.innerRadius(radius - 70);

        		var label = d3.svg.arc()
        			.outerRadius(radius - 40)
        			.innerRadius(radius - 40);

        		var arc = svg.selectAll('.arc')
        			.data(pie(dataset))
        			.enter().append('g')
        			.attr('class', 'arc');

        		arc.append('path')
        			.attr('d', path)
                    .attr('class', path)
        			.attr('fill', function(d) {
                        if (d.data.label === 'other files') {
                            return '#666666';
                        } else {
                            var bgcolor = color(d.data.label);
                            document.getElementById(d.data.label + '_count').style.backgroundColor = bgcolor;
                            return bgcolor;
                        }
        			})
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
                    });

        		arc.append('text')
        			.attr("transform", function(d) {
        				return "translate(" + label.centroid(d) + ")";
        			})
        			.attr("dy", "0.35em")
                    .style("font-size", "10px")
        			.text(function(d) {
                        if (d.value>0) { return d.data.label };
        			});
        	</script>

        	<script>
                var size_otherfiles = <?php echo $totalFilesizeOtherFiles; ?>;
                <?php foreach($smartsearches as $key => $value) {
                ?>
                var size_ss_<?php echo str_replace('!', '', $value[0]); ?> = <?php echo $totalFilesizeSmartSearches[$value[0]] ?>;
                <?php } ?>

                var showotherfiles = document.getElementById('showotherfiles').checked;

                var dataset = [];
                if (showotherfiles) {
                    dataset.push({
            			label: 'other files',
            			size: size_otherfiles
            		});
                }

                dataset.push(
                <?php foreach($smartsearches as $key => $value) {
                ?>
                { label: '<?php echo $value[0] ?>', size: <?php echo $totalFilesizeSmartSearches[$value[0]] ?> },
                <?php } ?>
                );

                // Bar chart (dupes size)

                var valueLabelWidth = 40; // space reserved for value labels (right)
                var barHeight = 15; // height of one bar
                var barLabelWidth = 200; // space reserved for bar labels
                var barLabelPadding = 10; // padding between bar and bar labels (left)
                var gridChartOffset = 0; // space between start of grid and first bar
                var maxBarWidth = 400; // width of the bar with the max value

                var totalsize = d3.sum(dataset, function(d) {
                    return d.size;
                });

                // svg container element
                var svg = d3.select('#ssfilesizechart').append("svg")
                    .attr('width', maxBarWidth + barLabelWidth + valueLabelWidth + barLabelPadding)
                    .attr('height', '500px');

                //var color = d3.scale.category20b();

                svg.append("g")
                    .attr("class", "bars");
                svg.append("g")
                    .attr("class", "barvaluelabel");
                svg.append("g")
                    .attr("class", "barlabel");

                /* ------- TOOLTIP -------*/

                var tip2 = d3.tip()
                    .attr('class', 'd3-tip')
                    .html(function(d) {
                        var percent = (d.size / totalsize * 100).toFixed(1) + '%';
                        return "<span style='font-size:12px;color:white;'>" + d.label + "</span><br><span style='font-size:12px; color:red;'>size: " + format(d.size) + " (" + percent + ")</span>";
                    });

                svg.call(tip2);

                d3.select("ssfilesizechart").append("div")
                    .attr("class", "tooltip")
                    .style("opacity", 0);

                /* ------- BARS -------*/

                // accessor functions
                var barLabel = function(d) {
                    return d['label'];
                };
                var barValue = function(d) {
                    return d['size'];
                };

                // scales
                var yScale = d3.scale.ordinal().domain(d3.range(0, dataset.length)).rangeBands([0, dataset.length * barHeight]);
                var y = function(d, i) {
                    return yScale(i);
                };
                var yText = function(d, i) {
                    return y(d, i) + yScale.rangeBand() / 2;
                };
                var x = d3.scale.linear().domain([0, d3.max(dataset, barValue)]).range([0, maxBarWidth]);

                // bars
                var bar = svg.select(".bars").selectAll("rect")
                       .data(dataset);
                       //.data(dataset.sort(function(x, y) { return d3.descending(x.size, y.size); }));

                bar.enter().append("rect")
                    .attr('transform', 'translate(' + barLabelWidth + ',' + gridChartOffset + ')')
                    .attr('height', yScale.rangeBand())
                    .attr('y', y)
                    .attr('class', 'bars')
                    .style('fill', function(d) {
                        if (d.label === 'other files') {
                            return '#666666';
                        } else {
                            var bgcolor = color(d.label);
                            document.getElementById(d.label + '_size').style.backgroundColor = bgcolor;
                            return bgcolor;
                        }
        			})
                    .attr('width', function(d) {
                        return x(barValue(d));
                    })
                    .on("mouseover", function(d) {
                        tip2.show(d);
                    })
                    .on('mouseout', function(d) {
                        tip2.hide(d)
                    })
                    .on('mousemove', function() {
                        return tip2
                            .style("top", (d3.event.pageY - 10) + "px")
                            .style("left", (d3.event.pageX + 10) + "px");
                    });


                bar
                    .transition().duration(750)
                    .attr("width", function(d) {
                        return x(barValue(d));
                    });

                bar.exit().remove();

                // bar labels
                var barlabel = svg.select(".barlabel").selectAll('text').data(dataset);

                barlabel.enter().append('text')
                    .attr('transform', 'translate(' + (barLabelWidth - barLabelPadding) + ',' + gridChartOffset + ')')
                    .attr('y', yText)
                    .attr("dy", ".35em") // vertical-align: middle
                    .attr("class", "barlabel")
                    .text(barLabel);

                barlabel.exit().remove();

                // bar value labels
                var barvaluelabel = svg.select(".barvaluelabel").selectAll('text').data(dataset);

                barvaluelabel.enter().append("text")
                    .attr('transform', 'translate(' + barLabelWidth + ',' + gridChartOffset + ')')
                    .attr("dx", 3) // padding-left
                    .attr("dy", ".35em") // vertical-align: middle
                    .attr("class", "barvaluelabel");

                barvaluelabel
                    .attr("x", function(d) {
                        return x(barValue(d));
                    })
                    .attr("y", yText)
                    .text(function(d) {
                        return format(barValue(d));
                    });

                barvaluelabel.exit().remove();

        	</script>
	</body>

	</html>
