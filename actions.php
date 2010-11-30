<?php

/* Load required lib files. */
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');
session_start();

$to = $_SESSION['to'];

// Wrangles text through Twixt.
function twixtify($status) {
    preg_match_all('/^d\s(\w+)/', $status, $dMatches);
	preg_match_all('/(^|\s)@(\w+)/', $status, $atMatches);
	preg_match_all('/(^|\s)#(\w+)/', $status, $tagMatches);
	$newstatus = '';
	for ($i=0; $i<count($dMatches[1]); $i++) {
		$newstatus .= 'd ' . $dMatches[1][$i] . ' ';
	}
	for ($i=0; $i<count($atMatches[2]); $i++) {
		$newstatus .= '@' . $atMatches[2][$i] . ' ';
	}
	$newstatus .= file_get_contents('http://twixt.successwhale.com/index.php?tweet=' . urlencode($status));
	for ($i=0; $i<count($tagMatches[2]); $i++) {
		$newstatus .= ' #' . $tagMatches[2][$i];
	}
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
?>

<script language="JavaScript">
    // Refresh all columns whenever an action is performed.
	window.onload=refreshAll();
</script>
