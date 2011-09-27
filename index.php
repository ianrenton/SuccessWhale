<?php
/* SuccessWhale, by Ian Renton
 * A web-based Twitter, Facebook and LinkedIn client written in PHP, MySQL
 * and JavaScript.
 * See http://onlydreaming.net/software/successwhale for details.

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.*/

//////// START OF CODE ////////

date_default_timezone_set('UTC');

require_once('common.php');
session_start();

// DB connection
mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");


// Check cookies, log in the user if the cookies exist and the secret matches,
// but the user is not logged in already.
if (!isset($_SESSION['sw_uid']) && isset($_COOKIE['sw_uid']) && isset($_COOKIE['secret'])) {
    $query = "SELECT secret FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_COOKIE['sw_uid']) . "';";
    $result = mysql_query($query) or die (mysql_error());
    $row = mysql_fetch_assoc($result);
    if ($_COOKIE['secret'] == $row['secret']) {
        logInUser($_COOKIE['sw_uid']);
    }
}
// If the user isn't logged in by this point, they don't have a session or a
// cookie, so kick out to the login page.
if (!isset($_SESSION['sw_uid'])) {
    header('Location: ./clearsessions.php');
}


// Get user setup
$query = "SELECT * FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "';";
$result = mysql_query($query);
$row = mysql_fetch_assoc($result);
$columns = explode(";",$row['columns']);
$numColumns = count($columns);
$colsperscreen = $row['colsperscreen'];
$posttoservices = $row['posttoservices'];
$theme = $row['theme'];
$_SESSION['highlighttime'] = $row['highlighttime'];
$_SESSION['utcOffset'] = $row['utcoffset'];



// Grab appropriate auth tokens from the database, and build objects with them.
$twitters = array();
if (TWITTER_ENABLED) {
    $query = "SELECT access_token FROM twitter_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "';";
    $result = mysql_query($query) or die (mysql_error());
    while ($row = mysql_fetch_assoc($result)) {
        $twitter_access_token = unserialize($row['access_token']);
        $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $twitter_access_token['oauth_token'], $twitter_access_token['oauth_token_secret']);
        $auth = $twitter->get('account/verify_credentials', array());
        $name = $auth['screen_name'];
        $_SESSION['utcOffset'] = $auth['utc_offset'];
        $twitters[$name] = $twitter;
    }
    $_SESSION['twitters'] = $twitters;
}
$facebooks = array();
if (FACEBOOK_ENABLED) {
    $query = "SELECT session FROM facebook_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "';";
    $result = mysql_query($query) or die (mysql_error());
    while ($row = mysql_fetch_assoc($result)) {
        $facebook = new Facebook(array(
          'appId' => FACEBOOK_APP_ID,
          'secret' => FACEBOOK_SECRET,
          'cookie' => true,
        ));
        try {
	         $facebook->setSession(unserialize($row['session']));
             $me = $facebook->api('/me', 'GET');
             $name = $me['name'];
             $facebooks[$name] = $facebook;
         }
         catch (Exception $e) {
          // We don't have a good session
          var_dump($e);
        }
    }
    $_SESSION['facebooks'] = $facebooks;
}
$linkedins = array();
if (LINKEDIN_ENABLED) {
    $query = "SELECT access_token FROM linkedin_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "';";
    $result = mysql_query($query) or die (mysql_error());
    while ($row = mysql_fetch_assoc($result)) {
        $API_CONFIG = array(
		    'appKey'       => LINKEDIN_APP_KEY,
			  'appSecret'    => LINKEDIN_SECRET_KEY,
			  'callbackUrl'  => LINKEDIN_CALLBACK
		  );
		$linkedin = new LinkedIn($API_CONFIG);
		$linkedin->setToken(unserialize($row['access_token']));
        try {
	        $nameReturn = $linkedin->profile('~:(first-name,last-name)');
			$nameBlob = $linkedin->xmlToArray($nameReturn['linkedin'], true);
			$name = $nameBlob['person']['children']['first-name']['content'] . " " . $nameBlob['person']['children']['last-name']['content'];
            $linkedins[$name] = $linkedin;
         }
         catch (Exception $e) {
             // We don't have a good session
		     if (DEBUG) {
             	var_dump($e);
			}
        }
    }
    $_SESSION['linkedins'] = $linkedins;
}

// Build column options list
$columnOptions = array();

