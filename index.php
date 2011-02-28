<?php
/* SuccessWhale, by Ian Renton
 * A lightweight, standards-compliant Twitter client written in PHP
 * and JavaScript.
 * Based on Abraham's Twitter OAuth PHP example: http://twitter.abrah.am/
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

// Check for conditions that are POSTed to index.php
checkAndSetTheme();
$colsperscreen = checkAndSetColsPerScreen();

// Create database tables if they don't exist.
if (DB_ENABLED) {
    mysql_connect(DB_SERVER,DB_USER,DB_PASS);
    @mysql_select_db(DB_NAME) or die( "Unable to select database");
    createTablesIfFirstInstall();
    mysql_close();
}

// ENTRY POINT 1: User has a cookie, visits index.php directly.
// Bring in access token from cookie if it exists, and create and save a 
// connection
if (TWITTER_ENABLED && (!isset($twitter)) && (!empty($_COOKIE['access_token']))) {
	$access_token = unserialize($_COOKIE['access_token']);
    $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
    $_SESSION['twitter'] = $twitter;
}
if (FACEBOOK_ENABLED && (!isset($facebook)) && (!empty($_COOKIE['facebook']))) {
	$facebook = $_COOKIE['facebook'];
    $_SESSION['facebook'] = $facebook;
}

// If no connectivity-providing objects exist, clear out sessions drop back to the front page.
// This gets hit when a user visits index.php for the first time without logging in or having
// any cookies.
// We need at least one authenticated service, represented by one of these objects,
// in order to carry on.
if ((!isset($twitter)) && (!isset($facebook))) {
    header('Location: ./clearsessions.php');
}

// Session-global the stuff that doesn't depend on which column we're rendering
$auth = $twitter->get('account/verify_credentials', array());
$_SESSION['thisUser'] = $auth['screen_name'];
$_SESSION['utcOffset'] = $auth['utc_offset'];

// Base column options, e.g. home timeline, mentions
$columnOptions = array("statuses/home_timeline" => "Home Timeline",
 "statuses/friends_timeline" => "Friends Only",
 "statuses/public_timeline" => "All Tweets",
 "statuses/mentions" => "Mentions",
 "direct_messages" => "DMs Received",
 "direct_messages/sent" => "DMs Sent");

// Add lists to column options
$listsFull = $twitter->get($auth['screen_name'] . '/lists', array());
$lists = $listsFull["lists"];
for ($i=0; $i<count($lists); $i++) {
    $columnOptions[$auth['screen_name'] . '/lists/' . $lists[$i]["slug"] . '/statuses'] = $lists[$i]["name"];
}

// Session-global the column options (timelines, lists etc.)
$_SESSION['columnOptions'] = $columnOptions;


// Load/setup user prefs in database
if (DB_ENABLED) {
    mysql_connect(DB_SERVER,DB_USER,DB_PASS);
    @mysql_select_db(DB_NAME) or die( "Unable to select database");

    // If user is a first-time visitor, add a row for them.
    $query = "SELECT * FROM userprefs WHERE username='" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
    $result = mysql_query($query);
    if (!mysql_num_rows($result) ) {
        // TODO finish transition to n-columns by removing two of 3 blanks
        $query = "INSERT INTO userprefs VALUES ('" . mysql_real_escape_string($_SESSION['thisUser']) . "','statuses/home_timeline;statuses/mentions;direct_messages','', '','')";
        mysql_query($query);
    }

    // If user is in the users table, update their access tokens
    $query = "SELECT * FROM users WHERE username='" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
    $result = mysql_query($query);
    if (mysql_num_rows($result) > 0) {
        $query = "UPDATE users SET auth_token = '" . mysql_real_escape_string(serialize($access_token)) . "' WHERE username = '" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
        mysql_query($query);
        if (isset($facebook)) {
            $query = "UPDATE users SET fb_session = '" . mysql_real_escape_string(serialize($facebook)) . "' WHERE username = '" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
            mysql_query($query);
        }
    }

    // Get user column setup
    $query = "SELECT * FROM userprefs WHERE username='" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
    $result = mysql_query($query);
    $userprefs = mysql_fetch_assoc($result);
    // TODO finish transition to n-columns by renaming column1 to column
    $columns = explode(";",$userprefs["column1"]);

    mysql_close();
} else {
    $columns=array("statuses/home_timeline","statuses/mentions","direct_messages");
}
$numColumns = count($columns);

// Sets up the refreshAll() function to load the columns stored in the database.
// This is called jQuery-style when the DOM is ready.  Because we need to
// populate it with values from the DB, this can't sit in javascript.js.
$content .= '<script language="javascript">
function refreshAll() {';
for ($i=0; $i<$numColumns; $i++) {
$content .= 'changeColumn("' . $i . '","column.php?div=' . $i . '&column=' . urlencode($columns[$i]) . '", 0);';
}
$content .= '}
</script>';


// Build the main display
$content .= '<div id="header">';
$content .= makeLinksForm((TWITTER_ENABLED && !isset($twitter)), (FACEBOOK_ENABLED && !isset($facebook)));
$content .= '<a href="index.php"><img src="images/logo.png" alt="SuccessWhale"/></a></div>';
if ($friends["error"] == null) {
	$content .= generateAddColumnBox($colsperscreen);
	$content .= generateSendBoxes();
	$content .= generateTweetTables($numColumns, $colsperscreen);
	$content .= '<div id="actionbox"></div>';
} else if ($friends["error"] == '<') {
	// Not sure what it is with the '<' error, but reloading seems to make it go away.
	header('Location: ' . htmlentities($_SERVER['PHP_SELF']) );
	die();
} else if ($friends["error"] == 'Could not authenticate you.') {
	// If we couldn't authenticate, log out and try again.
	header('Location: clearsessions.php' );
	die();
} else {
	$content .= '<p>Twitter reported an error:</p><p>' . $friends["error"] . '</p>';
}

/* Include HTML to display on the page */
include('html.inc');

