<?php

require_once('config.php');

$content .= '<div id="header">';
$content .= '<a href="index.php"><img src="images/logo.png" alt="SuccessWhale"/></a></div>';
$content .= '<p align="center" style="margin-top:100px;"><a href="./redirect.php" style="font-family:sans-serif; color:#aaa; text-decoration:none;">To begin, click here:<br/><img src="./images/lighter.png" border="0" alt="Sign in with Twitter" title="Sign in with Twitter" style="margin-top:5px;" /></a></p>';
if (DB_SERVER != '') {
    $content .= '<p align="center" style="margin-top:100px; font-size:90%;"><a href="./login.php">Retrieve Cached Authentication Token</a></p>';
}
$content .= '<p align="center" style="margin-top:50px">SuccessWhale is a free, open, cross-platform, web-based Twitter client.</p>';
$content .= '<p align="center">Find out more at <a href="http://www.onlydreaming.net/software/successwhale">SuccessWhale\'s homepage</a>.</p>';
$content .= '<p align="center">SuccessWhale is <a href="http://www.gnu.org/licenses/gpl.html">Free Software</a>.</p>';
 
/* Include HTML to display on the page */
include('html.inc');

?>
