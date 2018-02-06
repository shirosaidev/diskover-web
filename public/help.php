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
    <style>
    pre {
        background-color:#060606!important;
        color: #56B6C2!important;
        border: none;
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

			<div class="col-xs-6">
				<h4>Tagging files</h4>
				<p>To tag files in a diskover index, perform a search and then on the results page click a tag button (<em><span class="text-warning"><i class="glyphicon glyphicon-trash"></i> delete</span>, <span class="text-success"><i class="glyphicon glyphicon-cloud-upload"></i> archive</span> or <span class="text-info"><i class="glyphicon glyphicon-floppy-saved"></i> keep</span></em>) for each file. After you have selected files for tagging, click the <span style="color:red;"><i class="glyphicon glyphicon-tag"></i> Tag files</span> button and the files will be tagged in Elasticsearch. </p><p>Select All buttons and Search within text input select only the results on the current page.</p><p><strong>After tagging you may need to reload the page to see the updated results from Elasticsearch.</strong></p>
				<br>
				<h4>Search query examples</h4>

				<div class="well well-sm">
					<p><small class="text-success">all files in directory:</small><br>
						<strong>path_parent:"/Users/shirosai/Downloads"</strong></p>
					<p><small class="text-success">all files in directory and all subdirs:</small><br>
						<strong>path_parent:\/Users\/shirosai\/Downloads*</strong></p>
					<p><small class="text-success">files that haven't been modified in over 3 months and less than 5 years:</small><br>
						<strong>last_modified: [now-5y TO now-3M]</strong></p>
					<p><small class="text-success">files that haven't been modified or accessed in over 1 year:</small><br><strong>last_modified:[* TO now-1y] AND last_access:[* TO now-1y]</strong></p>
					<p><small class="text-success">image files:</small><br>
						<strong>extension:(jpg OR gif OR png OR tif OR tiff OR dpx OR exr OR psd OR bmp OR tga)</strong></p>
					<p><small class="text-success">audio files:</small><br>
						<strong>extension:(aif OR iff OR m3u OR m4a OR mid OR mp3 OR mpa OR wav OR wma)</strong></p>
					<p><small class="text-success">video files:</small><br>
						<strong>extension:(asf OR avi OR flv OR m4v OR mov OR mp4 OR mpg OR rm OR vob OR wmv)</strong></p>
					<p><small class="text-success">temp files:</small><br>
						<strong>extension:(cache OR tmp OR temp OR bak OR old)</strong></p>
					<p><small class="text-success">compressed files:</small><br>
						<strong>extension:(7z OR deb OR gz OR pkg OR rar OR rpm OR tar OR zip OR zipx)</strong></p>
					<p><small class="text-success">image sequence img001.dpx, img002.dpx, im003.dpx:</small><br>
						<strong>filename:img*.dpx</strong></p>
					<p><small class="text-success">duplicate files:</small><br>
						<strong>is_dupe:true</strong></p>
					<p><small class="text-success">all files with custom tag "version 8":</small><br>
						<strong>tag_custom:"version 8"</strong></p>
					<p><small class="text-success">all files with custom tag "version 8" and larger than 10 MB:</small><br>
						<strong>tag_custom:"version 8" AND filesize:>10485760</strong></p>
					<p><small class="text-success">all files tagged delete:</small><br>
						<strong>tag:"delete"</strong></p>
				</div>

				<h4>diskover.py socket command examples</h4>

				<div class="well well-sm">
					<p><small class="text-success">nc (netcat) example:</small></p>
					<pre>echo -n '{"action": "tagdupes"}' | nc -u 127.0.0.1 9999</pre>
					<p><small class="text-success">tag duplicate files in index:</small><br>
						<pre>{"action": "tagdupes", "index": "diskover-2017.04.22"}</pre></p>
					<p><small class="text-success">crawl directory and save to index:</small><br><pre>{"action": "crawl", "path": "/Users/cp", "index": "diskover-2017.10.06"}</pre></p>
					<p><small class="text-success">crawl directory (recursive) using 8 threads and save to default index:</small><br><pre>{"action": "crawl", "path": "/Users/cp/Downloads", "threads": 8}</pre></p>
					<p><small class="text-success">reindex (freshen) directory and update default index:</small><br><pre>{"action": "reindex", "path": "/Users/cp/Downloads"}</pre></p>
					<p><small class="text-success">reindex (freshen) directory and all subdirs and update default index:</small><br><pre>{"action": "reindex", "path": "/Users/cp/Documents", "recursive": "true"}</pre></p>
                    <p><small class="text-success">calculate all directory sizes/items and update default index:</small><br><pre>{"action": "dirsize", "index": "diskover-2017.12.11"}</pre></p>
                    <p><small class="text-success">calculate single directory size/items and update default index:</small><br><pre>{"action": "dirsize", "path": "/Users/cp/Documents"}</pre></p>
                </div>
			</div>


			<div class="col-xs-6">
				<h4>Rest API</h4>

				<div class="well well-sm">
					<h4>Get (with curl or web browser)</h4>
					<p>Getting file tag info is done with the GET method.</p>
					<p><small class="text-success">Curl example:</small></p>
					<pre>curl -X GET http://localhost:8000/api.php/index/endpoint</pre>
					<p><small class="text-success">List files with custom tag “version 1”:</small></p>
					<pre>GET http://localhost:8000/api.php/diskover-2018.01.17/tagfiles?custom=version%201</pre>

					<p><small class="text-success">List files tagged archive:</small></p>

					<pre>GET http://localhost:8000/api.php/diskover-2018.01.17/tagfiles?archive</pre>

					<p><small class="text-success">List total file size for tags:</small></p>

					<pre>GET http://localhost:8000/api.php/diskover-2018.01.17/tagsizes</pre>

					<p><small class="text-success">List total file size for tag:</small></p>

					<pre>GET http://localhost:8000/api.php/diskover-2018.01.17/tagsize?delete</pre>

					<p><small class="text-success">List tag size for custom tag “version 1”:</small></p>

					<pre>GET http://localhost:8000/api.php/diskover-2018.01.17/tagsize?custom=version%201</pre>

					<p><small class="text-success">List total number of files for tags:</small></p>

					<pre>GET http://localhost:8000/api.php/diskover-2018.01.17/tagcounts</pre>

					<p><small class="text-success">List total number of files for tag delete:</small></p>

					<pre>GET http://localhost:8000/api.php/diskover-2018.01.17/tagcount?delete</pre>

					<p><small class="text-success">List total number of files for custom tag “version 1”:</small></p>

					<pre>GET http://localhost:8000/api.php/diskover-2018.01.17/tagcount?custom=version+1</pre>

					<p><small class="text-success">List all duplicate files:</small></p>

					<pre>GET http://localhost:8000/api.php/diskover-2018.01.17/dupes</pre>

					<p><small class="text-success">List total file size of duplicate files:</small></p>

					<pre>GET http://localhost:8000/api.php/diskover-2018.01.17/dupessize</pre>

					<br>
					<h4>Update (with JSON object)</h4>
					<p>Updating file tags is done with the PUT method. You can send a JSON object in the body. The call returns the number of files affected.</p>

					<p><small class="text-success">Curl example:</small></p>
					<pre>curl -X PUT http://localhost:8000/api.php/index/endpoint -d {}</pre>

					<p><small class="text-success">Tag files delete:</small></p>

					<pre>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagfiles</pre>
					<pre>{"tag": "delete", "files": [“/Users/shirosai/file1.png", “/Users/shirosai/file2.png"]}</pre>

					<p><small class="text-success">Tag all files in directory archive:</small></p>

					<pre>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagdir</pre>
					<pre>{"tag": "archive", "path_parent": "/Users/shirosai/Downloads"}</pre>

					<p><small class="text-success">Tag all files in directory with custom tag “version 1”:</small></p>

					<pre>PUT http://localhost:8000/api.php/diskover-2018.01.17/tagdir</pre>
					<pre>{"tag_custom”: “version 1“, "path_parent": "/Users/shirosai/Downloads"}</pre>
				</div>

			</div>
		</div>
			<div class="row">
				<div class="col-xs-2">
					<p>
						<a class="btn btn-primary btn-lg" onclick="window.history.back()">
							<</a>
					</p>
				</div>
			</div>
		</div>
		<script language="javascript" src="js/jquery.min.js"></script>
		<script language="javascript" src="js/bootstrap.min.js"></script>
		<script language="javascript" src="js/diskover.js"></script>
</body>

</html>
