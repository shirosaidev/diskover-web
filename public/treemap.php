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

?>

	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>diskover &mdash; Treemap</title>
		<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="css/diskover.css" media="screen" />
		<link rel="stylesheet" href="css/diskover-treemap.css" media="screen" />
		<link rel="icon" type="image/png" href="images/diskoverfavico.png" />
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container" id="error" style="display:none; margin-top:70px;">
            <div class="row">
				<div class="alert alert-dismissible alert-warning col-xs-8">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, no files found, all files too small (filtered) or something else bad happened :(</strong> Choose a different path and try again.
				</div>
			</div>
		</div>
		<div class="container-fluid" id="mainwindow" style="margin-top: 70px;">
			<div class="row pull-right">
				<div class="col-xs-12">
					<div id="buttons-container" style="display:none;">
                        <span id="path" class="text-success" style="font-size:14px; font-weight: bold;"><?php echo $path; ?></span>
                        <button title="<?php echo getParentDir($path); ?>" class="btn btn-primary btn-sm" style="margin-right:20px;" onclick="window.location.href='<?php echo build_url('path', getParentDir($path)); ?>';"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</button>
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
                            <span style="font-size:11px; color:gray;">Show files </span><span style="position:relative; top:8px;"><label class="switch"><input id="showfiles" name="showfiles" type="checkbox"><span class="slider round"></span></label></span>
                            <span style="font-size:10px; color:gray; margin:10px;"><i class="glyphicon glyphicon-info-sign"></i> filters on filetree page affect this page</span>
					</div>
				</div>
			</div>
			<div class="row" id="treemap-wrapper" style="display:none;">
				<div class="col-xs-12">
					<div id="treemap-container"></div>
				</div>
			</div>
		</div>

		<script language="javascript" src="js/jquery.min.js"></script>
		<script language="javascript" src="js/bootstrap.min.js"></script>
		<script language="javascript" src="js/diskover.js"></script>
		<script language="javascript" src="js/d3.v3.min.js"></script>
		<script language="javascript" src="js/spin.min.js"></script>
		<script language="javascript" src="js/d3.tip.v0.6.3.js"></script>
		<script language="javascript" src="js/treemap.js"></script>
	</body>

	</html>
