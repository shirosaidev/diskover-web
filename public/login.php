<?php
/*
Copyright (C) Chris Park 2017
diskover is released under the Apache 2.0 license. See
LICENSE for the full license text.
 */

ob_start();
session_start();
require '../vendor/autoload.php';
use diskover\Constants;

?>

<!DOCTYPE html>
<html lang="en">

    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>diskover &mdash; Login</title>
    <link rel="stylesheet" href="css/bootswatch.min.css" media="screen" />
    <link rel="stylesheet" href="css/diskover.css" media="screen" />

      <style>
         body {
            padding-top: 100px;
            padding-bottom: 100px;
         }

         .form-signin {
            max-width: 330px;
            padding: 15px;
            padding-top: 0;
            margin: 0 auto;
         }

         .form-signin .form-signin-heading,
         .form-signin .checkbox {
            margin-bottom: 10px;
         }

         .form-signin-heading {
             color: #A8060E;
             font-size: 14px;
             text-align: center;
         }

         .form-signin .checkbox {
            font-weight: normal;
         }

         .form-signin .form-control {
            position: relative;
            height: auto;
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
            padding: 10px;
            font-size: 16px;
         }

         .form-signin .form-control:focus {
            z-index: 2;
         }

         .form-signin input[type="email"] {
            margin-bottom: -1px;
         }

         .form-signin input[type="password"] {
            margin-bottom: 10px;
         }

         h5{
            text-align: center;
            color: #D20915;
         }
      </style>

   </head>

   <body>

      <center><img src="images/diskover.png" alt="diskover" width="249" height="189" /></center>
      <h5>diskover-web</h5>
      <div class = "container form-signin">

         <?php
            $msg = '<i class="glyphicon glyphicon-lock"></i> Login';

            if (isset($_POST['login']) && !empty($_POST['username'])
               && !empty($_POST['password'])) {
                if ($_POST['username'] == Constants::DISKOVER_USER &&
                  $_POST['password'] == Constants::DISKOVER_PASS) {
                    $_SESSION['valid'] = true;
                    $_SESSION['timeout'] = time();
                    $_SESSION['username'] = Constants::DISKOVER_USER;

                    header("location:dashboard.php");
                    exit();
                } else {
                    $msg = '<i class="glyphicon glyphicon-ban-circle"></i> Wrong username or password';
                }
            }
         ?>
      </div>

      <div class = "container">

         <form class = "form-signin" role = "form"
            action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']);
            ?>" method = "post">
            <h4 class = "form-signin-heading"><?php echo $msg; ?></h4>
            <input type = "text" class = "form-control"
               name = "username" placeholder = "username"
               required autofocus></br>
            <input type = "password" class = "form-control"
               name = "password" placeholder = "password" required>
            <button class = "btn btn-lg btn-primary btn-block" type = "submit"
               name = "login"><i class="glyphicon glyphicon-log-in"></i> Login</button>
         </form>

      </div>
      <br />
      <br />
      <center><strong><i class="glyphicon glyphicon-heart"></i> Support diskover on <a href="https://www.patreon.com/diskover" target="_blank">Patreon</a> or <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CLF223XAS4W72" target="_blank">PayPal</a>.</strong></center>

   </body>
</html>
