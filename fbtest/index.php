<?php

require_once('../common.php');
session_start();

// Create our Application instance.
$facebook = new Facebook(array(
  'appId' => FACEBOOK_APP_ID,
  'secret' => FACEBOOK_SECRET,
  'cookie' => true,
)); 

// Try to get an existing session.  This exists if this page is being correctly
// callbacked from Facebook, but won't otherwise.
$session = $facebook->getSession();

if (FACEBOOK_ENABLED) {
    if ($session) {
      try {
        // Attempt to get data for which authentication is required.  This is another
        // check to make sure that authentication happened correctly.
        // These will throw an exception if they fail.
        $uid = $facebook->getUser();
        $me = $facebook->api('/me');
        
        // Save the access token as a cookie and in the database
        setcookie('facebook', serialize($facebook), mktime()+86400*365);
        if (isset($_SESSION['thisUser'])) {
            $query = "SELECT * FROM users WHERE username='" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
            $result = mysql_query($query);
            if (mysql_num_rows($result) > 0) {
                $query = "UPDATE users SET facebook = '" . mysql_real_escape_string(serialize($facebook)) . "' WHERE username = '" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
                mysql_query($query);
            }
        }
       
        // ENTRY POINT 2b: User has just connected via the Facebook callback.
        // User is authenticated with Facebook, so store the facebook object and
        // head back to index.php
        $_SESSION['facebook'] = $facebook;
        header('Location: ../index.php');

      } catch (FacebookApiException $e) {
        if (DEBUG) {
	        echo($e);
        }
      }
    }
} else {
    // Facebook is disabled
    if (DEBUG) {
        echo("Attempted to use a Facebook callback when Facebook integration is disabled.");
    } else {
        header('Location: ../index.php');
    }
}

?>
