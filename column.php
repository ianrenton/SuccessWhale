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
$columnOptions = $_SESSION['columnOptions'];

// Set session time constants (so we don't have to calculate them for every
// tweet)
$_SESSION['midnightYesterday'] = strtotime("midnight -1 day");
$_SESSION['oneWeekAgo'] = strtotime("midnight -6 days");
$_SESSION['janFirst'] = strtotime("january 1st");

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
	$blocklist = explode("\n", strtolower($row["blocklist"]));
} else {
    $blocklist = "";
}


// Get column-dependent data and render
if (isset($_GET['column'])) {

    // Header block
    $content .= '<div class="columnheading"><span class="title">';
    if ($columnOptions[$_GET['column']] != "") {
        $content .= $columnOptions[$_GET['column']];
    } else {
        if (!empty($_GET['column'])) {
            $content .= $_GET['column'];
        } else {
            $content .= 'New Column';
        }
    }
    $content .= '<br/><span class="accountname">';
    $sources = explode("|", $_GET['column']);
    if (count($sources) > 1) {
        $content .= 'Combined Feed';
    } else {
        if (!empty($sources[0])) {
	        $columnIdentifiers = explode(":", $sources[0]);
	        $service = $columnIdentifiers[0];
	        $username = $columnIdentifiers[1];
	        if ($service == "twitter") {
	            $content .= '@';
	        }
	        $content .= $username;
	    }
    }
    
    $content .= '</span>';
    $content .= '</span>';
    $content .= '<span class="columnbuttons"><a href="#" class="columnoptions icon left icon157"><span>Column Settings</span></a>';
    $content .= '<a class="confirmactionbutton deletecolumnbutton fullreload icon middle icon184" href="actions.php?delcol=' . $_GET['div'] . '"><span>Delete Column</span></a>';
    $content .= '<a href="javascript:changeColumn(\'' . substr($_GET['div'], 0) . '\', \'column.php?div=' . substr($_GET['div'], 0) . '&column=' . urlencode($_GET['column']) . '&count=' . $paramArray["count"] . '\', 1)" class="icon right icon2 refreshcolumnbutton"><span>Refresh Column</span></a></span>';
    $content .= makeNavForm($paramArray["count"], $columnOptions, $_GET['column']);
    $content .= '</div>';
        
    // Column identifiers are in three colon-separate bits, e.g.
    // twitter:tsuki_chama:statuses/user_timeline
    // Multiple source feeds have several of the above, separated by pipes.
    $items = array();
    $sources = explode("|", $_GET['column']);
    foreach($sources as $source) {
        if (!empty($source)) {
	        $columnIdentifiers = explode(":", $source);
	        $service = $columnIdentifiers[0];
	        $username = $columnIdentifiers[1];
	        $name = $columnIdentifiers[2];
	
	        if (preg_match("/^@(\w+)$/", $source, $matches)) {
	            // Matches @user
	            $service = "twitter";
	            $twitterUsernames = array_keys($twitters);
	            $username = $twitterUsernames[0];
	            $url = "statuses/user_timeline";
	            $paramArray['screen_name'] = $matches[1];
	        } else if (preg_match("/^@(\w+)\/([\w\s-]+)$/", $source, $matches)) {
	            // Matches @user/list
	            $service = "twitter";
	            $twitterUsernames = array_keys($twitters);
	            $username = $twitterUsernames[0];
	            $listStub = strtolower(str_replace(" ", "-", $matches[2]));
	            $url = $matches[1] . "/lists/" . $listStub . "/statuses";
	        } else {
	            // Doesn't match, assume it's a real URL like "statuses/mentions".
	            $url = $name;
	        }
	
	        if ($service == "twitter") {
	            $twitter = $twitters[$username];
	            if ($twitter != null) {
	                $data = $twitter->get($url, $paramArray);

	                $isMention = false;
	                $isDM = false;
                    if ($name == 'statuses/mentions') {
		                $isMention = true;
	                } elseif (($name == 'direct_messages') || ($name == 'direct_messages/sent')) {
		                $isDM = true;
	                }
	                for ($i=0; $i<count($data); $i++) {
	                    $item = generateTweetItem($data[$i], $isMention, $isDM, false, $username, $blocklist);
	                    $items[$item['time']] = $item['html'];
	                }
		            
	            } else {	
		            $content .= '<div class="error">Failwhale sighted off the port bow, cap\'n!  Please try to <a href="javascript:changeColumn(\'' . substr($_GET['div'], 0) . '\', \'column.php?div=' . substr($_GET['div'], 0) . '&column=' . urlencode($_GET['column']) . '&count=' . $paramArray["count"] . '\', 1)" class="icon right icon2 refreshcolumnbutton">refresh this column</a>, and if that doesn\'t work, please re-authenticate with Twitter:<br/><a href="./twitter-callback/redirect.php"><img src="./images/lighter.png" border="0" alt="Sign in with Twitter" title="Sign in with Twitter" /></a></div>';
	            }
	            
	        } elseif ($service == "facebook") {
	            $facebook = $facebooks[$username];
	            if ($facebook != null) {
	                $attachment =  array('access_token' => $facebook->getAccessToken());
                    try {
                        // Catch the Notifications column, which needs to be in FQL
                        if ($name == "notifications") {
                            $attachment['query'] = 'SELECT notification_id, recipient_id, sender_id, object_type, object_id, app_id, created_time, title_html, body_html, href FROM notification WHERE recipient_id="' . $facebook->getUser() . /*'AND is_unread = 1' . */ '" AND is_hidden = 0 LIMIT ' . $paramArray['count'];
                            $attachment['method'] = 'fql.query';
                            $data = $facebook->api($attachment);
                            $isNotifications = true;
                        } else {
                            $data = $facebook->api($name, $attachment);
                            $isNotifications = false;
                        }
                        
                        if (!$isNotifications) {
                            $data = $data['data'];
                        }

                        for ($i=0; $i<count($data); $i++) {
                            $item = generateFBStatusItem($data[$i], $isNotifications, false, $username, $blocklist);
                            $items[$item['time']] = $item['html'];
                        }
	                } catch (Exception $e) {
	                    $content .= '<div class="error">Your Facebook session has perished in the murky depths, cap\'n.<br/>Please try to <a href="javascript:location.reload(true)">reload SuccessWhale</a>, and if that doesn\'t work, re-authenticate with Facebook:<br/><a href="./facebook-callback/"><img src="./images/facebookconnect.gif"  alt="Sign in with Facebook" title="Sign in with Facebook" /></a><br/>(' . $e . ')</div>';
	                }
	            } else {	
		            $content .= '<div class="error">Your Facebook session has perished in the murky depths, cap\'n.<br/>Please try to <a href="javascript:location.reload(true)">reload SuccessWhale</a>, and if that doesn\'t work, re-authenticate with Facebook:<br/><a href="./facebook-callback/"><img src="./images/facebookconnect.gif"  alt="Sign in with Facebook" title="Sign in with Facebook" /></a></div>';
	            }
	        }
	    }
    }
    
    ksort($items);
    $items = array_reverse($items, true);
    $items = array_slice($items, 0, $paramArray['count'], true);
    foreach ($items as $itemHTML) {
        $content .= $itemHTML;
    }
    
    $content .= makeMoreLessForm($paramArray["count"], $columnOptions, $_GET['column']);
		        
    echo $content;
}

mysql_close();

?>
