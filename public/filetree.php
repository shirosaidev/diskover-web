<?php
require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

if (!empty($_GET['path'])) {
  $path = $_GET['path'];
	// remove any trailing slash unless root
	if ($path != "/") {
  	$path = rtrim($path, '/');
	}
}
?>

	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>diskover &mdash; File Tree</title>
		<!--<link rel="stylesheet" href="/css/bootstrap.min.css" media="screen" />
		<link rel="stylesheet" href="/css/bootstrap-theme.min.css" media="screen" />-->
		<link rel="stylesheet" href="/css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="/css/diskover.css" media="screen" />
		<link rel="stylesheet" href="/css/diskover-filetree.css" media="screen" />
	</head>

	<body>
		<?php include __DIR__ . "/nav.php"; ?>
		<div class="container" id="error" style="display:none;">
			<div class="row">
				<div class="alert alert-dismissible alert-warning col-xs-8">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, no files found, all files too small (filtered) or something else bad happened :(</strong> Choose a different path and try again or check browser console and Elasticsearch for errors.
				</div>
			</div>
		</div>
		<div class="container-fluid" id="mainwindow">
			<div class="row">
				<div class="col-xs-4">
						<form class="form-inline" id="path-container" style="display:none;">
							<div class="form-group">
								<input type="text" size="50" class="form-control input-sm" name="path" id="path" value="">
							</div>
							<button type="submit" id="submit" class="btn btn-primary btn-sm">Go</button>
						</form>
					<div class="row">
						<div class="buttons-container" id="buttons-container" style="display:none;">
							<div class="btn-group">
								<button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="dropdown">Size Filter
        <span class="caret"></span></button>
								<ul class="dropdown-menu">
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=1024&amp;mtime=<?php echo $_GET['mtime']; ?>">>1 KB</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=262144&amp;mtime=<?php echo $_GET['mtime']; ?>">>256 KB</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=524288&amp;mtime=<?php echo $_GET['mtime']; ?>">>512 KB</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=1048576&amp;mtime=<?php echo $_GET['mtime']; ?>">>1 MB (default)</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=2097152&amp;mtime=<?php echo $_GET['mtime']; ?>">>2 MB</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=5242880&amp;mtime=<?php echo $_GET['mtime']; ?>">>5 MB</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=10485760&amp;mtime=<?php echo $_GET['mtime']; ?>">>10 MB</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=26214400&amp;mtime=<?php echo $_GET['mtime']; ?>">>25 MB</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=52428800&amp;mtime=<?php echo $_GET['mtime']; ?>">>50 MB</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&filter=104857600&amp;mtime=<?php echo $_GET['mtime']; ?>">>100 MB</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=1073741824&amp;mtime=<?php echo $_GET['mtime']; ?>">>1 GB</a></li>
								</ul>
							</div>
							<div class="btn-group">
								<button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="dropdown">Mtime Filter
        <span class="caret"></span></button>
								<ul class="dropdown-menu">
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=0">0 (default)</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=1m">>1 month</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=3m">>3 months</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=6m">>6 months</a></li>
									<li><a href="/filetree.php?path=<?php echo $_GET['path']; ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=1y">>1 year</a></li>
								</ul>
							</div>
							<button type="submit" id="reload" class="btn btn-default btn-sm" title="reload"><i class="glyphicon glyphicon-refresh"></i> </button>
						</div>
					</div>
					<div class="row" id="tree-container"></div>
				</div>
				<div class="col-xs-8" id="chart-container" style="display:none;">
					<div class="row">
						<div class="col-xs-4 col-xs-offset-8">
							<div id="chart-buttons" class="pull-right">
								<div class="btn-group">
									<button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="dropdown">Hide Thresh
        <span class="caret"></span></button>
									<ul class="dropdown-menu">
										<li><a href="#_self" onclick="changeThreshold(0.1);">0.1</a></li>
										<li><a href="#_self" onclick="changeThreshold(0.3);">0.3</a></li>
										<li><a href="#_self" onclick="changeThreshold(0.5);">0.5</a></li>
										<li><a href="#_self" onclick="changeThreshold(0.7);">0.7</a></li>
										<li><a href="#_self" onclick="changeThreshold(0.9);">0.9 (default)</a></li>
										<li><a href="#_self" onclick="changeThreshold(1.0);">1.0</a></li>
									</ul>
								</div>
								<div class="btn-group" data-toggle="buttons">
									<button class="btn btn-default btn-sm" id="size"> Size</button>
									<button class="btn btn-default btn-sm" id="count"> Count</button>
								</div>
								<div id="statustext" class="statustext">
									<span id="statusfilters">
									</span><span id="statushidethresh">
									</span>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div id="piechart"></div>
					</div>
				</div>
			</div>
		</div>

		<script language="javascript" src="/js/jquery.min.js"></script>
		<script language="javascript" src="/js/bootstrap.min.js"></script>
		<script language="javascript" src="/js/diskover.js"></script>
		<script language="javascript" src="/js/d3.v3.min.js"></script>
		<script language="javascript" src="/js/spin.min.js"></script>
		<script language="javascript" src="/js/d3.tip.v0.6.3.js"></script>
		<script language="javascript" src="/js/pie.js"></script>
		<script language="javascript" src="/js/treelist.js"></script>
		<script language="javascript" src="/js/filetree.js"></script>
	</body>

	</html>
