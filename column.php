<?php

date_default_timezone_set('UTC');

require_once('common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");
    
$content = '';

// Get session vars
$twitters = $_SESSION['twitters'];
$facebooks = $_SESSION['facebooks'];
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


// If updatedb=1, update the users table in the database with the new column info
if (isset($_GET['column']) && isset($_GET['div']) && isset($_GET['updatedb']) && ($_GET['updatedb'] == '1')) {
    $query = "SELECT columns FROM sw_users WHERE sw_uid = '" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
    $result = mysql_query($query);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
        $userCols = explode(";", $row['columns']);
        $userCols[($_GET['div'])] = $_GET['column'];
        $newColsString = implode (";", $userCols);
        $query = "UPDATE sw_users SET columns = '" . mysql_real_escape_string($newColsString) . "' WHERE sw_uid = '" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
        mysql_query($query);
    }
}

// Get blocklist
$query = "SELECT blocklist FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
$result = mysql_query($query);
$row = mysql_fetch_assoc($result);
if ($row != FALSE) {
    $blocklist = $row["blocklist"];
} else {
    $blocklist = "";
}


// Get column-dependent data and render
if (isset($_GET['column'])) {
    // Column identifiers are in three colon-separate bits, e.g.
    // twitter:tsuki_chama:statuses/user_timeline
	$columnIdentifiers = explode(":", $_GET['column']);
	$service = $columnIdentifiers[0];
	$username = $columnIdentifiers[1];
	$name = $columnIdentifiers[2];
	
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
	    // TODO implement searches
	    //$url = $name;
	} else {
	    // Doesn't match, assume it's a real URL like "statuses/mentions".
	    $url = $name;
	}
	
	if ($columnOptions[$_GET['column']] != "") {
        $content .= '<div class="columnheading">' . $columnOptions[$_GET['column']];
    } else {
        if (!empty($name)) {
            $content .= '<div class="columnheading">' . $name;
        } else {
            $content .= '<div class="columnheading">New Column';
        }
    }
    
    $content .= '&nbsp;<a class="navformbutton" href="">V</a>&nbsp;';
    $content .= '&nbsp;<a href="javascript:changeColumn(\'' . substr($_GET['div'], 0) . '\', \'column.php?div=' . substr($_GET['div'], 0) . '&column=' . urlencode($_GET['column']) . '&count=' . $paramArray["count"] . '\', 1)">R</a>';
    $content .= makeNavForm($paramArray["count"], $columnOptions, $_GET['column']);
    $content .= '</div>';
	
	if ($service == "twitter") {
	    $twitter = $twitters[$username];
	    if ($twitter != null) {
		    $data = $twitter->get($url, $paramArray);

		    $isMention = false;
		    $isDM = false;
	        if ($name == 'statuses/mentions') {
			    $isMention = true;
		    } elseif ($name == 'direct_messages') {
			    $isDM = true;
		    }

		    $content .= generateTweetList($data, $isMention, $isDM, false, $username, $blocklist, $utcOffset, $midnightYesterday, $oneWeekAgo, $janFirst);
	    } else {	
		    $content .= '<div class="tweet">Failwhale sighted off the port bow, cap\'n!  Please try refreshing this page.</div>';
	    }
	    
	} elseif ($service == "facebook") {
	    $facebook = $facebooks[$username];
	    if ($facebook != null) {
	        $attachment =  array('access_token' => $facebook->getAccessToken());
	        try {
		        $data = $facebook->api("/me/home", $attachment);
		        $content .= generateFBStatusList($data, $thisUser, $blocklist, $utcOffset, $midnightYesterday, $oneWeekAgo, $janFirst);
		    } catch (Exception $e) {
		        $content .= '<div class="tweet">I\'m not sure what the Facebook equivalent of a FailWhale is, but I think we just found one.</div>';
		    }
	    } else {	
		    $content .= '<div class="tweet">I\'m not sure what the Facebook equivalent of a FailWhale is, but I think we just found one.</div>';
	    }
	}
    
    echo $content;
}

mysql_close();

?>
