<?php

require_once('common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");
    
$content = '';

if (($_GET['service'] == 'twitter') && isset($_GET['thisUser']) && isset($_GET['status'])) {
    // Get session vars
    $twitter = $_SESSION['twitters'][$_GET['thisUser']];
    $utcOffset = $_SESSION['utcOffset'];
    $columnOptions = $_SESSION['columnOptions'];

    $statusID = $_GET['status'];


    // Get tweet data and render
    //$thisItem = $twitter->get('statuses/show/' . $statusID, $paramArray);
    //$statusID = $thisItem['in_reply_to_status_id'];
    while ($statusID > 0) {
        $data = $twitter->get('statuses/show/' . $statusID, $paramArray);
        $statusID = $data['in_reply_to_status_id'];
        // Blank array is for the blocklist. Blocklists aren't obeyed in convo threads.
        $item = generateTweetItem($data, false, false, true, $_GET['thisUser'], array());
        $content .= $item['html'];
    }
    
    echo $content;
}

mysql_close();

?>
