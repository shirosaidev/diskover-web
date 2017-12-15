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

$message = $_REQUEST['message'];

// Check if file ID was provided
if (empty($_REQUEST['id'])) {
    $message = 'Doc ID not found, please go back and reload page.';
} else {
    // Connect to Elasticsearch
    $client = connectES();

    // Try to get file from Elasticsearch
    try {
        $file = $client->get([
            'id'    => $_REQUEST['id'],
            'index' => $_REQUEST['index'],
            'type'  => $_REQUEST['doctype']
        ]);
        $file = $file['_source'];
    } catch (Missing404Exception $e) {
        $message = 'Doc ID not found, please go back and reload page.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>diskover &mdash; File View</title>
	<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
  <link rel="stylesheet" href="css/diskover.css" media="screen" />
</head>
<body>
  <?php include "nav.php"; ?>
  <div class="container" id="message" style="margin-top:70px;">
<?php
if (!empty($message)) {
?>
<div class="row">
  <div class="alert alert-dismissible alert-danger col-xs-8">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <span class="glyphicon glyphicon-exclamation-sign"></span> <strong><?php echo $message; ?>
  </div>
</div>
<?php
exit();
}
?>
</div>

<div class="container">
  <div class="row">
    <div class="col-xs-12">
      <h2 class="path"><?php echo ($_REQUEST['doctype'] == 'file') ? '<i class="glyphicon glyphicon-file" style="color:#738291;"></i>' : '<i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;"></i>'; ?> <a href="advanced.php?submitted=true&amp;p=1&amp;filename=<?php echo rawurlencode($file['filename']); ?>"><?php echo $file['filename']; ?></a></h2>
      <h4 class="path">Full path: <?php echo $file['path_parent']."/".$file['filename']; ?></h4>
      <h5 class="path"><i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;"></i> Parent path: <a href="advanced.php?submitted=true&amp;p=1&amp;path_parent=<?php echo rawurlencode($file['path_parent']); ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>"><?php echo $file['path_parent']; ?></a></h5>
    </div>
  </div>
  <div class="row">
    <div class="col-xs-6">
      <ul class="list-group">
        <li class="list-group-item">
          <span class="badge"><?php echo formatBytes($file['filesize']); ?></span>
          Filesize
        </li>
        <?php if ($_REQUEST['doctype'] == 'directory') { ?>
        <li class="list-group-item">
            <span class="badge"><?php echo $file['items']; ?></span>
            Items
        </li>
        <?php } ?>
        <?php if ($_REQUEST['doctype'] == 'file') { ?>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['extension']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;extension=<?php echo $file['extension']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Extension</a>
        </li>
        <?php } ?>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['owner']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;owner=<?php echo $file['owner']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Owner</a>
        </li>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['group']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;group=<?php echo $file['group']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Group</a>
        </li>
        <?php if ($_REQUEST['doctype'] == 'file') { ?>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['inode']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;inode=<?php echo $file['inode']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Inode</a>
        </li>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['hardlinks']; ?></span>
          Hardlinks
        </li>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['filehash']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;filehash=<?php echo $file['filehash']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Filehash</a>
        </li>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['is_dupe']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;is_dupe=<?php echo $file['is_dupe']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Is dupe</a>
        </li>
        <?php } ?>
    </ul>
      </div>
      <div class="col-xs-6">
        <ul class="list-group">
      <li class="list-group-item">
        <span class="badge"><?php echo $file['last_modified']; ?></span>
        Last modified (utc)
      </li>
      <li class="list-group-item">
        <span class="badge"><?php echo $file['last_access']; ?></span>
        Last access (utc)
      </li>
      <li class="list-group-item">
        <span class="badge"><?php echo $file['last_change']; ?></span>
        Last change (utc)
      </li>
      </ul>
        <ul class="list-group">
          <li class="list-group-item">
            <span class="badge"><?php echo $_REQUEST['index']; ?></span>
            <a href="advanced.php?submitted=true&amp;p=1&amp;index=<?php echo $_REQUEST['index']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Index name</a>
          </li>
          <li class="list-group-item">
            <span class="badge"><?php echo $file['indexing_date']; ?></span>
            Indexed on (utc)
          </li>
        </ul>
        <ul class="list-group">
          <li class="list-group-item">
            <span class="badge"><?php echo $file['tag']; ?></span>
            <a href="advanced.php?submitted=true&amp;p=1&amp;tag=<?php echo $file['tag']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Tag</a>
          </li>
          <li class="list-group-item">
            <span class="badge"><?php echo $file['tag_custom']; ?></span>
            <a href="advanced.php?submitted=true&amp;p=1&amp;tag_custom=<?php echo $file['tag_custom']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Custom Tag</a>
          </li>
        </ul>
      </div>
    </div>
  <div class="row">
    <div class="col-xs-2">
      <p><a class="btn btn-primary btn-lg" onclick="window.history.back()">< </a></p>
    </div>
  </div>
</div>
<script language="javascript" src="js/jquery.min.js"></script>
<script language="javascript" src="js/bootstrap.min.js"></script>
<script language="javascript" src="js/diskover.js"></script>
</body>
</html>
