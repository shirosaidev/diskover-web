<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

session_start();
use diskover\Constants;
if (Constants::LOGIN_REQUIRED) {
    // check if user is logged in and timeout not exceeded
    if ($_SESSION['valid'] && (time() - $_SESSION['timeout'] < 3600)) {
        // reset timeout
        $_SESSION['timeout'] = time();
    } else {
        // user not logged in, redirect to login page
        unset($_SESSION["username"]);
        unset($_SESSION["password"]);
        unset($_SESSION['valid']);
        unset($_SESSION['timeout']);
        header("location:login.php");
        exit();
    }
}
