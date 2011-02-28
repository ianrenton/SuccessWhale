<?php

require_once('common.php');
session_start();

if ((isset($_POST['username'])) && (isset($_POST['password']))) {
    if (DB_ENABLED) {
        mysql_connect(DB_SERVER,DB_USER,DB_PASS);
        @mysql_select_db(DB_NAME) or die( "Unable to select database");
        
        // Get the user's access token from the table
        $query = "SELECT * FROM users WHERE username='" . mysql_real_escape_string($_POST['username']) . "'";
        $result = mysql_query($query);
        
        // Username check
        if (mysql_num_rows($result) > 0) {
            // Password check
            $storedPassword = mysql_result($result, 0, "password");
            if (strcmp($storedPassword, md5($_POST['password'])) == 0) {
                
                // Password match, so get access tokens
                $access_token = unserialize(mysql_result($result, 0, "auth_token"));
                $fb_session = unserialize(mysql_result($result, 0, "fb_session"));

                // ENTRY POINT 3: Loading stored tokens from DB, create objects with them
                // Save the twitter/facebook objects
                $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
                $_SESSION['twitter'] = $twitter;
                $_SESSION['facebook'] = $fb_session;

                // Save accesstoken cookie
                setcookie('access_token', serialize($access_token), mktime()+86400*365);
                setcookie('facebook', serialize($facebook), mktime()+86400*365);

                $_SESSION['status'] = 'verified';
                header('Location: ./index.php');
                die();
                
            } else {
                // Password didn't match
                header('Location: ./login.php?fail=true');
                die();
            }
        } else {
            // Username didn't match
            header('Location: ./login.php?fail=true');
            die();
        }
        
        mysql_close();

    }
} else {
    // Called without username/password POST.
    header('Location: ./login.php');
    die();
}

?>
