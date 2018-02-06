<!--
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
-->
<?php
$esIndex = $_GET['index'] ?: getCookie('index');
$esIndex2 = $_GET['index2'] ?: getCookie('index2');
?>
<nav class="navbar navbar-inverse navbar-fixed-top">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapsible">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
			<a class="navbar-brand" href="dashboard.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>"><img class="pull-left" style="position:absolute;left:15px;top:10px;" src="images/diskovernav.png" /><span style="margin-left:45px;">diskover</span></a>
		</div>

		<div class="collapse navbar-collapse" id="navbar-collapsible">
			<ul class="nav navbar-nav navbar-left">
				<li><a href="simple.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>">Simple Search</a></li>
				<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>">Advanced Search</a></li>
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Quick Search <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;tag=untagged">All files untagged</a></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;tag=delete">All files tagged delete</a></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;tag=archive">All files tagged archive</a></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;tag=keep">All files tagged keep</a></li>
                        <li><a href="simple.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;q=tag_custom:* AND NOT tag_custom:&quot;&quot;">All files with custom tag</a></li>
						<li class="divider"></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;last_mod_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-3 months ")); ?>&amp;sort=last_modified&amp;sortorder=asc">All files last modified > 3 months</a></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;last_mod_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-6 months ")); ?>&amp;sort=last_modified&amp;sortorder=asc">All files last modified > 6 months</a></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;last_mod_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-12 months ")); ?>&amp;sort=last_modified&amp;sortorder=asc">All files last modified > 1 year</a></li>
						<li class="divider"></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;last_access_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-3 months ")); ?>&amp;sort=last_access&amp;sortorder=asc">All files last accessed > 3 months</a></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;last_access_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-6 months ")); ?>&amp;sort=last_access&amp;sortorder=asc">All files last accessed > 6 months</a></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;last_access_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-12 months ")); ?>&amp;sort=last_access&amp;sortorder=asc">All files last accessed > 1 year</a></li>
						<li class="divider"></li>
                        <li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;file_size_bytes_low=1048576&amp;sort=filesize&amp;sortorder=desc">All files size > 1 MB</a></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;file_size_bytes_low=10485760&amp;sort=filesize&amp;sortorder=desc">All files size > 10 MB</a></li>
                        <li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;file_size_bytes_low=26214400&amp;sort=filesize&amp;sortorder=desc">All files size > 25 MB</a></li>
                        <li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;file_size_bytes_low=52428800&amp;sort=filesize&amp;sortorder=desc">All files size > 50 MB</a></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;file_size_bytes_low=104857600&amp;sort=filesize&amp;sortorder=desc">All files size > 100 MB</a></li>
                        <li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;file_size_bytes_low=262144000&amp;sort=filesize&amp;sortorder=desc">All files size > 250 MB</a></li>
                        <li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;file_size_bytes_low=524288000&amp;sort=filesize&amp;sortorder=desc">All files size > 500 MB</a></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;file_size_bytes_low=1048576000&amp;sort=filesize&amp;sortorder=desc">All files size > 1 GB</a></li>
						<li class="divider"></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;hardlinks_low=2">All files hardlinks > 1</a></li>
						<li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;is_dupe=true&amp;sort=filesize&amp;sortorder=desc">All duplicate files</a></li>
                        <li class="divider"></li>
                        <li><a href="advanced.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>&amp;submitted=true&amp;p=1&amp;last_mod_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-3 months ")); ?>&amp;last_access_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-3 months ")); ?>&amp;file_size_bytes_low=1&amp;sort=last_modified&amp;sortorder=asc">Recommended files to remove</a></li>
					</ul>
				</li>
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Analytics <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						<li><a href="filetree.php" id="filetreelink">File Tree</a></li>
						<li><a href="treemap.php" id="treemaplink">Treemap</a></li>
                        <li><a href="heatmap.php" id="heatmaplink">Heatmap</a></li>
                        <li><a href="top50.php" id="top50link">Top 50</a></li>
					</ul>
				</li>
			</ul>
			<ul class="nav navbar-nav navbar-right">
				<li><a href="admin.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>">Admin</a></li>
				<li><a href="help.php?index=<?php echo $esIndex; ?>&amp;index2=<?php echo $esIndex2; ?>">Help</a></li>
				<li><a href="https://github.com/shirosaidev/diskover" target="_blank">View on Github</a></li>
			</ul>
            <form method="get" action="simple.php" class="navbar-form" role="search">
                <input type="hidden" name="submitted" value="true" />
                <input type="hidden" name="p" value="1" />
                <?php if (isset($_REQUEST['resultsize'])) {
                    $resultSize = $_REQUEST['resultsize'];
                } elseif (getCookie("resultsize") != "") {
                    $resultSize = getCookie("resultsize");
                } else {
                    $resultSize = 100;
                } ?>
                <input type="hidden" name="resultsize" value="<?php echo $resultSize; ?>" />
				<div class="form-group" style="display:inline;">
                    <div class="input-group" style="display:table;">
                        <span class="input-group-addon" style="width: 1%;">
                            <i class="glyphicon glyphicon-search"></i>
                        </span>
    					<input type="text" name="q" class="form-control input" style="background-color: #424242!important;" placeholder="Search" value='<?php echo $_GET['q']; ?>'>
                        <span class="input-group-addon" style="width: 1%;">
                            <select name="doctype" class="form-control" style="height:20px;">
                              <option value="file" selected>file</option>
                              <option value="directory">directory</option>
                              <option value="">all</option>
                            </select>
                        </span>
                    </div>
				</div>
			</form>
		</div>
	</div>
</nav>
