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
                
                // Delete rows from tables
                $query = "DELETE FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
                mysql_query($query);
                $query = "DELETE FROM twitter_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
                mysql_query($query);
                $query = "DELETE FROM facebook_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
                mysql_query($query);
                $query = "DELETE FROM linkedin_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
                mysql_query($query);

                mysql_close();
                header('Location: ./clearsessions.php');
                die();
                
            } else {
                // Password didn't match
                mysql_close();
                header('Location: ./unregister.php?fail=true');
                die();
            }
        }
        
} elseif ((isset($_SESSION['sw_uid'])) && (isset($_GET['service'])) && (isset($_GET['id']))) {
        
        // Get the appropriate row
		if ($_GET['service'] == "twitter") {
			$table = "twitter_users";
		} elseif ($_GET['service'] == "facebook") {
			$table = "facebook_users";
		} elseif ($_GET['service'] == "linkedin") {
			$table = "linkedin_users";
		} else {
            header('Location: ./index.php');
            die();
		}
		
		mysql_connect(DB_SERVER,DB_USER,DB_PASS);
        @mysql_select_db(DB_NAME) or die( "Unable to select database");
        
        $query = "SELECT * FROM " . $table . " WHERE id='" . mysql_real_escape_string($_GET['id']) . "'";
        echo($query);
		$result = mysql_query($query);

        if (mysql_num_rows($result) > 0) {
            // SW UID check
			$row = mysql_fetch_assoc($result);
            if ($row['sw_uid'] == $_SESSION['sw_uid']) {
                
                // Delete rows from tables
                $query = "DELETE FROM " . $table . " WHERE id='" . mysql_real_escape_string($_GET['id']) . "'";
                mysql_query($query);

                mysql_close();
                header('Location: ./index.php');
                die();
                
            } else {
                // SW UID didn't match
                mysql_close();
                header('Location: ./index.php');
            	die();
            }
        }
        
} else {
    // Called without password POST.
    header('Location: ./unregister.php?fail=true');
    die();
}

?>
