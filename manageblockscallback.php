<?php

/* Start session and load lib */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

if ((isset($_SESSION['thisUser'])) && (isset($_POST['blocklist']))) {
    if (DB_SERVER != '') {
        mysql_connect(DB_SERVER,DB_USER,DB_PASS);
        @mysql_select_db(DB_NAME) or die( "Unable to select database");
        
        // Get the user's access token from the table
        $query = "SELECT * FROM userprefs WHERE username='" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
        $result = mysql_query($query);
        
        // Username check
        if (mysql_num_rows($result) > 0) {
			// Update table
			$query = "UPDATE userprefs SET blocklist='" . mysql_real_escape_string($_POST['blocklist']) . "' WHERE username='" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
			mysql_query($query);
			
			header('Location: ./index.php');
    		die();
		} else {
			header('Location: ./manageblocks.php?fail=true');
    		die();
		}
        
        mysql_close();

    }
} else {
    // Called without username/blocklist POST.
    header('Location: ./manageblocks.php?fail=true');
    die();
}

?>
