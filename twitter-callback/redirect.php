<?php

require_once('../common.php');
session_start();

// Create TwitterOAuth object and get request token
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
$request_token = $connection->getRequestToken(OAUTH_CALLBACK);

// Save request token to session
$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

// If last connection fails don't display authorization link
switch ($connection->http_code) {
  case 200:
    // Build authorize URL
    $url = $connection->getAuthorizeURL($token,"");
    header('Location: ' . $url); 
    break;
  default:
    echo 'Could not connect to Twitter. (Error code ' . $connection->http_code . ') Refresh the page or try again later.';
    break;
}
