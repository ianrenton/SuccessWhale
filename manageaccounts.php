<?php
require_once('common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

// Get blocklist
$query = "SELECT username FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
$result = mysql_query($query);
$username = mysql_result($result,0,"username");

	$content .= '<h4>SuccessWhale</h4>';
	$content .= '<p><a href="register.php">Register</a> <a href="unregister.php">Unregister</a></p>';
	$content .= '<h4>Twitter</h4>';
    $content .= '<a href="./twitter-callback/redirect.php">Add Twitter Account</a>';
	$content .= '<h4>Facebook</h4>';
    $content .= '<a href="./facebook-callback/">Add Facebook Account</a></div>';
 
echo($content);

mysql_close();

?>
