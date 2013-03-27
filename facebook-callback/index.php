<?php

require_once('../common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

// Create our Application instance.
$facebook = new Facebook(array(
  'appId' => FACEBOOK_APP_ID,
  'secret' => FACEBOOK_SECRET,
  'cookie' => true,
)); 

// Try to get an existing session.  This exists if this page is being correctly
// callbacked from Facebook, but won't otherwise.
$accesstoken = $facebook->getAccessToken();

if (FACEBOOK_ENABLED) {
    if ($accesstoken) {
      // Session is present, so we are being callbacked from Facebook
      try {
        // Attempt to get data for which authentication is required.  This is another
        // check to make sure that authentication happened correctly.
        // These will throw an exception if they fail.
        $uid = $facebook->getUser();
        $me = $facebook->api('/me');
        
        // Figure out if the Facebook details are already in the database
        $query = "SELECT COUNT(*) FROM facebook_users WHERE uid='" . $uid . "';";
        $result = mysql_query($query) or die (mysql_error());
        $row = mysql_fetch_assoc($result);
        if ($row['COUNT(*)'] == 0) {
            // This Facebook account is new to SuccessWhale
            if (!isset($_SESSION['sw_uid'])) {
                // No user is logged in, so make a new one and log them in.
                logInUser(addSWUser());
            }
            // The user is now logged in, so record their Facebook details alongside
            // their other details.
            $query="INSERT INTO facebook_users (sw_uid,uid,access_token)
                    VALUES ('" . mysql_real_escape_string($_SESSION['sw_uid']) . "', '".
                                mysql_real_escape_string($facebook->getUser())."','".
                                mysql_real_escape_string($facebook->getAccessToken())."');";
            mysql_query($query) or die(mysql_error());
        } else {
            // This Facebook account has been seen before, so update details.
            $query = "UPDATE facebook_users SET access_token='" . mysql_real_escape_string($facebook->getAccessToken()) . 
                                                "' WHERE uid='" . mysql_real_escape_string($facebook->getUser()) . "';";
            mysql_query($query) or die (mysql_error());
            // Now log in the appropriate user to SuccessWhale
            $query = "SELECT sw_uid FROM facebook_users WHERE uid='" . mysql_real_escape_string($facebook->getUser()) . "';";
            $result = mysql_query($query) or die (mysql_error());
            $row = mysql_fetch_assoc($result);
            logInUser($row['sw_uid']);
        }
        
        // The Facebook account is now up-to-date in the database and the user
        // is logged in, so head back to index.php.
        header('Location: ../index.php');

      } catch (FacebookApiException $e) {
        // Exception, so let's request a new session.
		$params = array();
        $params['scope'] = 'status_update,read_stream,publish_stream,manage_notifications,offline_access';
        $loginUrl = $facebook->getLoginUrl($params);
         
        header('Location: ' . $loginUrl);
      }
    } else {
        // No session, redirect to Facebook to get one
        $params = array();
        $params['scope'] = 'status_update,read_stream,publish_stream,manage_notifications,offline_access';
        $loginUrl = $facebook->getLoginUrl($params);
         
        header('Location: ' . $loginUrl);
    }
} else {
    // Facebook is disabled
    if (DEBUG) {
        die("Attempted to use a Facebook callback when Facebook integration is disabled.");
    } else {
        header('Location: ../index.php');
    }
}

mysql_close();

?>
