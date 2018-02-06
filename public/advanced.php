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
    // redirect to select indices page if no index cookie
    if (!$esIndex) {
        header("location:selectindices.php");
        exit();
    }
}
// check for index2 in url
if (isset($_GET['index2'])) {
    $esIndex2 = $_GET['index2'];
    setCookie('index2', $esIndex2);
} else {
    $esIndex2 = getenv('APP_ES_INDEX2') ?: getCookie('index2');
}

// Get search results from Elasticsearch if the user searched for something
$results = [];
$total_size = 0;

if (!empty($_REQUEST['submitted'])) {

    // Connect to Elasticsearch
    $client = connectES();

    // curent page
    $p = $_REQUEST['p'];

    // Setup search query
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = ($_REQUEST['doctype']) ? $_REQUEST['doctype'] : 'file,directory';

    $searchParams['body'] = [];
    $filterClauses = [];

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


    if ($_REQUEST['filename']) {
        $filterClauses[] = [ 'term' => [ 'filename' => $_REQUEST['filename'] ] ];
    }

    if ($_REQUEST['path_parent']) {
        $filterClauses[] = [ 'term' => [ 'path_parent' => $_REQUEST['path_parent'] ] ];
    }

    if ($_REQUEST['tag']) {
        $filterClauses[] = [ 'term' => [ 'tag' => $_REQUEST['tag'] ] ];
    }

    if ($_REQUEST['tag_custom']) {
        $filterClauses[] = [ 'term' => [ 'tag_custom' => $_REQUEST['tag_custom'] ] ];
    }

    if ($_REQUEST['inode']) {
        $filterClauses[] = [ 'term' => [ 'inode' => $_REQUEST['inode'] ] ];
    }

    if ($_REQUEST['last_mod_time_low'] || $_REQUEST['last_mod_time_high']) {
        $rangeFilter = [];
        if ($_REQUEST['last_mod_time_low']) {
            $rangeFilter['gte'] = (string) $_REQUEST['last_mod_time_low'];
        }
        if ($_REQUEST['last_mod_time_high']) {
            $rangeFilter['lte'] = (string) $_REQUEST['last_mod_time_high'];
        }
        $filterClauses[] = [ 'range' => [ 'last_modified' => $rangeFilter ] ];
    }

    if ($_REQUEST['last_access_time_low'] || $_REQUEST['last_access_time_high']) {
        $rangeFilter = [];
        if ($_REQUEST['last_access_time_low']) {
            $rangeFilter['gte'] = (string) $_REQUEST['last_access_time_low'];
        }
        if ($_REQUEST['last_access_time_high']) {
            $rangeFilter['lte'] = (string) $_REQUEST['last_access_time_high'];
        }
        $filterClauses[] = [ 'range' => [ 'last_access' => $rangeFilter ] ];
    }

    if ($_REQUEST['file_size_bytes_low'] || $_REQUEST['file_size_bytes_high']) {
        $rangeFilter = [];
        if ($_REQUEST['file_size_bytes_low']) {
            $rangeFilter['gte'] = (int) $_REQUEST['file_size_bytes_low'];
        }
        if ($_REQUEST['file_size_bytes_high']) {
            $rangeFilter['lte'] = (int) $_REQUEST['file_size_bytes_high'];
        }
        $filterClauses[] = [ 'range' => [ 'filesize' => $rangeFilter ] ];
    }

    if ($_REQUEST['hardlinks_low'] || $_REQUEST['hardlinks_high']) {
        $rangeFilter = [];
        if ($_REQUEST['hardlinks_low']) {
            $rangeFilter['gte'] = (int) $_REQUEST['hardlinks_low'];
        }
        if ($_REQUEST['hardlinks_high']) {
            $rangeFilter['lte'] = (int) $_REQUEST['hardlinks_high'];
        }
        $filterClauses[] = [ 'range' => [ 'hardlinks' => $rangeFilter ] ];
    }

    if ($_REQUEST['filehash']) {
        $filterClauses[] = [ 'term' => [ 'filehash' => $_REQUEST['filehash'] ] ];
    }

    if ($_REQUEST['extension']) {
        $filterClauses[] = [ 'term' => [ 'extension' => $_REQUEST['extension'] ] ];
    }

    if ($_REQUEST['owner']) {
        $filterClauses[] = [ 'term' => [ 'owner' => $_REQUEST['owner'] ] ];
    }

    if ($_REQUEST['group']) {
        $filterClauses[] = [ 'term' => [ 'group' => $_REQUEST['group'] ] ];
    }

    if ($_REQUEST['index']) {
        $filterClauses[] = [ 'term' => [ '_index' => $_REQUEST['index'] ] ];
    }

    if ($_REQUEST['is_dupe'] == "true") {
        $searchParams['body'] = [
        'query' => [
          'query_string' => [
            'query' => 'is_dupe:true'
          ]
        ]
    ];
    }

    // Build complete search request body
    if (count($filterClauses) == 1) {
        $searchParams['body']['query'] = $filterClauses[0];
    } elseif (count($filterClauses) > 1) {
        $searchParams['body']['query']['bool']['filter'] = [ $filterClauses ];
    } else {
        if (!$_REQUEST['is_dupe']) {
            $searchParams['body'] = [ 'query' => [ 'match_all' => (object) [] ] ];
        }
    }

    // Sort search results
    if (!$_REQUEST['sort'] && !$_REQUEST['sort2'] && !getCookie("sort") && !getCookie("sort2")) {
        $searchParams['body']['sort'] = [ 'path_parent' => [ 'order' => 'asc' ], 'filename' => 'asc' ];
    } else {
        $searchParams['body']['sort'] = [];
        if ($_REQUEST['sort'] && !$_REQUEST['sortorder']) {
            $searchParams['body']['sort'] = $_REQUEST['sort'];
            createCookie("sort", $_REQUEST['sort']);
        } elseif ($_REQUEST['sort'] && $_REQUEST['sortorder']) {
            array_push($searchParams['body']['sort'], [ $_REQUEST['sort'] => [ 'order' => $_REQUEST['sortorder'] ] ]);
            createCookie("sort", $_REQUEST['sort']);
            createCookie("sortorder", $_REQUEST['sortorder']);
        } elseif (getCookie('sort') && !getCookie('sortorder')) {
            $searchParams['body']['sort'] = getCookie('sort');
        } elseif (getCookie('sort') && getCookie('sortorder')) {
            array_push($searchParams['body']['sort'], [ getCookie('sort') => [ 'order' => getCookie('sortorder') ] ]);
        }
        // sort 2
        if ($_REQUEST['sort2'] && !$_REQUEST['sortorder2']) {
            $searchParams['body']['sort'] = $_REQUEST['sort2'];
            createCookie("sort2", $_REQUEST['sort2']);
        } elseif ($_REQUEST['sort2'] && $_REQUEST['sortorder2']) {
            array_push($searchParams['body']['sort'], [ $_REQUEST['sort2'] => [ 'order' => $_REQUEST['sortorder2'] ] ]);
            createCookie("sort2", $_REQUEST['sort2']);
            createCookie("sortorder2", $_REQUEST['sortorder2']);
        } elseif (getCookie('sort2') && !getCookie('sortorder2')) {
            $searchParams['body']['sort'] = getCookie('sort2');
        } elseif (getCookie('sort2') && getCookie('sortorder2')) {
            array_push($searchParams['body']['sort'], [ getCookie('sort2') => [ 'order' => getCookie('sortorder2') ] ]);
        }
    }

    // Send search query to Elasticsearch and get tag scroll id and first page of results
    $queryResponse = $client->search($searchParams);

    // total hits
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
  <title>diskover &mdash; Advanced Search</title>
	<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
  <link rel="stylesheet" href="css/diskover.css" media="screen" />
</head>
<body>
<?php include "nav.php"; ?>

<?php if (!isset($_REQUEST['submitted'])) {
    ?>

<div class="container" style="margin-top:70px;">
  <div class="row">
	<div class="col-xs-1" style="display:inline-block;vertical-align:middle;float:none;">
	  <img src="images/diskoversmall.png" alt="diskover" width="62" height="47" />
	</div>
	<div class="col-xs-8" style="display:inline-block;vertical-align:middle;float:none;">
	  <h1><i class="glyphicon glyphicon-search"></i> Advanced Search</h1>
	</div>
  </div>
<form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" class="form-horizontal">
	<fieldset>
    <input type="hidden" name="index" value="<?php echo $esIndex; ?>" />
    <input type="hidden" name="index2" value="<?php echo $esIndex2; ?>" />
    <input type="hidden" name="submitted" value="true" />
    <input type="hidden" name="p" value="1" />
    <input type="hidden" name="resultsize" value="<?php echo getCookie('resultsize') != "" ? getCookie('resultsize') : 100; ?>" />

<div class="container">
  <div class="form-group">
	<div class="row">
	  <div class="col-xs-6">
		<label for="filename">Filename is...</label>
		<input name="filename" value="<?php echo $_REQUEST['filename']; ?>" placeholder="somefile.m4a" class="form-control" />
	  </div>
	  <div class="col-xs-4">
		<label for="filehash">Filehash is...</label>
		<input name="filehash" value="<?php echo $_REQUEST['filehash']; ?>" placeholder="md5 hash" class="form-control" />
	  </div>
	  <div class="col-xs-2">
		<label for="inode">Inode is...</label>
		<input name="inode" value="<?php echo $_REQUEST['inode']; ?>" placeholder="inode num" class="form-control" />
	  </div>
	</div>
  </div>
  <div class="form-group">
	<div class="row">
	  <div class="col-xs-12">
		<label for="path_parent">Parent path is...  </label>
		<input name="path_parent" value="<?php echo $_REQUEST['path_parent']; ?>" placeholder="/Users/shirosai/Music" class="form-control" />
	  </div>
	</div>
  </div>
  <div class="form-group">
	<div class="row">
	  <div class="col-xs-4">
		<label for="file_size_bytes_low">File size is between...</label>
		<input name="file_size_bytes_low" value="<?php echo $_REQUEST['file_size_bytes_low']; ?>" type="number" placeholder="bytes" class="form-control" />
		<label for="file_size_bytes_high">and</label>
		<input name="file_size_bytes_high" value="<?php echo $_REQUEST['file_size_bytes_high']; ?>" type="number" placeholder="bytes" class="form-control" />
	  </div>
	  <div class="col-xs-2">
		<label for="hardlinks_low">Hardlinks is between...</label>
		<input name="hardlinks_low" value="<?php echo $_REQUEST['hardlinks_low']; ?>" type="number" placeholder="2" class="form-control" />
		<label for="hardlinks_high">and</label>
		<input name="hardlinks_high" value="<?php echo $_REQUEST['hardlinks_high']; ?>" type="number" placeholder="10" class="form-control" />
	  </div>
	  <div class="col-xs-3">
		<label for="last_mod_time_low">Last modified time is between...</label>
		<input name="last_mod_time_low" value="<?php echo $_REQUEST['last_mod_time_low']; ?>" type="string" placeholder="2015-03-06T00:00:00" class="form-control" />
		<label for="last_mod_time_high">and</label>
		<input name="last_mod_time_high" value="<?php echo $_REQUEST['last_mod_time_high']; ?>" type="string" placeholder="2017-03-06T00:00:00" class="form-control" />
	  </div>
	  <div class="col-xs-3">
		<label for="last_access_time_low">Last access time is between...</label>
		<input name="last_access_time_low" value="<?php echo $_REQUEST['last_access_time_low']; ?>" type="string" placeholder="2015-03-06T00:00:00" class="form-control" />
		<label for="last_access_time_high">and</label>
		<input name="last_access_time_high" value="<?php echo $_REQUEST['last_access_time_high']; ?>" type="string" placeholder="2017-03-06T00:00:00" class="form-control" />
	  </div>
	</div>
  </div>
  <div class="form-group">
	<div class="row">
	  <div class="col-xs-2">
		<label for="owner">Owner is...  </label>
		<input name="owner" value="<?php echo $_REQUEST['owner']; ?>" placeholder="shirosai" class="form-control" />
	  </div>
	  <div class="col-xs-2">
		<label for="group">Group is...  </label>
		<input name="group" value="<?php echo $_REQUEST['group']; ?>" placeholder="staff" class="form-control" />
	  </div>
	  <div class="col-xs-2">
		<label for="extension">Extension is...</label>
		<input name="extension" value="<?php echo $_REQUEST['extension']; ?>" type="string" placeholder="zip" class="form-control" />
	  </div>
	  <div class="col-xs-2">
		<label for="tag">Tag is...</label>
		<select class="form-control" name="tag">
		  <option value="<?php echo $_REQUEST['tag']; ?>" selected><?php echo $_REQUEST['tag']; ?></option>
		  <option value="untagged">untagged</option>
		  <option value="delete">delete</option>
		  <option value="archive">archive</option>
		  <option value="keep">keep</option>
		</select>
	  </div>
	  <div class="col-xs-4">
		<label for="tag_custom">Custom Tag is...</label>
		<input name="tag_custom" value="<?php echo $_REQUEST['tag_custom']; ?>" type="string" placeholder="version 8" class="form-control" />
	  </div>
	</div>
  </div>
  <div class="form-group">
	<div class="row">
	  <div class="col-xs-4">
		<label for="index">Index is...</label>
		<input name="index" value="<?php echo $_REQUEST['index']; ?>" type="string" placeholder="diskover-2017.05.24" class="form-control" />
	  </div>
	</div>
  </div>
  <div class="form-group">
	<div class="row">
	  <div class="col-xs-2">
		<label for="sort">Sort by...</label>
		<select class="form-control" name="sort">
		  <option value="<?php echo getCookie('sort'); ?>" selected><?php echo getCookie('sort'); ?></option>
		  <option value="filename">filename</option>
		  <option value="path_parent">path_parent</option>
		  <option value="filesize">filesize</option>
		  <option value="owner">owner</option>
		  <option value="group">group</option>
		  <option value="last_modified">last_modified</option>
		  <option value="last_access">last_access</option>
		  <option value="tag">tag</option>
		  <option value="tag_custom">tag_custom</option>
		</select>
	  </div>
	  <div class="col-xs-2">
		<label for="sortorder">Sort order...</label>
		<select class="form-control" name="sortorder">
		  <option value="<?php echo getCookie('sortorder'); ?>" selected><?php echo getCookie('sortorder'); ?></option>
		  <option value="asc">asc</option>
		  <option value="desc">desc</option>
		</select>
	  </div>
      <div class="col-xs-2">
		<label for="sort">Sort2 by...</label>
		<select class="form-control" name="sort2">
		  <option value="<?php echo getCookie('sort2'); ?>" selected><?php echo getCookie('sort2'); ?></option>
		  <option value="filename">filename</option>
		  <option value="path_parent">path_parent</option>
		  <option value="filesize">filesize</option>
		  <option value="owner">owner</option>
		  <option value="group">group</option>
		  <option value="last_modified">last_modified</option>
		  <option value="last_access">last_access</option>
		  <option value="tag">tag</option>
		  <option value="tag_custom">tag_custom</option>
		</select>
	  </div>
	  <div class="col-xs-2">
		<label for="sortorder">Sort2 order...</label>
		<select class="form-control" name="sortorder2">
		  <option value="<?php echo getCookie('sortorder2'); ?>" selected><?php echo getCookie('sortorder2'); ?></option>
		  <option value="asc">asc</option>
		  <option value="desc">desc</option>
		</select>
	  </div>
	</div>
    </div>
     <div class="form-group">
    <div class="row">
        <div class="col-xs-2">
  		<label for="tags">Show request JSON?</label>
  		<input type="checkbox" name="debug" value="true"<?php echo($_REQUEST['debug'] ? " checked" : ""); ?> />
  	  </div>
      <div class="col-xs-2">
		<label for="sortorder">Doc type...</label>
          <select class="form-control" name="doctype">
            <option value="file" selected>file</option>
            <option value="directory">directory</option>
            <option value="">all</option>
          </select>
      </div>
    </div>
  </div>
  </div>
  <button type="reset" class="btn btn-default">Clear</button>
  <button type="submit" class="btn btn-primary">Search</button>
  <span>&nbsp;<a href="simple.php">Switch to simple search</a></span>
		</fieldset>
</form>

<?php
} ?>

<?php

if (isset($_REQUEST['submitted'])) {
    include "results.php";

    // Print out request JSON if debug flag is set
    if ($_REQUEST['debug']) {
        ?>
<h3>Request JSON</h3>
<pre>
<?php echo json_encode($searchParams['body'], JSON_PRETTY_PRINT); ?>
</pre>
<?php
    }
}

?>
</div>
<script language="javascript" src="js/jquery.min.js"></script>
<script language="javascript" src="js/bootstrap.min.js"></script>
<script language="javascript" src="js/diskover.js"></script>
<script>
// listen for msgs from diskover socket server
listenSocketServer();
</script>
<iframe name="hiddeniframe" width=0 height=0 style="display:none;"></iframe>
</body>
</html>
