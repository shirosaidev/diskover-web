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


// Grab all the custom tags from file
$customtags = get_custom_tags();

// Get search results from Elasticsearch for tags
$results = [];
$searchParams = [];

$totalFilesize = ['untagged' => 0, 'delete' => 0, 'archive' => 0, 'keep' => 0];

$totalFilesizeCustom = [];
foreach($customtags as $key => $value) {
    $totalFilesizeCustom[$value[0]] = 0;
}

$searchParams['index'] = $esIndex;

// determine doc type based on toggle switches
if ($_GET['toggleonly'] === "showdirectories") {
    $doctype = 'directory';
    createCookie('tagsshowdirectories', 'true');
    createCookie('tagsshowfiles', 'false');
    createCookie('tagsshowall', 'false');
}
if ($_GET['toggleonly'] === "showfiles") {
    $doctype = 'file';
    createCookie('tagsshowfiles', 'true');
    createCookie('tagsshowdirectories', 'false');
    createCookie('tagsshowall', 'false');
}
if ($_GET['toggleonly'] === "showall") {
    $doctype = 'file,directory';
    createCookie('tagsshowall', 'true');
    createCookie('tagsshowfiles', 'false');
    createCookie('tagsshowdirectories', 'false');
}
if (!isset($_GET)) {
    $doctype = 'file,directory';
    createCookie('tagsshowall', 'true');
    createCookie('tagsshowfiles', 'false');
    createCookie('tagsshowdirectories', 'false');
}
$searchParams['type']  = $doctype;

