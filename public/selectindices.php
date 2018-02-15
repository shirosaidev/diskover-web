<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require "../src/diskover/Diskover.php";

$host = Constants::ES_HOST;
$port = Constants::ES_PORT;

// find newest index and check if it's still building (crawl in progress)

// Connect to Elasticsearch
$client = connectES();

// Get cURL resource
$curl = curl_init();
// Set curl options
curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'http://'.$host.':'.$port.'/diskover-*?pretty'
));
// Send the request & save response to $curlresp
$curlresp = curl_exec($curl);
$indices = json_decode($curlresp, true);
// Close request to clear up some resources
curl_close($curl);
// sort indices by creation_date
$indices_sorted = [];
foreach ($indices as $key => $val) {
    $indices_sorted[$indices[$key]['settings']['index']['creation_date']] = $key;
}
krsort($indices_sorted);
$newest_index = reset($indices_sorted);

// check if it's finished building (crawl stopped)

$results = [];
$searchParams = [];

$searchParams['index'] = $newest_index;
$searchParams['type']  = 'crawlstat_stop';

$searchParams['body'] = [
    'size' => 1,
    'query' => [
            'match_all' => (object) []
     ],
     'sort' => [
         'indexing_date' => [
             'order' => 'desc'
         ]
     ]
];
$queryResponse = $client->search($searchParams);

$crawlstoptime = $queryResponse['hits']['hits'][0]['_source']['stop_time'];
$crawlelapsedtime = $queryResponse['hits']['hits'][0]['_source']['elapsed_time'];
$crawlfinished = ($crawlstoptime) ? true : false;

// create cookies for default search sort if none already created
if (!getCookie('sort') && !getCookie('sort2')) {
    createCookie('sort', 'path_parent');
    createCookie('sortorder', 'asc');
    createCookie('sort2', 'filename');
    createCookie('sortorder2', 'asc');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>diskover &mdash; Index Selector</title>
<link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
<link rel="stylesheet" href="css/diskover.css" media="screen" />
<?php
$indexselected = "";
$index2selected = "";
// set cookies for indices and redirect to index page
if (isset($_POST['index'])) {
    $indexselected = trim(str_replace(['<- newest','*crawl still running*'], '', $_POST['index']));
    createCookie('index', $indexselected);
    if (isset($_POST['index2']) && $_POST['index2'] != "none") {
        $index2selected = $_POST['index2'];
        createCookie('index2', $_POST['index2']);
    } else if (isset($_POST['index2']) && ($_POST['index2'] == "none" || $_POST['index2'] == "" )) {
        deleteCookie('index2');
    }
    // delete existing path cookie
    deleteCookie('path');
    // redirect to index dashboard page
    header("location: /index.php?index=".$indexselected."&index2=".$index2selected."");
    exit();
}
?>
</head>

<body>

<div class="container" style="margin-top:100px;">
<div class="row">
	<div class="col-xs-12 text-center">
		<img src="images/diskover.png" alt="diskover" width="249" height="189" /><br />
        <span class="text-success small"><?php echo "diskover-web v".Constants::VERSION; ?></span>
	</div>
</div>
<div class="row">
    <br />
<div class="col-xs-6 col-xs-offset-3">
	<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="form-horizontal">
	<fieldset>
		<div class="form-group">
			<?php
				// Get cURL resource
				$curl = curl_init();
				// Set curl options
				curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => 'http://'.$host.':'.$port.'/diskover-*?pretty'
				));
				// Send the request & save response to $curlresp
				$curlresp = curl_exec($curl);
				$indices = json_decode($curlresp, true);
				// Close request to clear up some resources
				curl_close($curl);
			?>
            <?php if ($indices == null) { ?>
                <div class="alert alert-dismissible alert-danger">
                  <button type="button" class="close" data-dismiss="alert">&times;</button>
                  <i class="glyphicon glyphicon-exclamation-sign"></i> No diskover indices found in Elasticsearch. Please run a crawl and come back.
                </div>
            <?php } else { ?>
                <div class="alert alert-dismissible alert-info">
                  <button type="button" class="close" data-dismiss="alert">&times;</button>
                  <i class="glyphicon glyphicon-cog"></i> Please select the Elasticsearch diskover indices you want to use. Indices are sorted by creation date.
                </div>
            <?php } ?>
            <strong>Index</strong> <small>*required</small><br />
			<select name="index" id="index" class="form-control">
                <option selected><?php echo getCookie('index') ? getCookie('index') : ""; ?></option>
                <?php
				foreach ($indices_sorted as $key => $val) {
                    if ($val == $newest_index && !$crawlfinished) {
                        echo "<option>".$val." <- newest *crawl still running*</option>";
                    } elseif ($val == $newest_index && $crawlfinished){
                        echo "<option>".$val." <- newest</option>";
                    } else {
                        echo "<option>".$val."</option>";
                    }
				}
				?></select>
            <strong>Index 2</strong> <small>(previous index for data comparison)</small><br />
            <select name="index2" id="index2" class="form-control">
                <option selected><?php echo getCookie('index2') ? getCookie('index2') : ""; ?></option>
                <?php
				foreach ($indices_sorted as $key => $val) {
					echo "<option>".$val."</option>";
				}
                echo "<option>none</option>";
                ?></select>
		</div>
		<div class="form-group text-center">
			<button type="submit" class="btn btn-primary btn-lg"><i class="glyphicon glyphicon-saved"></i> Select</button>
		</div>
	</fieldset>
	</form>
    <br />
    <br />
    <center><i class="glyphicon glyphicon-heart"></i> Support diskover on <a href="https://www.patreon.com/diskover" target="_blank">Patreon</a> or <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CLF223XAS4W72" target="_blank">PayPal</a>.</center>
</div>
</div>
</div>

<script language="javascript" src="js/jquery.min.js"></script>
<script language="javascript" src="js/bootstrap.min.js"></script>

</body>

</html>
