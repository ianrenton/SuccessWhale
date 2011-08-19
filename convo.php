<?php

require_once('common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");
    
$content = '';

// Twitter
if (($_GET['service'] == 'twitter') && isset($_GET['thisUser']) && isset($_GET['status'])) {
    // Get session vars
    $twitter = $_SESSION['twitters'][$_GET['thisUser']];
    $utcOffset = $_SESSION['utcOffset'];
    $columnOptions = $_SESSION['columnOptions'];

    $statusID = $_GET['status'];


    // Get tweet data and render
    while ($statusID > 0) {
        $data = $twitter->get('statuses/show/' . $statusID, $paramArray);
        $statusID = $data['in_reply_to_status_id'];
        // Blank array is for the blocklist. Blocklists aren't obeyed in convo threads.
        $item = generateTweetItem($data, false, false, true, $_GET['thisUser'], array());
        $content .= $item['html'];
    }
    
    echo $content;

// Facebook
} elseif (($_GET['service'] == 'facebook') && isset($_GET['thisUser']) && isset($_GET['status'])) {
    // Get session vars
    $facebook = $_SESSION['facebooks'][$_GET['thisUser']];
    $utcOffset = $_SESSION['utcOffset'];
    $statusID = $_GET['status'];
	if ($facebook != null) {
        $attachment =  array('access_token' => $facebook->getAccessToken());
		$data = $facebook->api($statusID, $attachment);
		$item = generateFBStatusItem($data, false, false, $_GET['thisUser'], array());
        $content .= $item['html'];

        try {
            $data = $facebook->api($statusID . "/comments", $attachment);
            $data = $data['data'];

            for ($i=0; $i<count($data); $i++) {
                $item = generateFBStatusItem($data[$i], false, true, $_GET['thisUser'], array());
                $content .= $item['html'];
            }
		} catch (Exception $e) {
			echo("Oops, buggered that up :(<br/><br/>".$e);
		}
	}
	echo $content;
}

mysql_close();

?>
