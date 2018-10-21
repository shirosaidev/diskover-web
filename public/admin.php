<?php
/*
Copyright (C) Chris Park 2017-2018
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Auth.php";
require "../src/diskover/Diskover.php";


// unset command
if(isset($_GET['command'])) {
	unset($_GET['command']);
}

// curl function to get ES data
function curl_es($url, $request=null, $return_json=true) {
    $host = Constants::ES_HOST;
    $port = Constants::ES_PORT;
    $aws = Constants::AWS;
    $aws_https = Constants::AWS_HTTPS;
    $username = Constants::ES_USER;
    $password = Constants::ES_PASS;
    // Get cURL resource
    $curl = curl_init();
    // Set curl options
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    if ($request === "DELETE") {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
    } elseif ($request === "POST") {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    }
    if ($aws) {
        if ($aws_https) {
            curl_setopt($curl, CURLOPT_URL, 'https://'.$host.':'.$port.$url);
        } else {
            curl_setopt($curl, CURLOPT_URL, 'http://'.$host.':'.$port.$url);
        }
    } else {
        curl_setopt($curl, CURLOPT_URL, 'http://'.$host.':'.$port.$url);
    }
    // Add user/pass if using ES auth
    if (!empty($username) && !empty($password)) {
        curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    }
    // Send the request & save response to $curlresp
    $curlresp = curl_exec($curl);
    // Close request to clear up some resources
    curl_close($curl);
    if ($return_json) {
        return json_decode($curlresp, true);
    } else {
        return $curlresp;
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>diskover &mdash; Admin Panel</title>
<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
<link rel="stylesheet" href="css/diskover.css" media="screen" />
<link rel="icon" type="image/png" href="images/diskoverfavico.png" />
<style>
pre {
    background-color:#060606!important;
    color: #56B6C2!important;
    border: none;
    overflow: visible;
    opacity: 0.5;
}
textarea::-webkit-scrollbar-track
{
	background-color: #060606;
}

textarea::-webkit-scrollbar-corner
{
	background-color: #060606;
}

textarea::-webkit-scrollbar
{
	width: 8px;
    height: 8px;
	background-color: #060606;
}

textarea::-webkit-scrollbar-thumb
{
	background-color: #31363A;
    border-radius: 5px;
}
select::-webkit-scrollbar-track
{
	background-color: #060606;
}

select::-webkit-scrollbar-corner
{
	background-color: #060606;
}

select::-webkit-scrollbar
{
	width: 8px;
    height: 8px;
	background-color: #060606;
}

select::-webkit-scrollbar-thumb
{
	background-color: #31363A;
    border-radius: 5px;
}
</style>
</head>

<body>
<?php include "nav.php"; ?>

<div class="container" style="margin-top:70px;">
<div class="row">
    <div class="col-xs-6">
        <h1 class="text-nowrap"><i class="glyphicon glyphicon-cog"></i> Admin Panel</h1>
        <span style="color:#D20915;"><?php echo "diskover-web v".$VERSION; ?></span><br />
        <small><i class="glyphicon glyphicon-download-alt"></i> <a target="_blank" href="https://github.com/shirosaidev/diskover-web/releases/latest">Check for updates</a></small><br />
        Elasticsearch health: <span id="eshealthheart" style="font-size:18px;color:gray"><strong><i class="glyphicon glyphicon-heart-empty"></i></strong></span><br />
        Connected to Elasticsearch at <?php echo Constants::ES_HOST; ?>:<?php echo Constants::ES_PORT; ?>
</div>
<div class="col-xs-6">
<pre>

    __               __
   /\ \  __         /\ \
   \_\ \/\_\    ____\ \ \/'\     ___   __  __     __   _ __
   /'_` \/\ \  /',__\\ \ , <    / __`\/\ \/\ \  /'__`\/\`'__\   //
  /\ \L\ \ \ \/\__, `\\ \ \\`\ /\ \L\ \ \ \_/ |/\  __/\ \ \/   ('>
  \ \___,_\ \_\/\____/ \ \_\ \_\ \____/\ \___/ \ \____\\ \_\   /rr
   \/__,_ /\/_/\/___/   \/_/\/_/\/___/  \/__/   \/____/ \/_/  *\))_
</pre>
</div>
</div>
<br />
<div class="row">
	<div class="col-xs-6">
        <div class="well">
            <h5>diskover indices selected</h5>
            <?php
            $indexstats = curl_es('/'.$esIndex.'/_stats/store,docs');
            $indexsize = $indexstats['_all']['total']['store']['size_in_bytes'];
            $indexdoccount = $indexstats['_all']['total']['docs'];
            ?>
            <strong>Index: <span class="text-success"><?php echo $esIndex; ?></span></strong><br />
            <span style="color:gray"><small><i class="glyphicon glyphicon-stats"></i> docs (count/deleted): <?php echo $indexdoccount['count'].'/'.$indexdoccount['deleted']; ?> size: <?php echo formatBytes($indexsize); ?></small></span><br />
            <span class="text-info"><small><i class="glyphicon glyphicon-info-sign"></i> If deleted count is high you may want to optimize the index (below) to reduce size.</small></span><br />
            <br /><strong>Index 2:</strong> <?php echo $esIndex2; ?><br /><br />
            <a href="selectindices.php"><button type="button" class="btn btn-primary"><i class="glyphicon glyphicon-cog"></i> Change</button></a>
        </div>

        <hr />
        <?php
            $diskover_indices = curl_es('/diskover*?pretty');
            $fields_file = $diskover_indices[$esIndex]['mappings']['file']['properties'];
            $fields_dir = $diskover_indices[$esIndex]['mappings']['directory']['properties'];
            // combine file and directory fields and find unique
            $fields = [];
            foreach ($fields_file as $key => $value) {
                $fields[] = $key;
            }
            foreach ($fields_dir as $key => $value) {
                $fields[] = $key;
            }
            $fields = array_unique($fields);
        ?>
        <h4>Additional fields for search results</h4>
    <?php

// extra fields text file
$file_extrafields = 'extrafields.txt';

// check if form has been submitted
if (isset($_POST['extrafieldstext'])) {
    // save the text contents
    $extrafieldstext = $_POST['extrafieldstext'];
    // check for newline at end
    if (substr($extrafieldstext, -1) != PHP_EOL) {
        // add newline
        $extrafieldstext .= PHP_EOL;
    }
    $extrafields_ret = file_put_contents($file_extrafields, $extrafieldstext);
}

// read the textfile
$extrafieldstext = file_get_contents($file_extrafields);


?>
        <form name="editextrafields" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="form-horizontal">
            <fieldset>
                <div class="col-xs-12">
                    <div class="form-group">
                    <select class="form-control" id="" name="" style="width:200px; display: inline;">
                        <option value="" selected>Field names</option>
                          <?php foreach ($fields as $key => $value) { ?>
                              <option value=""><?php echo $value; ?></option>
                          <?php } ?>
                    </select>
                    </div>
                    <div class="form-group">
                        <span class="text-info">field|field desc</span>
                        <textarea class="form-control" rows="5" name="extrafieldstext"><?php echo htmlspecialchars($extrafieldstext) ?></textarea>
                    </div>
                    <div class="form-group">
                        <button type="reset" class="btn btn-default">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                        <?php if (isset($extrafields_ret)) {
                            if ($extrafields_ret !== FALSE) { ?>
                            <script>alert("extra fields saved to extrafields.txt");</script>
                            <meta http-equiv='refresh' content='0'>
                        <?php } else { ?>
                            <script>alert("error saving extra fields to extrafields.txt");</script>
                            <meta http-equiv='refresh' content='0'>
                        <?php } } ?>
                    </div>
                </div>
            </fieldset>
        </form>

        <br />
        <div class="well">
			<h5>diskover socket server status</h5>
		<?php
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
	echo '<span class="label label-success"><i class="glyphicon glyphicon-off"></i> diskover.py listening on port '.$socket_port.' TCP</span><input type="hidden" id="socketlistening" value="'.$socketlistening.'" />';
} else {
	$socketlistening = 0;
	echo '<span class="label label-warning"><i class="glyphicon glyphicon-off"></i> diskover.py not listening</span><input type="hidden" id="socketlistening" value="'.$socketlistening.'" />';
}
?>
<br /><br />
<button type="submit" class="btn btn-primary btn-sm" onclick="location.reload(true)" title="reload"><i class="glyphicon glyphicon-refresh"></i> </button>

<h4>Run diskover socket command</h4>
<fieldset>
    <div class="form-group">
        <input name="command" type="text" id="command" placeholder="json command" class="form-control" size="80" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-primary run-btn" onclick="runCommand()">Run</button>
    </div>
    <p>See <a href="help.php">help page</a> for command examples.</p>
</fieldset>
</div>

        <hr />
		<h4>Edit diskover-web config</h4>
		<?php

// configuration file
$file_config = '../src/diskover/Constants.php';

// check if form has been submitted
if (isset($_POST['configtext'])) {
    // save the text contents
    $config_ret = file_put_contents($file_config, $_POST['configtext']);
}

// read the textfile
$configtext = file_get_contents($file_config);

?>
			<form name="editconfig" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="form-horizontal">
				<fieldset>
					<div class="col-xs-12">
						<div class="form-group">
							<textarea class="form-control" rows="18" name="configtext"><?php echo htmlspecialchars($configtext) ?></textarea>
						</div>
						<div class="form-group">
							<button type="reset" class="btn btn-default">Cancel</button>
							<button type="submit" class="btn btn-primary">Save</button>
                            <?php if (isset($config_ret)) {
                                if ($config_ret !== FALSE) { ?>
                                <script>alert("config saved to Constants.php");</script>
                                <meta http-equiv='refresh' content='0'>
                            <?php } else { ?>
                                <script>alert("error saving config to Constants.php");</script>
                                <meta http-equiv='refresh' content='0'>
                            <?php } } ?>
						</div>
					</div>
				</fieldset>
			</form>

        <hr />
		<h4>Clear diskover cookies/cache</h4>
		<button type="submit" class="btn btn-primary" onclick=clearCache()>Clear</button>

        <hr />
        <h4>Optimize diskover indices</h4>
        <span style="color:yellow"><strong><i class="glyphicon glyphicon-warning-sign"></i> Could cause temp high load on ES</strong></span>
    	<form name="optimizeindices" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="form-horizontal">
    	<fieldset>
            <div class="col-xs-12">
    		<div class="form-group">
    			<?php
    				// optimize indices
    				if (isset($_POST['optimizeindices'])) {
    					foreach ($_POST['optimizeindices'] as $i) {
                            curl_es('/' . $i . '/_forcemerge?max_num_segments=1', 'POST', false);
    					}
                    ?>
                    <script>alert("selected indices optimized");</script>
                    <?php echo "<meta http-equiv='refresh' content='0'>"; ?>
    				<?php } ?>
    			<select multiple="" name="optimizeindices[]" id="optimizeindices" class="form-control"><?php
    				foreach ($diskover_indices as $key => $val) {
    					echo "<option>".$key."</option>";
    				}
    				?></select>
    		</div>
    		<div class="form-group">
    			<button type="submit" class="btn btn-success" onclick="optimizeIndex()">Optimize</button>
    		</div>
        </div>
        </fieldset>
    </form>

    <hr />
    <h4>Delete diskover indices</h4>
    <span style="color:red"><strong><i class="glyphicon glyphicon-warning-sign"></i> Careful, index will be deleted permanently!</strong></span>
    <form name="deleteindices" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="form-horizontal">
    <fieldset>
        <div class="col-xs-12">
        <div class="form-group">
            <?php
                // delete indices
                if (isset($_POST['delindices'])) {
                    foreach ($_POST['delindices'] as $i) {
                        curl_es('/' . $i . '?pretty', 'DELETE', false);
                    }
                ?>
                <script>alert("selected indices deleted");</script>
                <?php echo "<meta http-equiv='refresh' content='0'>"; ?>
                <?php } ?>
            <select multiple="" name="delindices[]" id="delindices" class="form-control"><?php
                foreach ($diskover_indices as $key => $val) {
                    echo "<option>".$key."</option>";
                }
                ?></select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-danger" onclick="delIndex()">Delete</button>
        </div>
    </div>
    </fieldset>
    </form>
</div>
<div class="col-xs-6">
    <div class="form-group">
    <h4>Edit smart searches</h4>
    <span class="text-info">!name|es query string</span>
    <?php

// smart searches text file
$file_smartsearches = 'smartsearches.txt';

// check if form has been submitted
if (isset($_POST['smartsearchtext'])) {
    // save the text contents
    $smartsearchtext = $_POST['smartsearchtext'];
    // check for newline at end
    if (substr($smartsearchtext, -1) != PHP_EOL) {
        // add newline
        $smartsearchtext .= PHP_EOL;
    }
    $smartsearches_ret = file_put_contents($file_smartsearches, $smartsearchtext);
}

// read the textfile
$smartsearchtext = file_get_contents($file_smartsearches);

?>
        <form name="editsmartsearch" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="form-horizontal">
            <fieldset>
                <div class="col-xs-12">
                    <div class="form-group">
                        <textarea class="form-control" rows="15" name="smartsearchtext"><?php echo htmlspecialchars($smartsearchtext) ?></textarea>
                    </div>
                    <div class="form-group">
                        <button type="reset" class="btn btn-default">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                        <?php if (isset($smartsearches_ret)) {
                            if ($smartsearches_ret !== FALSE) { ?>
                            <script>alert("smart searches saved to smartsearches.txt");</script>
                            <meta http-equiv='refresh' content='0'>
                        <?php } else { ?>
                            <script>alert("error saving smart searches to smartsearches.txt");</script>
                            <meta http-equiv='refresh' content='0'>
                        <?php } } ?>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
        <div class="form-group">
        <hr />
        <h4>Edit custom tags</h4>
        <span class="text-info">tag name|#hexcolor</span>
		<?php

// custom tags text file
$file_customtags = 'customtags.txt';

// check if form has been submitted
if (isset($_POST['tagtext'])) {
    // save the text contents
    $tagtext = $_POST['tagtext'];
    // check for newline at end
    if (substr($tagtext, -1) != PHP_EOL) {
        // add newline
        $tagtext .= PHP_EOL;
    }
    $customtags_ret = file_put_contents($file_customtags, $tagtext);
}

// read the textfile
$tagtext = file_get_contents($file_customtags);

?>
			<form "edittags" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="form-horizontal">
				<fieldset>
					<div class="col-xs-12">
						<div class="form-group">
							<textarea class="form-control" rows="15" name="tagtext"><?php echo htmlspecialchars($tagtext) ?></textarea>
						</div>
						<div class="form-group">
							<button type="reset" class="btn btn-default">Cancel</button>
							<button type="submit" class="btn btn-primary">Save</button>
                            <?php if (isset($customtags_ret)) {
                                if ($customtags_ret !== FALSE) { ?>
                                    <script>alert("tags saved to customtags.txt");</script>
                                    <meta http-equiv='refresh' content='0'>
                                    <?php } else { ?>
                                    <script>alert("error saving tags to customtags.txt");</script>
                                    <meta http-equiv='refresh' content='0'>
                            <?php } } ?>
						</div>
					</div>
				</fieldset>
			</form>
        </div>

		<div class="form-group">
            <?php
            $curlresp_esindices = curl_es('/diskover*?pretty', 'null', false);
            $curlresp_esinfo = curl_es('/diskover*/_stats?pretty', 'null', false);
            ?>
            <hr />
            <h4>Indices info</h4>
			<textarea name="curlresp" id="curlresp" class="form-control" rows=20><?php echo htmlspecialchars($curlresp_esindices) ?></textarea>
		</div>

		<div class="form-group">
            <hr />
            <h4>Elasticsearch info</h4>
			<textarea name="curlresp_esinfo" id="curlresp_esinfo" class="form-control" rows=20><?php echo htmlspecialchars($curlresp_esinfo) ?></textarea>
		</div>
    </div>
    </fieldset>
    </form>
