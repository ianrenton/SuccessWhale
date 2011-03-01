<?php

require_once('common.php');
session_start();

if ((isset($_SESSION['sw_uid'])) && (isset($_POST['password']))) {
        mysql_connect(DB_SERVER,DB_USER,DB_PASS);
        @mysql_select_db(DB_NAME) or die( "Unable to select database");
        
        // Get the user's access token from the table
        $query = "SELECT * FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
        $result = mysql_query($query);

        if (mysql_num_rows($result) > 0) {
            // Password check
            $storedPassword = mysql_result($result, 0, "password");
            if (strcmp($storedPassword, md5($_POST['password'] . PASSWORD_SALT)) == 0) {
                
                // Delete row from table
                $query = "UPDATE sw_users SET username=null, password=null WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
                $result = mysql_query($query);
                
                // Log out too if requested
                if (isset($_POST['logout'])) {
                    if ($_POST['logout'] == "true") {
                        mysql_close();
                        header('Location: ./clearsessions.php');
                    } else {
                        mysql_close();
                        header('Location: ./index.php');
                    }
                } else {
                    mysql_close();
                    header('Location: ./index.php');
                }
                die();
                
            } else {
                // Password didn't match
                mysql_close();
                header('Location: ./unregister.php?fail=true');
                die();
            }
        }
        
} else {
    // Called without password POST.
    header('Location: ./unregister.php');
    die();
}

?>
