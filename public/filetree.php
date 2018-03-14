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
		<title>diskover &mdash; File Tree</title>
		<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="css/diskover.css" media="screen" />
		<link rel="stylesheet" href="css/diskover-filetree.css" media="screen" />
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container" id="error" style="display:none; margin-top:70px;">
			<div class="row">
				<div class="alert alert-dismissible alert-warning col-xs-8">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, no files found, all files too small (filtered) or something else bad happened :(</strong> Choose a different path and try again. <a href="#" onclick="window.history.go(-1); return false;">Go back</a>.
				</div>
			</div>
		</div>
		<div class="container-fluid" id="mainwindow" style="margin-top:70px;">
            <div class="row">
				<div class="col-xs-7">
						<form class="form-inline" id="path-container" style="display:none;">
							<div class="form-group">
								<input type="text" size="90" class="form-control input-sm" style="color:#66C266!important;font-weight:bold;" name="pathinput" id="pathinput" value="">
							</div>
							<button title="change path" type="submit" id="changepath" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-circle-arrow-right"></i> Go</button>
                            <button title="<?php echo getParentDir($path); ?>" type="button" class="btn btn-primary btn-sm" onclick="window.location.href='<?php echo build_url('path', getParentDir($path)); ?>'; return false;"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</button>
                        </form>
				</div>
                <div class="col-xs-5 pull-right text-right" id="chart-buttons" style="display:none;">
                    <button type="submit" id="reload" class="btn btn-default btn-sm" title="reload"><i class="glyphicon glyphicon-refresh"></i> </button>
                        <div class="btn-group">
                            <button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="dropdown">Min Size Filter
    <span class="caret"></span></button>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo build_url('filter', 1); ?>">1 Bytes (default)</a></li>
                                <li><a href="<?php echo build_url('filter', 1024); ?>">1 KB</a></li>
                                <li><a href="<?php echo build_url('filter', 8192); ?>">8 KB</a></li>
                                <li><a href="<?php echo build_url('filter', 65536); ?>">64 KB</a></li>
                                <li><a href="<?php echo build_url('filter', 262144); ?>">256 KB</a></li>
                                <li><a href="<?php echo build_url('filter', 524288); ?>">512 KB</a></li>
                                <li><a href="<?php echo build_url('filter', 1048576); ?>">1 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 2097152); ?>">2 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 5242880); ?>">5 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 10485760); ?>">10 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 26214400); ?>">25 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 52428800); ?>">50 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 104857600); ?>">100 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 209715200); ?>">200 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 524288000); ?>">500 MB</a></li>
                                <li><a href="<?php echo build_url('filter', 1073741824); ?>">1 GB</a></li>
                            </ul>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="dropdown">Mtime Filter
    <span class="caret"></span></button>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo build_url('mtime', '0'); ?>">0 (default)</a></li>
                                <li><a href="<?php echo build_url('mtime', '1d'); ?>">1 day</a></li>
                                <li><a href="<?php echo build_url('mtime', '1w'); ?>">1 week</a></li>
                                <li><a href="<?php echo build_url('mtime', '1m'); ?>">1 month</a></li>
                                <li><a href="<?php echo build_url('mtime', '3m'); ?>">3 months</a></li>
                                <li><a href="<?php echo build_url('mtime', '6m'); ?>">6 months</a></li>
                                <li><a href="<?php echo build_url('mtime', '1y'); ?>">1 year</a></li>
                                <li><a href="<?php echo build_url('mtime', '2y'); ?>">2 years</a></li>
                                <li><a href="<?php echo build_url('mtime', '3y'); ?>">3 years</a></li>
                                <li><a href="<?php echo build_url('mtime', '5y'); ?>">5 years</a></li>
                            </ul>
                        </div>
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
                    <span style="font-size:11px; color:gray;">Show files </span><span style="position:relative; top:8px;"><label class="switch"><input id="showfiles" name="showfiles" type="checkbox"><span class="slider round"></span></label></span>
                    <div id="statustext" class="statustext">
                        <i class="glyphicon glyphicon-filter"></i> Filters: <span id="statusfilters"></span><span id="statushidethresh"></span>
                        &nbsp;&nbsp;<i class="glyphicon glyphicon-info-sign"></i> filters affect all analytics pages
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-4 tree-wrapper" id="tree-wrapper" style="display:none;">
                    <div id="tree-container"></div>
                </div>
                <div class="col-xs-8" id="chart-container" style="display:none;">
                    <div class="row">
                        <div class="col-xs-12 text-center">
                            <div id="piechart"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-4 text-center">
                            <div id="piechart-ext"></div>
                        </div>
                        <div class="col-xs-4 text-center">
                            <div id="barchart-filesizes"></div>
                        </div>
                        <div class="col-xs-4 text-center">
                            <div id="barchart-mtime"></div>
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
        <script language="javascript" src="js/filetree_d3_visuals.js"></script>
        <script language="javascript" src="js/treelist.js"></script>
        <script language="javascript" src="js/filetree.js"></script>
	</body>

	</html>
