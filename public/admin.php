<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// unset command
if(isset($_GET['command'])) {
	unset($_GET['command']);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>diskover &mdash; Admin Panel</title>
<!--<link rel="stylesheet" href="/css/bootstrap.min.css" media="screen" />
<link rel="stylesheet" href="/css/bootstrap-theme.min.css" media="screen" />-->
<link rel="stylesheet" href="/css/bootswatch.min.css" media="screen" />
<link rel="stylesheet" href="/css/diskover.css" media="screen" />
</head>

<body>
<?php include __DIR__ . "/nav.php"; ?>

<div class="container">
<div class="row">
	<div class="col-xs-6">
		<h1 class="text-nowrap"><img src="/images/diskoversmall.png" alt="diskover" width="62" height="47" /> diskover &mdash; Admin Panel</h1>
		<span class="text-success small"><?php echo "diskover-web v".Constants::VERSION; ?></span>
	</div>
	<div class="col-xs-6">
		<div class="pull-right">
			<h5>diskover socket listener status</h5>
		<?php
error_reporting(E_ERROR | E_PARSE);
// open socket connection to diskover listener
$host = Constants::SOCKET_LISTENER_HOST;
$port = Constants::SOCKET_LISTENER_PORT;
$fp = stream_socket_client("udp://".$host.":".$port, $errno, $errstr);
// set timeout
stream_set_timeout($fp, 2);
// test if listening
fwrite($fp, "ping");
$result = fread($fp, 1024);
// close socket
fclose($fp);
if ($result == "pong") {
	$socketlistening = 1;
	echo '<span class="label label-success"><i class="glyphicon glyphicon-heart"></i> diskover.py listening on port '.$port.' UDP</span><input type="hidden" id="socketlistening" value="'.$socketlistening.'" />';
} else {
	$socketlistening = 0;
	echo '<span class="label label-danger"><i class="glyphicon glyphicon-heart"></i> diskover.py not listening</span><input type="hidden" id="socketlistening" value="'.$socketlistening.'" />';
}
?>
&nbsp;
<button type="submit" class="btn btn-primary" onclick="location.reload(true)" title="reload"><i class="glyphicon glyphicon-refresh"></i> </button>
</div>
</div>
</div>
	<br />
<div class="row">
<div class="col-xs-6">
		<h4>Run diskover socket command</h4>
		<fieldset>
			<div class="form-group">
				<input name="command" type="text" id="command" placeholder="Command" class="form-control" size="80" />
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-primary" onclick="runCommand()">Run</button>
			</div>
		</fieldset>
	<br />
		<div class="alert alert-dismissible alert-danger" id="errormsg-container" style="display:none;">
					<button type="button" class="close" data-dismiss="alert">&times;</button><strong><span id="errormsg"></span></strong>
		</div>
			<div id="progress" class="progress" style="display:none;">
				<div id="progressbar" class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%; color:black;">
					0%
				</div>
			</div>
	<br />
		<h4>Edit config</h4>
		<?php

// configuration
$url = '/admin.php';
$file = '../src/diskover/Constants.php';

// check if form has been submitted
if (isset($_POST['text'])) {
    // save the text contents
    file_put_contents($file, $_POST['text']);
		$saved = TRUE;
}

// read the textfile
$text = file_get_contents($file);

?>
			<form action="" method="post" class="form-horizontal">
				<fieldset>
					<div class="col-xs-12">
						<div class="form-group">
							<textarea class="form-control" rows="18" name="text"><?php echo htmlspecialchars($text) ?></textarea>
						</div>
						<div class="form-group">
							<button type="reset" class="btn btn-default">Cancel</button>
							<button type="submit" class="btn btn-primary">Save</button>
							<span id="configsavestatus" class="text-success"><?php if ($saved == TRUE) { echo "saved."; } ?></span>
						</div>
					</div>
				</fieldset>
			</form>
<br />
		<h4>Clear diskover cookies/cache</h4>
		<button type="submit" class="btn btn-primary" onclick=clearCache()>Clear</button>
		<span id="clearcachestatus" class="text-success"></span>
</div>

<div class="col-xs-6">
	<h4>diskover indices</h4>
	<form action="" method="post" class="form-horizontal">
	<fieldset>
		<div class="form-group">
			<?php
				// delete indices
				if (isset($_POST['indices'])) {
					foreach ($_POST['indices'] as $i) {
						// Get cURL resource
						$curl = curl_init();
						// Set curl options
						curl_setopt_array($curl, array(
								CURLOPT_RETURNTRANSFER => 1,
								CURLOPT_CUSTOMREQUEST => 'DELETE',
								CURLOPT_URL => 'http://localhost:9200/'.$i.'?pretty'
						));
						// Send the request & save response to $curlresp
						$curlresp = curl_exec($curl);
						// Close request to clear up some resources
						curl_close($curl);
					}
				}

				// Get cURL resource
				$curl = curl_init();
				// Set curl options
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => 'http://localhost:9200/diskover-*?pretty'
				));
				// Send the request & save response to $curlresp
				$curlresp = curl_exec($curl);
				$indices = json_decode($curlresp, true);
				// Close request to clear up some resources
				curl_close($curl);

				// Get cURL resource
				$curl = curl_init();
				// Set curl options
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => 'http://localhost:9200/diskover-*/_stats?pretty'
				));
				// Send the request & save response to $curlresp_esinfo
				$curlresp_esinfo = curl_exec($curl);
				// Close request to clear up some resources
				curl_close($curl);

				// Get cURL resource
				$curl = curl_init();
				// Set curl options
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => 'http://localhost:9200/_cat/indices?v'
				));
				// Send the request & save response to $curlresp_esinfo
				$curlresp_eshealth = curl_exec($curl);
				// Close request to clear up some resources
				curl_close($curl);
			?>
			<select multiple="" name="indices[]" id="indices" class="form-control"><?php
				foreach ($indices as $key => $val) {
					echo "<option>".$key."</option>";
				}
				?></select>
		</div>
		<div class="form-group">
			<button type="submit" class="btn btn-primary" onclick="delIndex()">Delete</button>
		</div>
		<h4>Index info</h4>
		<div class="form-group">
			<textarea name="curlresp" id="curlresp" class="form-control" rows=12><?php echo htmlspecialchars($curlresp) ?></textarea>
		</div>
		<h4>Elasticsearch info</h4>
		<div class="form-group">
			<textarea name="curlresp_esinfo" id="curlresp_esinfo" class="form-control" rows=12><?php echo htmlspecialchars($curlresp_esinfo) ?></textarea>
		</div>
	</fieldset>
	</form>
