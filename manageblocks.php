<?php
require_once('common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

// Get blocklist
$query = "SELECT * FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
$result = mysql_query($query);
$blocklist = mysql_result($result,0,"blocklist");

$content .= '<div id="header">';
$content .= '<a href="index.php"><img src="images/logo.png" alt="SuccessWhale"/></a></div>';
$content .= '<p align="center" style="margin-top:100px;"><strong>Manage Banned Phrases</strong></p>';        
$content .= '<div style="width: 60%; margin:50px auto 0px auto;"><p>This page allows you to manage your banned phrases.  Tweets in any timeline that contain one of your banned phrases will not be displayed, so if you\'re an old grouch like me who hates seeing people\'s Foursquare tweets, you can choose to ban "4sq.com".</p><p>Your banned phrases list is a set of phrases of any length, separated by semicolons.  For example, to block Foursquare and Justin Bieber, you could block "4sq.com;bieber;Bieber".  (Yep, it\'s case sensitive.)</p><p>If you want to block semicolons, sucks to be you.  Don\'t enter something like semicolon-space-semicolon unless you want to block everything. :)</p></div>';
$content .= '<form name="manageblocksform" method="post" action="manageblockscallback.php">
        <table border="0" align="center" cellpadding="5" cellspacing="5" style="width:60%; margin:50px auto 0px auto;">
        <tr>
        <td ><input name="blocklist" type="input" id="blocklist" style="width:90%" value="' . $blocklist .'" autocomplete=off></td>
        </tr>
        <tr>
        <td><input type="submit" name="Submit" value="Set Blocked Phrases"></td>
        </tr>
        </table>
        </form>';
        
if (isset($_GET['fail'])) {
    $content .= '<p align="center" style="margin-top:50px; color:red;"><strong>Sorry!  SuccessWhale didn\'t like something you entered there.</strong></p>';
}
 
/* Include HTML to display on the page */
include('html.inc');

?>
