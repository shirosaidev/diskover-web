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

$minhardlinks = (int)getCookie('minhardlinks');
if ($minhardlinks === "" || $minhardlinks === 0) {
    $minhardlinks = getAvgHardlinks($client, $esIndex, $path, $filter, $mtime);
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
		<title>diskover &mdash; Hardlinks</title>
		<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="css/diskover.css" media="screen" />
        <link rel="stylesheet" href="css/diskover-hardlinks.css" media="screen" />
        <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container" id="nohardlinks" style="display:none; margin-top:70px;">
            <div class="row">
                <div class="alert alert-dismissible alert-info col-xs-8">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="glyphicon glyphicon-exclamation-sign"></i> <strong>Sorry, no hardlinks found.</strong> Try searching for a different directory and clicking it's hardlinks analytics button, or changing filters (on filetree page) or minhardlinks value.
                </div>
            </div>
        </div>
        <div class="container" id="error" style="display:none; margin-top:70px;">
            <div class="row">
                <div class="alert alert-dismissible alert-danger col-xs-8">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, an error has occured :( </strong><a href="#" onclick="window.history.go(-1); return false;">Go back</a>.<br /><br />
                    <small><a href="#" onclick="document.getElementById('debugerror').style.display = 'block'; return false;"> show debug error</a><br />
                    <span id="debugerror" style="display:none;"></span></small>
                </div>
            </div>
        </div>
		<div class="container-fluid" id="mainwindow" style="margin-top: 70px;">
            <div id="hardlinkscharts-wrapper" style="display:none;">
                <div class="row">
                    <div class="col-xs-12 text-center">
                    	<h2>Hardlinks</h2>
                    	<div class="row">
                    		<div class="col-xs-12 text-center">
                    			<form class="form-horizontal" id="changeminhardlinks">
                    			<div class="form-group form-inline">
                    				<label class="small">Min Hard Links</label>&nbsp;<input class="form-control input-sm" name="minhardlinks" id="minhardlinks" value="<?php echo $minhardlinks; ?>" size="5">&nbsp;<button type="submit" id="changeminhardlinksbutton" class="btn btn-default btn-xs" title="submit">Go </button>
                    				<span style="font-size:10px; color:gray; margin-left:20px;"><i class="glyphicon glyphicon-info-sign"></i> filters on filetree page affect this page, reload to see changes &nbsp;&nbsp;</span><button type="submit" id="reload" class="btn btn-default btn-xs" title="reload"><i class="glyphicon glyphicon-refresh"></i> </button><br />
                    				<h5 style="display: inline;"><span class="text-success bold"><?php echo stripslashes($path); ?></span></h5>
                    				<span><a title="<?php echo getParentDir($path); ?>" class="btn btn-primary btn-sm" onclick="window.location.href='<?php echo build_url('path', getParentDir($path)); ?>';"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</a></span>
                    			</div>
                    			</form>
                    		</div>
                    	</div>
                    </div>
                </div>
                <div class="row" style="margin-top:10px;">
                    <div class="col-xs-4">
                        <div id="hardlinkscountbarchart" class="hardlinkscountbarchart text-center"></div>
                    </div>
                    <div class="col-xs-8">
                        <div id="hardlinkscountgraph" class="hardlinkscountgraph text-center"></div>
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
