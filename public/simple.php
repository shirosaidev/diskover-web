<?php
/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

header("X-XSS-Protection: 0");
require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Auth.php";
require "../src/diskover/Diskover.php";


// Get search results from Elasticsearch if the user searched for something
$results = [];
$total_size = 0;
$ids_onpage = [];

if (!empty($_REQUEST['submitted'])) {

    // get request string from predict_search
    $request = predict_search($_REQUEST['q']);

    // Save search query
    saveSearchQuery($request);

    // Connect to Elasticsearch
    $client = connectES();

    // curent page
    $p = $_REQUEST['p'];

    // Setup search query
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = ($_REQUEST['doctype']) ? $_REQUEST['doctype'] : 'file,directory';

    // Scroll parameter alive time
    $searchParams['scroll'] = "1m";

    // search size (number of results to return per page)
    if (isset($_REQUEST['resultsize'])) {
        $searchParams['size'] = $_REQUEST['resultsize'];
        createCookie("resultsize", $_REQUEST['resultsize']);
    } elseif (getCookie("resultsize") != "") {
        $searchParams['size'] = getCookie("resultsize");
    } else {
        $searchParams['size'] = Constants::SEARCH_RESULTS;
    }

    // match all if search field empty
    if (empty($request)) {
        $searchParams['body'] = [
            'query' => [
                'match_all' => (object) []
            ]
        ];
        // match what's in the search field
    } else {
        $searchParams['body'] = [
            'query' => [
                'query_string' => [
                    'query' => $request,
                    'analyze_wildcard' => 'true'
                ]
            ]
        ];
    }

    // Sort search results
    $searchParams = sortSearchResults($_REQUEST, $searchParams);

    try {
        // Send search query to Elasticsearch and get scroll id and first page of results
        $queryResponse = $client->search($searchParams);
    } catch (Exception $e) {
        //echo 'Message: ' .$e->getMessage();
    }

    // set total hits
    $total = $queryResponse['hits']['total'];

    // Get the first scroll_id
    $scroll_id = $queryResponse['_scroll_id'];

    $i = 1;
    // Loop through all the pages of results
    while ($i <= ceil($total/$searchParams['size'])) {

    // check if we have the results for the page we are on
        if ($i == $p) {
            // Get results
            $results[$i] = $queryResponse['hits']['hits'];
            // Add to total filesize
            for ($x=0; $x<=count($results[$i]); $x++) {
                $total_size += (int)$results[$i][$x]['_source']['filesize'];
                // store the id and doctype in ids_onpage array
                $ids_onpage[$x]['id'] = $results[$i][$x]['_id'];
                $ids_onpage[$x]['type'] = $results[$i][$x]['_type'];
            }
            // end loop
            break;
        }

        // Execute a Scroll request and repeat
        $queryResponse = $client->scroll(
        [
            "scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
            "scroll" => "1m"           // and the same timeout window
        ]
    );

        // Get the scroll_id for next page of results
        $scroll_id = $queryResponse['_scroll_id'];
        $i += 1;
    }
}

$estime = number_format(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 6);

