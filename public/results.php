<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */
 
require '../vendor/autoload.php';
use diskover\Constants;

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

error_reporting(E_ALL ^ E_NOTICE);

// Get total directory size, count from Elasticsearch (recursive)
function get_dir_size($path) {
    $client = connectES();
    $esIndex = $_COOKIE['index'];

    $totalsize = 0;
    $totalcount = 0;
    $searchParams['body'] = [];

    // Setup search query
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = 'file';

    $path = addcslashes($path, '+-&&||!(){}[]^"~*?:\/ ');
    $searchParams['body'] = [
        'size' => 0,
            'query' => [
                'query_string' => [
                    'query' => '"' . $path . '"'
                ]
            ],
            'aggs' => [
                'dir_size' => [
                    'sum' => [
                        'field' => 'filesize'
                    ]
                ]
            ]
        ];

    // Send search query to Elasticsearch
    $queryResponse = $client->search($searchParams);

    // Get total count of directory and all subdirs
    $totalcount = (int)$queryResponse['hits']['total'];

    // Get total size of directory and all subdirs
    $totalsize = (int)$queryResponse['aggregations']['dir_size']['value'];

    // Create dirinfo list with size and count
    $dirinfo = [$totalsize, $totalcount];

    return $dirinfo;
}

// see if there are any extra custom fields to add
$extra_fields = [];
for ($i=1; $i < 5; $i++) {
    if (getCookie('field'.$i.'')) {
        $value = (getCookie('field'.$i.'-desc')) ? getCookie('field'.$i.'-desc') : getCookie('field'.$i.'');
        $extra_fields[getCookie('field'.$i.'')] = $value;
    }
}

