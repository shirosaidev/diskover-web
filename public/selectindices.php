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

$host = Constants::ES_HOST;
$port = Constants::ES_PORT;
$aws = Constants::AWS;
$aws_https = Constants::AWS_HTTPS;
$username = Constants::ES_USER;
$password = Constants::ES_PASS;

// find newest index and check if it's still building (crawl in progress)

// Connect to Elasticsearch
$client = connectES();

// Get cURL resource
$curl = curl_init();
// Set curl options
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
if ($aws) {
    if ($aws_https) {
        curl_setopt($curl, CURLOPT_URL, 'https://'.$host.':'.$port.'/diskover*?pretty');
    } else {
        curl_setopt($curl, CURLOPT_URL, 'http://'.$host.':'.$port.'/diskover*?pretty');
    }
} else {
    curl_setopt($curl, CURLOPT_URL, 'http://'.$host.':'.$port.'/diskover*?pretty');
}
// Add user/pass if using ES auth
if ($username !== '' && $password !== '') {
    curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
}
// Send the request & save response to $curlresp
$curlresp = curl_exec($curl);
$indices = json_decode($curlresp, true);
// Close request to clear up some resources
curl_close($curl);

if (!empty($indices)) {
    // sort indices by creation_date
    $indices_sorted = [];
    foreach ($indices as $key => $val) {
        $indices_sorted[$indices[$key]['settings']['index']['creation_date']] = $key;
    }
    krsort($indices_sorted);
    $newest_index = reset($indices_sorted);

    // check if it's finished being indexed

    // Get search results from Elasticsearch for index stats and to see if crawl finished
    $results = [];
    $searchParams = [];

    // Setup search query
    $searchParams['index'] = $esIndex;
    $searchParams['type']  = 'crawlstat';

    $searchParams['body'] = [
        'size' => 1,
        'query' => [
                'match' => [
                    'state' => 'finished_dircalc'
                ]
         ]
    ];
    $queryResponse = $client->search($searchParams);

    // determine if crawl is finished by checking if there is worker_name "main" which only gets added at end of crawl
    $crawlfinished = (sizeof($queryResponse['hits']['hits']) > 0) ? true : false;
}

$indexselected = "";
$index2selected = "";
// set cookies for indices and redirect to index page
if (isset($_POST['index'])) {
    $indexselected = trim(str_replace(['<- newest','*crawl still running*'], '', $_POST['index']));
    createCookie('index', $indexselected);
    if (isset($_POST['index2']) && $_POST['index2'] != "none") {
        $index2selected = $_POST['index2'];
        createCookie('index2', $_POST['index2']);
    } elseif (isset($_POST['index2']) && ($_POST['index2'] == "none" || $_POST['index2'] == "")) {
        deleteCookie('index2');
    }
    // delete existing path cookie
    deleteCookie('path');
    // unset session rootpath
    unset($_SESSION['rootpath']);

    // delete existing sort cookies
    deleteCookie('sort');
    deleteCookie('sortorder');
    deleteCookie('sort2');
    deleteCookie('sortorder2');

    // create cookies for default search sort
    createCookie('sort', 'path_parent');
    createCookie('sortorder', 'asc');
    createCookie('sort2', 'filename');
    createCookie('sortorder2', 'asc');

    // redirect to index dashboard page
    header("location: index.php?index=".$indexselected."&index2=".$index2selected."");
    exit();
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
    <link rel="icon" type="image/png" href="images/diskoverfavico.png" />
    <style>
       body {
          padding-top: 100px;
          padding-bottom: 100px;
       }

       .form-selectindex .form-control {
          position: relative;
          padding: 10px;
          font-size: 14px;
       }

       .form-selectindex .form-control:focus {
          z-index: 2;
       }
    </style>
</head>

<body>

<div class="container">
<div class="row">
	<div class="col-xs-12 text-center">
		<img src="images/diskover.png" alt="diskover" width="249" height="189" /><br />
        <br /><br />
	</div>
</div>
<div class="row">
    <div class="col-xs-6 col-xs-offset-3">
        <?php if (empty($indices)) {
    ?>
            <div class="alert alert-dismissible alert-danger">
              <button type="button" class="close" data-dismiss="alert">&times;</button>
              <i class="glyphicon glyphicon-exclamation-sign"></i> No diskover indices found in Elasticsearch. Please run a crawl and come back.
            </div>
        <?php
} else {
        ?>
            <div class="alert alert-dismissible alert-info">
              <button type="button" class="close" data-dismiss="alert">&times;</button>
              <i class="glyphicon glyphicon-info-sign"></i> Please select at least one diskover index (sorted by creation date). Index 2 is optional and is a previous index used for data comparison.
            </div>
        <?php
    } ?>
    </div>
</div>
<div class="row">
    <div class="col-xs-4 col-xs-offset-4">
	<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" class="form-horizontal" name="form-selectindex">
	<fieldset>
		<div class="form-group form-selectindex">
            <label for="index">Index:</label>
			<select name="index" id="index" class="form-control" required autofocus>
                <option selected><?php echo getCookie('index') ? getCookie('index') : ""; ?></option>
                <?php
                if (!empty($indices_sorted)) {
                    foreach ($indices_sorted as $key => $val) {
                        if ($val == $newest_index && !$crawlfinished) {
                            echo "<option>".$val." <- newest *crawl still running*</option>";
                        } elseif ($val == $newest_index && $crawlfinished) {
                            echo "<option>".$val." <- newest</option>";
                        } else {
                            echo "<option>".$val."</option>";
                        }
                    }
                }
                ?></select>
            <label for="index2">Index 2:</label>
            <select name="index2" id="index2" class="form-control">
                <option selected><?php echo getCookie('index2') ? getCookie('index2') : ""; ?></option>
                <?php
                if (!empty($indices_sorted)) {
                    foreach ($indices_sorted as $key => $val) {
                        echo "<option>".$val."</option>";
                    }
                }
                echo "<option>none</option>";
                ?></select>
		</div>
		<div class="form-group text-center">
			<button type="submit" class="btn btn-lg btn-primary btn-block"><i class="glyphicon glyphicon-saved"></i> Select</button>
		</div>
	</fieldset>
	</form>
    </div>
</div>
</div>

<script language="javascript" src="js/jquery.min.js"></script>
<script language="javascript" src="js/bootstrap.min.js"></script>
<script language="javascript" src="js/diskover.js"></script>

</body>

</html>