// Combined
$columnOptions["combined:" . $name] = "-- Combined --";
// Combined: T&MF
$mnString = "";
foreach ($twitters as $name => $twitter) {
    $mnString .= "twitter:" . $name . ":statuses/home_timeline|";
}
foreach ($facebooks as $name => $facebook) {
    $mnString .= "facebook:" . $name . ":home|";
}
foreach ($linkedins as $name => $linkedin) {
    $mnString .= "linkedin:" . $name . ":type=SHAR|";
}
$columnOptions[$mnString] = "Home Feeds";
// Combined: M&N
$mnString = "";
foreach ($twitters as $name => $twitter) {
    $mnString .= "twitter:" . $name . ":statuses/mentions|";
}
foreach ($facebooks as $name => $facebook) {
    $mnString .= "facebook:" . $name . ":notifications|";
}
$columnOptions[$mnString] = "Mentions & Notifications";
// Combined: M&N&me
$mnString = "";
foreach ($twitters as $name => $twitter) {
    $mnString .= "twitter:" . $name . ":statuses/mentions|";
    $mnString .= "twitter:" . $name . ":statuses/user_timeline|";
}
foreach ($facebooks as $name => $facebook) {
    $mnString .= "facebook:" . $name . ":notifications|";
    $mnString .= "facebook:" . $name . ":/me/feed|";
}
$columnOptions[$mnString] = "Mentions, Notifications & Me";

// Twitter
foreach ($twitters as $name => $twitter) {
    // Twitter basics
    $columnOptions["twitter:" . $name] = "-- Twitter: @" . $name . " --";
    $columnOptions["twitter:" . $name . ":statuses/home_timeline"] = "Home Timeline";
    $columnOptions["twitter:" . $name . ":statuses/friends_timeline"] = "Friends Only";
    $columnOptions["twitter:" . $name . ":statuses/user_timeline"] = "My Tweets";
    $columnOptions["twitter:" . $name . ":statuses/public_timeline"] = "All Tweets";
    $columnOptions["twitter:" . $name . ":statuses/mentions"] = "Mentions";
    $columnOptions["twitter:" . $name . ":statuses/mentions|twitter:" . $name . ":statuses/user_timeline"] = "Mentions & My Tweets";
    $columnOptions["twitter:" . $name . ":direct_messages|twitter:" . $name . ":direct_messages/sent"] = "DMs Sent & Received";
    $columnOptions["twitter:" . $name . ":direct_messages"] = "DMs Received";
    $columnOptions["twitter:" . $name . ":direct_messages/sent"] = "DMs Sent";
    // Twitter lists
    $listsFull = $twitter->get($name . '/lists', array());
    $lists = $listsFull["lists"];
    for ($i=0; $i<count($lists); $i++) {
        $columnOptions["twitter:" . $name . ":" . $name . '/lists/' . $lists[$i]["slug"] . '/statuses'] = $lists[$i]["name"];
    }
}

// Facebook
foreach ($facebooks as $name => $facebook) {
    $columnOptions[("facebook:" . $name)] = "-- Facebook: " . $name . " --";
    $columnOptions["facebook:" . $name . ":/me/home"] = "Main Feed";
    $columnOptions["facebook:" . $name . ":/me/feed"] = "Wall Posts";
    $columnOptions["facebook:" . $name . ":notifications"] = "Notifications";
    $columnOptions["facebook:" . $name . ":/me/events"] = "Events";
}

// LinkedIn
foreach ($linkedins as $name => $linkedin) {
    $columnOptions[("linkedin:" . $name)] = "-- LinkedIn: " . $name . " --";
    $columnOptions["linkedin:" . $name . ":type=SHAR"] = "Connection Updates";
}

// Session-global the column options (timelines, lists etc.)
$_SESSION['columnOptions'] = $columnOptions;


// Get user column setup
$query = "SELECT columns FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "';";
$result = mysql_query($query);
$row = mysql_fetch_assoc($result);
$columns = explode(";",$row['columns']);

$numColumns = count($columns);

// Sets up the refreshAll() function to load the columns stored in the database.
// This is called jQuery-style when the DOM is ready.  Because we need to
// populate it with values from the DB, this can't sit in javascript.js.
$content .= '<script language="javascript">
var allColumns = new Array();
';
for ($i=0; $i<$numColumns; $i++) {
$content .= 'allColumns[' . $i . '] = "column.php?div=' . $i . '&column=' . urlencode($columns[$i]) . '";
';
}
$content .= '</script>';


// Build the main display
$content .= '<div id="headerplusstatusform">';
$content .= makeLinksForm();
$content .= generateSendBoxes();
$content .= '<div id="addcolumndiv"><a href="actions.php?newcol=true" class="icon icon10 doactionbutton fullreload" title="New Column"><span>New Column</span></a></div>';
$content .= generateServiceSelectors($posttoservices);
$content .= '</div></div>';
$content .= generateTweetTables($numColumns, $colsperscreen);

mysql_close();

/* Include HTML to display on the page */
include('html.inc');

// Generates the top area - status entry box, "number of chars left" box, and Post button.
function generateSendBoxes() {
	$content = '<div id="statusformdiv">';
    $content .= '<div id="statusform" name="statusform">';
    $content .= '<input type="text" autocomplete="off" name="status" id="status" class="status">';
    $content .= '<a id="submitbutton" class="button right" href="#"><span>Post</span></a><span class="counter">140</span>';
    return $content;
}

