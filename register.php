<?php
session_start();

$content .= '<div class="settingsheader">Create SuccessWhale Account</div>'; 
$content .= '<div class="settingscontent">';

$content .= 'Creating a SuccessWhale account will allow you to log in using the username and password<br/>you supply, allowing you to use SuccessWhale from locations that block Twitter<br/>and Facebook.<br/><br/>Enter your desired username and password below to create the account.<br/><br/>';
$content .= '<form name="registerform" method="post" action="registercallback.php">
        <table border="0" align="center" cellpadding="5" cellspacing="5">
        <tr>
        <td><p style="margin:0; padding:0;">Username</p></td>
        <td><input name="username" id="username" value="" style="width:200px"></td>
        </tr>
        <tr>
        <td><p style="margin:0; padding:0;">Password</p></td>
        <td><input name="password" type="password" id="password" style="width:200px"></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td><input type="submit" name="Submit" value="Create Account"></td>
        </tr>
        </table>
        </form>';

$content .= '</div>';

echo($content);

?>
