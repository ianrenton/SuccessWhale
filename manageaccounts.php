<?php
require_once('common.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

// Get data
$query = "SELECT username FROM sw_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
$result = mysql_query($query);
$hasSWAccount = (mysql_num_rows($result) == 1);
$username = mysql_result($result,0,"username");

$twitters = array();
if (TWITTER_ENABLED) {
    $query = "SELECT id, access_token FROM twitter_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "';";
    $result = mysql_query($query) or die (mysql_error());
    while ($row = mysql_fetch_assoc($result)) {
        $twitter_access_token = unserialize($row['access_token']);
        $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $twitter_access_token['oauth_token'], $twitter_access_token['oauth_token_secret']);
        $auth = $twitter->get('account/verify_credentials', array());
        $twitters[$row['id']] = $auth['screen_name'];
    }
}
$facebooks = array();
if (FACEBOOK_ENABLED) {
    $query = "SELECT id, session FROM facebook_users WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "';";
    $result = mysql_query($query) or die (mysql_error());
    while ($row = mysql_fetch_assoc($result)) {
        $facebook = new Facebook(array(
          'appId' => FACEBOOK_APP_ID,
          'secret' => FACEBOOK_SECRET,
          'cookie' => true,
        ));
        try {
	         $facebook->setSession(unserialize($row['session']));
             $me = $facebook->api('/me', 'GET');
             $facebooks[$row['id']] = $me['name'];
         }
         catch (Exception $e) {
        }
    }
}
$linkedins = array(); //TODO add db stuff

$content .= '<div class="settingsheader">Accounts</div>';
$content .= '<div class="settingscontent">';
$content .= '<h3>SuccessWhale</h3>';
if ($hasSWAccount) {
	$content .= '<div class="account">' . $username . '<div class="manageaccountbuttons"><a href="unregister.php" class="button fancybox"><span>Remove</span></a></div></div>';
} else {
	$content .= '<div class="account"><span class="noaccount">None yet</span><div class="manageaccountbuttons"><a href="register.php" class="button fancybox"><span>Create</span></div></div>';
}
if (TWITTER_ENABLED) {
	$content .= '<h3>Twitter</h3>';
	foreach ($twitters as $id => $name) {
		$content .= '<div class="account">@' . $name . '<div class="manageaccountbuttons"><a href="unregistercallback.php?service=twitter&id=' . $id . '" class="button"><span>Remove</span></a></div></div>';
	}
	$content .= '<div class="account"><span class="noaccount">&nbsp;</span><div class="manageaccountbuttons"><a href="./twitter-callback/redirect.php" class="button"><span>Add Twitter Account</span></a></div></div>';
}
if (FACEBOOK_ENABLED) {
	$content .= '<h3>Facebook</h3>';
	foreach ($facebooks as $id => $name) {
		$content .= '<div class="account">' . $name . '<div class="manageaccountbuttons"><a href="unregistercallback.php?service=facebook&id=' . $id . '" class="button"><span>Remove</span></a></div></div>';
	}
	$content .= '<div class="account"><span class="noaccount">&nbsp;</span><div class="manageaccountbuttons"><a href="./facebook-callback/" class="button"><span>Add Facebook Account</span></a></div></div>';
}
if (LINKEDIN_ENABLED) {
	$content .= '<h3>LinkedIn</h3>';
	foreach ($linkedins as $id => $name) {
		$content .= '<div class="account">' . $name . '<div class="manageaccountbuttons"><a href="unregistercallback.php?service=linkedin&id=' . $id . '" class="button"><span>Remove</span></a></div></div>';
	}
	$content .= '<div class="account"><span class="noaccount">&nbsp;</span><div class="manageaccountbuttons"><a href="./linkedin-callback/" class="button"><span>Add LinkedIn Account</span></a></div></div>';
}
$content .= '</div>';

echo($content);

mysql_close();

?>
