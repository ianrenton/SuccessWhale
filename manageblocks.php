<?php
require_once('common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

// Get blocklist
$query = "SELECT blocklist FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
$result = mysql_query($query);
$blocklist = mysql_result($result,0,"blocklist");

$content .= '<div class="settingsheader">Banned Phrases List</div>';
$content .= '<div class="settingscontent">';
$content .= '<p>Messages in any timeline that contain one of the following phrases will not be displayed.<br/>Enter one phrase per line.</p>';
$content .= '<textarea name="blocklist" id="blocklist" rows="10" columns="80">' . $blocklist .'</textarea><br/>
        <p class="setBannedPhrases"><a class="button" id="setBannedPhrases"><span>Set Banned Phrases</span></a></p>';
$content .= '</div>';

echo($content);

mysql_close();

?>
