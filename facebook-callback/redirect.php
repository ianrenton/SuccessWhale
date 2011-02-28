<?php

require_once('common.php');
session_start();

// Create a Facebook instance so that we can get the right login URL.
$facebook = new Facebook(array(
  'appId' => FACEBOOK_APP_ID,
  'secret' => FACEBOOK_SECRET,
  'cookie' => true,
));
$params = array();
$params['req_perms'] = 'status_update,read_stream';
$loginUrl = $facebook->getLoginUrl($params);
 
header('Location: ' . $loginUrl);

?>