</div>
</div>
<div class="row">
	<div class="col-xs-12">
		<h4>Elasticsearch health</h4>
		<div class="form-group">
			<textarea name="curlresp_eshealth" id="curlresp_eshealth" class="form-control" rows=12><?php echo htmlspecialchars($curlresp_eshealth) ?></textarea>
		</div>
	</div>
</div>
</div>

<script language="javascript" src="/js/jquery.min.js"></script>
<script language="javascript" src="/js/bootstrap.min.js"></script>
<script language="javascript" src="/js/diskover.js"></script>
<script>
// clear diskover cookies/cache
function clearCache() {
	console.log("purging cookies/cache");
	deleteCookie('filter');
	deleteCookie('mtime');
    deleteCookie('maxdepth');
	deleteCookie('hide_thresh');
	deleteCookie('path');
	deleteCookie('use_count');
	deleteCookie('sort');
	deleteCookie('sortorder');
    deleteCookie('savedsearches');
    deleteCookie('resultsize');
	sessionStorage.removeItem("diskover-filetree");
	sessionStorage.removeItem("diskover-treemap");
	document.getElementById('clearcachestatus').innerHTML = 'cleared.';
}

// Curl command
function delIndex() {
	var indices = document.getElementById('indices').value;
	if (!indices) {
		alert("select at least one index")
		return false;
	}
}

// Run remote command progress bar
function runCommand() {
	var socketlistening = document.getElementById('socketlistening').value;
	if (socketlistening == 0) {
		alert("diskover socket not listening")
		return false;
	}
	var command = document.getElementById('command').value;
	if (!command) {
		alert("no command")
		return false;
	}
	var source = new EventSource('run_command.php?command='+command);
	source.addEventListener('message', function(e) {
			var data = JSON.parse(e.data);
			console.log(data);
			document.getElementById('progressbar').innerHTML = data.percent + '%';
			document.getElementById('progressbar').innerHTML += ' ['+data.eta+', '+data.it_per_sec+' '+data.it_name+'/s]';
			document.getElementById('progressbar').setAttribute('aria-valuenow', data.percent);
			document.getElementById('progressbar').style.width = data.percent+'%';
			// check if we received exit msg
			if (data.msg == 'exit') {
				// close event source listener
				source.close();
				// hide progress bar
				//document.getElementById('progress').style.display = 'none';
				document.getElementById('progressbar').innerHTML = 'Finished (exit code:  '+data.exitcode+', elapsed time: '+data.elapsedtime+')';
			}
			// check if we received err msg
			if (data.msg == 'error') {
				// hide progress
				document.getElementById('progress').style.display = 'none';
				// display error
				document.getElementById('errormsg-container').style.display = 'block';
				document.getElementById('errormsg').style.display = 'block';
				document.getElementById('errormsg').innerHTML = '<i class="glyphicon glyphicon-exclamation-sign"></i> Error, check command';
				// close event source listener
				source.close();
			} else {
				// hide error
				document.getElementById('errormsg-container').style.display = 'none';
				document.getElementById('errormsg').style.display = 'none';
				// show progress bar
				document.getElementById('progress').style.display = 'block';
			}
		}, false);

}
</script>
</body>

</html>