// grab all the file and directory sizes

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
}
// Execute the search
foreach ($totalFilesizeCustom as $tag => $value) {
    $searchParams['body'] = [
       'size' => 0,
       'query' => [
         'match' => [
           'tag_custom' => $tag
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

    // Get total size of all files with tag_custom
    $totalFilesizeCustom[$tag] = $queryResponse['aggregations']['total_size']['value'];
}

// grab the tag counts for both file and directory
$results = [];
$searchParams = [];
$tagCounts = ['untagged' => 0, 'delete' => 0, 'archive' => 0, 'keep' => 0];

$tagCountsCustom = [];
foreach($customtags as $key => $value) {
    $tagCountsCustom[$value[0]] = 0;
}


// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = $doctype;

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
// Execute the search
foreach ($tagCountsCustom as $tag => $value) {
    $searchParams['body'] = [
       'size' => 0,
       'query' => [
         'match' => [
           'tag_custom' => $tag
         ]
       ]
    ];

    // Send search query to Elasticsearch
    $queryResponse = $client->search($searchParams);

    // Get total for tag
    $tagCountsCustom[$tag] = $queryResponse['hits']['total'];
}
?>

	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>diskover &mdash; Tags</title>
		<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="css/diskover.css" media="screen" />
        <link rel="stylesheet" href="css/diskover-tags.css" media="screen" />
        <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container-fluid" id="mainwindow" style="margin-top: 70px;">
            <div class="row">
                <div class="col-xs-12 text-center">
                    <h2>Tags</h2>
                </div>
            </div>
			<div class="row">
                <div class="col-xs-6">
                      <div id="tagcountchart" class="text-center"></div>
                      <br /><hr />
                  </div>
                <div class="col-xs-6">
                    <div id="filesizechart" class="text-center"></div>
                    <br /><hr />
              </div>
				</div>
                <div class="row">
                    <div class="col-xs-6">
                        <div class="chartbox text-center">
                          <span class="label" style="font-size:16px;background-color:#666666"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=">untagged <?php echo $tagCounts['untagged']; ?></a></span>
                          <span class="label" style="font-size:16px;background-color:#F69327"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=delete">delete <?php echo $tagCounts['delete']; ?></a></span>
                          <span class="label" style="font-size:16px;background-color:#65C165"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=archive">archive <?php echo $tagCounts['archive']; ?></a></span>
                          <span class="label" style="font-size:16px;background-color:#52A3BB"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=keep">keep <?php echo $tagCounts['keep']; ?></a></span>
                          <br /><br />
                          <?php foreach($customtags as $key => $value) { ?>
                          <span class="label" style="font-size:12px;background-color:<?php echo $value[1]; ?>"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag_custom=<?php echo rawurlencode($value[0]) ?>"><?php echo $value[0]; ?> <?php echo $tagCountsCustom[$value[0]]; ?></a></span>
                          <?php } ?>
                      </div>
                  </div>
                  <div class="col-xs-6">
                      <div class="chartbox text-center" style="display:absolute;">
                        <span class="label" style="font-size:16px;background-color:#666666;"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=">untagged <?php echo formatBytes($totalFilesize['untagged']); ?></a></span>
                        <span class="label" style="font-size:16px;background-color:#F69327"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=delete">delete <?php echo formatBytes($totalFilesize['delete']); ?></a></span>
                        <span class="label" style="font-size:16px;background-color:#65C165"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=archive">archive <?php echo formatBytes($totalFilesize['archive']); ?></a></span>
                        <span class="label" style="font-size:16px;background-color:#52A3BB"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag=keep">keep <?php echo formatBytes($totalFilesize['keep']); ?></a></span>
                        <br /><br />
                        <?php foreach($customtags as $key => $value) { ?>
                        <span class="label" style="font-size:12px;background-color:<?php echo $value[1]; ?>"><a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>&amp;submitted=true&amp;p=1&amp;tag_custom=<?php echo rawurlencode($value[0]) ?>"><?php echo $value[0]; ?> <?php echo formatBytes($totalFilesizeCustom[$value[0]]); ?></a></span>
                        <?php } ?>
                    </div>
                </div>
            </div>
                <div class="row">
                    <div class="col-xs-12">
                        <br />
                        <form class="form-inline" style="display:inline-block" name="toggleform" id="toggleform" method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                <span style="font-size:11px; color:gray;">Show untagged </span><span style="position:relative; top:8px;"><label class="switch"><input onchange="setCookie('tagsshowuntagged', document.getElementById('showuntagged').checked); $('#toggleform').submit();" id="showuntagged" name="showuntagged" type="checkbox"><span class="slider round"></span></label></span>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <span style="font-size:11px; color:gray;">Show files only </span><span style="position:relative; top:8px;"><label class="switch"><input onchange="setCookie('tagsshowfiles', document.getElementById('showfiles').checked); $('#toggleform').submit();" id="showfiles" name="toggleonly" value="showfiles" type="radio"><span class="slider round"></span></label></span>
                            &nbsp;
                            <span style="font-size:11px; color:gray;">Show directories only </span><span style="position:relative; top:8px;"><label class="switch"><input onchange="setCookie('tagsshowdirectories', document.getElementById('showdirectories').checked); $('#toggleform').submit();" id="showdirectories" name="toggleonly" value="showdirectories" type="radio"><span class="slider round"></span></label></span>
                            &nbsp;
                            <span style="font-size:11px; color:gray;">Show all </span><span style="position:relative; top:8px;"><label class="switch"><input onchange="setCookie('tagsshowall', document.getElementById('showall').checked); $('#toggleform').submit();" id="showall" name="toggleonly" value="showall" type="radio"><span class="slider round"></span></label></span>
                        </form>
                        &nbsp;&nbsp;&nbsp;&nbsp;<span><a href="admin.php">Edit custom tags</a></span>
                    </div>
                </div>
			</div>
		<script language="javascript" src="js/jquery.min.js"></script>
		<script language="javascript" src="js/bootstrap.min.js"></script>
		<script language="javascript" src="js/diskover.js"></script>
		<script language="javascript" src="js/d3.v3.min.js"></script>
		<script language="javascript" src="js/d3.tip.v0.6.3.js"></script>

        <!-- button toggle script -->
        <script>
            if (getCookie('tagsshowuntagged') === "true") {
                $('#showuntagged').prop('checked', true);
            }
            if (getCookie('tagsshowfiles') === "true") {
                $('#showfiles').prop('checked', true);
            }
            if (getCookie('tagsshowdirectories') === "true") {
                $('#showdirectories').prop('checked', true);
            }
            if (getCookie('tagsshowall') === "true") {
                $('#showall').prop('checked', true);
            }
        </script>

        <!-- d3 charts -->
    	<script>
            var count_untagged = <?php echo $tagCounts['untagged'] ?>;
    		var count_delete = <?php echo $tagCounts['delete'] ?>;
    		var count_archive = <?php echo $tagCounts['archive'] ?>;
    		var count_keep = <?php echo $tagCounts['keep'] ?>;
            <?php foreach($customtags as $key => $value) {
            ?>
            var count_custom_<?php echo $key ?> = <?php echo $tagCountsCustom[$value[0]] ?>;
            <?php } ?>

            var showuntagged = document.getElementById('showuntagged').checked;

            var dataset = [];
            if (showuntagged) {
                dataset.push({
        			label: 'untagged',
        			count: count_untagged
        		});
            }

            dataset.push({
    			label: 'delete',
    			count: count_delete
    		}, {
    			label: 'archive',
    			count: count_archive
    		}, {
    			label: 'keep',
    			count: count_keep
    		}
            <?php foreach($customtags as $key => $value) {
            ?>
            , { label: '<?php echo $value[0] ?>', count: <?php echo $tagCountsCustom[$value[0]] ?> }
            <?php } ?>
            );

            var totalcount = d3.sum(dataset, function(d) {
                return d.count;
            });

    		var width = 720;
    		var height = 500;
    		var radius = Math.min(width, height) / 2;

            var color_range = [];

            if (showuntagged) {
                color_range.push("#666666");
            }

            color_range.push(
                "#F69327",
                "#65C165",
                "#52A3BB"
            <?php foreach($customtags as $key => $value) { ?>
            <?php echo ", \"".$value[1]."\"" ?>
            <?php } ?>
            );

    		var color = d3.scale.ordinal()
    			.range(color_range);

    		var svg = d3.select("#tagcountchart")
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

            d3.select("#tagcountchart").append("div")
                .attr("class", "tooltip")
                .style("opacity", 0);

    		var pie = d3.layout.pie()
    			.value(function(d) {
    				return d.count;
    			})
    			.sort(null);

    		var path = d3.svg.arc()
    			.outerRadius(radius - 10)
    			.innerRadius(0);

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
    				return color(d.data.label);
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
            // Bar chart (dupes size)
            var size_untagged = <?php echo $totalFilesize['untagged'] ?>;
            var size_delete = <?php echo $totalFilesize['delete'] ?>;
            var size_archive = <?php echo $totalFilesize['archive'] ?>;
            var size_keep = <?php echo $totalFilesize['keep'] ?>;
            <?php foreach($customtags as $key => $value) {
            ?>
            var size_custom_<?php echo $key ?> = <?php echo $totalFilesizeCustom[$value[0]] ?>;
            <?php } ?>

            var showuntagged = document.getElementById('showuntagged').checked;

            var dataset = [];
            if (showuntagged) {
                dataset.push({
                    label: 'untagged',
                    size: size_untagged
                });
            }

            dataset.push({
                label: 'delete',
                size: size_delete
            }, {
                label: 'archive',
                size: size_archive
            }, {
                label: 'keep',
                size: size_keep
            }
            <?php foreach($customtags as $key => $value) {
            ?>
            , { label: '<?php echo $value[0] ?>', size: <?php echo $totalFilesizeCustom[$value[0]] ?> }
            <?php } ?>
            );

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
            var svg = d3.select('#filesizechart').append("svg")
                .attr('width', maxBarWidth + barLabelWidth + valueLabelWidth);

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

            d3.select("filesizechart").append("div")
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
    				return color(d.label);
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
