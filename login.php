<?php

$content .= '<div id="header">';
$content .= '<a href="index.php"><img src="images/logo.png" alt="SuccessWhale"/></a></div>';
$content .= '<p align="center" style="margin-top:100px;"><strong>Retrieve Cached Authentication Token</strong></p>';
$content .= '<div style="width: 60%; margin:50px auto 0px auto;"><p>SuccessWhale allows users to cache their Twitter authentication token, and protect it with a password.  This is useful for users that wish to use SuccessWhale in places where <code>twitter.com</code> is blocked.  This form allows users that are having their authentication token cached to log in and retrieve that token, bypassing the need to visit twitter.com to authenticate.</p><p>If you would like to use this feature, you must first log in using the normal method from a PC where <code>twitter.com</code> is available.  Once logged in, click the "Cache Auth Token" link at the top-right, and set a password.  You will then be able to log in using this form from any PC.</p><p>If, after logging in using this form, you are directed back to the "Log in with Twitter" page, the token we have cached has expired, and you will have to log in again using the normal method to update your cached token.</p></div>';
$content .= '<form name="loginform" method="post" action="logincallback.php">
        <table border="0" align="center" cellpadding="5" cellspacing="5" style="margin:50px auto 0px auto;">
        <tr>
        <td><p style="margin:0; padding:0;">Twitter Username</p></td>
        <td><input name="username" type="text" id="username" style="width:200px"></td>
        </tr>
        <tr>
        <td><p style="margin:0; padding:0;">SuccessWhale Password</p></td>
        <td><input name="password" type="password" id="password" style="width:200px"></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td><input type="submit" name="Submit" value="Sign In"></td>
        </tr>
        </table>
        </form>';
        
if (isset($_GET['fail'])) {
    $content .= '<p align="center" style="margin-top:50px; color:red;"><strong>Sorry!  Either that user has not opted to cache their token, or the password you entered was incorrect.</strong></p>';
}
 
/* Include HTML to display on the page */
include('html.inc');

?>