?>
	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>diskover &mdash; Simple Search</title>
		<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="css/diskover.css" media="screen" />
        <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
	</head>

	<body>
		<?php include "nav.php"; ?>

		<?php if (!isset($_REQUEST['submitted'])) {
        $resultSize = getCookie('resultsize') != "" ? getCookie('resultsize') : Constants::SEARCH_RESULTS;
    ?>

		<div class="container-fluid" style="margin-top:70px;">
			<div class="row">
				<div class="col-xs-2 col-xs-offset-5">
					<p class="text-center"><img src="images/diskoversmall.png" style="margin-top:120px;" alt="diskover" width="62" height="47" /></p>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6 col-xs-offset-3">
					<p class="text-center">
						<h1 class="text-nowrap text-center"><i class="glyphicon glyphicon-search"></i> Simple Search</h1>
					</p>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-8 col-xs-offset-2">
						<form id="simplesearch" method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" class="form-inline text-center">
                            <input type="hidden" name="index" value="<?php echo $esIndex; ?>" />
                            <input type="hidden" name="index2" value="<?php echo $esIndex2; ?>" />
                            <input name="q" id="search" autocomplete="off" value="<?php echo $request; ?>" type="text" placeholder="Press ! to start a smartsearch or / for paths or \ to disable for ES query syntax" class="form-control input-lg" style="width:70%;" />
                            <input type="hidden" name="submitted" value="true" />
							<input type="hidden" name="p" value="1" />
                            <input type="hidden" name="resultsize" value="<?php echo $resultSize; ?>" />
                    		<select class="form-control input-lg" name="doctype">
                                <option value="">all</option>
                                <option value="file">file</option>
                                <option value="directory">directory</option>
                    		</select>
							<button type="submit" class="btn btn-primary btn-lg">Search</button>
						</form>
                        <div class="essearchreply" id="essearchreply">
                            <div class="essearchreply-text" id="essearchreply-text"></div>
                        </div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-8 col-xs-offset-2">
                    <br />
					<p class="text-center">
						<a href="help.php?<?php echo $_SERVER['QUERY_STRING']; ?>">Search examples</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<a href="https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#query-string-syntax" target="_blank">Query string syntax help</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<a href="advanced.php?<?php echo $_SERVER['QUERY_STRING']; ?>">Switch to advanced search</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <a href="admin.php?<?php echo $_SERVER['QUERY_STRING']; ?>">Edit smart searches</a></p>
				</div>
			</div>
            <?php $savedsearches = getSavedSearchQuery();
        if ($savedsearches) { ?>
			<div class="row">
				<div class="col-xs-6 col-xs-offset-3">
					<h5 style="margin-top:60px;"><i class="glyphicon glyphicon-time"></i> Search history</h5>
					<div class="well well-sm">
						<?php
        foreach ($savedsearches as $key => $value) {
            echo '<a class="small" href=/simple.php?' . $_SERVER['QUERY_STRING'] . '&submitted=true&p=1&q=' . rawurlencode($value) . '&resultsize=' . $resultSize . '>' . $value . '</a><br />';
        }
    } ?>
					</div>
				</div>
			</div>

			<?php
} ?>

			<?php

if (isset($_REQUEST['submitted'])) {
    include "results.php";
}

?>
	</div>
	<script language="javascript" src="js/jquery.min.js"></script>
	<script language="javascript" src="js/bootstrap.min.js"></script>
	<script language="javascript" src="js/diskover.js"></script>
    <script>
    $(document).ready(function () {

        // search items in ES on keypress on simple search page
    	$("#search").keyup(function () {
            if ($('#search').val() === "") {
                $('#essearchreply-text').html("");
                $('#essearchreply').hide();
                return false;
            }
            var results;
            // delay for 500 ms before searching ES for user input
            setTimeout(function() {
                $.ajax({
                    type:'GET',
                    url:'searchkeypress.php',
                    data: $('#simplesearch').serialize(),
                    success: function(data) {
                    		if (data != "") {
                                // set width and position of search results div to match search input
                                var w = $('#search').width();
                                var p = $('#search').position();
                                $("#essearchreply").css({left: p.left, position:'absolute'});
                                $('#essearchreply').width(w+30);
                    			$('#essearchreply').show();
                                $('#essearchreply-text').html(data);
                    		} else {
                                $('#essearchreply-text').html("");
                                $('#essearchreply').hide();
                    		}
                        }
                });
            }, 500);
            return false;
    	});

        // listen for msgs from diskover socket server
        listenSocketServer();
    });
    </script>
<div id="loading">
  <img id="loading-image" width="32" height="32" src="images/ajax-loader.gif" alt="Updating..." />
  <div id="loading-text"></div>
</div>
<iframe name="hiddeniframe" width=0 height=0 style="display:none;"></iframe>
<?php require "logform.php"; ?>
</body>
</html>
