<?php

require_once('common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");
    
$content = '';

if (isset($_GET['thisUser']) && isset($_GET['status'])) {
    // Get session vars
    $twitter = $_SESSION['twitters'][$_GET['thisUser']];
    $utcOffset = $_SESSION['utcOffset'];
    $columnOptions = $_SESSION['columnOptions'];

    $statusID = $_GET['status'];


    // Get tweet data and render
    $data = array();
    $i = 0;
    $thisItem = $twitter->get('statuses/show/' . $statusID, $paramArray);
    $statusID = $thisItem['in_reply_to_status_id'];
    while ($statusID > 0) {
        $thisItem = $twitter->get('statuses/show/' . $statusID, $paramArray);
        $data[$i++] = $thisItem;
        $statusID = $thisItem['in_reply_to_status_id'];
    }

    // Blank string is for the blocklist. Blocklists aren't obeyed in convo threads anyway.
    $content .= generateTweetList($data, false, false, true, $thisUser, '', $utcOffset, $midnightYesterday, $oneWeekAgo, $janFirst);

    echo $content;
}

mysql_close();

?>
