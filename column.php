<?php

date_default_timezone_set('UTC');

require_once('common.php');
session_start();

if (DB_SERVER != '') {
    // Connect to DB, we will be using this a lot!
    mysql_connect(DB_SERVER,DB_USER,DB_PASS);
    @mysql_select_db(DB_NAME) or die( "Unable to select database");
}
    
$content = '';

// Get session vars
$to = $_SESSION['twitter'];
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
        //TODO replace column1 with column when renaming db field
        $query = "SELECT column1 FROM userprefs WHERE username = '" . mysql_real_escape_string($thisUser) . "'";
        $result = mysql_query($query);
        if (mysql_num_rows($result) > 0) {
            $userprefrow = mysql_fetch_assoc($result);
            //TODO replace column1 with column when renaming db field
            $userCols = explode(";", $userprefrow['column1']);
            $userCols[($_GET['div'])] = $_GET['column'];
            $newColsString = implode (";", $userCols);
            //TODO replace column1 with column when renaming db field
            $query = "UPDATE userprefs SET column1 = '" . mysql_real_escape_string($newColsString) . "' WHERE username = '" . mysql_real_escape_string($thisUser) . "'";
            mysql_query($query);
        }
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
	
	if (preg_match("/^@(\w+)$/", $name, $matches)) {
	    // Matches @user
	    $url = "statuses/user_timeline";
	    $paramArray['screen_name'] = $matches[1];
	} else if (preg_match("/^@(\w+)\/([\w\s-]+)$/", $name, $matches)) {
	    // Matches @user/list
	    $listStub = strtolower(str_replace(" ", "-", $matches[2]));
	    $url = $matches[1] . "/lists/" . $listStub . "/statuses";
	//} else if (preg_match("/^#(\w+)$/", $name, $matches)) {
	    // Matches #tag
	    // Don't know how to handle this, saved searches?
	    //$url = $name;
	} else {
	    // Doesn't match, assume it's a real URL like "statuses/mentions".
	    $url = $name;
	}
	
	if ($to != null) {
		$data = $to->get($url, $paramArray);
		
		if ($columnOptions[$url] != "") {
		    $content .= '<h2>' . $columnOptions[$name] . '</h2>';
		} else {
		    $content .= '<h2';
		    if (strlen($name) > 20) {
		        $content .= ' class="compact"';
		    }
		    $content .= '>' . $name . '</h2>';
		}

		$isMention = false;
		$isDM = false;
	    if ($name == 'statuses/mentions') {
			$isMention = true;
		} elseif ($name == 'direct_messages') {
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
