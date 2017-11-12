<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// Connect to Elasticsearch
$client = connectES();

// Update files if any were submitted
if (count($_POST['ids_tag']) > 0) {

  // update existing tag field with new value
  foreach ($_POST['ids_tag'] as $id => $value) {
    $index = $_POST[$id];
    $params = array();
    $params['id'] = $id;
    $params['index'] = $index;
    $params['type'] = Constants::ES_TYPE;
    $result = $client->get($params);
    $result['_source']['tag'] = $value;

    $params['body']['doc'] = $result['_source'];
    $result = $client->update($params);
  }
}

if (count($_POST['ids_tag_custom']) > 0) {

  // update existing tag_custom field with new value
  foreach ($_POST['ids_tag_custom'] as $id => $value) {
    $index = $_POST[$id];
    $params = array();
    $params['id'] = $id;
    $params['index'] = $index;
    $params['type'] = Constants::ES_TYPE;
    $result = $client->get($params);
    $result['_source']['tag_custom'] = $value;

    $params['body']['doc'] = $result['_source'];
    $result = $client->update($params);
  }
}

if (isset($_REQUEST["destination"])) {
		header("Location: {$_REQUEST["destination"]}");
  } else if(isset($_SERVER["HTTP_REFERER"])) {
		header("Location: {$_SERVER["HTTP_REFERER"]}");
  } else {
	header("Location: /simple.php");
  }

?>
