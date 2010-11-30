<?php

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

$statusID = $_GET['status'];


// Get tweet data and render
$data = array();
$i = 0;
$thisItem = $to->get('statuses/show/' . $statusID, $paramArray);
$statusID = $thisItem['in_reply_to_status_id'];
while ($statusID > 0) {
    $thisItem = $to->get('statuses/show/' . $statusID, $paramArray);
    $data[$i++] = $thisItem;
    $statusID = $thisItem['in_reply_to_status_id'];
}

$content .= makeConvoHider($_GET['div']);
// Blank string is for the blocklist. Blocklists aren't obeyed in convo threads anyway.
$content .= generateTweetList($data, false, false, true, $thisUser, '', $utcOffset, $midnightYesterday, $oneWeekAgo, $janFirst);
//$content .= makeConvoHiderLower($_GET['div']);

echo $content;

// End of script, close DB.
if (DB_SERVER != '') {
    mysql_close();
}

?>
