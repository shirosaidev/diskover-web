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
if ($path === "/" && $file['path_parent'] === "/") {
    $fullpath = '/' . $file['filename'];
    $parentpath = $file['path_parent'];
    if ($file['filename'] === "") { // root /
        $filename = '/';
    } else {
        $filename = $file['filename'];
    }
} else {
    $fullpath = $file['path_parent'] . '/' . $file['filename'];
    $parentpath = $file['path_parent'];
    $filename = $file['filename'];
}

// see if there are any extra custom fields to add
$extra_fields = [];
for ($i=1; $i < 5; $i++) {
    if (getCookie('field'.$i.'')) {
        $value = (getCookie('field'.$i.'-desc')) ? getCookie('field'.$i.'-desc') : getCookie('field'.$i.'');
        $extra_fields[getCookie('field'.$i.'')] = $value;
    }
}

// get crawl elased time for directory
if ($filedoctype == 'directory') {
    $searchParams = [];
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = 'crawlstat';
    $searchParams['body'] = [
        '_source' => ['elapsed_time'],
        'size' => 1,
        'query' => [
                'match' => [
                    'path' => $fullpath
                ]
         ],
         'sort' => [
             'elapsed_time' => [
                 'order' => 'desc'
             ]
         ]
    ];
    $queryResponse = $client->search($searchParams);
    $crawltime = $queryResponse['hits']['hits'][0]['_source']['elapsed_time'];
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
      <h2 class="path"><?php echo ($_REQUEST['doctype'] == 'file') ? '<i class="glyphicon glyphicon-file" style="color:#738291;"></i>' : '<i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;"></i>'; ?> <a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;filename=<?php echo rawurlencode($file['filename']); ?>&amp;path_parent=<?php echo rawurlencode($file['path_parent']); ?>"><?php echo $filename; ?></a></h2>
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
      <h4 class="path">Full path: <?php echo $fullpath; ?></h4>
      <?php if ($_REQUEST['doctype'] == 'directory') { ?>
          <div class="dropdown" style="display:inline-block;">
              <button title="analytics" class="btn btn-default dropdown-toggle btn-xs file-btns" type="button" data-toggle="dropdown"><i class="glyphicon glyphicon-stats"></i>
                  <span class="caret"></span></button>
                  <ul class="dropdown-menu">
                      <li class="small"><a href="filetree.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-tree-conifer"></i> filetree</a></li>
                      <li class="small"><a href="treemap.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-th-large"></i> treemap</a></li>
                      <li class="small"><a href="heatmap.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-fire"></i> heatmap</a></li>
                      <li class="small"><a href="top50.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($fullpath); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-th-list"></i> top 50</a></li>
                      </ul>
              </div>
              <div class="dropdown" style="display:inline-block;">
                  <button title="analytics" class="btn btn-default dropdown-toggle btn-xs file-btns" type="button" data-toggle="dropdown"><i class="glyphicon glyphicon-filter"></i>
                      <span class="caret"></span></button>
                      <ul class="dropdown-menu">
                          <li class="small"><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;path_parent=<?php echo rawurlencode($fullpath); ?>"><i class="glyphicon glyphicon-filter"></i> filter (non-recursive)</a></li>
                          <li class="small"><a href="simple.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;q=path_parent:<?php echo rawurlencode(escape_chars($fullpath . '*')); ?>"><i class="glyphicon glyphicon-filter"></i> filter (recursive)</a></li>
                          </ul>
                  </div>
      <br />
      <?php } ?>
      <h5 class="path"><i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;"></i> <span style="color:gray">Parent path: <?php echo $parentpath; ?> </span></h5>
      <div class="dropdown" style="display:inline-block;">
          <button title="analytics" class="btn btn-default dropdown-toggle btn-xs file-btns" type="button" data-toggle="dropdown"><i class="glyphicon glyphicon-stats"></i>
              <span class="caret"></span></button>
              <ul class="dropdown-menu">
                  <li class="small"><a href="filetree.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-tree-conifer"></i> filetree</a></li>
                  <li class="small"><a href="treemap.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-th-large"></i> treemap</a></li>
                  <li class="small"><a href="heatmap.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-fire"></i> heatmap</a></li>
                  <li class="small"><a href="top50.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><i class="glyphicon glyphicon-th-list"></i> top 50</a></li>
                  </ul>
          </div>
          <div class="dropdown" style="display:inline-block;">
              <button title="analytics" class="btn btn-default dropdown-toggle btn-xs file-btns" type="button" data-toggle="dropdown"><i class="glyphicon glyphicon-filter"></i>
                  <span class="caret"></span></button>
                  <ul class="dropdown-menu">
                      <li class="small"><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;path_parent=<?php echo rawurlencode($file['path_parent']); ?>"><i class="glyphicon glyphicon-filter"></i> filter (non-recursive)</a></li>
                      <li class="small"><a href="simple.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;q=path_parent:<?php echo escape_chars($file['path_parent'] . '*'); ?>"><i class="glyphicon glyphicon-filter"></i> filter (recursive)</a></li>
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
          <span class="badge"><?php echo $file['dupe_md5']; ?></span>
          <a href="advanced.php?submitted=true&amp;p=1&amp;dupe_md5=<?php echo $file['dupe_md5']; ?>&amp;doctype=<?php echo $_REQUEST['doctype']; ?>">Dupe MD5</a>
        </li>
        <?php } ?>
    </ul>
    <ul class="list-group">
        <?php
        if (count($extra_fields) > 0) {
          foreach ($extra_fields as $key => $value) { ?>
              <li class="list-group-item">
                  <span class="badge"><?php echo $file[$key]; ?></span>
                  <?php echo $value; ?>
              </li>
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
            Indexed at (utc)
          </li>
          <li class="list-group-item">
            <span class="badge"><?php echo secondsToTime($crawltime); ?></span>
            Crawl time
          </li>
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