</div>
<div class="row">
	<div class="col-xs-12">
        <hr />
		<h4 style="display:inline-block">Elasticsearch health / index sizes</h4>
        <?php
        $curlresp_eshealth = curl_es('/_cat/indices?v', 'null', false);
        if (strpos($curlresp_eshealth, 'green')) {
            $eshealth = 'green';
        } elseif(strpos($curlresp_eshealth, 'yellow')) {
            $eshealth = 'yellow';
        } elseif(strpos($curlresp_eshealth, 'red')) {
            $eshealth = 'red';
        } else {
            $eshealth = 'gray';
        }
        ?>
        &nbsp;&nbsp;<span style="font-size:24px;color:<?php echo $eshealth ?>"><strong><i class="glyphicon glyphicon-heart-empty"></i></strong></span>
		<div class="form-group">
            <input type="hidden" name="eshealth" id="eshealth" value="<?php echo $eshealth ?>" />
			<textarea name="curlresp_eshealth" id="curlresp_eshealth" class="form-control" rows=12><?php echo htmlspecialchars($curlresp_eshealth) ?></textarea>
		</div>
        <br />
	</div>
</div>
<div class="row">
    <div class="col-xs-12">
        <hr />
        <h5>Send anonymous stats to the diskover developer</h5>
        <p>Allow usage statistics to be sent to the diskover developer to help improve the product.</p>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="sendstats" onclick="sendStats();">
            <label class="form-check-label" for="sendstats">Allow limited anonymous usage stats</label>
        </div>
        <br />
    </div>
