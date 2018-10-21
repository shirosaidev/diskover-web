<?php
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL ^ E_NOTICE);

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
	die("No POST");
}

function get_client_ip() {
	$ipaddress = '';
	if (isset($_SERVER['HTTP_CLIENT_IP']))
	    $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	    $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	else if(isset($_SERVER['HTTP_X_FORWARDED']))
	    $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
	    $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	else if(isset($_SERVER['HTTP_FORWARDED']))
	    $ipaddress = $_SERVER['HTTP_FORWARDED'];
	else if(isset($_SERVER['REMOTE_ADDR']))
	    $ipaddress = $_SERVER['REMOTE_ADDR'];
	else
	    $ipaddress = 'UNKNOWN';
	return $ipaddress;
}
$ip = get_client_ip();
$host = gethostbyaddr($ip);
$json = file_get_contents("http://ipinfo.io/$ip/geo");
$json = json_decode($json ,true);
$country = $json['country'];
$region= $json['region'];
$city = $json['city'];

$date = new DateTime();
$date = $date->format("Y-m-d h:i:s");
$userdate = $_POST['date'];
$version = $_POST['version'];
$searchquery = $_POST['searchquery'];
$searchresults = $_POST['searchresults'];
$searchresultssize = $_POST['searchresultssize'];
$request = $_POST['request'];
$diskspacetotal = $_POST['diskspacetotal'];
$diskspaceused = $_POST['diskspaceused'];
$totalfilesize = $_POST['totalfilesize'];
$totaldirs = $_POST['totaldirs'];
$totalfiles = $_POST['totalfiles'];
$path = $_POST['path'];
$crawlstarttime = $_POST['crawlstarttime'];
$crawlfinishtime = $_POST['crawlfinishtime'];
$crawlelapsedtime = $_POST['crawlelapsedtime'];
$crawlcumulativetime = $_POST['crawlcumulativetime'];
$bulkupdatetime = $_POST['bulkupdatetime'];
$workerbots = $_POST['workerbots'];


$servername = "localhost";
$username = "id7562806_admin";
$password = "_D@rkD@ta!";
$dbname = "id7562806_diskoverweb_userdata";

$connection = new mysqli($servername, $username, $password, $dbname);
if ($connection->connect_error) {
	die("Connection failed: " . $connnection->connect_error);
}

// add data to mysql table
$query = "INSERT INTO `logform_data` (`date`, `userdate`, `ip`, `host`, `country`, `region`, `city`, `version`, `searchquery`, `searchresults`, `searchresultssize`, `request`, `diskspacetotal`, `diskspaceused`, `totalfilesize`, `totaldirs`, `totalfiles`, `path`, `crawlstarttime`, `crawlfinishtime`, `crawlelapsedtime`, `crawlcumulativetime`, `bulkupdatetime`, `workerbots`) VALUES ('$date', '$userdate', '$ip', '$host', '$country', '$region', '$city', '$version', '$searchquery', '$searchresults', '$searchresultssize', '$request', '$diskspacetotal', '$diskspaceused', '$totalfilesize', '$totaldirs', '$totalfiles', '$path', '$crawlstarttime', '$crawlfinishtime', '$crawlelapsedtime', '$crawlcumulativetime', '$bulkupdatetime', '$workerbots');";
if ($connection->query($query) !== TRUE) {
	echo "Error: " . $query . "<br>" . $connection->error;
}

$connection->close();
?>