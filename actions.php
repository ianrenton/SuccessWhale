<?php

/* Load required lib files. */
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');
session_start();

$to = $_SESSION['to'];
$thisUser = $_SESSION['thisUser'];
$fullRefresh = false;

// Wrangles text through Twixt.
function twixtify($status) {
	
	// New code that generates "@user My message including #tags but is too lo... http://is.gd/blah"
	$newstatus = substr($status,0,116);
	$newstatus .= "... ";
	$newstatus .= file_get_contents('http://twixt.successwhale.com/index.php?tweet=' . urlencode($status));
	
	return $newstatus;
}

// If we're POSTing with a status, send it to Twitter and reload.  Pass it
// through Twixt if necessary.
if (isset($_POST['status'])) {
        $status = stripslashes($_POST['status']);
	if (strlen($status) > 140) {
		$status = twixtify($status);
	}
	$replyid = '';
	if (isset($_POST['replyid'])) {
		$replyid = $_POST['replyid'];
	}
	$to->post('statuses/update', array('status' => $status, 'in_reply_to_status_id' => $replyid));
}

// If we're GETing with a destroy, send the request to Twitter.
if (isset($_GET['destroystatus'])) {
	$to->post('statuses/destroy', array('id' => $_GET['destroystatus']));
}
// If we're GETing with a destroy DM, send the request to Twitter.
if (isset($_GET['destroydm'])) {
	$to->post('direct_messages/destroy', array('id' => $_GET['destroydm']));
}
// If we're GETing with an RT, send the request to Twitter.
if (isset($_GET['retweet'])) {
	$retweet = "statuses/retweet/".$_GET['retweet'];
	$response = $to->post($retweet);
			
}
// If we're GETing with a report, send the request to Twitter.
if (isset($_GET['report'])) {
	$to->post('report_spam', array('screen_name' => $_GET['report']));
}

// If we're adding a column, add it and set for full refresh
if (isset($_GET['newcol'])) {
    if (DB_SERVER != '') {
        mysql_connect(DB_SERVER,DB_USER,DB_PASS);
        @mysql_select_db(DB_NAME) or die( "Unable to select database");
        //TODO replace column1 with column when renaming db field
        $query = "SELECT column1 FROM userprefs WHERE username = '" . mysql_real_escape_string($thisUser) . "'";
        $result = mysql_query($query);
        if (mysql_num_rows($result) > 0) {
            $userprefrow = mysql_fetch_assoc($result);
            //TODO replace column1 with column when renaming db field
            $newColsString = "" . $userprefrow['column1'] . ";New Column";
            //TODO replace column1 with column when renaming db field
            $query = "UPDATE userprefs SET column1 = '" . mysql_real_escape_string($newColsString) . "' WHERE username = '" . mysql_real_escape_string($thisUser) . "'";
            mysql_query($query);
        }
        $fullRefresh = true;
    }
}

// If we're deleting a column, delete it and set for full refresh
if (isset($_GET['delcol'])) {
    if (DB_SERVER != '') {
        mysql_connect(DB_SERVER,DB_USER,DB_PASS);
        @mysql_select_db(DB_NAME) or die( "Unable to select database");
        //TODO replace column1 with column when renaming db field
        $query = "SELECT column1 FROM userprefs WHERE username = '" . mysql_real_escape_string($thisUser) . "'";
        $result = mysql_query($query);
        if (mysql_num_rows($result) > 0) {
            $userprefrow = mysql_fetch_assoc($result);
            //TODO replace column1 with column when renaming db field
            $userCols = explode(";", $userprefrow['column1']);
            unset($userCols[$_GET['delcol']]);
            $newColsString = implode (";", array_values($userCols));
            //TODO replace column1 with column when renaming db field
            $query = "UPDATE userprefs SET column1 = '" . mysql_real_escape_string($newColsString) . "' WHERE username = '" . mysql_real_escape_string($thisUser) . "'";
            mysql_query($query);
        }
        $fullRefresh = true;
    }
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
