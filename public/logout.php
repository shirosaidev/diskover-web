<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

session_start();
// log user out and send to login page
unset($_SESSION["username"]);
unset($_SESSION["password"]);
unset($_SESSION['valid']);
unset($_SESSION['timeout']);
header("location:login.php");
exit();

?>