// Generates the top area - status entry box, "number of chars left" box, and Post button.
function generateSendBoxes() {
	$content = '<div id="statusformdiv">';
    $content .= '<form id="statusform" name="statusform" method="" action="">';
    $content .= '<input type="text" size="140" autocomplete="off" name="status" id="status" class="status" onKeyDown="countText(this.form.status);" onKeyUp="countText(this.form.status);">';
	$content .= '&nbsp;&nbsp;<b id="charsLeft">140</b>&nbsp;&nbsp;';
    $content .= '<input type="hidden" name="replyid" id="replyid" value="" />';
    $content .= '<input type="submit" name="submit" class="submitbutton" value="Post" />';
    if (!empty($_SESSION['facebook'])) {
        $content .= '<input type="checkbox" name="postToFacebook" id="postToFacebook" value="true"><label id="postToFacebookLabel" name="postToFacebookLabel" for="postToFacebook"> Facebook too</label>';
    }
    $content .= '</form>';
	$content .= '</div>';
	return $content;
}

// Generates the three main tables of tweets
// TODO sort out scrolling vs squishing
function generateTweetTables($numColumns, $colsperscreen) {
	$content = '<div id="mainarea"><table class="bigtable" id="bigtable" border="0" style="min-width:' . ($numColumns*(100/$colsperscreen)) . '%; width:' . ($numColumns*(100/$colsperscreen)) . '%; max-width:' . ($numColumns*(100/$colsperscreen)+1) . '%; "><tr>';
	for ($i=0; $i<$numColumns; $i++) {
	    $content .= '<td width="' . (100/$numColumns) . '%" valign=top>';
	    $content .= '<div class="column" name="column" id="column' . $i . '"><h2><img src="images/ajax-loader.gif" alt="Loading..."/></h2></div>';
	    $content .= '</td>';
	}
	$content .= '</tr></table>';
	$content .= '</div>';
	return $content;
}

// Generates the bottom box with the Add Column button
function generateAddColumnBox($colsperscreen) {
	$content = '<div id="addcolumndiv"><form name="colsperscreenselect" method="post" action="index.php">';
	$content .= '<ul><li><input name="colsperscreen" id="colsperscreen" size="1" value="' . $colsperscreen . '" alt="Columns per screen" title="Columns per screen"><input type="submit" style="display:none"></li><li>';
	$content .= '<a href="javascript:doAction(\'actions.php?newcol=true\')"><img src="images/newcolumn.png" title="New Column" alt="New Column"></a>';
	$content .= '</li></ul></form></div>';
	return $content;
}

