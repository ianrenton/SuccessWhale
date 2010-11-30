<?php

date_default_timezone_set('UTC');

/* Load required lib files. */
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');
require_once('renderfunctions.php');
session_start();

if (DB_SERVER != '') {
    // Connect to DB, we will be using this a lot!
    mysql_connect(DB_SERVER,DB_USER,DB_PASS);
    @mysql_select_db(DB_NAME) or die( "Unable to select database");
}
    
$content = '';

// Get session vars
$to = $_SESSION['to'];
$thisUser = $_SESSION['thisUser'];
$utcOffset = $_SESSION['utcOffset'];
$columnOptions = $_SESSION['columnOptions'];

// Set count
if (isset($_GET['count'])) {
    $paramArray["count"] = $_GET['count'];
} else {
    $paramArray["count"] = 20;
}
// Lists use per_page not count, for some reason.
$paramArray["per_page"] = $paramArray["count"];


if (DB_SERVER != '') {
	// If updatedb=1, update the userprefs in the database with the new column info
    if (isset($_GET['column']) && isset($_GET['div']) && isset($_GET['updatedb']) && ($_GET['updatedb'] == '1')) {
        $query = "UPDATE userprefs SET " . $_GET['div'] . " = '" . mysql_real_escape_string($_GET['column']) . "' WHERE username = '" . mysql_real_escape_string($thisUser) . "'";
        mysql_query($query);
    }

	// Get blocklist
	$query = "SELECT * FROM userprefs WHERE username='" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
    $result = mysql_query($query);
    $userprefs = mysql_fetch_assoc($result);
    if ($userprefs != FALSE) {
        $blocklist = $userprefs["blocklist"];
    } else {
        $blocklist = "";
    }
}

// Get column-dependent data and render
if (isset($_GET['column'])) {
	$name = $_GET['column'];
	$url = $columnOptions[$name];
	if ($to != null) {
		$data = $to->get($url, $paramArray);
		$content .= '<h2>' . $name . '</h2>';

		$isMention = false;
		$isDM = false;
	    if ($name == 'Mentions') {
			$isMention = true;
		} elseif ($name == 'Direct Messages') {
			$isDM = true;
		}

		$content .= generateTweetList($data, $isMention, $isDM, false, $thisUser, $blocklist, $utcOffset, $midnightYesterday, $oneWeekAgo, $janFirst);
		$content .= makeNavForm($paramArray["count"], $columnOptions, $name);
	} else {	
		$content .= '<h2>Error</h2><div class="tweet">Failwhale sighted off the port bow, cap\'n!  Please try refreshing this page.</div>';
		
	}	
    
    echo $content;
}

// End of script, close DB.
if (DB_SERVER != '') {
    mysql_close();
}

?>
