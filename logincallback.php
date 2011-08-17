<?php

require_once('common.php');
session_start();

if ((isset($_POST['username'])) && (isset($_POST['password']))) {
        mysql_connect(DB_SERVER,DB_USER,DB_PASS);
        @mysql_select_db(DB_NAME) or die( "Unable to select database");
        
        // Get the user's access token from the table
        $query = "SELECT * FROM sw_users WHERE username='" . mysql_real_escape_string($_POST['username']) . "'";
        $result = mysql_query($query);
        
        // User exists check
        if (mysql_num_rows($result) > 0) {
            // Password check
            $storedPassword = mysql_result($result, 0, "password");
            if (strcmp($storedPassword, md5($_POST['password'] . PASSWORD_SALT)) == 0) {
                
                // Password match, so just log in - access tokens and such will
                // be automatically pulled from the database when the user gets
                // to index.php.
                logInUser(mysql_result($result, 0, "sw_uid"));

                mysql_close();
                header('Location: ./index.php');
                die();
                
            } else {
                // Password didn't match
                mysql_close();
                header('Location: ./connect.php?fail=true');
                die();
            }
        } else {
            // Username didn't match
            mysql_close();
            header('Location: ./connect.php?fail=true');
            die();
        }

} else {
    // Called without username/password POST.
    header('Location: ./connect.php');
    die();
}

?>