// Generates the top-right config area
function makeLinksForm($showTwitterLink, $showFacebookLink) {
    $dir = opendir('./css');
    
	$content = '<div id="links"><form name="themeselect" method="post" action="index.php">';
	$content .= '<ul><li>Theme: <select name="theme" onchange="this.form.submit()">';
	
    while($file = readdir($dir)) {
        if($file != '.' && $file != '..') {
            if (preg_match("/\w*\.css/", $file, $matches)) {
                $content .= '<option value="' . $matches[0] . '"';
				if ($matches[0] == $_COOKIE["theme"]) {
					$content .= ' selected="selected"';
				}
				$content .= '>' . substr($matches[0], 0, -4) . '</option>\n';
            }
        }
    }
    closedir($dir);
	$content .= '</select></li>';
	
	// Add Twitter/Facebook/etc accounts to SuccessWhale.  Only shows ones you're
	// missing.
	if ($showTwitterLink) {
	    $content .= '<li><a href="./twitter-callback/redirect.php">Add Twitter</a></li>';
	}
	if ($showFacebookLink) {
	    $content .= '<li><a href="./facebook-callback/redirect.php">Add Facebook</a></li>';
	}

    
	
	// Cache tokens item
	if (DB_SERVER != '') {
	    mysql_connect(DB_SERVER,DB_USER,DB_PASS);
        @mysql_select_db(DB_NAME) or die( "Unable to select database");
	    $query = "SELECT * FROM users WHERE username='" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
        $result = mysql_query($query);
        if (mysql_num_rows($result) > 0) {
            $content .= '<li><a href="./unregister.php">Stop Caching Auth Token</a></li>';
        } else {
            $content .= '<li><a href="./register.php">Cache Auth Token</a></li>';
        }
        mysql_close();
	}
	
	// Blocklist item
	if (DB_SERVER != '') {
		$content .= '<li><a href="manageblocks.php">Manage Banned Phrases</a></li>';
	}
	
	$content .= '<li><a href="./clearsessions.php">Log Out</a></li></ul></form></div>';
	return $content;
}

function createTablesIfFirstInstall() {
    $query = '  CREATE TABLE IF NOT EXISTS `linkcache` (
                  `url` varchar(255) NOT NULL,
                  `replacetext` varchar(20000) NOT NULL,
                  `wholeblock` tinyint(1) NOT NULL default \'0\',
                  PRIMARY KEY  (`url`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
    mysql_query($query);
    // TODO update column1=>column
    $query = '  CREATE TABLE IF NOT EXISTS `userprefs` (
                  `username` varchar(255) NOT NULL,
                  `column1` varchar(255) NOT NULL,
                  `column2` varchar(255) NOT NULL,
                  `column3` varchar(255) NOT NULL,
                  `blocklist` varchar(5000) NOT NULL,
                  PRIMARY KEY  (`username`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
    mysql_query($query);
    $query = '  CREATE TABLE IF NOT EXISTS `users` (
                  `username` varchar(255) NOT NULL,
                  `password` varchar(255) NOT NULL,
                  `auth_token` varchar(255) NOT NULL,
                  PRIMARY KEY  (`username`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
    mysql_query($query);
}


// If a theme has just been selected, set a cookie and reload.
// If no theme has ever been set, set default.css and reload.
// Otherwise do nothing, keep the user's theme.
function checkAndSetTheme() {
    if (isset($_POST['theme'])) {
	    setcookie("theme", $_POST['theme'], time()+60*60*24*365);
	    header('Location: ' . htmlentities($_SERVER['PHP_SELF']) );
	    die();
    } else {
	    if (!isset($_COOKIE['theme'])) {
		    setcookie("theme", "default.css", time()+60*60*24*365);
		    header('Location: ' . htmlentities($_SERVER['PHP_SELF']) );
		    die();
	    }
    }
}

// If number of cols per screen has just been set, set a cookie and reload.
// If no number of cols per screen has ever been set, set to 3 and reload.
// In any eventuality, load the number.
function checkAndSetColsPerScreen() {
    if (isset($_POST['colsperscreen'])) {
	    setcookie("colsperscreen", $_POST['colsperscreen'], time()+60*60*24*365);
	    $colsperscreen = $_POST['colsperscreen'];
    } else {
	    if (!isset($_COOKIE['colsperscreen'])) {
		    setcookie("colsperscreen", "3", time()+60*60*24*365);
		    $colsperscreen = 3;
	    } else {
		    $colsperscreen = $_COOKIE['colsperscreen'];
	    }
    }
    return $colsperscreen;
}

?>
