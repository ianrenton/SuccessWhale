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

// DB connection
mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

// Check for conditions that are POSTed to index.php
checkAndSetTheme();
$colsperscreen = checkAndSetColsPerScreen();

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
        $twitters[$name] = $twitter;
    }
    $_SESSION['twitters'] = $twitters;
}
$facebooks = array();
if (FACEBOOK_ENABLED) {
    $query = "SELECT access_token FROM facebook_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "';";
    $result = mysql_query($query) or die (mysql_error());
    while ($row = mysql_fetch_assoc($result)) {
        $facebook = new Facebook(array(
          'appId' => FACEBOOK_APP_ID,
          'secret' => FACEBOOK_SECRET,
          'cookie' => true,
        ));
        try {
         $attachment =  array('access_token' => $row['access_token']);
         $me = $facebook->api('/me', 'GET', $attachment);
         $name = $me['name'];
         $facebooks[$name] = $facebook;
        }
         catch (Exception $e) {
          //$res = mysql_query('DELETE FROM facebook_users WHERE expires=0');
          // We don't have a good session, so let's get one!
          //TODO re-implement once offline support is sorted: header('Location: ./facebook-callback/');
        }
    }
    $_SESSION['facebooks'] = $facebooks;
}

// Build column options list
$columnOptions = array();
foreach ($twitters as $name => $twitter) {
    // Twitter basics
    $columnOptions["twitter:" . $name] = "-- Twitter: @" . $name . " --";
    $columnOptions["twitter:" . $name . ":statuses/home_timeline"] = "Home Timeline";
    $columnOptions["twitter:" . $name . ":statuses/friends_timeline"] = "Friends Only";
    $columnOptions["twitter:" . $name . ":statuses/public_timeline"] = "All Tweets";
    $columnOptions["twitter:" . $name . ":statuses/mentions"] = "Mentions";
    $columnOptions["twitter:" . $name . ":direct_messages"] = "DMs Received";
    $columnOptions["twitter:" . $name . ":direct_messages/sent"] = "DMs Sent";
    // Twitter lists
    $listsFull = $twitter->get($name . '/lists', array());
    $lists = $listsFull["lists"];
    for ($i=0; $i<count($lists); $i++) {
        $columnOptions["twitter:" . $name . ":" . $name . '/lists/' . $lists[$i]["slug"] . '/statuses'] = $lists[$i]["name"];
    }
}
foreach ($facebooks as $name => $facebook) {
    // Facebook basics TODO
    $columnOptions[("facebook:" . $name)] = "-- Facebook: " . $name . " --";
    $columnOptions["facebook:" . $name . ":blah"] = "Friends Status Feed";
}
// Session-global the column options (timelines, lists etc.)
$_SESSION['columnOptions'] = $columnOptions;

/* TODO put this somewhere
$_SESSION['utcOffset'] = $auth['utc_offset'];*/


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
function refreshAll() {';
for ($i=0; $i<$numColumns; $i++) {
$content .= 'changeColumn("' . $i . '","column.php?div=' . $i . '&column=' . urlencode($columns[$i]) . '", 0);';
}
$content .= '}
</script>';


// Build the main display
$content .= '<div id="header">';
$content .= generateAddColumnBox($colsperscreen);
$content .= makeLinksForm((TWITTER_ENABLED && !isset($twitter)), (FACEBOOK_ENABLED && !isset($facebook)));
$content .= '<a href="index.php"><img src="images/logo.png" alt="SuccessWhale"/></a></div>';
if ($friends["error"] == null) {
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

mysql_close();

/* Include HTML to display on the page */
include('html.inc');

// Generates the top area - status entry box, "number of chars left" box, and Post button.
function generateSendBoxes() {
	$content = '<div id="statusformdiv">';
    $content .= '<form id="statusform" name="statusform" method="" action="">';
    $content .= '<input type="text" size="140" autocomplete="off" name="status" id="status" class="status" onKeyDown="countText(this.form.status);" onKeyUp="countText(this.form.status);">';
    $content .= '<input type="hidden" name="replyid" id="replyid" value="" />';
    $content .= '<input type="submit" name="submit" class="submitbutton" value="Post" />';
	$content .= '&nbsp;&nbsp;<span id="chars">This post is 0 characters long</span><br/>';

    foreach ($_SESSION['twitters'] as $username => $twitter) {
        $content .= '<input type="checkbox" class="accountSelector" id="twitter:' . $username . '" value="twitter:' . $username . '" checked />';
        $content .= '<label for="twitter:' . $username . '">twitter:' . $username . '</label>';
    }
    foreach ($_SESSION['facebooks'] as $username => $facebook) {
        $content .= '<input type="checkbox" class="accountSelector" id="facebook:' . $username . '" value="facebook:' . $username . '" checked />';
        $content .= '<label for="facebook:' . $username . '">facebook:' . $username . '</label>';
    }
    $content .= '<input type="hidden" name="postToAccounts" id="postToAccounts" />';
    
    
	// Add Twitter/Facebook/etc accounts.
    $content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="./twitter-callback/redirect.php">+ Twitter</a>&nbsp;&nbsp;&nbsp;';
    $content .= '<a href="./facebook-callback/">+ Facebook</a>';

    $content .= '</form>';
	$content .= '</div>';
	return $content;
}

// Generates the three main tables of tweets
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
	$content = '<div class="links"><form name="colsperscreenselect" method="post" action="index.php">';
	$content .= '<ul><li><input name="colsperscreen" id="colsperscreen" size="1" value="' . $colsperscreen . '" alt="Columns per screen" title="Columns per screen"><input type="submit" style="display:none"></li><li>';
	$content .= '<a href="javascript:doAction(\'actions.php?newcol=true\')"><img src="images/newcolumn.png" title="New Column" alt="New Column"></a>';
	$content .= '</li></ul></form></div>';
	return $content;
}

// Generates the top-right config area
function makeLinksForm() {
    $dir = opendir('./css');
    
	$content = '<div class="links"><form name="themeselect" method="post" action="index.php">';
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
	
	// Cache tokens item
    $query = "SELECT * FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
    $result = mysql_query($query);
    $row = mysql_fetch_assoc($result);
    if (isset($row["password"])) {
        $content .= '<li><a href="./unregister.php">Stop Caching Auth Token</a></li>';
    } else {
        $content .= '<li><a href="./register.php">Cache Auth Token</a></li>';
    }
	
	// Blocklist item
	if (DB_SERVER != '') {
		$content .= '<li><a href="manageblocks.php">Manage Banned Phrases</a></li>';
	}
	
	$content .= '<li><a href="./clearsessions.php">Log Out</a></li></ul></form></div>';
	return $content;
}

    // TODO update this mess, provide an install.php
function createTablesIfFirstInstall() {
    $query = '  CREATE TABLE IF NOT EXISTS `linkcache` (
                  `url` varchar(255) NOT NULL,
                  `replacetext` varchar(20000) NOT NULL,
                  `wholeblock` tinyint(1) NOT NULL default \'0\',
                  PRIMARY KEY  (`url`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
    mysql_query($query);
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
