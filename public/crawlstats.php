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


// check if crawl has finished
// Get search results from Elasticsearch for index stats and to see if crawl finished
// return boolean if crawl finished (true)
$results = [];
$searchParams = [];

// Setup search query
$searchParams['index'] = $esIndex;
$searchParams['type']  = 'crawlstat';

$searchParams['body'] = [
    'size' => 1,
    'query' => [
            'match' => [
                'state' => 'finished_dircalc'
            ]
     ]
];
$queryResponse = $client->search($searchParams);

// determine if crawl is finished by checking if there is state "finished_dircalc" which only gets added at end of crawl
$crawlfinished = (sizeof($queryResponse['hits']['hits']) > 0) ? true : false;

?>

	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>diskover &mdash; Crawl Stats</title>
		<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="css/diskover.css" media="screen" />
        <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
        <style>
            #crawlstatschart1 {
                top: 10px;
                position: relative;
            }
            #crawlstatschart2 {
                top: 10px;
                position: relative;
            }
            #crawlstatschart1 svg {
                width: 1400px;
                height: 600px;
            }
            #crawlstatschart2 svg {
                width: 1400px;
                height: 200px;
            }
            #bulkindexchart {
                top: 50px;
                position: relative;
            }
            #bulkindexchart svg {
                width: 1400px;
                height: 300px;
            }
            .stack rect {
                opacity: .8;
                cursor: pointer;
            }
            .stack rect:hover {
                opacity: .7;
                cursor: pointer;
            }

            circle {
                opacity: .8;
                cursor: pointer;
            }
            circle:hover {
                opacity: .7;
                cursor: pointer;
            }

            text {
                fill: gray;
                font-size: 10px;
                pointer-events: none;
                text-anchor: middle;
            }

            .bubble {
                fill: lightgray;
                font-size: 10px;
                pointer-events: none;
                text-anchor: middle;
            }

            .d3-tip {
                font-size: 11px;
                line-height: 1;
                font-weight: bold;
                padding: 12px;
                background: rgba(25, 25, 25, 0.8);
                color: #fff;
                border-radius: 2px;
                pointer-events: none;
                word-break: break-all;
                word-wrap: break-word;
            }
            .axis {
                font: 10px sans-serif;
                fill: gray;
            }
            .axis path,
            .axis line {
              fill: none;
              stroke: #555;
              shape-rendering: crispEdges;
            }

            .path,
            .line {
              fill: none;
              stroke-width: 1px;
              stroke: steelblue;
              pointer-events: none;
              shape-rendering: crispEdges;
              opacity: .6;
            }

            .area_bulkindexchart {
                opacity: .3;
            }

            .hovertext {
                fill: red;
                font-size: 11px;
                font-weight: bold;
            }
        </style>
	</head>

	<body>
		<?php include "nav.php"; ?>
		<div class="container-fluid" id="mainwindow" style="margin-top: 70px;">
            <div class="row">
                <div class="col-xs-12 text-center">
                    <h2>Crawl Stats</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 text-right">
                    <span style="margin-right:30px;"><small><i class="glyphicon glyphicon-repeat"></i> Auto refresh <a href="#_self" id="autorefresh_on" onclick="autorefresh(3000);">on</a> <a href="#_self" id="autorefresh_off" onclick="autorefresh(0);">off</a></small></span>
                </div>
            </div>
			<div class="row">
                <div class="col-xs-12">
                      <div id="crawlstatschart-container" class="text-center">
                          <div id="crawlstatschart2"></div>
                          <div id="crawlstatschart1"></div>
                          <div id="bulkindexchart"></div>
                      </div>
                </div>
			</div>
		</div><br />
        <br />
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <div id="workerindexingstats" style="display: none;">
                        <h2 class="text-center">Worker Indexing Stats</h2>
                        <h4 class="text-center">Top By Crawl Time</h4>
                        <table id="topbycrawltime" class="table table-striped table-condensed" style="font-size:12px;">
                        </table>
                        <br />
                        <h4 class="text-center">Top By Bulk Time</h4>
                        <table id="topbybulktime" class="table table-striped table-condensed" style="font-size:12px;">
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="crawlfinished" name="crawlfinished" value="<?php echo $crawlfinished ? "true" : "false"; ?>">
        <input type="hidden" id="indexname" name="indexname" value="<?php echo $esIndex; ?>">

        <script language="javascript" src="js/jquery.min.js"></script>
        <script language="javascript" src="js/bootstrap.min.js"></script>
		<script language="javascript" src="js/diskover.js"></script>
        <script language="javascript" src="js/d3.v3.min.js"></script>
        <script language="javascript" src="js/spin.min.js"></script>
        <script language="javascript" src="js/d3.tip.v0.6.3.js"></script>
        <script language="javascript" src="js/crawlstats.js"></script>
    </body>
</html>
