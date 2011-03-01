<?php

require_once('../common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

if (TWITTER_ENABLED) {
    // If the oauth_token is old log out and try again.  This probably never happens.
    if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
      $_SESSION['oauth_status'] = 'oldtoken';
      header('Location: ../clearsessions.php');
    }

    // Create TwitterOAuth object with app key/secret and token key/secret from default phase
    $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

    // Request access tokens from twitter
    $access_token = $twitter->getAccessToken($_REQUEST['oauth_verifier']);
    
    // If HTTP response is 200 continue otherwise send to connect page to retry
    if (200 == $twitter->http_code) {
        // The user has been verified and the access tokens can be saved for future use
        // Figure out if the Twitter details are already in the database
        $query = "SELECT COUNT(*) FROM twitter_users WHERE uid='" . $access_token['user_id'] . "';";
        $result = mysql_query($query) or die (mysql_error());
        $row = mysql_fetch_assoc($result);
        if ($row['COUNT(*)'] == 0) {
            // This Twitter account is new to SuccessWhale
            if (!isset($_SESSION['sw_uid'])) {
                // No user is logged in, so make a new one.
                logInUser(addSWUser());
            }
            // The user is now logged in, so record their Twitter details alongside
            // their other details.
            $query="INSERT INTO twitter_users (sw_uid,uid,username,access_token)
                    VALUES ('" . mysql_real_escape_string($_SESSION['sw_uid']) . "', '".
                                mysql_real_escape_string($access_token['user_id'])."','".
                                mysql_real_escape_string($access_token['screen_name'])."','".
                                mysql_real_escape_string(serialize($access_token))."');";
            mysql_query($query) or die(mysql_error());
        } else {
            // This Twitter account has been seen before, so update details.
            $query = "UPDATE twitter_users SET username='" . mysql_real_escape_string($access_token['screen_name']) . 
                                                "', access_token='" . mysql_real_escape_string(serialize($access_token)) . 
                                                "' WHERE uid='" . mysql_real_escape_string($access_token['user_id']) . "';";
            mysql_query($query) or die (mysql_error());
            // Now log in the appropriate user to SuccessWhale
            $query = "SELECT sw_uid FROM twitter_users WHERE uid='" . $access_token['user_id'] . "';";
            $result = mysql_query($query) or die (mysql_error());
            $row = mysql_fetch_assoc($result);
            logInUser($row['sw_uid']);
        }
        
        // The Facebook account is now up-to-date in the database and the user
        // is logged in, so head back to index.php.
        header('Location: ../index.php');
        
    } else {
        // HTTP response not 200, something has gone wrong.
        if (DEBUG) {
            die("HTTP response from Twitter was:<br>" . $connection);
        } else {
            header('Location: ../clearsessions.php');
        }
    }
} else {
    // Twitter is disabled
    if (DEBUG) {
        die("Attempted to use a Twitter callback when Twitter integration is disabled.");
    } else {
        header('Location: ../index.php');
    }
}

mysql_close();

?>
