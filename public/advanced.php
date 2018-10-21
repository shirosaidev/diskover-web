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
require "d3_inc.php";

// Grab all the custom tags from file
$customtags = get_custom_tags();

// Get search results from Elasticsearch if the user searched for something
$results = [];
$total_size = 0;
$ids_onpage = [];

if (!empty($_REQUEST['submitted'])) {
    // set sort cookies
    if (!empty($_REQUEST['sort'])) {
        createCookie('sort', $_REQUEST['sort']);
    }
    if (!empty($_REQUEST['sort2'])) {
        createCookie('sort2', $_REQUEST['sort2']);
    }
    if (!empty($_REQUEST['sortorder'])) {
        createCookie('sortorder', $_REQUEST['sortorder']);
    }
    if (!empty($_REQUEST['sortorder2'])) {
        createCookie('sortorder2', $_REQUEST['sortorder2']);
    }

    // Connect to Elasticsearch
    $client = connectES();

    // curent page
    $p = $_REQUEST['p'];

    // Setup search query
    $searchParams['index'] = $esIndex;
    $searchParams['type'] = ($_REQUEST['doctype']) ? $_REQUEST['doctype'] : 'file,directory';

    $searchParams['body'] = [];
    $q = [];

    // Scroll parameter alive time
    $searchParams['scroll'] = "1m";

    // search size (number of results to return per page)
    if (!empty($_REQUEST['resultsize'])) {
        $searchParams['size'] = $_REQUEST['resultsize'];
        createCookie("resultsize", $_REQUEST['resultsize']);
    } elseif (getCookie("resultsize") != "") {
        $searchParams['size'] = getCookie("resultsize");
    } else {
        $searchParams['size'] = Constants::SEARCH_RESULTS;
    }

    if (!empty($_REQUEST['filename'])) {
        if (strpos($_REQUEST['filename'], '*') !== false) {
            $filename_wildcard = str_replace('\*', '*', escape_chars($_REQUEST['filename']));
            $filename_arr = preg_split('/\b/', $filename_wildcard);
            $filestring = "filename:(" . $filename_wildcard;
            // Add first letter upercase/lowercase and all lowercase/uppercase versions of keyword
            $filestring .= " " . strtoupper($filename_wildcard);
            $filestring .= " " . strtolower($filename_wildcard);
            $filestring .= " ";
            for ($i=0; $i <= count($filename_arr); $i++) {
                if ($filename_arr[$i] !== "*") {
                    $filestring .= ($filename_arr[$i] !== "of" && $filename_arr[$i] !== "the") ? ucfirst($filename_arr[$i]) : $filename_arr[$i];
                } else {
                    $filestring .= "*";
                }
            }
            $filestring .= ")";
        } else {
            $filename = escape_chars($_REQUEST['filename']);
            $filestring = "filename:(" . $filename;
            // Add first letter upercase/lowercase and all lowercase/uppercase versions of keyword
            $filestring .= " " . strtoupper($filename);
            $filestring .= " " . strtolower($filename);
            $filestring .= " " . ucfirst($filename);
            $filestring .= ")";
        }
        $q[] = $filestring;
    }

    if (!empty($_REQUEST['path_parent'])) {
        if (strpos($_REQUEST['path_parent'], '*') !== false) {
            $pathparent_wildcard = str_replace('\*', '*', escape_chars($_REQUEST['path_parent']));
            $pathparent_arr = preg_split('/\b/', $pathparent_wildcard);
            $pathstring = "path_parent:(" . $pathparent_wildcard;
            // Add first letter upercase/lowercase and all lowercase/uppercase versions of keyword
            $pathstring .= " " . strtoupper($pathparent_wildcard);
            $pathstring .= " " . strtolower($pathparent_wildcard);
            $pathstring .= " ";
            for ($i=0; $i <= count($pathparent_arr); $i++) {
                if ($pathparent_arr[$i] !== "*") {
                    $pathstring .= ($pathparent_arr[$i] !== "of" && $pathparent_arr[$i] !== "the") ? ucfirst($pathparent_arr[$i]) : $pathparent_arr[$i];
                } else {
                    $pathstring .= "*";
                }
            }
            $pathstring .= ")";
        } else {
            $parentpath = escape_chars($_REQUEST['path_parent']);
            $pathstring = "path_parent:(" . $parentpath;
            // Add first letter upercase/lowercase and all lowercase/uppercase versions of keyword
            $pathstring .= " " . strtoupper($parentpath);
            $pathstring .= " " . strtolower($parentpath);
            $pathstring .= " " . ucfirst($parentpath);
            $pathstring .= ")";
        }
        $q[] = $pathstring;
    }

    if (!empty($_REQUEST['tag'])) {
        $q[] = "tag:" . $_REQUEST['tag'];
    }

    if (!empty($_REQUEST['tag_custom'])) {
        $q[] = "tag_custom:" . '"' . $_REQUEST['tag_custom'] . '"';
    }

    if (!empty($_REQUEST['inode'])) {
        $q[] = "inode:" . $_REQUEST['inode'];
    }

    if (!empty($_REQUEST['last_mod_time_low']) && !empty($_REQUEST['last_mod_time_high'])) {
        $q[] = "last_modified:[" . (string) $_REQUEST['last_mod_time_low'] . " TO " . (string) $_REQUEST['last_mod_time_high'] . "]";
    } elseif (!empty($_REQUEST['last_mod_time_low'])) {
        $q[] = "last_modified:[" . (string) $_REQUEST['last_mod_time_low'] . " TO *]";
    } elseif (!empty($_REQUEST['last_mod_time_high'])) {
        $q[] = "last_modified:[* TO " . (string) $_REQUEST['last_mod_time_high'] . "]";
    }

    if (!empty($_REQUEST['last_access_time_low']) && !empty($_REQUEST['last_access_time_high'])) {
        $q[] = "last_access:[" . (string) $_REQUEST['last_access_time_low'] . " TO " . (string) $_REQUEST['last_access_time_high'] . "]";
    } elseif (!empty($_REQUEST['last_access_time_low'])) {
        $q[] = "last_access:[" . (string) $_REQUEST['last_access_time_low'] . " TO *]";
    } elseif (!empty($_REQUEST['last_access_time_high'])) {
        $q[] = "last_access:[* TO " . (string) $_REQUEST['last_access_time_high'] . "]";
    }

    if (!empty($_REQUEST['file_size_bytes_low']) && !empty($_REQUEST['file_size_bytes_high'])) {
        $file_size_bytes_low = convertToBytes($_REQUEST['file_size_bytes_low'], $_REQUEST['file_size_bytes_low_unit']);
        $file_size_bytes_high = convertToBytes($_REQUEST['file_size_bytes_high'], $_REQUEST['file_size_bytes_high_unit']);
        $q[] = "filesize:[" . (string) $file_size_bytes_low . " TO " . (string) $file_size_bytes_high . "]";
    } elseif (!empty($_REQUEST['file_size_bytes_low'])) {
        $file_size_bytes_low = convertToBytes($_REQUEST['file_size_bytes_low'], $_REQUEST['file_size_bytes_low_unit']);
        $q[] = "filesize:[" . (string) $file_size_bytes_low . " TO *]";
    } elseif (!empty($_REQUEST['file_size_bytes_high'])) {
        $file_size_bytes_high = convertToBytes($_REQUEST['file_size_bytes_high'], $_REQUEST['file_size_bytes_high_unit']);
        $q[] = "filesize:[* TO " . (string) $file_size_bytes_high . "]";
    }

    if (!empty($_REQUEST['hardlinks_low']) && !empty($_REQUEST['hardlinks_high'])) {
        $q[] = "hardlinks:[" . (string) $_REQUEST['hardlinks_low'] . " TO " . (string) $_REQUEST['hardlinks_high'] . "]";
    } elseif (!empty($_REQUEST['hardlinks_low'])) {
        $q[] = "hardlinks:[" . (string) $_REQUEST['hardlinks_low'] . " TO *]";
    } elseif (!empty($_REQUEST['hardlinks_high'])) {
        $q[] = "hardlinks:[* TO " . (string) $_REQUEST['hardlinks_high'] . "]";
    }

    if (!empty($_REQUEST['filehash'])) {
        $q[] = "filehash:" . $_REQUEST['filehash'];
    }

    if (!empty($_REQUEST['extension'])) {
        $q[] = "extension:" . $_REQUEST['extension'];
    }

    if (!empty($_REQUEST['owner'])) {
        $q[] = "owner:" . $_REQUEST['owner'];
    }

    if (!empty($_REQUEST['group'])) {
        $q[] = "group:" . $_REQUEST['group'];
    }

    if (!empty($_REQUEST['dupe_md5'])) {
        $q[] = "dupe_md5:" . $_REQUEST['dupe_md5'];
    }

    // s3 fields
    if ($s3_index) {
        if (!empty($_REQUEST['s3_bucket'])) {
            $q[] = "s3_bucket:" . $_REQUEST['s3_bucket'];
        }
        if (!empty($_REQUEST['s3_key'])) {
            $q[] = "s3_key:" . $_REQUEST['s3_key'];
        }
        if (!empty($_REQUEST['s3_storage_class'])) {
            $q[] = "s3_storage_class:" . $_REQUEST['s3_storage_class'];
        }
        if (!empty($_REQUEST['s3_etag'])) {
            $q[] = "s3_etag:" . $_REQUEST['s3_etag'];
        }
        if (!empty($_REQUEST['s3_multipart_upload'])) {
            $q[] = "s3_multipart_upload:" . $_REQUEST['s3_multipart_upload'];
        }
        if (!empty($_REQUEST['s3_replication_status'])) {
            $q[] = "s3_replication_status:" . $_REQUEST['s3_replication_status'];
        }
        if (!empty($_REQUEST['s3_encryption_status'])) {
            $q[] = "s3_encryption_status:" . $_REQUEST['s3_encryption_status'];
        }
    }

    // Build complete search request body
    if (count($q) == 1) {
        $querystring = $q[0];
        $keyword = 
        $searchParams['body']['query']['query_string']['query'] = $querystring;
        $searchParams['body']['query']['query_string']['analyze_wildcard'] = 'true';
    } elseif (count($q) > 1) {
        $querystring = "";
        $i = 0;
        while($i<=count($q)) {
            $querystring .= $q[$i];
            if ($i < count($q)-1) {
                $querystring .= " AND ";
            }
            $i += 1;
        }
        $searchParams['body']['query']['query_string']['query'] = $querystring;
        $searchParams['body']['query']['query_string']['analyze_wildcard'] = 'true';
    } else {
        $searchParams['body'] = [ 'query' => [ 'match_all' => (object) [] ] ];
    }
    $request = $querystring;
    // Save search query
    saveSearchQuery($querystring);

    // Sort search results
    $searchParams = sortSearchResults($_REQUEST, $searchParams);

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
  <title>diskover &mdash; Advanced Search</title>
  <link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
  <link rel="stylesheet" href="css/diskover.css" media="screen" />
  <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
</head>
<body>
<?php include "nav.php"; ?>

<?php if (!isset($_REQUEST['submitted'])) {
$resultSize = getCookie('resultsize') != "" ? getCookie('resultsize') : Constants::SEARCH_RESULTS;
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
    <input type="hidden" id="queryinput" name="q" value="<?php echo $_REQUEST['q']; ?>" />
    <input type="hidden" name="resultsize" value="<?php echo $resultSize; ?>" />
<div class="container">
  <div class="form-group">
	<div class="row">
	  <div class="col-xs-6">
		<label for="filename">Filename is...</label>
		<input name="filename" value="" placeholder="somefile.m4a or someimg*.png or *file name* or directory_name" class="form-control" />
	  </div>
	  <div class="col-xs-2">
		<label for="filehash">Filehash is...</label>
		<input name="filehash" value="" placeholder="hash" class="form-control" />
	  </div>
      <?php if (!$s3_index) { ?>
      <div class="col-xs-2">
		<label for="filehash">Dupe MD5 Sum is...</label>
		<input name="dupe_md5" value="" placeholder="md5 sum" class="form-control" />
	  </div>
	  <div class="col-xs-2">
		<label for="inode">Inode is...</label>
		<input name="inode" value="" placeholder="inode num" type="number" class="form-control" />
	  </div>
      <?php } ?>
	</div>
  </div>
  <div class="form-group">
	<div class="row">
	  <div class="col-xs-12">
		<label for="path_parent">Parent path is...  </label>
		<input name="path_parent" value="" placeholder="/Users/shirosai/Music or /Users/shirosai/Downloads* or dirname* or *dir name*" class="form-control" />
	  </div>
	</div>
  </div>
  <div class="form-group">
	<div class="row">
	  <div class="col-xs-3">
		<label for="file_size_bytes_low">File size is between...</label>
		<input name="file_size_bytes_low" value="" type="number" placeholder="size" class="form-control" />
		<label for="file_size_bytes_high">and</label>
		<input name="file_size_bytes_high" value="" type="number" placeholder="size" class="form-control" />
	  </div>
      <div class="col-xs-1">
        <label>&nbsp;</label>
		<select class="form-control" name="file_size_bytes_low_unit">
		  <option value="bytes">Bytes</option>
		  <option value="KB">KB</option>
		  <option value="MB">MB</option>
		  <option value="GB">GB</option>
		</select>
        <label>&nbsp;</label>
        <select class="form-control" name="file_size_bytes_high_unit">
		  <option value="bytes">Bytes</option>
		  <option value="KB">KB</option>
		  <option value="MB">MB</option>
		  <option value="GB">GB</option>
		</select>
	  </div>
      <?php if (!$s3_index) { ?>
	  <div class="col-xs-2">
		<label for="hardlinks_low">Hardlinks is between...</label>
		<input name="hardlinks_low" value="" type="number" placeholder="2" class="form-control" />
		<label for="hardlinks_high">and</label>
		<input name="hardlinks_high" value="" type="number" placeholder="10" class="form-control" />
	  </div>
      <?php } ?>
	  <div class="col-xs-3">
		<label for="last_mod_time_low">Last modified time (utc) is between...</label>
		<input name="last_mod_time_low" value="" type="string" placeholder="2015-03-06T00:00:00 or 2016-01-22" class="form-control" />
		<label for="last_mod_time_high">and</label>
		<input name="last_mod_time_high" value="" type="string" placeholder="2017-03-06T00:00:00 or now-6M/d" class="form-control" />
	  </div>
      <?php if (!$s3_index && !$qumulo_index) { ?>
	  <div class="col-xs-3">
		<label for="last_access_time_low">Last access time (utc) is between...</label>
		<input name="last_access_time_low" value="" type="string" placeholder="2015-03-06T00:00:00 or now-2w" class="form-control" />
		<label for="last_access_time_high">and</label>
		<input name="last_access_time_high" value="" type="string" placeholder="2017-03-06T00:00:00 or now-1y" class="form-control" />
	  </div>
      <?php } ?>
	</div>
  </div>
  <div class="form-group">
	<div class="row">
      <?php if (!$s3_index) { ?>
	  <div class="col-xs-2">
		<label for="owner">Owner is...  </label>
		<input name="owner" value="" placeholder="shirosai or (NOT root)" class="form-control" />
	  </div>
	  <div class="col-xs-2">
		<label for="group">Group is...  </label>
		<input name="group" value="" placeholder="staff" class="form-control" />
	  </div>
      <?php } ?>
	  <div class="col-xs-2">
		<label for="extension">Extension is...</label>
		<input name="extension" value="" type="string" placeholder="zip or (tmp OR cache)" class="form-control" />
	  </div>
	  <div class="col-xs-2">
		<label for="tag">Tag is...</label>
		<select class="form-control" name="tag">
          <option value="" selected></option>
		  <option value="">untagged</option>
		  <option value="delete">delete</option>
		  <option value="archive">archive</option>
		  <option value="keep">keep</option>
		</select>
	  </div>
	  <div class="col-xs-4">
		<label for="tag_custom">Custom Tag is...</label>
		<select name="tag_custom" class="form-control">
             <option value="" selected></option>
        <?php foreach($customtags as $key => $value) { ?>
            <option value="<?php echo $value[0]; ?>"><?php echo $value[0]; ?></option>
        <?php } ?>
        </select>
	  </div>
	</div>
  </div>
  <div class="form-group">
	<div class="row">
	  <div class="col-xs-2">
		<label for="sort">Sort by...</label>
		<select class="form-control" name="sort" id="sort">
          <option value=""></option>
		  <option value="filename" <?php echo (getCookie('sort') === 'filename') ? 'selected' : ''; ?>>filename</option>
		  <option value="path_parent" <?php echo (getCookie('sort') === 'path_parent') ? 'selected' : ''; ?>>path_parent</option>
		  <option value="filesize" <?php echo (getCookie('sort') === 'filesize') ? 'selected' : ''; ?>>filesize</option>
          <option value="items" <?php echo (getCookie('sort') === 'items') ? 'selected' : ''; ?>>items</option>
          <option value="items_files" <?php echo (getCookie('sort') === 'items_files') ? 'selected' : ''; ?>>items (files)</option>
          <option value="items_subdirs" <?php echo (getCookie('sort') === 'items_subdirs') ? 'selected' : ''; ?>>items (subdirs)</option>
		  <option value="owner" <?php echo (getCookie('sort') === 'owner') ? 'selected' : ''; ?>>owner</option>
		  <option value="group" <?php echo (getCookie('sort') === 'group') ? 'selected' : ''; ?>>group</option>
		  <option value="last_modified" <?php echo (getCookie('sort') === 'last_modified') ? 'selected' : ''; ?>>last_modified</option>
		  <option value="last_access" <?php echo (getCookie('sort') === 'last_access') ? 'selected' : ''; ?>>last_access</option>
		  <option value="tag" <?php echo (getCookie('sort') === 'tag') ? 'selected' : ''; ?>>tag</option>
		  <option value="tag_custom" <?php echo (getCookie('sort') === 'tag_custom') ? 'selected' : ''; ?>>tag_custom</option>
          <option value="change_percent_filesize" <?php echo (getCookie('sort') === 'change_percent_filesize') ? 'selected' : ''; ?>>change_percent_filesize</option>
          <option value="change_percent_items" <?php echo (getCookie('sort') === 'change_percent_items') ? 'selected' : ''; ?>>change_percent_items</option>
          <?php if ($s3_index) { ?>
          <option value="s3_bucket" <?php echo (getCookie('sort') === 's3_bucket') ? 'selected' : ''; ?>>s3_bucket</option>
          <option value="s3_key" <?php echo (getCookie('sort') === 's3_bucket') ? 'selected' : ''; ?>>s3_key</option>
          <option value="s3_storage_class" <?php echo (getCookie('sort') === 's3_storage_class') ? 'selected' : ''; ?>>s3_storage_class</option>
          <option value="s3_etag" <?php echo (getCookie('sort') === 's3_etag') ? 'selected' : ''; ?>>s3_etag</option>
          <option value="s3_multipart_upload" <?php echo (getCookie('sort') === 's3_multipart_upload') ? 'selected' : ''; ?>>s3_multipart_upload</option>
          <option value="s3_replication_status" <?php echo (getCookie('sort') === 's3_replication_status') ? 'selected' : ''; ?>>s3_replication_status</option>
          <option value="s3_encryption_status" <?php echo (getCookie('sort') === 's3_encryption_status') ? 'selected' : ''; ?>>s3_encryption_status</option>
          <?php } ?>
		</select>
	  </div>
	  <div class="col-xs-2">
		<label for="sortorder">Sort order...</label>
		<select class="form-control" name="sortorder" id="sortorder">
          <option value=""></option>
		  <option value="asc" <?php echo (getCookie('sortorder') === 'asc') ? 'selected' : ''; ?>>asc</option>
		  <option value="desc" <?php echo (getCookie('sortorder') === 'desc') ? 'selected' : ''; ?>>desc</option>
		</select>
	  </div>
      <div class="col-xs-2">
		<label for="sort">Sort2 by...</label>
		<select class="form-control" name="sort2" id="sort2">
          <option value=""></option>
		  <option value="filename" <?php echo (getCookie('sort2') === 'filename') ? 'selected' : ''; ?>>filename</option>
		  <option value="path_parent" <?php echo (getCookie('sort2') === 'path_parent') ? 'selected' : ''; ?>>path_parent</option>
		  <option value="filesize" <?php echo (getCookie('sort2') === 'filesize') ? 'selected' : ''; ?>>filesize</option>
          <option value="items" <?php echo (getCookie('sort2') === 'items') ? 'selected' : ''; ?>>items</option>
          <option value="items_files" <?php echo (getCookie('sort2') === 'items_files') ? 'selected' : ''; ?>>items (files)</option>
          <option value="items_subdirs" <?php echo (getCookie('sort2') === 'items_subdirs') ? 'selected' : ''; ?>>items (subdirs)</option>
		  <option value="owner" <?php echo (getCookie('sort2') === 'owner') ? 'selected' : ''; ?>>owner</option>
		  <option value="group" <?php echo (getCookie('sort2') === 'group') ? 'selected' : ''; ?>>group</option>
		  <option value="last_modified" <?php echo (getCookie('sort2') === 'last_modified') ? 'selected' : ''; ?>>last_modified</option>
		  <option value="last_access" <?php echo (getCookie('sort2') === 'last_access') ? 'selected' : ''; ?>>last_access</option>
		  <option value="tag" <?php echo (getCookie('sort2') === 'tag') ? 'selected' : ''; ?>>tag</option>
		  <option value="tag_custom" <?php echo (getCookie('sort2') === 'tag_custom') ? 'selected' : ''; ?>>tag_custom</option>
          <option value="change_percent_filesize" <?php echo (getCookie('sort2') === 'change_percent_filesize') ? 'selected' : ''; ?>>change_percent_filesize</option>
          <option value="change_percent_items" <?php echo (getCookie('sort2') === 'change_percent_items') ? 'selected' : ''; ?>>change_percent_items</option>
          <?php if ($s3_index) { ?>
          <option value="s3_bucket" <?php echo (getCookie('sort2') === 's3_bucket') ? 'selected' : ''; ?>>s3_bucket</option>
          <option value="s3_key" <?php echo (getCookie('sort2') === 's3_bucket') ? 'selected' : ''; ?>>s3_key</option>
          <option value="s3_storage_class" <?php echo (getCookie('sort2') === 's3_storage_class') ? 'selected' : ''; ?>>s3_storage_class</option>
          <option value="s3_etag" <?php echo (getCookie('sort2') === 's3_etag') ? 'selected' : ''; ?>>s3_etag</option>
          <option value="s3_multipart_upload" <?php echo (getCookie('sort2') === 's3_multipart_upload') ? 'selected' : ''; ?>>s3_multipart_upload</option>
          <option value="s3_replication_status" <?php echo (getCookie('sort2') === 's3_replication_status') ? 'selected' : ''; ?>>s3_replication_status</option>
          <option value="s3_encryption_status" <?php echo (getCookie('sort2') === 's3_encryption_status') ? 'selected' : ''; ?>>s3_encryption_status</option>
          <?php } ?>
		</select>
	  </div>
	  <div class="col-xs-2">
		<label for="sortorder">Sort2 order...</label>
		<select class="form-control" name="sortorder2" id="sortorder2">
          <option value=""></option>
		  <option value="asc" <?php echo (getCookie('sortorder2') === 'asc') ? 'selected' : ''; ?>>asc</option>
		  <option value="desc" <?php echo (getCookie('sortorder2') === 'desc') ? 'selected' : ''; ?>>desc</option>
		</select>
	  </div>
	</div>
    </div>
    <?php if ($s3_index) { ?>
    <div class="form-group">
    <div class="row">
      <div class="col-xs-4">
        <label for="s3_bucket"><span style="color:#FD9827;">S3 Bucket...</span>  </label>
        <input name="s3_bucket" value="" placeholder="s3 bucket name" class="form-control" />
      </div>
      <div class="col-xs-4">
        <label for="s3_key"><span style="color:#FD9827;">S3 Key is...</span>  </label>
        <input name="s3_key" value="" placeholder="s3 key" class="form-control" />
      </div>
      <div class="col-xs-4">
        <label for="s3_storage_class"><span style="color:#FD9827;">S3 Storage class is...</span></label>
        <select class="form-control" name="s3_storage_class">
          <option value="" selected></option>
          <option value="STANDARD">Standard</option>
          <option value="RRS">Reduced Redundancy Storage (RRS)</option>
          <option value="IA">Standard Infrequent Access (IA)</option>
          <option value="Z-IA">One-Zone Infrequent Access (Z-IA)</option>
          <option value="GLACIER">Glacier</option>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-4">
        <label for="s3_etag"><span style="color:#FD9827;">S3 Etag...</span>  </label>
        <input name="s3_etag" value="" placeholder="s3 etag" class="form-control" />
      </div>
      <div class="col-xs-2">
        <label for="s3_multipart_upload"><span style="color:#FD9827;">S3 Multipart upload is...</span></label>
        <select class="form-control" name="s3_multipart_upload">
          <option value="" selected></option>
          <option value="true">true</option>
          <option value="false">false</option>
        </select>
      </div>
      <div class="col-xs-3">
        <label for="s3_replication_status"><span style="color:#FD9827;">S3 Replication status...</span>  </label>
        <input name="s3_replication_status" value="" placeholder="s3 replication status" class="form-control" />
      </div>
      <div class="col-xs-3">
        <label for="s3_encryption_status"><span style="color:#FD9827;">S3 Encryption status is...</span></label>
        <select class="form-control" name="s3_encryption_status">
          <option value="" selected></option>
          <option value="NOT-SSE">Unencrypted (NOT-SSE)</option>
          <option value="SSE-S3">S3-managed keys (SSE-S3)</option>
          <option value="SSE-KMS">KMS-managed keys (SSE-KMS)</option>
        </select>
      </div>
    </div>
  </div>
    <?php } ?>
    <div class="form-group">
    <div class="row">
        <div class="col-xs-2">
  		<label for="tags">Show request JSON?</label>
  		<input type="checkbox" name="debug" value="true"<?php echo($_REQUEST['debug'] ? " checked" : ""); ?> />
  	  </div>
      <div class="col-xs-2">
		<label for="sortorder">Doc type...</label>
          <select class="form-control" name="doctype">
              <option value="">all</option>
              <option value="file">file</option>
              <option value="directory">directory</option>
          </select>
      </div>
    </div>
  </div>
  </div>
  <button type="reset" class="btn btn-default">Clear</button>
  <button type="submit" class="btn btn-primary" onclick="setCookies();">Search</button>
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
$(document).ready(function () {
    // listen for msgs from diskover socket server
    listenSocketServer();
});

function setCookies() {
    // set cookies on form submit
    setCookie('sort', document.getElementById('sort').value);
    setCookie('sort2', document.getElementById('sort2').value);
    setCookie('sortorder', document.getElementById('sortorder').value);
    setCookie('sortorder2', document.getElementById('sortorder2').value);
}
</script>
<div id="loading">
  <img id="loading-image" width="32" height="32" src="images/ajax-loader.gif" alt="Updating..." />
  <div id="loading-text"></div>
</div>
<iframe name="hiddeniframe" width=0 height=0 style="display:none;"></iframe>
<?php require "logform.php"; ?>
</body>
</html>
