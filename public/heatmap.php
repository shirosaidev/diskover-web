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
}
// check for index2 in url
if (isset($_GET['index2'])) {
    $esIndex2 = $_GET['index2'];
    setCookie('index2', $esIndex2);
} else {
    $esIndex2 = getenv('APP_ES_INDEX2') ?: getCookie('index2');
}

// redirect to select indices page if no index or index2
if (!$esIndex || !$esIndex2) {
    header("location:selectindices.php");
    exit();
}

// remove any trailing slash unless root
if (!empty($_GET['path']) && $_GET['path'] !== "/") {
    $path = rtrim($_GET['path'], '/');
}
?>

	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>diskover &mdash; Heatmap</title>
		<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="css/diskover.css" media="screen" />
		<link rel="stylesheet" href="css/diskover-heatmap.css" media="screen" />
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container" id="error" style="display:none; margin-top:70px;">
			<div class="row">
				<div class="alert alert-dismissible alert-danger col-xs-8">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, no files found, all files too small (filtered) or something else bad happened :(</strong> Choose a different path and try again or check browser console and Elasticsearch for errors.
				</div>
			</div>
		</div>
		<div class="container-fluid" id="mainwindow" style="margin-top:70px;">
			<div class="row pull-right">
                <div class="col-xs-12">
                    <div id="path-wrapper" style="display:none;">
                        <span id="path" class="text-success" style="font-size:14px; font-weight:bold;"><?php echo $path; ?></span>
                        <span><a title="<?php echo getParentDir($path); ?>" class="btn btn-primary btn-sm" onclick="window.location.href='heatmap.php?path=<?php echo getParentDir($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;maxdepth=<?php echo $_GET['maxdepth']; ?>';"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</a></span>
                    </div>
                    <div id="heatmapcontrols" class="heatmapcontrols" style="display:none;">
                        <label>Radius </label><input type="range" id="radius" value="25" min="1" max="100" />
                        <label>Blur </label><input type="range" id="blur" value="15" min="1" max="60" />
                        <label>Max </label><input type="range" id="maxs" value="" min="" max="" />
                    </div>
					<div id="buttons-container" style="display:none;">
                        <button type="submit" id="reload" class="btn btn-default btn-sm" title="reload"> <i class="glyphicon glyphicon-refresh"></i></button>
						<div class="btn-group" data-toggle="buttons">
							<button class="btn btn-default btn-sm" id="size"> Size</button>
							<button class="btn btn-default btn-sm" id="count"> Count</button>
                        </div>
                            <span style="font-size:11px; color:gray;">Maxdepth:</span>
                            <div class="btn-group">
                                <button class="btn btn-default btn-sm" id="depth1">1</button>
                                <button class="btn btn-default btn-sm" id="depth2">2</button>
                                <button class="btn btn-default btn-sm" id="depth3">3</button>
                                <button class="btn btn-default btn-sm" id="depth4">4</button>
                                <button class="btn btn-default btn-sm" id="depth5">5</button>
                            </div>
                            <span style="font-size:10px; color:gray;">*filters on filetree page affect this page</span>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12">
                    <div style="position:relative; display:none;" id="heatmap-wrapper">
                        <canvas id="heatmap-overlay" style="position:absolute;top:10px;"></canvas>
					    <div id="heatmap-container" style="z-index:0;position:absolute;top:0px;left:0px;"></div>
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
        <script language="javascript" src="js/simpleheat.js"></script>
		<script language="javascript" src="js/heatmap.js"></script>
	</body>

	</html>
