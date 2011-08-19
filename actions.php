<?php

require_once('common.php');
session_start();

$twitters = $_SESSION['twitters'];
$facebooks = $_SESSION['facebooks'];
$fullRefresh = false;

// Wrangles text through Twixt.
function twixtify($status) {
	
	// New code that generates "@user My message including #tags but is too lo... http://is.gd/blah"
	$newstatus = substr($status,0,116);
	$newstatus .= "... ";
	$newstatus .= file_get_contents('http://twixt.successwhale.com/index.php?tweet=' . urlencode($status));
	
	return $newstatus;
}

// If we're POSTing with a status, send it to services and reload.  Pass it
// through Twixt if necessary.
if (isset($_POST['status'])) {
    $status = stripslashes($_POST['status']);
	if (strlen($status) > 140) {
		$statusForTwitter = twixtify($status);
	} else {
	    $statusForTwitter = $status;
	}
	
	$replyid = '';
	if (isset($_POST['replyid'])) {
		$replyid = $_POST['replyid'];
	}
	if (isset($_POST['postToAccounts'])) {
	    $accounts = explode(";", $_POST['postToAccounts']);
	    foreach ($accounts as $account) {
	        $parts = explode(":", $account);
	        $service = $parts[0];
	        $username = $parts[1];
	        
	        if ($service == "twitter") {
	            $twitter = $twitters[$username];
	            if (($twitter != null) && (!empty($statusForTwitter))) {
	                $twitter->post('statuses/update', array('status' => $statusForTwitter, 'in_reply_to_status_id' => $replyid));
	            }
	        } elseif ($service == "facebook") {
	            $facebook = $facebooks[$username];
	            if (($facebook != null) && (!empty($status))) {
					if ($replyid == '') {
						// Posting to feed
	                	$facebook->api('/me/feed', 'POST', array('message'=> $status, 'cb' => ''));
					} else {
						// Posting a comment
						$facebook->api($replyid . '/comments', 'POST', array('message'=> $status));
					}
	            }
	        }
	    }
	}
}

// If we're GETing with a destroy tweet, send the request to Twitter.
if (isset($_GET['destroystatus']) && isset($_GET['thisUser'])) {
    $twitter = $twitters[$_GET['thisUser']];
    if ($twitter != null) {
	    $twitter->post('statuses/destroy', array('id' => $_GET['destroystatus']));
	}
}
// If we're GETing with a destroy Twitter DM, send the request to Twitter.
if (isset($_GET['destroydm']) && isset($_GET['thisUser'])) {
    $twitter = $twitters[$_GET['thisUser']];
    if ($twitter != null) {
	    $twitter->post('direct_messages/destroy', array('id' => $_GET['destroydm']));
	}
}
// If we're GETing with an RT, send the request to Twitter.
if (isset($_GET['retweet']) && isset($_GET['thisUser'])) {
    $twitter = $twitters[$_GET['thisUser']];
    if ($twitter != null) {
	    $retweet = "statuses/retweet/".$_GET['retweet'];
	    $response = $twitter->post($retweet);
	}		
}

// If we're GETing with an like request, send the request to Facebook.
if (isset($_GET['likeID']) && isset($_GET['thisUser']) && isset($_GET['like'])) {
    $facebook = $facebooks[$_GET['thisUser']];
    if ($facebook != null) {
	    $like = $_GET['likeID'] . "/likes";
		$attachment =  array('access_token' => $facebook->getAccessToken());
		$httpType = 'post';
		if ($_GET['like'] == 'false') {
			$httpType = 'delete';
		}
	    $response = $facebook->api($like, $httpType, $attachment);
	}
}



// If we're GETing with a Twitter report, send the request to Twitter.
if (isset($_GET['report']) && isset($_GET['thisUser'])) {
    $twitter = $twitters[$_GET['thisUser']];
    if ($twitter != null) {
	    $twitter->post('report_spam', array('screen_name' => $_GET['report']));
	}
}

// If we're adding a column, add it and set for full refresh
if (isset($_GET['newcol'])) {
    mysql_connect(DB_SERVER,DB_USER,DB_PASS);
    @mysql_select_db(DB_NAME) or die( "Unable to select database");
    $query = "SELECT columns FROM sw_users WHERE sw_uid = '" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
    $result = mysql_query($query);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
        $newColsString = "" . $row['columns'] . ";New Column";
        $query = "UPDATE sw_users SET columns = '" . mysql_real_escape_string($newColsString) . "' WHERE sw_uid = '" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
        mysql_query($query);
    }
    $fullRefresh = true;
    mysql_close();
}

// If we're deleting a column, delete it and set for full refresh
if (isset($_GET['delcol'])) {
    mysql_connect(DB_SERVER,DB_USER,DB_PASS);
    @mysql_select_db(DB_NAME) or die( "Unable to select database");
    $query = "SELECT columns FROM sw_users WHERE sw_uid = '" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
    $result = mysql_query($query);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
        $userCols = explode(";", $row['columns']);
        unset($userCols[$_GET['delcol']]);
        $newColsString = implode (";", array_values($userCols));
        $query = "UPDATE sw_users SET columns = '" . mysql_real_escape_string($newColsString) . "' WHERE sw_uid = '" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
        mysql_query($query);
    }
    $fullRefresh = true;
}


// Set the theme
if (isset($_POST['theme'])) {
    mysql_connect(DB_SERVER,DB_USER,DB_PASS);
    @mysql_select_db(DB_NAME) or die( "Unable to select database");
    $query = "UPDATE sw_users SET theme = '" . mysql_real_escape_string($_POST['theme']) . "' WHERE sw_uid = '" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
    mysql_query($query);
    mysql_close();
}


// Set the number of columns per screen
if (isset($_POST['colsperscreen'])) {
    mysql_connect(DB_SERVER,DB_USER,DB_PASS);
    @mysql_select_db(DB_NAME) or die( "Unable to select database");
    $query = "UPDATE sw_users SET colsperscreen = '" . mysql_real_escape_string($_POST['colsperscreen']) . "' WHERE sw_uid = '" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
    mysql_query($query);
    mysql_close();
}


// Set the default services to post to
if (isset($_POST['posttoservices'])) {
    mysql_connect(DB_SERVER,DB_USER,DB_PASS);
    @mysql_select_db(DB_NAME) or die( "Unable to select database");
    $query = "UPDATE sw_users SET posttoservices = '" . mysql_real_escape_string($_POST['posttoservices']) . "' WHERE sw_uid = '" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
    mysql_query($query);
    mysql_close();
}


// Refresh according to whichever action is performed.
if ($fullRefresh == true) {
    echo ('<script language="JavaScript">
        history.go(0);
    </script>');
} else {
    echo ('<script language="JavaScript">
	    window.onload=refreshAll();
	</script>');
}


?>
