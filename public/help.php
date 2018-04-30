<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
use Elasticsearch\Common\Exceptions\Missing404Exception;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Auth.php";
require "../src/diskover/Diskover.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>diskover &mdash; Help</title>
	<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
	<link rel="stylesheet" href="css/diskover.css" media="screen" />
	<link rel="icon" type="image/png" href="images/diskoverfavico.png" />
    <style>
    code {
        background-color:#333!important;
        color: #56B6C2!important;
    }
    strong {
        color: lightgray;
    }
    </style>
</head>
<body>
	<?php include "nav.php"; ?>

	<div class="container" style="margin-top:70px;">
		<div class="row">
			<div class="col-xs-12">
				<h1><i class="glyphicon glyphicon-question-sign"></i> Help</h1>
			</div>
		</div>
        <div class="row">
            <div class="col-xs-10">
                <div class="alert alert-dismissible alert-info">
                    <i class="glyphicon glyphicon-info-sign"></i> <strong>For discussions, questions or feature requests, please ask on <a href="https://groups.google.com/forum/?hl=en#!forum/diskover" target="_blank">Google Group</a>. For any bugs, please submit an issue on <a href="https://github.com/shirosaidev/diskover/issues" target="_blank">GitHub issues</a> page.</strong>
                </div>
            </div>
        </div>
		<div class="row">
			<div class="col-xs-6">
                <div class="well">
                    <h3>Tagging files</h3>
    				<p>To tag files or directories perform a search and then on the results page click a tag button <strong><i class="glyphicon glyphicon-tag"></i></strong> for the file or directory you want to tag. From the drop-down menu you can select from standard tags (<em><span class="delete"><i class="glyphicon glyphicon-trash"></i> delete</span>, <span class="archive"><i class="glyphicon glyphicon-cloud-upload"></i> archive</span> or <span class="keep"><i class="glyphicon glyphicon-floppy-saved"></i> keep</span></em>) or pick one of the custom tags or add a new custom tag. Custom tags are created with "<strong>tag name|#hexcolor</strong>" (no quotes).<br />
                    <p>You can remove a tag and custom tag on the tag menu by clicking the "untagged" and/or "Remove custom tag" menu options.<br />
                    <p>In the tag menu you can also apply tags to all on page to copy the current selected tag to all on the current page. When clicking the tag menu for directories, you also have the option to tag all sub directories (recursively) or tag all files (recursively). This also works to remove all tags recursively if the selected item has no tags.<br />
                    <p><strong>After tagging you may need to reload the page to see the updated results from Elasticsearch.</strong><br />
                </div>
				<div class="well">
                    <h3>Rest API</h3>
					<h4>Get (with curl or web browser)</h4>
					<p>Getting file/directory tag info is done with the GET method.<br />
					<p>Curl example:<br />
					<code>curl -X GET http://localhost:8000/api.php/index/endpoint</code></p>
                    <p>List all files with no tag (untagged):<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tags?tag=&type=file</code></p>
                    <p>List all directories with no tag (untagged) and no custom tag:<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tags?tag=&tag_custom=&type=directory</code></p>
					<p>List files with custom tag "version 1":<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tags?tag_custom=version%201&type=file</code></p>
                    <p>List directories with custom tag "version 1":<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tags?tag_custom=version%201&type=directory</code></p>
                    <p>List files/directories (all items) with custom tag "version 1":<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tags?tag_custom=version%201</code></p>
					<p>List files tagged archive:<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tags?tag=archive&type=file</code></p>
                    <p>List directories tagged delete:<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tags?tag=delete&type=directory</code></p>
					<p>List total size (in bytes) of files for each tag:<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tagsize?type=file</code></p>
					<p>List total size (in bytes) of files tagged delete:<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tagsize?tag=delete&type=file</code></p>
					<p>List total size (in bytes) of files with custom tag "version 1":<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tagsize?tag_custom=version%201&type=file</code></p>
					<p>List total number of files for each tag:<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tagcount&type=file</code></p>
					<p>List total number of files tagged delete:<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tagcount?tag=delete&type=file</code></p>
					<p>List total number of files with custom tag "version 1":<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tagcount?tag_custom=version+1&type=file</code></p>
                    <p>List total number of files tagged keep and custom tag "version 1":<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/tagcount?tag=keep&tag_custom=version+1&type=file</code></p>
					<p>List all duplicate files:<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/dupes</code></p>
					<p>List total file size of duplicate files:<br />
					<code>GET http://localhost:8000/api.php/diskover-2018.01.17/dupessize</code></p>
					<br>
					<h4>Update (with JSON object)</h4>
					<p>Updating file/directory tags is done with the PUT method. You can send a JSON object in the body. The call returns the number of items affected.<br />
					<p>Curl example:<br />
					<code>curl -X PUT http://localhost:8000/api.php/index/endpoint -d {}</code></p>
					<p>Tag files delete:<br />
					<code>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagfile</code></p>
					<code>{"tag": "delete", "files": ["/Users/shirosai/file1.png", "/Users/shirosai/file2.png"]}</code></p>
                    <p>Tag files delete and custom tag "version 1":<br />
					<code>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagfile</code></p>
					<code>{"tag": "delete", "tag_custom": "version 1", "files": ["/Users/shirosai/file1.png", "/Users/shirosai/file2.png"]}</code></p>
					<p>Tag directory archive (non-recursive):<br />
					<code>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagdir</code></p>
					<code>{"tag": "archive", "path_parent": "/Users/shirosai/Downloads"}</code></p>
					<p>Tag directory and all files in directory with custom tag "version 1" (non-recursive):<br />
					<code>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagdir</code></p>
					<code>{"tag_custom": "version 1", "path_parent": "/Users/shirosai/Downloads", "tagfiles": "true"}</code></p>
                    <p>Tag directory and all sub dirs (no files) with custom tag "version 1" (recursive):<br />
					<code>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagdir</code></p>
					<code>{"tag_custom": "version 1", "path_parent": "/Users/shirosai/Downloads", "recursive": "true"}</code></p>
                    <p>Tag directory and all items (files/directories) in directory and all sub dirs with custom tag "version 1" (recursive):<br />
					<code>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagdir</code></p>
					<code>{"tag_custom": "version 1", "path_parent": "/Users/shirosai/Downloads", "recursive": "true", "files": "true"}</code></p>
                    <p>Remove tag from directory and all files in directory (non-recursive):<br />
					<code>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagdir</code></p>
					<code>{"tag": "", "path_parent": "/Users/shirosai/Downloads", "tagfiles": "true"}</code></p>
                    <p>Remove tag_custom from directory and all files in directory (non-recursive):<br />
					<code>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagdir</code></p>
					<code>{"tag_custom": "", "path_parent": "/Users/shirosai/Downloads", "tagfiles": "true"}</code></p>
                    <p>Remove tag and tag_custom from directory and all items (files/directories) in directory and all sub dirs (recursive):<br />
					<code>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagdir</code></p>
					<code>{"tag": "", "tag_custom": "", "path_parent": "/Users/shirosai/Downloads", "recursive": "true"}</code></p>
				</div>
			</div>
			<div class="col-xs-6">
				<div class="well">
                    <h3>Smart search examples</h3>
                    <p>To start a smartsearch, press the "<strong>!</strong>" key or for paths use "<strong>/</strong>" in the search input. Smart searches can be edited on the Admin page.</p>
                    <p>To disable smartsearch and just use normal ES query, start the search with "<strong>\</strong>".</p>
					<p>all files in directory:<br>
						<strong>/Users/shirosai/Downloads</strong><br />
					<p>all files in directory and all subdirs:<br>
						<strong>/Users/shirosai/Downloads*</strong><br />
					<p>image files:<br>
						<strong>!img</strong><br />
					<p>audio files:<br>
						<strong>!aud</strong><br />
					<p>video files:<br>
						<strong>!vid</strong><br />
                    <p>document files:<br>
						<strong>!doc</strong><br />
					<p>temp files:<br>
						<strong>!tmp</strong><br />
					<p>comcodessed files:<br>
						<strong>!comcodess</strong><br />
                    <p>database files:<br>
						<strong>!datab</strong><br />
                    <p>disc image files:<br>
						<strong>!discimg</strong><br />
                    <p>executable files:<br>
						<strong>!exe</strong><br />
                    <p>web files:<br>
						<strong>!web</strong><br />
                    <p>code files:<br>
						<strong>!code</strong><br />
                    <p>system files:<br>
						<strong>!sys</strong><br />
				</div>
				<div class="well">
                    <h3>Search query examples</h3>
					<p>all files in directory:<br>
						<strong>path_parent:"/Users/shirosai/Downloads"</strong><br />
					<p>all files in directory and all subdirs:<br>
						<strong>path_parent:\/Users\/shirosai\/Downloads*</strong><br />
					<p>files that haven't been modified in over 3 months and less than 5 years:<br>
						<strong>last_modified: [now-5y TO now-3M]</strong><br />
					<p>files that haven't been modified or accessed in over 1 year:<br><strong>last_modified:[* TO now-1y] AND last_access:[* TO now-1y]</strong><br />
					<p>image files:<br>
						<strong>extension:(jpg OR gif OR png OR tif OR tiff OR dpx OR exr OR psd OR bmp OR tga)</strong><br />
					<p>audio files:<br>
						<strong>extension:(aif OR iff OR m3u OR m4a OR mid OR mp3 OR mpa OR wav OR wma)</strong><br />
					<p>video files:<br>
						<strong>extension:(asf OR avi OR flv OR m4v OR mov OR mp4 OR mpg OR rm OR vob OR wmv)</strong><br />
					<p>temp files:<br>
						<strong>extension:(cache OR tmp OR temp OR bak OR old)</strong><br />
					<p>comcodessed files:<br>
						<strong>extension:(7z OR deb OR gz OR pkg OR rar OR rpm OR tar OR zip OR zipx)</strong><br />
					<p>image sequence img001.dpx, img002.dpx, im003.dpx:<br>
						<strong>filename:img*.dpx</strong><br />
					<p>duplicate files:<br>
						<strong>dupe_md5:(NOT "")</strong><br />
					<p>all files with custom tag "version 8":<br>
						<strong>tag_custom:"version 8"</strong><br />
					<p>all files with custom tag "version 8" and larger than 10 MB:<br>
						<strong>tag_custom:"version 8" AND filesize:>10485760</strong><br />
					<p>all files tagged delete:<br>
						<strong>tag:"delete"</strong><br />
				</div>
                <div class="well">
                    <h3>diskover.py socket command examples</h3>
					<p>nc (netcat) example:<br />
					<code>echo -n '{"action": "finddupes"}' | nc 127.0.0.1 9999</code></p>
					<p>tag duplicate files in index:<br>
						<code>{"action": "finddupes", "index": "diskover-2017.04.22"}</code></p>
					<p>crawl directory and save to diskover-2017.10.06 index:<br><code>{"action": "crawl", "path": "/Users/cp", "index": "diskover-2017.10.06"}</code></p>
					<p>crawl directory (recursive) using 8 threads and save to default index:<br><code>{"action": "crawl", "path": "/Users/cp/Downloads", "threads": 8}</code></p>
					<p>reindex (freshen) directory (non-recursive) and update default index:<br><code>{"action": "reindex", "path": "/Users/cp/Downloads"}</code></p>
					<p>reindex (freshen) directory and all subdirs (recursive) and update default index:<br><code>{"action": "reindex", "path": "/Users/cp/Documents", "recursive": "true"}</code></p>
                </div>
			</div>
		</div>
		</div>
        <br />
		<script language="javascript" src="js/jquery.min.js"></script>
		<script language="javascript" src="js/bootstrap.min.js"></script>
		<script language="javascript" src="js/diskover.js"></script>
</body>

</html>
