<?php
require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;

error_reporting(E_ALL ^ E_NOTICE);

// display results
if (count($results[$p]) > 0) {
	//print_r($_SERVER);
?>
<div class="container-fluid searchresults">
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
                <form method="post" action="/tagfiles.php" class="form-inline">
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
					<?php
					// get and change url variable for sort
					function sortURL($sort) {
						$query = $_GET;
						$query['sort'] = $sort;
						$query['sortorder'] = 'asc';
						$query_result = http_build_query($query);
						if (($_GET['sort'] == $sort) && ($_GET['sortorder'] == 'asc')) {
							$class = 'sortarrows-active';
						} elseif (($_GET['sort'] == null) && (getCookie('sort') == $sort ) && (getCookie('sortorder') == 'asc')) {
    						$class = 'sortarrows-active';
                        } else {
							$class = '';
						}
						$a_asc = "<a href=\"".$_SERVER['PHP_SELF']."?".$query_result."\" onclick=\"setCookie('sort', '".$sort."'); setCookie('sortorder', 'asc');\"><i class=\"glyphicon glyphicon-chevron-up sortarrows ".$class."\"></i></a>";
						$query['sortorder'] = 'desc';
						$query_result = http_build_query($query);
						if (($_GET['sort'] == $sort) && ($_GET['sortorder'] == 'desc')) {
							$class = 'sortarrows-active';
                        } elseif (($_GET['sort'] == null) && (getCookie('sort') == $sort ) && (getCookie('sortorder') == 'desc')) {
    						$class = 'sortarrows-active';
						} else {
							$class = '';
						}
						$a_desc = "<a href=\"".$_SERVER['PHP_SELF']."?".$query_result."\" onclick=\"setCookie('sort', '".$sort."'); setCookie('sortorder', 'desc');\"><i class=\"glyphicon glyphicon-chevron-down sortarrows ".$class."\"></i></a>";
						return $a_asc.$a_desc;
					}
					?>
          <th class="text-nowrap">Filename <?php echo sortURL('filename'); ?></th>
          <th class="text-nowrap">Parent Path <?php echo sortURL('path_parent'); ?></th>
					<th class="text-nowrap">Size <?php echo sortURL('filesize'); ?></th>
          <th class="text-nowrap">Owner <?php echo sortURL('owner'); ?></th>
          <th class="text-nowrap">Group <?php echo sortURL('group'); ?></th>
          <th class="text-nowrap">Last Modified (utc) <?php echo sortURL('last_modified'); ?></th>
          <th class="text-nowrap">Last Access (utc) <?php echo sortURL('last_access'); ?></th>
          <?php
          if (Constants::EXTRA_FIELDS != "") {
            foreach (Constants::EXTRA_FIELDS as $key => $value) { ?>
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
					<th class="text-nowrap">Filename</th>
					<th class="text-nowrap">Parent Path</th>
					<th class="text-nowrap">Size</th>
					<th class="text-nowrap">Owner</th>
					<th class="text-nowrap">Group</th>
					<th class="text-nowrap">Last Modified (utc)</th>
					<th class="text-nowrap">Last Access (utc)</th>
                    <?php
                    if (Constants::EXTRA_FIELDS != "") {
                      foreach (Constants::EXTRA_FIELDS as $key => $value) { ?>
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
      <input type="hidden" name="<?php echo $result['_id']; ?>" value="<?php echo $result['_index']; ?>" />
      <tr class="<?php if ($file['tag'] == 'delete') { echo 'deleterow'; } elseif ($file['tag'] == 'archive') { echo 'archiverow'; } elseif ($file['tag'] == 'keep') { echo 'keeprow'; }?>">
        <th scope="row" class="text-nowrap"><?php echo $i; ?></th>
        <td class="path"><i class="glyphicon glyphicon-file" style="color:#738291;font-size:13px;"></i> <a href="/view.php?id=<?php echo $result['_id'] . '&amp;index=' . $result['_index']; ?>"><?php echo $file['filename']; ?></a></td>
		  <td class="path"><a href="/filetree.php?path=<?php echo rawurlencode($file['path_parent']); ?>&amp;filter=<?php echo $_COOKIE['filter']; ?>&amp;mtime=<?php echo $_COOKIE['mtime']; ?>"><label title="filetree" class="btn btn-default btn-xs file-btns"><i class="glyphicon glyphicon-folder-open"></i></label></a>&nbsp;<a href="/treemap.php?path=<?php echo rawurlencode($file['path_parent']); ?>"><label title="treemap" class="btn btn-default btn-xs file-btns"><i class="glyphicon glyphicon-th-large"></i></label></a>&nbsp;<a href="/advanced.php?submitted=true&amp;p=1&amp;path_parent=<?php echo rawurlencode($file['path_parent']); ?>"><label title="filter" class="btn btn-default btn-xs file-btns"><i class="glyphicon glyphicon-filter"></i></label></a>&nbsp;<i class="glyphicon glyphicon-folder-close" style="color:#8ACEE9;font-size:13px;"></i> <?php echo $file['path_parent']; ?></td>
        <td class="text-nowrap"><?php echo formatBytes($file['filesize']); ?></td>
        <td class="text-nowrap"><?php echo $file['owner']; ?></td>
        <td class="text-nowrap"><?php echo $file['group']; ?></td>
        <td class="text-nowrap"><?php echo $file['last_modified']; ?></td>
        <td class="text-nowrap"><?php echo $file['last_access']; ?></td>
        <?php
        if (Constants::EXTRA_FIELDS != "") {
          foreach (Constants::EXTRA_FIELDS as $key => $value) { ?>
              <td><?php echo $file[$key]; ?></td>
          <?php }
        } ?>
        <td class="text-nowrap"><div class="btn-group tagButtons" style="white-space:nowrap;" data-toggle="buttons">
            <label class="tagDeleteLabel btn btn-warning btn-sm <?php if ($file['tag'] == 'delete') { echo 'active'; }?>" style="display:inline-block;float:none;" id="highlightRowDelete">
              <input class="tagDeleteInput" type="radio" name="ids_tag[<?php echo $result['_id']; ?>]" value="delete" <?php if ($file['tag'] == 'delete') { echo 'checked'; }; ?> /><i title="delete" class="glyphicon glyphicon-trash"></i>
            </label>
            <label class="tagArchiveLabel btn btn-success btn-sm <?php if ($file['tag'] == 'archive') { echo 'active'; }?>" style="display:inline-block;float:none;" id="highlightRowArchive">
              <input class="tagArchiveInput" type="radio" name="ids_tag[<?php echo $result['_id']; ?>]" value="archive" <?php if ($file['tag'] == 'archive') { echo 'checked'; }; ?> /><i title="archive" class="glyphicon glyphicon-cloud-upload"></i>
            </label>
            <label class="tagKeepLabel btn btn-info btn-sm <?php if ($file['tag'] == 'keep') { echo 'active'; }?>" style="display:inline-block;float:none;" id="highlightRowKeep">
              <input class="tagKeepInput" type="radio" name="ids_tag[<?php echo $result['_id']; ?>]" value="keep" <?php if ($file['tag'] == 'keep') { echo 'checked'; }; ?> /><i title="keep" class="glyphicon glyphicon-floppy-saved"></i>
            </label>
						<label class="tagUntaggedLabel btn btn-default btn-sm" style="display:inline-block;float:none;" id="highlightRowUntagged">
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
    <div class="col-xs-12">
			<div class="row text-right">
        <form method="post" action="/tagfiles.php" class="form-inline">
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
</form>
  <div class="row">
      <div class="col-xs-2">
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
