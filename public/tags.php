<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Diskover.php";

// check for index in url
if (isset($_GET['index'])) {
    $esIndex = $_GET['index'];
    setCookie('index', $esIndex);
} else {
    // get index from env var or cookie
    $esIndex = getenv('APP_ES_INDEX') ?: getCookie('index');
    // redirect to select indices page if no index cookie
    if (!$esIndex) {
        header("location:selectindices.php");
        exit();
    }
}

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

$results = [];
$searchParams = [];
$tagCounts = ['untagged' => 0, 'delete' => 0, 'archive' => 0, 'keep' => 0];

$tagCountsCustom = [];
foreach($customtags as $key => $value) {
    $tagCountsCustom[$value[0]] = 0;
}


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
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container-fluid" id="mainwindow" style="margin-top: 70px;">
            <div class="row">
                <div class="col-xs-12 text-center">
                    <h1>Tags</h1>
                </div>
            </div>
			<div class="row">
                <div class="col-xs-6">
                      <div id="tagcountchart" class="text-center"></div>
                      <br /><hr />
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
                    <div id="filesizechart" class="text-center"></div>
                    <br /><hr />
                    <div class="chartbox text-center">
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
                        <br /><br />
                        <form class="form-inline" name="showuntaggedform" id="showuntaggedform" method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                            <div class="checkbox">
                                <label><input onchange="$('#showuntaggedform').submit();" type="checkbox" id="showuntagged" name="showuntagged" <?php if ($_GET['showuntagged'] == "on") { echo "checked"; } ?>> show untagged</label>
                            </div>
                        </form>
                    </div>
                </div>
			</div>
		<script language="javascript" src="js/jquery.min.js"></script>
		<script language="javascript" src="js/bootstrap.min.js"></script>
		<script language="javascript" src="js/diskover.js"></script>
		<script language="javascript" src="js/d3.v3.min.js"></script>
		<script language="javascript" src="js/d3.tip.v0.6.3.js"></script>

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

        		arc.append('text')
        			.attr("transform", function(d) {
        				return "translate(" + label.centroid(d) + ")";
        			})
        			.attr("dy", "0.35em")
                    .style("font-size", "10px")
        			.text(function(d) {
        				return d.data.label;
        			});
        	</script>

        	<script>
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

                var totalsize = d3.sum(dataset, function(d) {
                    return d.size;
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

                var svg = d3.select("#filesizechart")
        			.append('svg')
        			.attr('width', width)
        			.attr('height', height)
        			.append('g')
        			.attr('transform', 'translate(' + width / 2 + ',' + height / 2 + ')');

                var tip2 = d3.tip()
                    .attr('class', 'd3-tip')
                    .html(function(d) {
                        var percent = (d.value / totalsize * 100).toFixed(1) + '%';
                        return "<span style='font-size:12px;color:white;'>" + d.data.label + "</span><br><span style='font-size:12px; color:red;'>" + format(d.value) + " (" + percent + ")</span>";
                    });

                svg.call(tip2);

                d3.select("#filesizechart").append("div")
                    .attr("class", "tooltip")
                    .style("opacity", 0);

        		var pie = d3.layout.pie()
        			.value(function(d) {
        				return d.size;
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
        			.attr('fill', function(d) {
        				return color(d.data.label);
        			})
                    .on("mouseover", function(d) {
                        tip2.show(d);
                    })
                    .on("mouseout", function(d) {
                        tip2.hide(d);
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

        		arc.append('text')
        			.attr("transform", function(d) {
        				return "translate(" + label.centroid(d) + ")";
        			})
        			.attr("dy", "0.35em")
                    .style("font-size", "10px")
        			.text(function(d) {
        				return d.data.label;
        			});
        	</script>
	</body>

	</html>
