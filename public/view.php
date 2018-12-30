<?php
/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
use Elasticsearch\Common\Exceptions\Missing404Exception;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Auth.php";
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
        $fileid = $file['_id'];
        $filedoctype = $file['_type'];
        $file = $file['_source'];
    } catch (Missing404Exception $e) {
        $message = 'Doc ID not found, please go back and reload page.';
    }
}

// set fullpath, parentpath and filename and check for root /
if ($file['path_parent'] === "/") {
    $parentpath = $file['path_parent'];
    if ($file['filename'] === "") { // root /
        $filename = '/';
        $fullpath = '/';
    } else {
        $filename = $file['filename'];
        $fullpath = '/' . $filename;
    }
} else {
    $fullpath = $file['path_parent'] . '/' . $file['filename'];
    $parentpath = $file['path_parent'];
    $filename = $file['filename'];
}

// see if there are any extra custom fields to add
$extra_fields = get_extra_fields();

// get crawl elased time for directory
if ($filedoctype == 'directory') {
    $crawltime = $file['crawl_time'];
}

// Grab all the custom tags from file
$customtags = get_custom_tags();

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
  <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
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
      <?php if ($s3_index && $file['path_parent'] == '/') { $foldericon = '<i class="glyphicon glyphicon-cloud" style="color:#FD9827;"></i>'; } else if ($s3_index && $file['path_parent'] == '/s3') { $foldericon = '<i class="glyphicon glyphicon-cloud-upload" style="color:#FD9827;"></i>'; } else { $foldericon = '<i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;"></i>'; } ?>
      <h2 class="path"><?php echo ($_REQUEST['doctype'] == 'file') ? '<i class="glyphicon glyphicon-file" style="color:#738291;"></i>' : $foldericon; ?> <a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;filename=<?php echo rawurlencode($file['filename']); ?>&amp;path_parent=<?php echo rawurlencode($file['path_parent']); echo ($_REQUEST['doctype'] == 'file') ? '&doctype=file' : '&doctype=directory'; ?>"><?php echo $filename; ?></a></h2>
      <!-- tag dropdown -->
      <form id="changetag" name="changetag" class="form-inline">
      <input type="hidden" name="id" value="<?php echo $fileid; ?>">
      <input type="hidden" name="doctype" value="<?php echo $filedoctype; ?>">
      <input type="hidden" name="tag" value="" id="tag">
      <input type="hidden" name="tag_custom" value="" id="tag_custom">
      <div class="dropdown">
          <?php
          $tags = "";
          if ($file['tag']) {
              if ($file['tag'] === "delete") {
                  $tags .= "<i class=\"glyphicon glyphicon-trash delete\"></i> <span class=\"delete\">delete</span>";
              } elseif ($file['tag'] === "archive") {
                  $tags .= "<i class=\"glyphicon glyphicon-cloud-upload archive\"></i> <span class=\"archive\">archive</span>";
              } elseif ($file['tag'] === "keep") {
                  $tags .= "<i class=\"glyphicon glyphicon-floppy-saved keep\"></i> <span class=\"keep\">keep</span>";
              }
          }
          if ($file['tag_custom']) {
              if ($file['tag']) {
                  $tags .= "&nbsp;&nbsp;";
              }
              $color = get_custom_tag_color($file['tag_custom']);
              $tags .= "<span style=\"color:". $color ."\"><i class=\"glyphicon glyphicon-tag\"></i> " . $file['tag_custom'] . "</span>";
          }
          ?>
          <button title="tags" class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown"><?php if ($tags) { echo $tags; } else { echo "<i class=\"glyphicon glyphicon-tag\"></i> <strong>Untagged</strong>. Add tag..."; } ?>
              <span class="caret"></span></button>
              <ul class="dropdown-menu">
                  <li onclick="$('#tag').val('delete'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-trash delete"></i> <span class="delete">delete</span></a></li>
                  <li onclick="$('#tag').val('archive'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-cloud-upload archive"></i> <span class="archive">archive</span></a></li>
                  <li onclick="$('#tag').val('keep'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-floppy-saved keep"></i> <span class="keep">keep</span></a></li>
                  <li onclick="$('#tag').val('null'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-remove-sign" style="color:#555;"></i> <span class="untagged">untagged</span></a></li>
                  <li class="divider"></li>
                  <?php foreach ($customtags as $key => $value) { ?>
                    <li onclick="$('#tag_custom').val('<?php echo $value[0]; ?>'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-tag" style="color:<?php echo $value[1]; ?>"></i> <span style="color:<?php echo $value[1]; ?>"><?php echo $value[0]; ?></span></a></li>
                  <?php } ?>
                  <li onclick="$('#tag_custom').val('null'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-remove" style="color:gray"></i> <span style="color:gray">remove custom tag</span></a></li>
                  <li>
                          <input type="text" name="tagtext" id="tagtext" class="form-control input" style="margin-left:12px;" value="" placeholder="Add new...">
                          <button class="btn btn-default btn-sm" onclick="$('#tag_custom').val(document.getElementById('tagtext').value); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag').serialize(),success: function() { location.reload(); } }); return false;" type="submit"> <i class="glyphicon glyphicon-plus"></i></button><br />
                          <span style="margin:0px;padding:0px;margin-left:22px;font-size:11px;color:#666;">tag name|#hexcolor</span>
                  </li>
                  <li class="divider"></li>
                  <?php if ($result['_type'] == 'directory') { ?>
                  <li onclick="$('#tag').val('tagall_subdirs_recurs'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-folder-open" style="color:gray"></i> <span style="color:gray">Apply tags to subdirs (recursive)</span></a></li>
                  <li onclick="$('#tag').val('tagall_files_recurs'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-file" style="color:gray"></i> <span style="color:gray">Apply tags to files (recursive)</span></a></li>
                  <?php } ?>
                  </form>
                  <li><a href="admin.php?index=<?php echo $esIndex;?>&amp;index2=<?php echo $esIndex2;?>"><span style="color:darkgray">Edit tags</span></a></li>
              </ul>
          </div>
         <!-- end tag dropdown -->
      <h4 class="path">Full path: <span id="fullpath"><?php echo $fullpath; ?></span></h4> <a href="#" class="btn btn-default btn-xs file-btns" onclick="copyToClipboard('#fullpath')">Copy text</a>
      <?php if ($_REQUEST['doctype'] == 'directory') { ?>
          <div class="dropdown" style="display:inline-block;">
              <button title="analytics" class="btn btn-default dropdown-toggle btn-xs file-btns" type="button" data-toggle="dropdown"><i class="glyphicon glyphicon-stats"></i>
                  <span class="caret"></span></button>
                  <ul class="dropdown-menu">
                      <li class="small"><a href="filetree.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-tree-conifer"></i> filetree</a></li>
                      <li class="small"><a href="treemap.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-th-large"></i> treemap</a></li>
                      <li class="small"><a href="heatmap.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-fire"></i> heatmap</a></li>
                          <li class="small"><a href="hotdirs.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-fire"></i> hotdirs</a></li>
                          <li class="small"><a href="top50.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-th-list"></i> top 50</a></li>
                          <li class="small"><a href="dupes.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>&amp;mindupes=<?php echo $_COOKIE['mindupes']; ?>"><i class="glyphicon glyphicon-duplicate"></i> dupes</a></li>
                          <li class="small"><a href="hardlinks.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>&amp;minhardlinks=<?php echo $_COOKIE['minhardlinks']; ?>"><i class="glyphicon glyphicon-link"></i> hardlinks</a></li>
                      </ul>
              </div>
              <div class="dropdown" style="display:inline-block;">
                  <button title="analytics" class="btn btn-default dropdown-toggle btn-xs file-btns" type="button" data-toggle="dropdown"><i class="glyphicon glyphicon-filter"></i>
                      <span class="caret"></span></button>
                      <ul class="dropdown-menu">
                          <li class="small"><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;path_parent=<?php echo rawurlencode($fullpath); ?>"><i class="glyphicon glyphicon-filter"></i> filter (non-recursive)</a></li>
                          <li class="small"><a href="simple.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;q=path_parent:<?php echo rawurlencode(escape_chars($file['path_parent'] . '/' . $file['filename'])); ?> OR path_parent:<?php echo rawurlencode(escape_chars($file['path_parent'] . '/' . $file['filename']) . '\/*'); ?>"><i class="glyphicon glyphicon-filter"></i> filter (recursive)</a></li>
                          </ul>
                  </div>
      <br />
      <?php } ?>
      <h5 class="path"><i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;"></i> <span style="color:gray">Parent path: <span id="parentpath"><?php echo $parentpath; ?></span> </span></h5> <a href="#" class="btn btn-default btn-xs file-btns" onclick="copyToClipboard('#parentpath')">Copy text</a>
      <div class="dropdown" style="display:inline-block;">
          <button title="analytics" class="btn btn-default dropdown-toggle btn-xs file-btns" type="button" data-toggle="dropdown"><i class="glyphicon glyphicon-stats"></i>
              <span class="caret"></span></button>
              <ul class="dropdown-menu">
                  <li class="small"><a href="filetree.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-tree-conifer"></i> filetree</a></li>
                  <li class="small"><a href="treemap.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-th-large"></i> treemap</a></li>
                  <li class="small"><a href="heatmap.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-fire"></i> heatmap</a></li>
                  <li class="small"><a href="hotdirs.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-fire"></i> hotdirs</a></li>
                  <li class="small"><a href="top50.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-th-list"></i> top 50</a></li>
                  <li class="small"><a href="dupes.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>&amp;mindupes=<?php echo $_COOKIE['mindupes']; ?>"><i class="glyphicon glyphicon-duplicate"></i> dupes</a></li>
                  <li class="small"><a href="hardlinks.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>&amp;minhardlinks=<?php echo $_COOKIE['minhardlinks']; ?>"><i class="glyphicon glyphicon-link"></i> hardlinks</a></li>
                  </ul>
          </div>
          <div class="dropdown" style="display:inline-block;">
              <button title="analytics" class="btn btn-default dropdown-toggle btn-xs file-btns" type="button" data-toggle="dropdown"><i class="glyphicon glyphicon-filter"></i>
                  <span class="caret"></span></button>
                  <ul class="dropdown-menu">
                      <li class="small"><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;path_parent=<?php echo rawurlencode($file['path_parent']); ?>"><i class="glyphicon glyphicon-filter"></i> filter (non-recursive)</a></li>
                      <li class="small"><a href="simple.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;q=path_parent:<?php echo rawurlencode(escape_chars($file['path_parent'])); ?> OR path_parent:<?php echo rawurlencode(escape_chars($file['path_parent']) . '\/*'); ?>"><i class="glyphicon glyphicon-filter"></i> filter (recursive)</a></li>
                      </ul>
              </div>
      <br /><br />
    </div>
  </div>
  <div class="row">
    <div class="col-xs-6">
      <ul class="list-group">
        <li class="list-group-item">
            <span class="pull-right">&nbsp;
            <!-- show comparison file size -->
            <?php if ($esIndex2 != "") { ?>
            <?php $fileinfo_index2 = get_index2_fileinfo($client, $esIndex2, $file['path_parent'], $file['filename']);
            if ($file['filesize'] > 0 && $fileinfo_index2[0] > 0) {
                $filesize_change = number_format(changePercent($file['filesize'], $fileinfo_index2[0]), 2);
            } else if ($file['filesize'] > 0 && $fileinfo_index2[0] == 0) {
                $filesize_change = 100.0;
            }
            if ($filesize_change != 0) { ?>
            <small><?php echo formatBytes($fileinfo_index2[0]); ?>
                <span style="color:<?php echo $filesize_change > 0 ? "red" : "#29FE2F"; ?>;">(<?php echo $filesize_change > 0 ? '<i class="glyphicon glyphicon-chevron-up"></i> +' : '<i class="glyphicon glyphicon-chevron-down"></i>'; ?>
            <?php echo $filesize_change; ?>%)</span></small>
        <?php } } ?>
         <!-- end show comparison file size -->
          </span>
          <span class="badge"><?php echo formatBytes($file['filesize']); ?></span>
          Filesize
        </li>
        <?php if ($_REQUEST['doctype'] == 'directory') { ?>
        <li class="list-group-item">
            <span class="pull-right">&nbsp;
            <!-- show comparison items -->
            <?php if ($esIndex2 != "") { ?>
            <?php
            if ($file['items'] > 0 && $fileinfo_index2[1] > 0) {
                $diritems_change = number_format(changePercent($file['items'], $fileinfo_index2[1]), 2);
            } else if ($file['items'] > 0 && $fileinfo_index2[1] == 0) {
                $diritems_change = 100.0;
            }
            if ($diritems_change != 0) { ?>
            <small><?php echo $fileinfo_index2[1]; ?>
                <span style="color:<?php echo $diritems_change > 0 ? "red" : "#29FE2F"; ?>;">(<?php echo $diritems_change > 0 ? '<i class="glyphicon glyphicon-chevron-up"></i> +' : '<i class="glyphicon glyphicon-chevron-down"></i>'; ?>
            <?php echo $diritems_change; ?>%)</span></small>
        <?php } } ?>
        <!-- end show comparison items -->
        </span>
            <span class="badge"><?php echo $file['items']; ?></span>
            Items
        </li>
        <li class="list-group-item">
            <span class="pull-right">&nbsp;
            <!-- show comparison items -->
            <?php if ($esIndex2 != "") { ?>
            <?php
            if ($file['items_files'] > 0 && $fileinfo_index2[2] > 0) {
                $diritems_files_change = number_format(changePercent($file['items_files'], $fileinfo_index2[2]), 2);
            } else if ($file['items_files'] > 0 && $fileinfo_index2[2] == 0) {
                $diritems_files_change = 100.0;
            }
            if ($diritems_files_change != 0) { ?>
            <small><?php echo $fileinfo_index2[2]; ?>
                <span style="color:<?php echo $diritems_files_change > 0 ? "red" : "#29FE2F"; ?>;">(<?php echo $diritems_files_change > 0 ? '<i class="glyphicon glyphicon-chevron-up"></i> +' : '<i class="glyphicon glyphicon-chevron-down"></i>'; ?>
            <?php echo $diritems_files_change; ?>%)</span></small>
        <?php } } ?>
        <!-- end show comparison items -->
        </span>
            <span class="badge"><?php echo $file['items_files']; ?></span>
            Items (files)
        </li>
        <li class="list-group-item">
            <span class="pull-right">&nbsp;
            <!-- show comparison items -->
            <?php if ($esIndex2 != "") { ?>
            <?php
            if ($file['items_subdirs'] > 0 && $fileinfo_index2[3] > 0) {
                $diritems_subdirs_change = number_format(changePercent($file['items_subdirs'], $fileinfo_index2[3]), 2);
            } else if ($file['items_subdirs'] > 0 && $fileinfo_index2[3] == 0) {
                $diritems_subdirs_change = 100.0;
            }
            if ($diritems_subdirs_change != 0) { ?>
            <small><?php echo $fileinfo_index2[3]; ?>
                <span style="color:<?php echo $diritems_subdirs_change > 0 ? "red" : "#29FE2F"; ?>;">(<?php echo $diritems_subdirs_change > 0 ? '<i class="glyphicon glyphicon-chevron-up"></i> +' : '<i class="glyphicon glyphicon-chevron-down"></i>'; ?>
            <?php echo $diritems_subdirs_change; ?>%)</span></small>
        <?php } } ?>
        <!-- end show comparison items -->
        </span>
            <span class="badge"><?php echo $file['items_subdirs']; ?></span>
            Items (subdirs)
        </li>
        <?php } ?>
        <?php if ($_REQUEST['doctype'] == 'file') { ?>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['extension']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;extension=<?php echo $file['extension']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Extension</a>
        </li>
        <?php } ?>
        <?php if ($s3_index) { ?>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['s3_bucket']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;s3_bucket=<?php echo $file['s3_bucket']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Bucket</a>
        </li>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['s3_key']; ?></span>
          Key
        </li>
        <?php } else { ?>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['owner']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;owner=<?php echo $file['owner']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Owner</a>
        </li>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['group']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;group=<?php echo $file['group']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Group</a>
        </li>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['inode']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;inode=<?php echo $file['inode']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Inode</a>
        </li>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['hardlinks']; ?></span>
          Hardlinks
        </li>
        <?php } ?>
        <?php if ($_REQUEST['doctype'] == 'file') { ?>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['filehash']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;filehash=<?php echo $file['filehash']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Filehash</a>
        </li>
        <li class="list-group-item">
          <span class="badge"><?php echo $file['dupe_md5']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;dupe_md5=<?php echo $file['dupe_md5']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Dupe MD5</a>
        </li>
        <?php } ?>
    </ul>
    <?php if ($s3_index != '1' && getCookie('costpergb') > 0) { ?>
    <ul class="list-group">
        <li class="list-group-item">
            <span class="badge">$ <?php echo number_format(round($file['costpergb'], 2), 2); ?></span>
            Cost per GB
        </li>
    </ul>
    <?php } ?> 
    <ul class="list-group">
        <?php
        if (count($extra_fields) > 0) {
          foreach ($extra_fields as $key => $value) { ?>
          <?php if (strpos($file[$key], '\n')) { ?>
              <li class="list-group-item">
                  <?php echo $value; ?><br />
                  <span class="small"><?php echo nl2br($file[$key]); ?></span>
              </li>
          <?php } else { ?>
              <li class="list-group-item">
                  <span class="badge"><?php echo $file[$key]; ?></span>
                  <?php echo $value; ?>
              </li>
          <?php } ?>
          <?php }
          } ?>
    </ul>
      </div>
      <div class="col-xs-6">
        <ul class="list-group">
      <li class="list-group-item">
        <span class="badge"><?php echo $file['last_modified']; ?></span>
        Last modified (utc)
      </li>
      <?php if ($s3_index) { ?>
      <li class="list-group-item">
        <span class="badge"><?php echo $file['s3_storage_class']; ?></span>
        <a href="advanced.php?submitted=true&amp;p=1&amp;s3_storage_class=<?php echo $file['s3_storage_class']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Storage class</a>
      </li>
      <li class="list-group-item">
        <span class="badge"><?php echo $file['s3_etag']; ?></span>
        <a href="advanced.php?submitted=true&amp;p=1&amp;s3_etag=<?php echo $file['s3_etag']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Etag</a>
      </li>
      <li class="list-group-item">
        <span class="badge"><?php echo $file['s3_multipart_upload']; ?></span>
        <a href="advanced.php?submitted=true&amp;p=1&amp;s3_multipart_upload=<?php echo $file['s3_multipart_upload']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Multipart upload</a>
      </li>
      <li class="list-group-item">
        <span class="badge"><?php echo $file['s3_replication_status']; ?></span>
        <a href="advanced.php?submitted=true&amp;p=1&amp;s3_replication_status=<?php echo $file['s3_replication_status']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Replication status</a>
      </li>
      <li class="list-group-item">
        <span class="badge"><?php echo $file['s3_encryption_status']; ?></span>
        <a href="advanced.php?submitted=true&amp;p=1&amp;s3_encryption_status=<?php echo $file['s3_encryption_status']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Encryption status</a>
      </li>
      <?php } else { ?>
      <li class="list-group-item">
        <?php if ($qumulo == '1') { ?>
        <span class="badge"><?php echo $file['creation_time']; ?></span>
        Creation time (utc)
        <?php } else { ?>
        <span class="badge"><?php echo $file['last_access']; ?></span>
        Last access (utc)
        <?php } ?>
      </li>
      <li class="list-group-item">
        <span class="badge"><?php echo $file['last_change']; ?></span>
        Last change (utc)
      </li>
      <?php } ?>
      </ul>
        <ul class="list-group">
          <li class="list-group-item">
            <span class="badge"><?php echo $_REQUEST['index']; ?></span>
            <a href="advanced.php?submitted=true&amp;p=1&amp;index=<?php echo $_REQUEST['index']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Index name</a>
          </li>
          <li class="list-group-item">
            <span class="badge"><?php echo $file['worker_name']; ?></span>
            <a href="simple.php?submitted=true&amp;p=1&amp;q=worker_name:<?php echo $file['worker_name']; ?>">Worker</a>
          </li>
          <li class="list-group-item">
            <span class="badge"><?php echo $file['indexing_date']; ?></span>
            Indexed at (utc)
          </li>
          <?php if ($_REQUEST['doctype'] == 'directory') { ?>
          <li class="list-group-item">
            <span class="badge"><?php echo secondsToTime($crawltime); ?></span>
            Crawl time
          </li>
          <?php } ?>
        </ul>
      </div>
    </div>
</div>
<script language="javascript" src="js/jquery.min.js"></script>
<script language="javascript" src="js/bootstrap.min.js"></script>
<script language="javascript" src="js/diskover.js"></script>
<div id="loading">
  <img id="loading-image" width="32" height="32" src="images/ajax-loader.gif" alt="Updating..." />
</div>
</body>
</html>