function generateServiceSelectors($posttoservices) {
	$content .= '<div id="serviceselectors"><span class="postto">Post to:</span>';
    
    $numSelectors = count($_SESSION['twitters']) + count($_SESSION['facebooks']) + count($_SESSION['linkedins']);
    $counter = 0;
    foreach ($_SESSION['twitters'] as $username => $twitter) {
        $counter++;
        $content .= '<a class="button accountselector';
        if ($numSelectors != 1) {
            if ($counter == 1) {
                $content .= " left";
            } else if ($counter == $numSelectors) {
                $content .= " right";
            } else {
                $content .= " middle";
            }
        }
        if (strpos($posttoservices, ("twitter:" . $username)) !== FALSE) {
            $content .= " pressed";
        }
        $content .= '"><input type="checkbox" class="accountSelector" id="accountSelector' . $counter . '" value="twitter:' . $username . '" ';
        if (strpos($posttoservices, ("twitter:" . $username)) !== FALSE) {
            $content .= "checked ";
        }
        $content .= '/>';
        $content .= '<span><img src="/images/serviceicons/twitter.png" alt="Twitter" title="Twitter" />&nbsp;&nbsp;' . $username . '</span></a>';
    }
    foreach ($_SESSION['facebooks'] as $username => $facebook) {
        $counter++;
        $content .= '<a class="button accountselector';
        if ($numSelectors != 1) {
            if ($counter == 1) {
                $content .= " left";
            } else if ($counter == $numSelectors) {
                $content .= " right";
            } else {
                $content .= " middle";
            }
        }
        if (strpos($posttoservices, ("facebook:" . $username)) !== FALSE) {
            $content .= " pressed";
        }
        $content .= '"><input type="checkbox" class="accountSelector" id="accountSelector' . $counter . '" value="facebook:' . $username . '" ';
        if (strpos($posttoservices, ("facebook:" . $username)) !== FALSE) {
            $content .= "checked ";
        }
        $content .= '/>';
        $content .= '<span><img src="/images/serviceicons/facebook.png" alt="Facebook" title="Facebook" />&nbsp;&nbsp;' . $username . '</span></a>';
    }
	foreach ($_SESSION['linkedins'] as $username => $linkedin) {
        $counter++;
        $content .= '<a class="button accountselector';
        if ($numSelectors != 1) {
            if ($counter == 1) {
                $content .= " left";
            } else if ($counter == $numSelectors) {
                $content .= " right";
            } else {
                $content .= " middle";
            }
        }
        if (strpos($posttoservices, ("linkedin:" . $username)) !== FALSE) {
            $content .= " pressed";
        }
        $content .= '"><input type="checkbox" class="accountSelector" id="accountSelector' . $counter . '" value="linkedin:' . $username . '" ';
        if (strpos($posttoservices, ("linkedin:" . $username)) !== FALSE) {
            $content .= "checked ";
        }
        $content .= '/>';
        $content .= '<span><img src="/images/serviceicons/linkedin.gif" alt="LinkedIn" title="LinkedIn" />&nbsp;&nbsp;' . $username . '</span></a>';
    }
    $content .= '<input type="hidden" name="postToAccounts" id="postToAccounts" value="' . $posttoservices . '"/>';
    
    $content .= '</div>';
	$content .= '</div>';
	return $content;
}

// Generates the three main tables of tweets
function generateTweetTables($numColumns, $colsperscreen) {
	$content = '<div id="mainarea"><table class="bigtable" id="bigtable" border="0" style="min-width:' . ($numColumns*(100/$colsperscreen)) . '%; width:' . ($numColumns*(100/$colsperscreen)) . '%; max-width:' . ($numColumns*(100/$colsperscreen)+1) . '%; "><tr>';
	for ($i=0; $i<$numColumns; $i++) {
	    $content .= '<td width="' . (100/$numColumns) . '%" valign=top>';
	    $content .= '<div class="column" name="column" id="column' . $i . '"><div class="columnheading"><span class="columnbuttons">';
	    $content .= '<a class="icon" style="background:url(/images/ajax-loader.gif) 10px 6px no-repeat;"><span>Refresh Column</span></a></span></span>';
	    $content .= '</div></div>';
	    $content .= '</td>';
	}
	$content .= '</tr></table>';
	$content .= '</div>';
	return $content;
}

// Generates the top-right config area
function makeLinksForm() {
    
	$content = '<div class="links">';
	$content .= '<a href="manageappearance.php" class="icon left icon98 popup" title="Appearance"><span>Appearance</span></a>';
	$content .= '<a href="manageaccounts.php" class="icon middle icon112 popup" title="Accounts"><span>Accounts</span></a>';
	$content .= '<a href="manageblocks.php" class="icon middle icon146 popup" title="Banned Phrases"><span>Banned Phrases</span></a>';
	$content .= '<a href="clearsessions.php" class="icon right icon63" title="Log Out"><span>Log Out</span></a></div>';
	
	return $content;
}

?>