// display results
if (count($results[$p]) > 0) {
	//print_r($_SERVER);
?>
<div class="container-fluid searchresults" style="margin-top: 70px;">
  <div class="row">
		<div class="col-xs-6">
			<div class="row">
				<div class="alert alert-dismissible alert-success col-xs-8">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<span class="glyphicon glyphicon-search"></span> <strong><?php echo $total; ?> files found. (<?php echo formatBytes($total_size); ?> total this page)</strong>
				</div>
			</div>
		</div>
		<div class="col-xs-6">
			<div class="row">
				<div class="alert alert-dismissible alert-warning col-xs-8 pull-right unsavedChangesAlert" style="display:none;">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<span class="glyphicon glyphicon-save"></span> <strong> <span class="changetagcounter"></span>. Press Tag files to save changes.</strong>
				</div>
			</div>
		</div>
  </div>
  <div class="row">
			<div class="col-xs-12 text-right">
				<div class="row">
                <form method="post" action="tagfiles.php" class="form-inline tagfiles">
                <input type="hidden" id="socketlistening" name="socketlistening" value="<?php echo $socketlistening; ?>" />
				<div class="btn-group">
					<button class="btn btn-default tagAllDelete" type="button" name="tagAll"> Select All Delete</button>
					<button class="btn btn-default tagAllArchive" type="button" name="tagAll"> All Archive</button>
					<button class="btn btn-default tagAllKeep" type="button" name="tagAll"> All Keep</button>
					<button class="btn btn-default tagAllUntagged" type="button" name="tagAll"> All Untagged</button>
				</div>
				<button title="reload" type="button" class="btn btn-default reload-results"><i class="glyphicon glyphicon-refresh"></i> </button>
				<button type="submit" class="btn btn-default button-tagfiles"><i class="glyphicon glyphicon-tag"></i> Tag files</button>
				<div class="form-group">
					<input type="text" class="search form-control" placeholder="Search within results">
				</div>
				</div>
		</div>
    <div class="counter pull-right"></div>
    <table class="table results table-striped table-hover table-condensed">
      <thead>
        <tr>
          <th class="text-nowrap">#</th>
          <th class="text-nowrap">Name <?php echo sortURL('filename'); ?></th>
          <th class="text-nowrap">Path <?php echo sortURL('path_parent'); ?></th>
		  <th class="text-nowrap">File Size <?php echo sortURL('filesize'); ?></th>
          <?php if ($_GET['doctype'] == 'directory') { ?>
          <th class="text-nowrap">Items <?php echo sortURL('items'); ?></th>
          <?php } ?>
          <th class="text-nowrap">Owner <?php echo sortURL('owner'); ?></th>
          <th class="text-nowrap">Group <?php echo sortURL('group'); ?></th>
          <th class="text-nowrap">Modified (utc) <?php echo sortURL('last_modified'); ?></th>
          <th class="text-nowrap">Accessed (utc) <?php echo sortURL('last_access'); ?></th>
          <?php
          if (count($extra_fields) > 0) {
            foreach ($extra_fields as $key => $value) { ?>
                <th class="text-nowrap"><?php echo $value ?> <?php echo sortURL($key); ?></th>
            <?php }
            } ?>
          <th class="text-nowrap">Tag (del/arch/keep) <?php echo sortURL('tag'); ?></th>
          <th class="text-nowrap">Custom Tag <?php echo sortURL('tag_custom'); ?></th>
        </tr>
        <tr class="warning no-result">
          <td colspan="10"><i class="fa fa-warning"></i> No result</td>
        </tr>
      </thead>
      <tfoot>
				<tr>
					<th class="text-nowrap">#</th>
					<th class="text-nowrap">Name</th>
					<th class="text-nowrap">Path</th>
					<th class="text-nowrap">File Size</th>
                    <?php if ($_GET['doctype'] == 'directory') { ?>
                    <th class="text-nowrap">Items</th>
                    <?php } ?>
					<th class="text-nowrap">Owner</th>
					<th class="text-nowrap">Group</th>
					<th class="text-nowrap">Modified (utc)</th>
					<th class="text-nowrap">Accessed (utc)</th>
                    <?php
                    if (count($extra_fields) > 0) {
                      foreach ($extra_fields as $key => $value) { ?>
                          <th class="text-nowrap"><?php echo $value; ?></th>
                      <?php }
                    } ?>
					<th class="text-nowrap">Tag (del/arch/keep)</th>
					<th class="text-nowrap">Custom Tag</th>
				</tr>
      </tfoot>
      <tbody id="results-tbody">
      <?php
        error_reporting(E_ALL ^ E_NOTICE);
        $limit = $searchParams['size'];
        $i = $p * $limit - $limit;
        foreach ($results[$p] as $result) {
          $file = $result['_source'];
          $i += 1;
      ?>
      <input type="hidden" name="<?php echo $result['_id']; ?>" value="<?php echo $result['_type']; ?>" />
      <tr class="<?php if ($file['tag'] == 'delete') { echo 'deleterow'; } elseif ($file['tag'] == 'archive') { echo 'archiverow'; } elseif ($file['tag'] == 'keep') { echo 'keeprow'; }?>">
        <th scope="row" class="text-nowrap"><?php echo $i; ?></th>
        <td class="path"><?php if ($result['_type'] == 'directory') { $cmd = "{\"action\": \"dirsize\", \"path\": \"".$file['path_parent'].'/'.$file['filename']."\", \"index\": \"".$esIndex."\"}"; ?><a onclick='runCommand(<?php echo $cmd; ?>);' href="#"><label title="calculate directory size" class="btn btn-default btn-xs file-cmd-btns run-btn<?php if (!$socketlistening) { ?> disabled<?php } ?>"><i class="glyphicon glyphicon-hdd"></i></label></a>&nbsp;<?php $cmd = "{\"action\": \"reindex\", \"path\": \"".$file['path_parent'].'/'.$file['filename']."\", \"index\": \"".$esIndex."\"}"; ?><a onclick='runCommand(<?php echo $cmd; ?>);' href="#"><label title="reindex directory (non-recursive)" class="btn btn-default btn-xs file-cmd-btns run-btn<?php if (!$socketlistening) { ?> disabled<?php } ?>"><i class="glyphicon glyphicon-repeat"></i></label></a><?php } ?> <?php echo ($result['_type'] == 'file') ? '<i class="glyphicon glyphicon-file" style="color:#738291;font-size:13px;"></i>' : '<i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;font-size:13px;"></i>'; ?> <a href="view.php?id=<?php echo $result['_id'] . '&amp;index=' . $result['_index'] . '&amp;doctype=' . $result['_type']; ?>"><?php echo $file['filename']; ?></a></td>
		  <td class="path"><a href="filetree.php?path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><label title="filetree" class="btn btn-default btn-xs file-btns"><i class="glyphicon glyphicon-tree-conifer"></i></label></a>&nbsp;<a href="treemap.php?path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><label title="treemap" class="btn btn-default btn-xs file-btns"><i class="glyphicon glyphicon-th-large"></i></label></a>&nbsp;<a href="top50.php?path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><label title="top50" class="btn btn-default btn-xs file-btns"><i class="glyphicon glyphicon-th-list"></i></label></a>&nbsp;<a href="advanced.php?submitted=true&amp;p=1&amp;path_parent=<?php echo rawurlencode($file['path_parent']); ?>"><label title="filter" class="btn btn-default btn-xs file-btns"><i class="glyphicon glyphicon-filter"></i></label></a> <?php echo $file['path_parent']; ?></td>
        <td class="text-nowrap"><?php echo formatBytes($file['filesize']); ?></td>
        <?php if ($_GET['doctype'] == 'directory') { ?>
        <td class="text-nowrap"><?php echo $file['items']; ?></th>
        <?php } ?>
        <td class="text-nowrap"><?php echo $file['owner']; ?></td>
        <td class="text-nowrap"><?php echo $file['group']; ?></td>
        <td class="text-nowrap"><?php echo $file['last_modified']; ?></td>
        <td class="text-nowrap"><?php echo $file['last_access']; ?></td>
        <?php
        if (count($extra_fields) > 0) {
          foreach ($extra_fields as $key => $value) { ?>
              <td><?php echo $file[$key]; ?></td>
          <?php }
          } ?>
        <td class="text-nowrap"><div class="btn-group tagButtons" style="white-space:nowrap;" data-toggle="buttons">
            <label class="tagDeleteLabel btn btn-warning btn-xs <?php if ($file['tag'] == 'delete') { echo 'active'; }?>" style="display:inline-block;float:none;" id="highlightRowDelete">
              <input class="tagDeleteInput" type="radio" name="ids_tag[<?php echo $result['_id']; ?>]" value="delete" <?php if ($file['tag'] == 'delete') { echo 'checked'; }; ?> /><i title="delete" class="glyphicon glyphicon-trash"></i>
            </label>
            <label class="tagArchiveLabel btn btn-success btn-xs <?php if ($file['tag'] == 'archive') { echo 'active'; }?>" style="display:inline-block;float:none;" id="highlightRowArchive">
              <input class="tagArchiveInput" type="radio" name="ids_tag[<?php echo $result['_id']; ?>]" value="archive" <?php if ($file['tag'] == 'archive') { echo 'checked'; }; ?> /><i title="archive" class="glyphicon glyphicon-cloud-upload"></i>
            </label>
            <label class="tagKeepLabel btn btn-info btn-xs <?php if ($file['tag'] == 'keep') { echo 'active'; }?>" style="display:inline-block;float:none;" id="highlightRowKeep">
              <input class="tagKeepInput" type="radio" name="ids_tag[<?php echo $result['_id']; ?>]" value="keep" <?php if ($file['tag'] == 'keep') { echo 'checked'; }; ?> /><i title="keep" class="glyphicon glyphicon-floppy-saved"></i>
            </label>
						<label class="tagUntaggedLabel btn btn-default btn-xs" style="display:inline-block;float:none;" id="highlightRowUntagged">
              <input class="tagUntaggedInput" type="radio" name="ids_tag[<?php echo $result['_id']; ?>]" value="untagged" <?php if ($file['tag'] == 'untagged') { echo 'checked'; }; ?> /><i title="untagged" class="glyphicon glyphicon-remove-sign"></i>
            </label>
          </div></td>
        <td class="text-nowrap customtag"><div class="input-group"><input type="text" name="ids_tag_custom[<?php echo $result['_id']; ?>]" class="custom-tag-input form-control input-sm" value="<?php echo $file['tag_custom']; ?>"><span class="input-group-btn"><button title="copy to all" type="button" class="btn btn-default btn-xs copyCustomTag file-btns"><i class="glyphicon glyphicon-tags"></i></button></span></div></td>
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
        <div class="btn-group">
            <div class="btn-group">
              <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <i class="glyphicon glyphicon-export"></i> Export
                <span class="caret"></span>
              </a>
              <ul class="dropdown-menu">
                <li><a target="hiddeniframe" href="export.php?q=<?php echo $_GET['q']; ?>&amp;p=<?php echo $_GET['p'] ?>&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=json">This page (json)</a></li>
                <li><a target="hiddeniframe" href="export.php?q=<?php echo $_GET['q']; ?>&amp;p=all&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=json">All pages (json)</a></li>
                <li><a target="hiddeniframe" href="export.php?q=<?php echo $_GET['q']; ?>&amp;p=<?php echo $_GET['p'] ?>&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=csv">This page (csv)</a></li>
                <li><a target="hiddeniframe" href="export.php?q=<?php echo $_GET['q']; ?>&amp;p=all&amp;resultsize=<?php echo $_GET['resultsize']; ?>&amp;doctype=<?php echo $_GET['doctype']; ?>&amp;export=csv">All pages (csv)</a></li>
               </ul>
            </div>
          </div>
      </div>
    </div>
    <div class="col-xs-6">
			<div class="row text-right">
        <form method="post" action="tagfiles.php" class="form-inline tagfiles">
      <div class="btn-group">
        <button class="btn btn-default tagAllDelete" type="button" name="tagAll"> Select All Delete</button>
        <button class="btn btn-default tagAllArchive" type="button" name="tagAll"> All Archive</button>
        <button class="btn btn-default tagAllKeep" type="button" name="tagAll"> All Keep</button>
				<button class="btn btn-default tagAllUntagged" type="button" name="tagAll"> All Untagged</button>
      </div>
      <button type="button" class="btn btn-default reload-results"><i class="glyphicon glyphicon-refresh"></i> </button>
      <button type="submit" class="btn btn-default button-tagfiles"><i class="glyphicon glyphicon-tag"></i> Tag files</button>
    </form>
            </div>
    </div>
</div>
</form><br />
  <div class="row">
      <div class="col-xs-4">
          <div class="row">
              <form class="form-inline" method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
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
  </div>
  <div class="row">
    <div class="col-xs-12">
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
<div class="alert alert-dismissible alert-danger" id="errormsg-container" style="display:none; width:400px; position: fixed; right: 50px; bottom: 20px; z-index:2">
            <button type="button" class="close" data-dismiss="alert">&times;</button><strong><span id="errormsg"></span></strong>
</div>
<div id="progress-container" class="alert alert-dismissible alert-info" style="display:none; width:400px; height:80px; position: fixed; right: 50px; bottom: 20px; z-index:2">
  <strong>Task running</strong>, keep this window open until done.<br />
  <div id="progress" class="progress">
    <div id="progressbar" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%; color:white; font-weight:bold; height:20px;">
      0%
    </div>
  </div>
</div>
<?php
} // END if there are search results

else {
?>
<div class="container">
  <div class="row">
    <div class="alert alert-dismissible alert-danger col-xs-8">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Sorry, no files found :(</strong> Change a few things up and try searching again.
    </div>
  </div>
</div>
<?php

} // END elsif there are no search results

?>
