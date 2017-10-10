<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
// Set time limit to indefinite execution
set_time_limit(0);

require __DIR__ . '/../vendor/autoload.php';
use diskover\Constants;
error_reporting(E_ERROR | E_WARNING | E_PARSE);
require __DIR__ . "/../src/diskover/Diskover.php";

if(isset($_GET['command'])) {
	$result = runCommand($_GET['command']);
	// unset command
	unset($_GET['command']);
}

function runCommand($command) {
	$host = Constants::SOCKET_LISTENER_HOST;
	$port = Constants::SOCKET_LISTENER_PORT;
	// open socket connection to diskover listener
	$fp = stream_socket_client("udp://".$host.":".$port, $errno, $errstr);
	if (!$fp) {
  	echo "ERROR: $errno - $errstr<br />\n";
	} else {
		// send command to server
  	fwrite($fp, $command);
  	while (1) {
			$result = fread($fp, 1024);
			$id = time();
			// print result
			echo "id: $id". PHP_EOL;
			echo "data: $result". PHP_EOL;
			echo PHP_EOL;
			// flush buffers to screen
			ob_flush();
			flush();
			// parse json
			$data = json_decode($result, true);
			// check if we've received exit code or error
			if ($data['msg'] == 'exit' || $data['msg'] == 'error') {
				break;
			}
    }
		// close socket
    fclose($fp);
	}
}
	
?>