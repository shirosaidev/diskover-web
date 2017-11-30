<?php
require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// redirect to select indices page if no index cookie
$esIndex = getenv('APP_ES_INDEX') ?: getCookie('index');
if (!$esIndex) {
    header("location:selectindices.php");
}

// remove any trailing slash unless root
if (isset($_GET['path']) && $_GET['path'] !== "/") {
    $path = rtrim($_GET['path'], '/');
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
				<div class="alert alert-dismissible alert-danger col-xs-8">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, no files found, all files too small (filtered) or something else bad happened :(</strong> Choose a different path and try again or check browser console and Elasticsearch for errors.
				</div>
			</div>
		</div>
		<div class="container-fluid" id="mainwindow">
            <div class="row">
				<div class="col-xs-8">
						<form class="form-inline" id="path-container" style="display:none;">
							<div class="form-group">
								<input type="text" size="100" class="form-control input-sm" style="color:#66C266!important;font-weight:bold;" name="pathinput" id="pathinput" value="">
							</div>
							<button title="change path" type="submit" id="changepath" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-circle-arrow-right"></i> Go</button>
                            <span style="margin-right:10px;"><a title="<?php echo getParentDir($path); ?>" class="btn btn-primary btn-sm" onclick="window.location.href='/filetree.php?path=<?php echo rawurlencode(getParentDir($path)); ?>&amp;filter=<?php echo getCookie('filter'); ?>&amp;mtime=<?php echo getCookie('mtime'); ?>&amp;use_count=<?php echo getCookie('use_count'); ?>'"><i class="glyphicon glyphicon-circle-arrow-up"></i> Up level</a></span>
						</form>
				</div>
                <div class="col-xs-4 pull-right text-right" id="chart-buttons" style="display:none;">
                    <button type="submit" id="reload" class="btn btn-default btn-sm" title="reload"><i class="glyphicon glyphicon-refresh"></i> </button>
                        <div class="btn-group">
                            <button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="dropdown">Min Size Filter
    <span class="caret"></span></button>
                            <ul class="dropdown-menu">
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=1&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">1 Bytes (default)</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=1024&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">1 KB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=8192&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">8 KB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=65536&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">64 KB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=262144&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">256 KB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=524288&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">512 KB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=1048576&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">1 MB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=2097152&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">2 MB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=5242880&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">5 MB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=10485760&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">10 MB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=26214400&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">25 MB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=52428800&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">50 MB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=104857600&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">100 MB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=209715200&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">200 MB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=524288000&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">500 MB</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=1073741824&amp;mtime=<?php echo $_GET['mtime']; ?>&amp;use_count=<?php echo $_GET['use_count']; ?>">1 GB</a></li>
                            </ul>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="dropdown">Mtime Filter
    <span class="caret"></span></button>
                            <ul class="dropdown-menu">
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=0&amp;use_count=<?php echo $_GET['use_count']; ?>">0 (default)</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=1d&amp;use_count=<?php echo $_GET['use_count']; ?>">1 day</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=1w&amp;use_count=<?php echo $_GET['use_count']; ?>">1 week</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=1m&amp;use_count=<?php echo $_GET['use_count']; ?>">1 month</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=3m&amp;use_count=<?php echo $_GET['use_count']; ?>">3 months</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=6m&amp;use_count=<?php echo $_GET['use_count']; ?>">6 months</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=1y&amp;use_count=<?php echo $_GET['use_count']; ?>">1 year</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=2y&amp;use_count=<?php echo $_GET['use_count']; ?>">2 years</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=3y&amp;use_count=<?php echo $_GET['use_count']; ?>">3 years</a></li>
                                <li><a href="/filetree.php?path=<?php echo rawurlencode($path); ?>&amp;filter=<?php echo $_GET['filter']; ?>&amp;mtime=5y&amp;use_count=<?php echo $_GET['use_count']; ?>">5 years</a></li>
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
                    <div id="statustext" class="statustext">
                        <span id="statusfilters">
                        </span><span id="statushidethresh">
                        </span>
                        *filters affect all analytics pages
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-4 tree-wrapper" id="tree-wrapper" style="display:none;">
                    <div id="tree-container"></div>
                </div>
                <div class="col-xs-8" id="chart-container" style="display:none;">
                    <div class="row">
                        <div class="col-xs-12">
                            <div id="piechart"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-4">
                            <div id="piechart-ext"></div>
                        </div>
                        <div class="col-xs-4">
                            <div id="barchart-filesizes"></div>
                        </div>
                        <div class="col-xs-4">
                            <div id="barchart-mtime" class="barchart"></div>
                        </div>
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
        <script language="javascript" src="/js/filetree_d3_visuals.js"></script>
        <script language="javascript" src="/js/treelist.js"></script>
        <script language="javascript" src="/js/filetree.js"></script>
	</body>

	</html>
