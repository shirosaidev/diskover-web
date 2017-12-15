<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */
 
header('Cache-Control: no-cache');
// Set time limit to indefinite execution
set_time_limit(0);

require '../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ERROR | E_WARNING | E_PARSE);
require "../src/diskover/Diskover.php";

$host = Constants::SOCKET_LISTENER_HOST;
$port = Constants::SOCKET_LISTENER_PORT;
$buff = 1024;
$timeout = 10;

$command = $_GET['command'];
$x = true;

// open socket connection to diskover listener
$fp = stream_socket_client("tcp://".$host.":".$port, $errno, $errstr, $timeout);
if (!$fp) {
    die();
} else {
    // send command to server
    fwrite($fp, $command);
    while (1) {
        $line = fgets($fp, $buff);
        if ($line == '') {
            break;
        }
        echo $line;
        ob_flush();
        flush();
    }
    // close socket
    fclose($fp);
}

?>
