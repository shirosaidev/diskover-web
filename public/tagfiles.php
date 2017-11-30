<?php

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;

error_reporting(E_ALL ^ E_NOTICE);
require __DIR__ . "/../src/diskover/Diskover.php";

// get index cookies
$esIndex = getenv('APP_ES_INDEX') ?: getCookie('index');

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>diskover &mdash; Tag Confirmation</title>
  <!--<link rel="stylesheet" href="/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" href="/css/bootstrap-theme.min.css" media="screen" />-->
	<link rel="stylesheet" href="/css/bootswatch.min.css" media="screen" />
  <link rel="stylesheet" href="/css/diskover.css" media="screen" />
</head>
<body>
<?php include __DIR__ . "/nav.php"; ?>

<?php

// Connect to Elasticsearch
$client = connectES();

// Update files if any were submitted

// update existing tag field with new value
foreach ($_POST['ids_tag'] as $id => $value) {
    $doctype = $_POST[$id];
    $params = array();
    $params['id'] = $id;
    $params['index'] = $esIndex;
    $params['type'] = $doctype;
    $result = $client->get($params);
    $result['_source']['tag'] = $value;
    $params['body']['doc'] = $result['_source'];
    $result = $client->update($params);
}

// update existing tag_custom field with new value
foreach ($_POST['ids_tag_custom'] as $id => $value) {
    $doctype = $_POST[$id];
    $params = array();
    $params['id'] = $id;
    $params['index'] = $esIndex;
    $params['type'] = $doctype;
    $result = $client->get($params);
    $result['_source']['tag_custom'] = $value;
    $params['body']['doc'] = $result['_source'];
    $result = $client->update($params);
}

?>

<div class="container">
  <div class="row">
	<div class="col-xs-8">
        <div class="alert alert-dismissible alert-success">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <strong><i class="glyphicon glyphicon-tags"></i> Files have been tagged in Elasticsearch.</strong> Redirecting to search results in 3 seconds...
</div>
	</div>
  </div>
</div>
<script>
    setTimeout(function(){history.back();}, 3000);
</script>
<script language="javascript" src="/js/jquery.min.js"></script>
<script language="javascript" src="/js/bootstrap.min.js"></script>
<script language="javascript" src="/js/diskover.js"></script>
</body>
</html>
