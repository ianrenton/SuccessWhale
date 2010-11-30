<?php

/* Start session and load lib */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

if ((isset($_POST['username'])) && (isset($_POST['password']))) {
    if (DB_SERVER != '') {
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
                
                // Password match, so get access token
                $access_token = unserialize(mysql_result($result, 0, "auth_token"));

                /* Save the access tokens. Normally these would be saved in a database for future use. */
                $_SESSION['access_token'] = $access_token;

                // Save accesstoken cookie
                setcookie('access_token', serialize($access_token), mktime()+86400*365);

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
