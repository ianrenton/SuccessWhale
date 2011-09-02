<?php
require_once('common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

// Get data
$query = "SELECT username FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
$result = mysql_query($query);
$hasSWAccount = (mysql_num_rows($result) == 1);
$username = mysql_result($result,0,"username");

$twitters = $_SESSION['twitters'];
$facebooks = $_SESSION['facebooks'];

$content .= '<div class="settingsheader">Accounts</div>';
$content .= '<div class="settingscontent">';
$content .= '<h3>SuccessWhale</h3>';
if ($hasSWAccount) {
	$content .= '<div class="account">' . $username . '<div class="manageaccountbuttons"><a href="unregister.php" class="button"><span>Remove</span></a></div></div>';
} else {
	$content .= '<div class="account"><span class="noaccount">None yet</span><div class="manageaccountbuttons"><a href="register.php" class="button"><span>Register</span></div></div>';
}
$content .= '<h3>Twitter</h3>';
foreach ($twitters as $name => $object) {
	$content .= '<div class="account">@' . $name . '<div class="manageaccountbuttons"><a class="button"><span>Remove</span></a></div></div>';
}
$content .= '<div class="account"><span class="noaccount">&nbsp;</span><div class="manageaccountbuttons"><a href="./twitter-callback/redirect.php" class="button"><span>Add Twitter Account</span></a></div></div>';
$content .= '<h3>Facebook</h3>';
foreach ($facebooks as $name => $object) {
	$content .= '<div class="account">' . $name . '<div class="manageaccountbuttons"><a class="button"><span>Remove</span></a></div></div>';
}
$content .= '<div class="account"><span class="noaccount">&nbsp;</span><div class="manageaccountbuttons"><a href="./facebook-callback/" class="button"><span>Add Facebook Account</span></a></div></div>';

$content .= '</div>';

echo($content);

mysql_close();

?>
