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
		<title>diskover &mdash; Hardlinks</title>
		<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="css/diskover.css" media="screen" />
        <link rel="stylesheet" href="css/diskover-hardlinks.css" media="screen" />
        <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container-fluid" id="mainwindow" style="margin-top: 70px;">
            <div id="hardlinkscharts-wrapper" style="display:none;">
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <h1>Hardlinks</h1><span style="font-size:10px; color:gray;"><i class="glyphicon glyphicon-info-sign"></i> filters on filetree page affect this page, reload to see changes &nbsp;&nbsp;</span><button type="submit" id="reload" class="btn btn-default btn-xs" title="reload"><i class="glyphicon glyphicon-refresh"></i> </button>
                    </div>
                </div>
                <br />
                <div class="row">
                    <div class="col-xs-12">
                        <div id="hardlinkscountchart" class="hardlinkscountchart text-center"></div>
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
        <script language="javascript" src="js/hardlinks.js"></script>
	</body>

	</html>
