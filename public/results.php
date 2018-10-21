<?php
/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);

// see if there are any extra custom fields to add
$extra_fields = get_extra_fields();

// Grab all the custom tags from file
$customtags = get_custom_tags();

if (Constants::ENABLE_SOCKET_CLIENT) {
    error_reporting(E_ERROR | E_PARSE);
    // open socket connection to diskover listener
    $socket_host = Constants::SOCKET_LISTENER_HOST;
    $socket_port = Constants::SOCKET_LISTENER_PORT;
    $fp = stream_socket_client("tcp://".$socket_host.":".$socket_port, $errno, $errstr, 10);
    // test if listening
    fwrite($fp, "ping");
    $result = fgets($fp, 1024);
    // close socket
    fclose($fp);
    if ($result == "pong") {
        $socketlistening = 1;
    } else {
        $socketlistening = 0;
    }
} else {
    $socketlistening = 0;
}

error_reporting(E_ALL ^ E_NOTICE);

// display results
if (!empty($results[$p]) && count($results[$p]) > 0) {
	//print_r($_SERVER);
  $show_change_percent = showChangePercent($client, $esIndex, $esIndex2);
?>
<div class="container-fluid searchresults" style="margin-top: 70px;">
  <div class="row">
		<div class="col-xs-7">
			<div class="row">
				<div class="alert alert-dismissible alert-success col-xs-8">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php
                        $rs = $searchParams['size'];
                        $cp = $_GET['p'];
                        $ei = $rs * $cp;
                        $si = $ei - $rs + 1;
                    ?>
					<i class="glyphicon glyphicon-search"></i> Showing <strong><?php echo $si; ?></strong> to <strong><?php echo $ei; ?></strong> of <?php echo $total; ?> items found. <small>(<?php echo formatBytes($total_size); ?> total this page)</small>
				</div>
			</div>
		</div>
        <div class="col-xs-5 text-right">
            <div class="row">
                <span style="margin-right: 10px;">
                <?php if($socketlistening) { ?>
                    <i title="socket server listening" class="glyphicon glyphicon-off text-success"></i>
                <?php } else { ?>
                    <i title="socket server not listening or client not enabled" class="glyphicon glyphicon-off text-warning"></i>
                <?php } ?></span>
                    <div class="btn-group" style="display:inline-block;">
                      <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="export search results">
                        <i class="glyphicon glyphicon-export"></i>
                        <span class="caret"></span>
                      </a>
                      <ul class="dropdown-menu">
                        <li><a target="hiddeniframe" href="export.php?q=<?php echo rawurlencode($_GET['q']); ?>&amp;p=<?php echo $_GET['p'] ?>&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=json&amp;export_type=file">Files this page (json)</a></li>
                        <li><a target="hiddeniframe" href="export.php?q=<?php echo rawurlencode($_GET['q']); ?>&amp;p=all&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=json&amp;export_type=file">Files all pages (json)</a></li>
                        <li><a target="hiddeniframe" href="export.php?q=<?php echo rawurlencode($_GET['q']); ?>&amp;p=<?php echo $_GET['p'] ?>&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=csv&amp;export_type=file">Files this page (csv)</a></li>
                        <li><a target="hiddeniframe" href="export.php?q=<?php echo rawurlencode($_GET['q']); ?>&amp;p=all&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=csv&amp;export_type=file">Files all pages (csv)</a></li>
                        <li class="divider"></li>
                        <li><a target="hiddeniframe" href="export.php?q=<?php echo rawurlencode($_GET['q']); ?>&amp;p=<?php echo $_GET['p'] ?>&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=json&amp;export_type=directory">Directories this page (json)</a></li>
                        <li><a target="hiddeniframe" href="export.php?q=<?php echo rawurlencode($_GET['q']); ?>&amp;p=all&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=json&amp;export_type=directory">Directories all pages (json)</a></li>
                        <li><a target="hiddeniframe" href="export.php?q=<?php echo rawurlencode($_GET['q']); ?>&amp;p=<?php echo $_GET['p'] ?>&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=csv&amp;export_type=directory">Directories this page (csv)</a></li>
                        <li><a target="hiddeniframe" href="export.php?q=<?php echo rawurlencode($_GET['q']); ?>&amp;p=all&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=csv&amp;export_type=directory">Directories all pages (csv)</a></li>
                       </ul>
                    </div>
            <button title="reload" type="button" class="btn btn-default reload-results"><i class="glyphicon glyphicon-refresh"></i> </button>
            <div class="form-group" style="display:inline-block;">
                <input type="text" id="searchwithin" class="search form-control" placeholder="Search within results">
            </div>
            </div>
    </div>
  </div>
  <div class="row">
    <div class="counter pull-right"></div>
    <?php $numofcol = 11; ?>
    <table class="table results table-striped table-hover table-condensed">
      <thead>
        <tr>
          <th class="text-nowrap">#</th>-
          <th class="text-nowrap">Name <?php echo sortURL('filename'); ?></th>
          <th class="text-nowrap">Tags <?php echo sortURL('tag'); ?></th>
          <th class="text-nowrap">Path <?php echo sortURL('path_parent'); ?></th>
		      <th class="text-nowrap">File Size <?php echo sortURL('filesize'); ?></th>
          <th class="text-nowrap">% <span style="color:darkgray;font-size: 11px;"><i title="Percentage of total file size this page" class="glyphicon glyphicon-question-sign"></i></span></th>
          <?php if ($_GET['doctype'] == 'directory' || $_GET['doctype'] == '') { ?>
          <?php if ($show_change_percent) { ?>
          <th width="8%" class="text-nowrap">Change % <?php echo sortURL('change_percent_filesize'); ?></th>
          <?php $numofcol+=1; ?>
          <?php } ?>
          <th class="text-nowrap">Items <?php echo sortURL('items'); ?></th>
          <?php if ($show_change_percent) { ?>
          <th width="8%" class="text-nowrap">Change % <?php echo sortURL('change_percent_items'); ?></th>
          <?php $numofcol+=1; ?>
          <?php } ?>
          <th class="text-nowrap">Items (files) <?php echo sortURL('items_files'); ?></th>
          <th class="text-nowrap">Items (subdirs) <?php echo sortURL('items_subdirs'); ?></th>
          <?php $numofcol+=3; ?>
          <?php } ?>
          <?php if ($s3_index) { ?>
          <th class="text-nowrap">Bucket <?php echo sortURL('s3_bucket'); ?></th>
          <th class="text-nowrap">Key <?php echo sortURL('s3_key'); ?></th>
          <th class="text-nowrap">Storage class <?php echo sortURL('s3_storage_class'); ?></th>
          <?php } else { ?>
          <th class="text-nowrap">Owner <?php echo sortURL('owner'); ?></th>
          <th class="text-nowrap">Group <?php echo sortURL('group'); ?></th>
          <?php } ?>
          <th width="8%" class="text-nowrap">Modified (utc) <?php echo sortURL('last_modified'); ?></th>
          <th width="4%" class="text-nowrap">Rating <span style="color:darkgray;font-size: 11px;"><i title="Rating is based on last modified time, older is higher rating" class="glyphicon glyphicon-question-sign"></i></span></th>
          <?php if ($qumulo_index == '1') { ?>
          <th class="text-nowrap">Created (utc) <?php echo sortURL('creation_time'); ?></th>
          <?php } elseif ($s3_index != '1') { ?>
          <th width="8%" class="text-nowrap">Accessed (utc) <?php echo sortURL('last_access'); ?></th>
          <?php } ?>
          <?php
          if (count($extra_fields) > 0) {
            foreach ($extra_fields as $key => $value) { ?>
                <th class="text-nowrap"><?php echo $value ?> <?php echo sortURL($key); ?></th>
            <?php $numofcol+=1; }
            } ?>
        </tr>
        <tr class="info no-result">
          <td colspan=<?php echo $numofcol; ?>><span style="color:white;"><strong><i class="glyphicon glyphicon-info-sign"></i> No results on this page</strong></td>
        </tr>
      </thead>
      <tfoot>
				<tr>
					<th class="text-nowrap">#</th>
					<th class="text-nowrap">Name</th>
                    <th class="text-nowrap">Tags</th>
					<th class="text-nowrap">Path</th>
					<th class="text-nowrap">File Size</th>
          <th class="text-nowrap">%</th>
          <?php if ($_GET['doctype'] == 'directory' || $_GET['doctype'] == '') { ?>
          <?php if ($show_change_percent) { ?>
          <th class="text-nowrap">Change %</th>
          <?php } ?>
          <th class="text-nowrap">Items</th>
          <?php if ($show_change_percent) { ?>
          <th class="text-nowrap">Change %</th>
          <?php } ?>
          <th class="text-nowrap">Items (files)</th>
          <th class="text-nowrap">Items (subdirs)</th>
          <?php } ?>
          <?php if ($s3_index) { ?>
          <th class="text-nowrap">Bucket <?php echo sortURL('s3_bucket'); ?></th>
          <th class="text-nowrap">Key <?php echo sortURL('s3_key'); ?></th>
          <th class="text-nowrap">Storage class <?php echo sortURL('s3_storage_class'); ?></th>
          <?php } else { ?>
					<th class="text-nowrap">Owner</th>
					<th class="text-nowrap">Group</th>
          <?php } ?>
					<th class="text-nowrap">Modified (utc)</th>
          <th class="text-nowrap">Rating</th>
          <?php if ($qumulo_index) { ?>
          <th class="text-nowrap">Created (utc)</th>
          <?php } elseif (!$s3_index) { ?>
					<th class="text-nowrap">Accessed (utc)</th>
          <?php } ?>
          <?php
          if (count($extra_fields) > 0) {
            foreach ($extra_fields as $key => $value) { ?>
                <th class="text-nowrap"><?php echo $value; ?></th>
            <?php }
          } ?>
				</tr>
      </tfoot>
      <tbody id="results-tbody">
      <?php
        error_reporting(E_ALL ^ E_NOTICE);
        $limit = $searchParams['size'];
        $i = $p * $limit - $limit;
        foreach ($results[$p] as $result) {
          $file = $result['_source'];

          // calculate rating
          $date1 = date_create(date('Y-m-dTH:i:s'));
          $date2 = date_create($file['last_modified']);
          $diff = date_diff($date1,$date2);
          $mtime_daysago = $diff->format('%a');
          if ($mtime_daysago >= 730) {
            $file_rating = 5;
          } elseif ($mtime_daysago < 730 && $mtime_daysago >= 365 ) {
            $file_rating = 4;
          } elseif ($mtime_daysago < 365 && $mtime_daysago >= 180 ) {
            $file_rating = 3;
          } elseif ($mtime_daysago < 180 && $mtime_daysago >= 90 ) {
            $file_rating = 2;
          } elseif ($mtime_daysago < 90 && $mtime_daysago >= 30 ) {
            $file_rating = 1;
          } else {
            $file_rating = 0;
          }

          $i += 1;
      ?>
      <tr class="<?php if ($file['tag'] == 'delete') { echo 'deleterow'; } elseif ($file['tag'] == 'archive') { echo 'archiverow'; } elseif ($file['tag'] == 'keep') { echo 'keeprow'; }?>">
        <th scope="row" class="text-nowrap <?php if ($file['tag'] == 'delete') { echo 'deletehighlight_bg'; } elseif ($file['tag'] == 'archive') { echo 'archivehighlight_bg'; } elseif ($file['tag'] == 'keep') { echo 'keephighlight_bg'; }?>" style="color:#555;"><?php echo $i; ?></th>
        <td class="path highlight">
            <?php
            // set fullpath, parentpath and filename vars and check for root /
            if ($file['path_parent'] === "/" && $file['filename'] === "") {  // / root
                $fullpath = $file['path_parent'] . $file['filename'];
                $parentpath = $file['path_parent'];
                $filename = "/";
            } elseif ($file['path_parent'] === "/" && $file['filename'] !== "") { // directory in /
                $fullpath = $file['path_parent'] . $file['filename'];
                $parentpath = $file['path_parent'];
                $filename = $file['filename'];
            } else {
                $fullpath = $file['path_parent'] . '/' . $file['filename'];
                $parentpath = $file['path_parent'];
                $filename = $file['filename'];
            }
            ?>
            <?php if ($result['_type'] == 'directory') { ?> <a href="simple.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;q=path_parent:<?php echo rawurlencode(escape_chars($fullpath)); ?>&amp;submitted=true&amp;p=1"><?php if ($s3_index && $file['path_parent'] == '/') { ?><i class="glyphicon glyphicon-cloud" style="color:#FD9827;font-size:13px;padding-right:3px;"></i><?php } else if ($s3_index && $file['path_parent'] == '/s3') { ?><i class="glyphicon glyphicon-cloud-upload" style="color:#FD9827;font-size:13px;padding-right:3px;"></i><?php } else { ?><i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;font-size:13px;padding-right:3px;"></i><?php } ?> <?php echo $filename; ?></a> <a href="view.php?id=<?php echo $result['_id'] . '&amp;index=' . $result['_index'] . '&amp;doctype=' . $result['_type']; ?>"><button class="btn btn-default btn-xs" type="button" style="color:gray;font-size:11px;margin-left:3px;"><i title="directory info" class="glyphicon glyphicon-info-sign"></i></button></a><?php } else { ?><a href="view.php?id=<?php echo $result['_id'] . '&amp;index=' . $result['_index'] . '&amp;doctype=' . $result['_type']; ?>"><i class="glyphicon glyphicon-file" style="color:#738291;font-size:13px;padding-right:3px;"></i> <?php echo $filename; ?></a><?php } ?>
            <!-- socket commands dropdown -->
            <?php if ($socketlistening) {
            if ($result['_type'] == 'directory') { ?>
            <div class="dropdown pathdropdown" style="display:inline-block;">
                <?php if ($file['path_parent'] . '/' . $file['filename'] !== $_SESSION['rootpath']) { ?>
                <button title="socket server commands" class="btn btn-default dropdown-toggle btn-xs run-btn file-cmd-btns" type="button" data-toggle="dropdown">
                <?php } else { ?>
                <button title="socket server commands" class="btn btn-default dropdown-toggle btn-xs run-btn file-cmd-btn-root" type="button" data-toggle="dropdown">
                <?php } ?>
                <i class="glyphicon glyphicon-tasks"></i>
                    <span class="caret"></span></button>
                    <ul class="dropdown-menu">
                        <?php if ($file['path_parent'] . '/' . $file['filename'] !== $_SESSION['rootpath']) { ?>
                        <li class="small"><?php $cmd = "{\"action\": \"reindex\", \"path\": \"".$fullpath."\", \"index\": \"".$esIndex."\", \"adaptivebatch\": \"true\"}"; ?><a onclick='runCommand(<?php echo $cmd; ?>);' href="#_self"><i class="glyphicon glyphicon-repeat"></i> reindex directory (non-recursive)</a></li>
                        <li class="small"><?php $cmd = "{\"action\": \"reindex\", \"path\": \"".$fullpath."\", \"index\": \"".$esIndex."\", \"recursive\": \"true\", \"adaptivebatch\": \"true\"}"; ?><a onclick='runCommand(<?php echo $cmd; ?>);' href="#_self"><i class="glyphicon glyphicon-repeat"></i> reindex directory (recursive)</a></li>
                        <?php } else { ?>
                        <li class="small"><?php $cmd = "{\"action\": \"crawl\", \"path\": \"".$fullpath."\", \"index\": \"".$esIndex."\", \"adaptivebatch\": \"true\"}"; ?><a onclick='runCommand(<?php echo $cmd; ?>);' href="#_self"><i class="glyphicon glyphicon-repeat"></i> re-crawl rootpath (OVERWRITE INDEX)</a></li>
                        <li class="small"><?php $cmd = "{\"action\": \"finddupes\", \"index\": \"".$esIndex."\"}"; ?><a onclick='runCommand(<?php echo $cmd; ?>);' href="#_self"><i class="glyphicon glyphicon-duplicate"></i> find dupes</a></li>
                        <?php if (!empty($esIndex2)) { ?>
                        <li class="small"><?php $cmd = "{\"action\": \"hotdirs\", \"index\": \"".$esIndex."\", \"index2\": \"".$esIndex2."\"}"; ?><a onclick='runCommand(<?php echo $cmd; ?>);' href="#_self"><i class="glyphicon glyphicon-fire"></i> find hotdirs</a></li>
                        <?php } ?>
                        <?php } ?>
                </ul>
            </div>
            <!-- end socket command dropdown -->
            <?php } } ?>
        </td>
            <td class="text-nowrap tagdropdown">
            <!-- tag dropdown -->
            <form class="form-inline" id="changetag_<?php echo $result['_id']; ?>" name="changetag_<?php echo $result['_id']; ?>">
            <input type="hidden" name="id" value="<?php echo $result['_id']; ?>">
            <input type="hidden" name="doctype" value="<?php echo $result['_type']; ?>">
            <input type="hidden" name="tag" value="" id="tag_<?php echo $result['_id']; ?>">
            <input type="hidden" name="tag_custom" value="" id="tag_custom_<?php echo $result['_id']; ?>">
            <?php foreach($ids_onpage as $key => $value) { ?>
            <input type="hidden" name="idsonpage[<?php echo $key; ?>][id]" value="<?php echo $value['id']; ?>">
            <input type="hidden" name="idsonpage[<?php echo $key; ?>][type]" value="<?php echo $value['type']; ?>">
            <?php } ?>
            <div class="dropdown">
                <?php
                $tags = "";
                if ($file['tag']) {
                    if ($file['tag'] === "delete") {
                        $tags .= "<i class=\"glyphicon glyphicon-trash delete\"></i> <span class=\"delete taglabel\">delete</span>";
                    } elseif ($file['tag'] === "archive") {
                        $tags .= "<i class=\"glyphicon glyphicon-cloud-upload archive\"></i> <span class=\"archive taglabel\">archive</span>";
                    } elseif ($file['tag'] === "keep") {
                        $tags .= "<i class=\"glyphicon glyphicon-floppy-saved keep\"></i> <span class=\"keep taglabel\">keep</span>";
                    }
                }
                if ($file['tag_custom']) {
                    if ($file['tag']) {
                        $tags .= "&nbsp;&nbsp;";
                    }
                    $color = get_custom_tag_color($file['tag_custom']);
                    $tags .= "<span style=\"color:". $color ."\"><i class=\"glyphicon glyphicon-tag\"></i>" . $file['tag_custom'] . "</span>";
                }
                ?>
                <button title="tags" class="btn btn-default dropdown-toggle btn-xs" type="button" data-toggle="dropdown"><?php if ($tags) { echo $tags; } else { echo "<i class=\"glyphicon glyphicon-tag\" style=\"color:darkgray;\"></i> "; } ?>
                    <span class="caret" style="color:gray;"></span></button>
                    <ul class="dropdown-menu">
                        <li class="small" onclick="$('#tag_<?php echo $result['_id']; ?>').val('delete'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag_<?php echo $result['_id']; ?>').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-trash delete"></i> <span class="delete">delete</span></a></li>
                        <li class="small" onclick="$('#tag_<?php echo $result['_id']; ?>').val('archive'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag_<?php echo $result['_id']; ?>').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-cloud-upload archive"></i> <span class="archive">archive</span></a></li>
                        <li class="small" onclick="$('#tag_<?php echo $result['_id']; ?>').val('keep'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag_<?php echo $result['_id']; ?>').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-floppy-saved keep"></i> <span class="keep">keep</span></a></li>
                        <li class="small" onclick="$('#tag_<?php echo $result['_id']; ?>').val('null'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag_<?php echo $result['_id']; ?>').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-remove-sign" style="color:#555;"></i> <span class="untagged">untagged</span></a></li>
                        <li class="divider"></li>
                        <?php foreach ($customtags as $key => $value) { ?>
                          <li class="small" onclick="$('#tag_custom_<?php echo $result['_id']; ?>').val('<?php echo $value[0]; ?>'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag_<?php echo $result['_id']; ?>').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-tag" style="color:<?php echo $value[1]; ?>"></i> <span style="color:<?php echo $value[1]; ?>"><?php echo $value[0]; ?></span></a></li>
                        <?php } ?>
                        <li class="small" onclick="$('#tag_custom_<?php echo $result['_id']; ?>').val('null'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag_<?php echo $result['_id']; ?>').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-remove" style="color:gray"></i> <span style="color:gray">Remove custom tag</span></a></li>
                        <li class="small">
                                <input type="text" name="tagtext" id="tagtext_<?php echo $result['_id']; ?>" class="form-control input-sm" style="margin-left:12px;" value="" placeholder="Add new...">
                                <button class="btn btn-default btn-xs" onclick="$('#tag_custom_<?php echo $result['_id']; ?>').val(document.getElementById('tagtext_<?php echo $result['_id']; ?>').value); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag_<?php echo $result['_id']; ?>').serialize(),success: function() { location.reload(); } }); return false;" type="submit"> <small><i class="glyphicon glyphicon-plus"></i></small></button><br />
                                <span style="margin:0px;padding:0px;margin-left:22px;font-size:9px;color:#666;">tag name|#hexcolor</span>
                        </li>
                        <li class="divider"></li>
                        <?php if ($result['_type'] == 'directory') { ?>
                        <li class="small" onclick="$('#tag_<?php echo $result['_id']; ?>').val('tagall_subdirs_recurs'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag_<?php echo $result['_id']; ?>').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-folder-open" style="color:gray"></i> <span style="color:gray">Apply tags to subdirs (recursive)</span></a></li>
                        <li class="small" onclick="$('#tag_<?php echo $result['_id']; ?>').val('tagall_files_recurs'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag_<?php echo $result['_id']; ?>').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-file" style="color:gray"></i> <span style="color:gray">Apply tags to files (recursive)</span></a></li>
                        <?php } ?>
                        <li class="small" onclick="$('#tag_<?php echo $result['_id']; ?>').val('tagall_ids_onpage'); document.getElementById('loading').style.display='block'; $.ajax({type:'POST',url:'tagfiles.php',data: $('#changetag_<?php echo $result['_id']; ?>').serialize(),success: function() { location.reload(); } }); return false;"><a href="#"><i class="glyphicon glyphicon-copy" style="color:gray"></i> <span style="color:gray">Copy tags to all items on page</span></a></li>
                        </form>
                        <li class="small"><a href="admin.php?index=<?php echo $esIndex;?>&amp;index2=<?php echo $esIndex2;?>"><span style="color:gray"><i class="glyphicon glyphicon-tags"></i> Edit tags</span></a></li>
                    </ul>
                </div>
               <!-- end tag dropdown -->
           </td>
          <td class="path">
              <!-- path buttons -->
              <div class="dropdown pathdropdown" style="display:inline-block;">
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
                  <div class="dropdown pathdropdown" style="display:inline-block;">
                      <button title="filter" class="btn btn-default dropdown-toggle btn-xs file-btns" type="button" data-toggle="dropdown"><i class="glyphicon glyphicon-filter"></i>
                          <span class="caret"></span></button>
                          <ul class="dropdown-menu">
                              <li class="small"><a href="simple.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;q=path_parent:<?php echo rawurlencode(escape_chars($file['path_parent'])); ?>"><i class="glyphicon glyphicon-filter"></i> filter (non-recursive)</a></li>
                              <li class="small"><a href="simple.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;q=path_parent:<?php echo rawurlencode(escape_chars($file['path_parent'])); ?> OR path_parent:<?php echo rawurlencode(escape_chars($file['path_parent']) . '\/*'); ?>"><i class="glyphicon glyphicon-filter"></i> filter (recursive)</a></li>
                              </ul>
                      </div>
              <!-- end path buttons -->
              <span class="highlight"><a class="pathdark" href="simple.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;q=path_parent:<?php echo rawurlencode(escape_chars($file['path_parent'])); ?>"><?php echo $file['path_parent']; ?></a></span>
          </td>
        <td class="text-nowrap highlight" style="font-weight:bold;color:#D20915;"><?php echo formatBytes($file['filesize']); ?></td>
        <td width="8%" class="highlight"><div class="text-right percent" style="width:<?php echo ($total_size > 0) ? number_format(($file['filesize'] / $total_size) * 100, 2) : number_format(0, 2); ?>%;"></div> <span style="color:gray;"><small><?php echo ($total_size > 0) ? number_format(($file['filesize'] / $total_size) * 100, 2) : number_format(0, 2); ?>%</small></span></td>
        <?php if ($_GET['doctype'] == 'directory' || $_GET['doctype'] == '') { ?>
        <!-- show comparison file size -->
        <?php if ($show_change_percent) { $filesize_change = 0; ?>
          <td class="highlight">
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
          </td>
        <?php } } ?>
        <!-- end show comparison file size -->
        <td class="text-nowrap highlight"><?php echo $file['items']; ?>
        <!-- show comparison items -->
        <?php if ($show_change_percent) { $diritems_change = 0; ?>
        <td class="highlight">
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
      </td>
    <?php } } ?>
    <td class="text-nowrap highlight"><?php echo $file['items_files']; ?>
    <td class="text-nowrap highlight"><?php echo $file['items_subdirs']; ?>
    <!-- end show comparison items -->
    </td>
        <?php } ?>
        <?php if ($s3_index) { ?>
        <td class="highlight"><?php echo $file['s3_bucket']; ?></td>
        <td class="path highlight"><?php echo $file['s3_key']; ?></td>
        <td class="highlight"><?php echo $file['s3_storage_class']; ?></td>
        <?php } else { ?>
        <td class="highlight"><?php echo $file['owner']; ?></td>
        <td class="highlight"><?php echo $file['group']; ?></td>
        <?php } ?>
        <td class="highlight modified"><?php echo $file['last_modified']; ?></td>
        <td class="highlight rating"><?php for ($n = 0; $n < $file_rating; $n++) { echo "<i class=\"glyphicon glyphicon-remove\"></i>"; } ?></td>
        <?php if ($qumulo_index) { ?>
        <td class="highlight"><?php echo $file['creation_time']; ?></td>
        <?php } elseif (!$s3_index) { ?>
        <td class="highlight"><?php echo $file['last_access']; ?></td>
        <?php } ?>
        <?php
        if (count($extra_fields) > 0) {
          foreach ($extra_fields as $key => $value) { ?>
              <td class="highlight"><?php echo $file[$key]; ?></td>
          <?php }
          } ?>
      </tr>
      <?php
        } // END foreach loop over results
      ?>
      </tbody>
    </table>
  </div>
<div class="row">
    <div class="col-xs-6">
        <div class="row text-left">
            <form class="form-inline" style="display:inline-block;" method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <input type="hidden" id="socketlistening" name="socketlistening" value="<?php echo $socketlistening; ?>" />
              <?php
              foreach($_GET as $name => $value) {
                $name = htmlspecialchars($name);
                $value = htmlspecialchars($value);
                echo '<input type="hidden" name="'. $name .'" value="'. $value .'">';
              }
              ?>
            <div class="form-group">
                  <div class="col-xs-12">
                      <div class="row">
                       <label>Results per page</label>
                  <select class="form-control" name="resultsize" id="resultsize">
                    <option <?php echo $searchParams['size'] == 10 ? "selected" : ""; ?>>10</option>
                    <option <?php echo $searchParams['size'] == 25 ? "selected" : ""; ?>>25</option>
                    <option <?php echo $searchParams['size'] == 50 ? "selected" : ""; ?>>50</option>
                    <option <?php echo $searchParams['size'] == 100 ? "selected" : ""; ?>>100</option>
                    <option <?php echo $searchParams['size'] == 200 ? "selected" : ""; ?>>200</option>
                    <option <?php echo $searchParams['size'] == 300 ? "selected" : ""; ?>>300</option>
                  </select>
              </div>
              </div>
          </div>
          </form>
      </div>
    </div>
    <div class="col-xs-6">
			<div class="row text-right">
    <button onclick="$('html, body').animate({ scrollTop: 0 }, 'fast');" type="button" class="btn btn-default" title="go to top"><i class="glyphicon glyphicon-triangle-top"></i> </button>
            </div>
    </div>
</div>
</form><br />
  <div class="row">
    <div class="col-xs-12 text-center center">
			<div class="row">
      <ul class="pagination">
        <?php
        parse_str($_SERVER["QUERY_STRING"], $querystring);
        $links = 7;
        $page = $querystring['p'];
        $last = ceil($total / $limit);
        $start = (($page - $links) > 0) ? $page - $links : 1;
        $end = (($page + $links) < $last) ? $page + $links : $last;
        $qsfp = $qslp = $qsp = $qsn = $querystring;
        $qsfp['p'] = 1;
        $qslp['p'] = $last;
        if ($qsp['p'] > 1) {
          $qsp['p'] -= 1;
        }
        if ($qsn['p'] < $last) {
          $qsn['p'] += 1;
        }
        $qsfp = http_build_query($qsfp);
        $qslp = http_build_query($qslp);
        $qsn = http_build_query($qsn);
        $qsp = http_build_query($qsp);
        $firstpageurl = $_SERVER['PHP_SELF'] . "?" . $qsfp;
        $lastpageurl = $_SERVER['PHP_SELF'] . "?" . $qslp;
        $prevpageurl = $_SERVER['PHP_SELF'] . "?" . $qsp;
        $nextpageurl = $_SERVER['PHP_SELF'] . "?" . $qsn;
        ?>
        <?php if ($start > 1) { echo '<li><a href="' . $firstpageurl . '">1</a></li>'; } ?>
        <?php if ($page == 1) { echo '<li class="disabled"><a href="#">'; } else { echo '<li><a href="' . $prevpageurl . '">'; } ?>&laquo;</a></li>
        <?php
        for ($i=$start; $i<=$end; $i++) {
          $qs = $querystring;
          $qs['p'] = $i;
          $qs1 = http_build_query($qs);
          $url = $_SERVER['PHP_SELF'] . "?" . $qs1;
        ?>
        <li<?php if ($page == $i) { echo ' class="active"'; } ?>><a href="<?php echo $url; ?>"><?php echo $i; ?></a></li>
        <?php } ?>
        <?php if ($page >= $last) { echo '<li class="disabled"><a href="#">'; } else { echo '<li><a href="' . $nextpageurl . '">'; } ?>&raquo;</a></li>
        <?php if ($end < $last) { echo '<li><a href="' . $lastpageurl . '">' . $last . '</a></li>'; } ?>
      </ul>
      <br />
    </div>
	</div>
  </div>
</div>
<div class="alert alert-dismissible alert-danger" id="errormsg-container">
    <button type="button" class="close" data-dismiss="alert">&times;</button><strong><span id="errormsg"></span></strong>
</div>
<hr>
<p style="text-align:center; font-size:11px; color:#555;">
<?php
$time = number_format(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 6);
echo "ES Process Time: {$estime}, Process Time: {$time}";
?>
</p>
<?php
} // END if there are search results
else {
?>
<div class="container" style="margin-top: 70px;">
  <div class="row">
    <div class="alert alert-dismissible alert-info col-xs-8">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <i class="glyphicon glyphicon-exclamation-sign"></i> <strong>Sorry, no items found.</strong> Change a few things up and try searching again. See help for search examples. <a href="#" onclick="window.history.go(-1); return false;">Go back</a>.
    </div>
  </div>
</div>
<?php

} // END elsif there are no search results

?>