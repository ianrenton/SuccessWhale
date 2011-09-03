<?php
session_start();

$content .= '<div class="settingsheader">Remove SuccessWhale Account</div>';    
    
if (isset($_GET['fail'])) {
    $content .= '<div class="error">Sorry, the password you entered was incorrect.</div>';
}

$content .= '<div class="settingscontent">';

$content .= '<strong>Warning:</strong>This will remove your SuccessWhale account and all saved preferences.<br/>You will no longer be able to use the alternative login to access the app.<br/><br/>Please enter your SuccessWhale password below to confirm that you really want<br/>to do this.<br/><br/>';
$content .= '<form name="unregisterform" method="post" action="unregistercallback.php">
        <table border="0" align="center" cellpadding="5" cellspacing="5">
        <tr>
        <td><p style="margin:0; padding:0;">Password</p></td>
        <td><input name="password" type="password" id="password" style="width:200px"></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td><input type="submit" name="Submit" value="Remove Account"></td>
        </tr>
        </table>
        </form>';

$content .= '</div>';

echo($content);

?>
