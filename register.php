<?php
session_start();

$content .= '<div id="header">';
$content .= '<a href="index.php"><img src="images/logo.png" alt="SuccessWhale"/></a></div>';
$content .= '<p align="center" style="margin-top:100px;"><strong>Cache Authentication Token</strong></p>';
$content .= '<div style="width: 60%; margin:50px auto 0px auto;"><p>SuccessWhale allows users to cache their Twitter authentication token, and protect it with a password.  This is useful for users that wish to use SuccessWhale in places where <code>twitter.com</code> is blocked.  This form allows users to enable this service.</p><p>Please enter a password below to start caching your token.  Once you successfully return to the main SuccessWhale page, your authentication token is cached and you can now log in to SuccessWhale from any other PC by clicking "Retrieve Cached Authentication Token" and entering the password you enter below.</p><p>We never store your password as plaintext and you can stop caching (thus deleting your password) at any time.  But if you\'re paranoid like me, you might want to use something other than your real Twitter password here.</p></div>';
$content .= '<form name="registerform" method="post" action="registercallback.php">
        <table border="0" align="center" cellpadding="5" cellspacing="5" style="margin:50px auto 0px auto;">
        <tr>
        <td><p style="margin:0; padding:0;">Twitter Username</p></td>
        <td><input name="username" type="hidden" id="username" value="' . $_SESSION['thisUser'] .'"><p style="margin:0; padding:0;">' . $_SESSION['thisUser'] . '</p></td>
        </tr>
        <tr>
        <td><p style="margin:0; padding:0;">New SuccessWhale Password</p></td>
        <td><input name="password" type="password" id="password" style="width:200px"></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td><input type="submit" name="Submit" value="Cache my Token, baby!"></td>
        </tr>
        </table>
        </form>';
 
/* Include HTML to display on the page */
include('html.inc');

?>
