<?php

require_once('common.php');
session_start();

$content .= '<div class="centredbluebox">';
$content .= '<a href="index.php"><img src="images/logo.png" alt="SuccessWhale"/></a>';
$content .= '</div>';

$content .= '<div class="centredgreybox">';
$content .= 'To begin, sign in with Twitter or Facebook:<br/><a href="./twitter-callback/redirect.php"><img src="./images/lighter.png" border="0" alt="Sign in with Twitter" title="Sign in with Twitter" style="margin-top:15px;" /></a> <a href="./facebook-callback/"><img src="./images/facebookconnect.gif"  alt="Sign in with Facebook" title="Sign in with Facebook"style="margin-bottom:2px;"></a>';
$content .= '</div>';

$content .= '<div class="centredgreybox">';
$content .= 'Alternatively, if you have one, you can sign in using your SuccessWhale account:<br/><br/>';
$content .= '<form name="loginform" method="post" action="logincallback.php">';
$content .= 'Username: <input name="username" type="text" id="username" style="width:100px"> Password: <input name="password" type="password" id="password" style="width:100px"> <input type="submit" name="Submit" value="Sign In">';
$content .= '</form>';

if (isset($_GET['fail'])) {
    $content .= '<p align="center" style="margin-top:10px; color:red;"><strong>Sorry!  Either that user is not known, or the password you entered was incorrect.</strong></p>';
}

$content .= '</div>';

$content .= '<p align="center" style="margin-top:50px">SuccessWhale is a free, open, cross-platform, web-based Twitter client.</p>';
$content .= '<p align="center">Find out more at <a href="http://www.onlydreaming.net/software/successwhale">SuccessWhale\'s homepage</a>.</p>';
$content .= '<p align="center">SuccessWhale is <a href="http://www.gnu.org/licenses/gpl.html">Free Software</a>.</p>';
 
/* Include HTML to display on the page */
include('html.inc');

?>
