<?php
require_once('common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

// Get blocklist
$query = "SELECT username FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
$result = mysql_query($query);
$hasSWAccount = (mysql_num_rows($result) == 1);
$username = mysql_result($result,0,"username");

$content .= '<div class="settingsheader">Accounts</div>';
$content .= '<div class="settingscontent">';
$content .= '<h4>SuccessWhale</h4>';
if ($hasSWAccount) {
	$content .= '<p>' . $username . '<br/><a href="unregister.php">Remove Account</a></p>';
} else {
	$content .= '<p><a href="register.php">Register</a></p>';
}
$content .= '<h4>Twitter</h4>';
$content .= '<a href="./twitter-callback/redirect.php">Add Twitter Account</a>';
$content .= '<h4>Facebook</h4>';
$content .= '<a href="./facebook-callback/">Add Facebook Account</a></div>';
$content .= '</div>';

echo($content);

mysql_close();

?>
