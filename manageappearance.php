<?php
require_once('common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

// Get blocklist
$query = "SELECT colsperscreen, theme FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
$result = mysql_query($query);
$colsperscreen = mysql_result($result,0,"colsperscreen");
$theme = mysql_result($result,0,"theme");
    
$content .= '<div class="settingsheader">Appearance</div>';
$content .= '<div class="settingscontent">';
$content .= '<p>Theme: <select id="theme">';

$dir = opendir('./themes');
while($file = readdir($dir)) {
    if($file != '.' && $file != '..') {
        $content .= '<option value="' . $file . '"';
		if ($file == $theme) {
			$content .= ' selected="selected"';
		}
		$content .= '>' . $file . '</option>\n';
    }
}
closedir($dir);
$content .= '</select></p>';
$content .= '<p>Columns per Screen: <input name="colsperscreen" id="colsperscreen" size="1" value="' . $colsperscreen . '"></p>';
$content .= '</div>';

echo($content);

mysql_close();

?>