</div>
<div class="alert alert-dismissible alert-danger" id="errormsg-container">
    <button type="button" class="close" data-dismiss="alert">&times;</button><strong><span id="errormsg"></span></strong>
</div>
</div>

<script language="javascript" src="js/jquery.min.js"></script>
<script language="javascript" src="js/bootstrap.min.js"></script>
<script language="javascript" src="js/diskover.js"></script>
<script>
// clear diskover cookies/cache
function clearCache() {
	console.log("purging cookies/cache");
    cookies = ['filter', 'mtime', 'maxdepth', 'hide_thresh', 'path', 'use_count', 'show_files', 'sort', 'sortorder',
                'sort2', 'sortorder2', 'resultsize', 'index', 'index2', 'running_task_id', 'tagsshowuntagged', 
                'tagsshowfiles', 'tagsshowdirectories', 'tagsshowall', 'showotherfiles', 'qumulo', 's3', 'minhardlinks', 
                'mindupes', 'min_change_percent', 'show_new_dirs', 'PHPSESSID', 'sendstats', 'support', 'sponsoring'];
    for (var i = 0; i < cookies.length; i++) {
        deleteCookie(cookies[i]);
    }
    session_storage = ["diskover-filetree", "diskover-treemap", "diskover-heatmap", "diskover-hotdirs-heatmap", 
                        "diskover-dupes", "diskover-hardlinks"];
    for (var i = 0; i < session_storage.length; i++) {
        sessionStorage.removeItem(session_storage[i]);
    }
	alert('cleared, please restart browser');
    return true;
}

// send anonymous stats
function sendStats() {
    if (document.getElementById('sendstats').checked) {
        setCookie('sendstats', 1);
    } else {
        setCookie('sendstats', 0);
    }
}

// del index check
function delIndex() {
	var indices = document.getElementById('delindices').value;
	if (!indices) {
		alert("select at least one index")
		return false;
	}
}
// optimize index check
function optimizeIndex() {
	var indices = document.getElementById('optimizeindices').value;
	if (!indices) {
		alert("select at least one index")
		return false;
	}
}
// listen for msgs from diskover socket server
listenSocketServer();


// set es health heart color at top of page
var color = document.getElementById('eshealth').value;
document.getElementById('eshealthheart').style.color=color;

// set sendstats checkbox
if (getCookie('sendstats') == 1) {
    document.getElementById('sendstats').checked = true;
} else {
    document.getElementById('sendstats').checked = false;
}
</script>
<div id="loading">
  <img id="loading-image" width="32" height="32" src="images/ajax-loader.gif" alt="Updating..." />
  <div id="loading-text"></div>
</div>
</body>

</html>
