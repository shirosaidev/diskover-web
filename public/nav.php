<nav class="navbar navbar-default navbar-fixed">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
			<a class="navbar-brand" href="/dashboard.php">diskover</a>
		</div>

		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			<ul class="nav navbar-nav">
				<li><a href="/simple.php">Simple Search</a></li>
				<li><a href="/advanced.php">Advanced Search</a></li>
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Quick Search <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;tag=untagged">All files untagged</a></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;tag=delete">All files tagged delete</a></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;tag=archive">All files tagged archive</a></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;tag=keep">All files tagged keep</a></li>
						<li class="divider"></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;last_mod_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-3 months ")); ?>&amp;sort=last_modified&amp;sortorder=asc">All files last modified > 3 months</a></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;last_mod_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-6 months ")); ?>&amp;sort=last_modified&amp;sortorder=asc">All files last modified > 6 months</a></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;last_mod_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-12 months ")); ?>&amp;sort=last_modified&amp;sortorder=asc">All files last modified > 1 year</a></li>
						<li class="divider"></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;last_access_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-3 months ")); ?>&amp;sort=last_access&amp;sortorder=asc">All files last accessed > 3 months</a></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;last_access_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-6 months ")); ?>&amp;sort=last_access&amp;sortorder=asc">All files last accessed > 6 months</a></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;last_access_time_high=<?php echo gmdate("Y-m-d\TH:i:s", strtotime("-12 months ")); ?>&amp;sort=last_access&amp;sortorder=asc">All files last accessed > 1 year</a></li>
						<li class="divider"></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;file_size_bytes_low=10485760&amp;sort=filesize&amp;sortorder=desc">All files size > 10 MB</a></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;file_size_bytes_low=104857600&amp;sort=filesize&amp;sortorder=desc">All files size > 100 MB</a></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;file_size_bytes_low=1048576000&amp;sort=filesize&amp;sortorder=desc">All files size > 1 GB</a></li>
						<li class="divider"></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;hardlinks_low=2">All files hardlinks > 1</a></li>
						<li><a href="/advanced.php?submitted=true&amp;p=1&amp;is_dupe=true&amp;sort=filesize&amp;sortorder=desc">All duplicate files</a></li>
					</ul>
				</li>
				<li><a href="#" id="filetreelink">File Tree</a></li>
			</ul>
			<form method="get" action="/simple.php" class="navbar-form navbar-left" role="search">
				<div class="form-group">
					<input type="text" name="q" class="form-control" placeholder="What you looking for?">
					<input type="hidden" name="submitted" value="true" />
					<input type="hidden" name="p" value="1" />
				</div>
				<button type="submit" class="btn btn-default">Search</button>
			</form>
			<ul class="nav navbar-nav navbar-right">
				<li><a href="https://github.com/shirosaidev/diskover-web">View on Github</a></li>
			</ul>
		</div>
	</div>
</nav>