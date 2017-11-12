<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;

error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// Get search results from Elasticsearch if the user searched for something
$results = [];
$total_size = 0;

if (!empty($_REQUEST['submitted'])) {

    // Save search query
    saveSearchQuery($_REQUEST['q']);

    // Connect to Elasticsearch
    $client = connectES();

    // curent page
    $p = $_REQUEST['p'];

    // Setup search query
    $searchParams['index'] = Constants::ES_INDEX; // which index to search
    $searchParams['type']  = Constants::ES_TYPE;  // which type within the index to search

    // Scroll parameter alive time
    $searchParams['scroll'] = "1m";

    // search size (number of results to return per page)
    if (isset($_REQUEST['resultsize'])) {
        $searchParams['size'] = $_REQUEST['resultsize'];
        createCookie("resultsize", $_REQUEST['resultsize']);
    } elseif (getCookie("resultsize") != "") {
        $searchParams['size'] = getCookie("resultsize");
    } else {
        $searchParams['size'] = 100;
    }

    // match all if search field empty
    if (empty($_REQUEST['q'])) {
        $searchParams['body'] = [ 'query' => [ 'match_all' => (object) [] ] ];
        // match what's in the search field
    } else {
        $req = $_REQUEST['q'];
        $searchParams['body']['query']['query_string']['query'] = $req;
        $searchParams['body']['query']['query_string']['analyze_wildcard'] = 'true';
    }

    // Check if we need to sort search differently
    // check request
    if ($_REQUEST['sort']) {
        $searchParams['body']['sort'] = $_REQUEST['sort'];
        if ($_REQUEST['sortorder']) {
            $searchParams['body']['sort'] = [ ''.$_REQUEST['sort'].'' => ['order' => $_REQUEST['sortorder'] ] ];
        }
    // check cookie
    } elseif (getCookie('sort')) {
        $searchParams['body']['sort'] = getCookie('sort');
        if (getCookie('sortorder')) {
            $searchParams['body']['sort'] = [ ''.getCookie('sort').'' => ['order' => getCookie('sortorder') ] ];
        }
    } else {
        // sort by parent path, then filename
        $searchParams['body']['sort'] = [ 'path_parent' => ['order' => 'asc' ], 'filename' => 'asc' ];
    }

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
                $total_size += $results[$i][$x]['_source']['filesize'];
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
?>
	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>diskover &mdash; Simple Search</title>
		<!--<link rel="stylesheet" href="/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" href="/css/bootstrap-theme.min.css" media="screen" />-->
		<link rel="stylesheet" href="/css/bootswatch.min.css" media="screen" />
		<link rel="stylesheet" href="/css/diskover.css" media="screen" />
	</head>

	<body>
		<?php include __DIR__ . "/nav.php"; ?>

		<?php if (!isset($_REQUEST['submitted'])) {
        $resultSize = getCookie('resultsize') != "" ? getCookie('resultsize') : 100;
    ?>

		<div class="container-fluid">
			<div class="row">
				<div class="col-xs-2 col-xs-offset-5">
					<p class="text-center"><img src="/images/diskoversmall.png" style="margin-top:120px;" alt="diskover" width="62" height="47" /></p>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6 col-xs-offset-3">
					<p class="text-center">
						<h1 class="text-nowrap text-center">diskover &mdash; Simple Search</h1>
					</p>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-8 col-xs-offset-2">
					<p class="text-center">
						<form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" class="form-inline text-center">
                            <input type="hidden" name="destination" value="<?php echo $_SERVER[" REQUEST_URI "]; ?>"/>
							<input name="q" value="<?php echo $_REQUEST['q']; ?>" type="text" placeholder="What are you looking for?" class="form-control input-lg" size="70" />
							<input type="hidden" name="submitted" value="true" />
							<input type="hidden" name="p" value="1" />
                            <input type="hidden" name="resultsize" value="<?php echo $resultSize; ?>" />
							<button type="submit" class="btn btn-primary btn-lg">Search</button>
						</form>
					</p>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-8 col-xs-offset-2">
					<p class="text-center">
						<a href="/help.php">Search examples</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<a href="https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#query-string-syntax" target="_blank">Query string syntax help</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<a href="/advanced.php">Switch to advanced search</a></p>
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
            echo '<a class="small" href=/simple.php?submitted=true&p=1&q=' . rawurlencode($value) . '&resultsize=' . $resultSize . '>' . $value . '</a><br />';
        }
    } ?>
					</div>
				</div>
			</div>

			<?php
} ?>

			<?php

if (isset($_REQUEST['submitted'])) {
    include __DIR__ . "/results.php";
}

?>
	</div>
	<script language="javascript" src="/js/jquery.min.js"></script>
	<script language="javascript" src="/js/bootstrap.min.js"></script>
	<script language="javascript" src="/js/diskover.js"></script>
</body>

</html>
