<?php

require_once('../common.php');
session_start();

if (TWITTER_ENABLED) {
    // If the oauth_token is old log out and try again.  This probably never happens.
    if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
      $_SESSION['oauth_status'] = 'oldtoken';
      header('Location: ../clearsessions.php');
    }

    // Create TwitterOAuth object with app key/secret and token key/secret from default phase
    $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

    // Request access tokens from twitter
    $access_token = $twitter->getAccessToken($_REQUEST['oauth_verifier']);
    
    // Save the access token as a cookie and in the database
    setcookie('access_token', serialize($access_token), mktime()+86400*365);
    if (isset($_SESSION['thisUser'])) {
        $query = "SELECT * FROM users WHERE username='" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
        $result = mysql_query($query);
        if (mysql_num_rows($result) > 0) {
            $query = "UPDATE users SET auth_token = '" . mysql_real_escape_string(serialize($access_token)) . "' WHERE username = '" . mysql_real_escape_string($_SESSION['thisUser']) . "'";
            mysql_query($query);
        }
    }

    // ENTRY POINT 2a: User has just connected via the Twitter callback.
    // Store the twitter object.
    $_SESSION['twitter'] = $twitter;
    $_SESSION['twitter_access_token'] = $access_token;

    // Remove no longer needed request tokens
    unset($_SESSION['oauth_token']);
    unset($_SESSION['oauth_token_secret']);

    // If HTTP response is 200 continue otherwise send to connect page to retry
    if (200 == $twitter->http_code) {
      // The user has been verified and the access tokens can be saved for future use
      $_SESSION['status'] = 'verified';
      header('Location: ../index.php');
    } else {
      header('Location: ../clearsessions.php');
    }
} else {
    // Twitter is disabled
    if (DEBUG) {
        echo("Attempted to use a Twitter callback when Twitter integration is disabled.");
    } else {
        header('Location: ../index.php');
    }
}

?>
