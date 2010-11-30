<?php
session_start();

$content .= '<div id="header">';
$content .= '<a href="index.php"><img src="images/logo.png" alt="SuccessWhale"/></a></div>';
$content .= '<p align="center" style="margin-top:100px;"><strong>Stop Caching Authentication Token</strong></p>';        
$content .= '<div style="width: 60%; margin:50px auto 0px auto;"><p>Please enter your SuccessWhale password below to confirm that you want to stop caching your Twitter authentication token.  Your token and password will be removed from SuccessWhale\'s database, and you will be returned to the main SuccessWhale page.  Once you successfully complete this form, you will no longer be able to log in from computers that block <code>twitter.com</code>.</p><p>Your authentication token will remain active and stored as a cookie on this PC unless you check the "Log me out too!" box.</p></div>';
$content .= '<form name="unregisterform" method="post" action="unregistercallback.php">
        <table border="0" align="center" cellpadding="5" cellspacing="5" style="margin:50px auto 0px auto;">
        <tr>
        <td><p style="margin:0; padding:0;">Twitter Username</p></td>
        <td><input name="username" type="hidden" id="username" value="' . $_SESSION['thisUser'] .'"><p style="margin:0; padding:0;">' . $_SESSION['thisUser'] . '</p></td>
        </tr>
        <tr>
        <td><p style="margin:0; padding:0;">SuccessWhale Password</p></td>
        <td><input name="password" type="password" id="password" style="width:200px"></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td><p style="margin:0; padding:0;"><input type="checkbox" name="logout" value="true"> Log me out too!</p></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td><input type="submit" name="Submit" value="Stop Caching my Token"></td>
        </tr>
        </table>
        </form>';
        
if (isset($_GET['fail'])) {
    $content .= '<p align="center" style="margin-top:50px; color:red;"><strong>Sorry!  Either you have not opted to cache your token and are here by accident, or the password you entered was incorrect.</strong></p>';
}
 
/* Include HTML to display on the page */
include('html.inc');

?>
