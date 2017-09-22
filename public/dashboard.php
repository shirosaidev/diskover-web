<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// Connect to Elasticsearch
$client = connectES();

// Get search results from Elasticsearch for tags
$results = [];
$tagCounts = ['untagged' => 0, 'delete' => 0, 'archive' => 0, 'keep' => 0];
$totalFilesize = ['untagged' => 0, 'delete' => 0, 'archive' => 0, 'keep' => 0];

// Setup search query
$searchParams['index'] = Constants::ES_INDEX; // which index to search
$searchParams['type']  = Constants::ES_TYPE;  // which type within the index to search

// Execute the search
foreach ($tagCounts as $tag => $value) {
  $searchParams['body'] = [
     'size' => 0,
     'query' => [
       'match' => [
         'tag' => $tag
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

  // Get total for tag
  $tagCounts[$tag] = $queryResponse['hits']['total'];

  // Get total size of all files with tag
  $totalFilesize[$tag] = $queryResponse['aggregations']['total_size']['value'];

}

// Get search results from Elasticsearch for duplicate files
$results = [];
$searchParams = [];
$totalDupes = 0;
$totalFilesizeDupes = 0;

// Setup search query
$searchParams['index'] = Constants::ES_INDEX; // which index to search
$searchParams['type']  = Constants::ES_TYPE;  // which type within the index to search


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
        'query' => 'is_dupe:true'
      ]
    ]
];
$queryResponse = $client->search($searchParams);

// Get total count of duplicate files
$totalDupes = $queryResponse['hits']['total'];

// Get total size of all duplicate files
$totalFilesizeDupes = $queryResponse['aggregations']['total_size']['value'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>diskover &mdash; Dashboard</title>
  <!--<link rel="stylesheet" href="/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" href="/css/bootstrap-theme.min.css" media="screen" />-->
	<link rel="stylesheet" href="/css/bootswatch.min.css" media="screen" />
  <link rel="stylesheet" href="/css/diskover.css" media="screen" />
	<style>
		.arc text {
			font: 10px sans-serif;
			text-anchor: middle;
		}
		.arc path {
			stroke: #0B0C0E;
		}
	</style>
</head>
<body>
<?php include __DIR__ . "/nav.php"; ?>
<div class="container">
  <div class="row">
    <div class="col-xs-4">
      <img src="/images/diskover.png" class="img-responsive" style="margin-top:30px;" alt="diskover" width="249" height="189" />
    </div>
    <div class="col-xs-8">
      <div class="jumbotron">
        <h1><span class="glyphicon glyphicon-piggy-bank"></span> Space Savings</h1>
        <p>You could save <strong><?php echo formatBytes($totalFilesize['untagged']+$totalFilesize['delete']+$totalFilesize['archive']+$totalFilesize['keep']); ?></strong> of disk space if you delete or archive all your files.
          There are <strong><?php echo $totalDupes; ?></strong> duplicate files taking up <strong><?php echo formatBytes($totalFilesizeDupes); ?></strong> space.</p>
      </div>
    </div>
  </div>
<div class="alert alert-dismissible alert-success">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <strong>Welcome to diskover's web file manager.</strong> This application will help you <a href="/simple.php" class="alert-link">search and tag files</a> in your diskover indices in Elasticsearch.
</div>
<?php
if ($totalFilesize['untagged'] == 0 AND $totalFilesize['delete'] == 0 AND $totalFilesize['archive'] == 0 AND $totalFilesize['keep'] == 0) {
?>
<div class="alert alert-dismissible alert-danger">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <h4><span class="glyphicon glyphicon-alert"></span> No diskover indices found! :(</h4>
  <p>It looks like you haven't crawled any files yet. Crawl some files and come back.</p>
</div>
<?php
}
?>

<?php
if ($totalDupes > 0) {
?>
<div class="alert alert-dismissible alert-danger">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <h4><span class="glyphicon glyphicon-duplicate"></span> Duplicate files!</h4>
  <p>It looks like you have <a href="/advanced.php?submitted=true&amp;p=1&amp;is_dupe=true&amp;sort=filesize&amp;sortorder=desc" class="alert-link">duplicate files</a>, tag the copies for deletion to save space.</p>
</div>
<?php
}
?>
<?php
if ($tagCounts['untagged'] > 0) {
?>
<div class="alert alert-dismissible alert-warning">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <h4><span class="glyphicon glyphicon-tags"></span> Untagged files!</h4>
  <p>It looks like you have <a href="/advanced.php?submitted=true&amp;p=1&amp;tag=untagged" class="alert-link">untagged files</a>, time to start tagging and free up some space :)</p>
</div>
<?php
}
?>
<?php
if ($tagCounts['untagged'] == 0 AND $totalFilesize['delete'] > 0 AND $totalFilesize['archive'] > 0 AND $totalFilesize['keep'] > 0 ) {
?>
<div class="alert alert-dismissible alert-info">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <span class="glyphicon glyphicon-thumbs-up"></span> <strong>Well done!</strong> It looks like all files have been tagged.
</div>
<?php
}
?>
<div class="row">
  <div class="col-xs-4">
    <h3><span class="glyphicon glyphicon-tag"></span> Tag Counts</h3>
    <ul class="nav nav-pills">
      <li><a href="/advanced.php?submitted=true&amp;p=1&amp;tag=untagged">untagged <span class="badge"><?php echo $tagCounts['untagged']; ?></span></a></li>
      <li><a href="/advanced.php?submitted=true&amp;p=1&amp;tag=delete">delete <span class="badge"><?php echo $tagCounts['delete']; ?></span></a></li>
      <li><a href="/advanced.php?submitted=true&amp;p=1&amp;tag=archive">archive <span class="badge"><?php echo $tagCounts['archive']; ?></span></a></li>
      <li><a href="/advanced.php?submitted=true&amp;p=1&amp;tag=keep">keep <span class="badge"><?php echo $tagCounts['keep']; ?></span></a></li>
    </ul>
  </div>
	<div class="col-xs-2">
		<div id="tagcountchart"></div>
	</div>
  <div class="col-xs-4">
    <h3><span class="glyphicon glyphicon-hdd"></span> Total File Sizes</h3>
    <ul class="list-group">
      <li class="list-group-item">
        <span class="badge"><?php echo formatBytes($totalFilesize['untagged']); ?></span>
        untagged
      </li>
      <li class="list-group-item">
        <span class="badge"><?php echo formatBytes($totalFilesize['delete']); ?></span>
        delete
      </li>
      <li class="list-group-item">
        <span class="badge"><?php echo formatBytes($totalFilesize['archive']); ?></span>
        archive
      </li>
      <li class="list-group-item">
        <span class="badge"><?php echo formatBytes($totalFilesize['keep']); ?></span>
        keep
      </li>
    </ul>
	</div>
	<div class="col-xs-2">
	<div id="filesizechart"></div>
	</div>
</div>
</div>
<script language="javascript" src="/js/jquery.min.js"></script>
<script language="javascript" src="/js/bootstrap.min.js"></script>
<script language="javascript" src="/js/diskover.js"></script>
<script language="javascript" src="/js/d3.v3.min.js"></script>
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
</body>
</html>
