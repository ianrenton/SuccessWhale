<?php

/* Start session and load lib */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

if ((isset($_SESSION['sw_uid'])) && (isset($_POST['blocklist']))) {
        mysql_connect(DB_SERVER,DB_USER,DB_PASS);
        @mysql_select_db(DB_NAME) or die( "Unable to select database");

			// Update table
			$query = "UPDATE sw_users SET blocklist='" . mysql_real_escape_string($_POST['blocklist']) . "' WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
			mysql_query($query);
			
			mysql_close();
			header('Location: ./index.php');
    		die();
		}
} else {
    // Called without username/blocklist POST.
    header('Location: ./manageblocks.php?fail=true');
    die();
}

?>
