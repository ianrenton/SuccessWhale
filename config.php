<?php

// SuccessWhale miscellaneous stuff.  Set your own password salt to any random
// string to avoid passwords being hacked by rainbow table.
define("PASSWORD_SALT", "");

// Twitter API stuff.  If you're hosting your own version of SuccessWhale (or
// something based on it), register your app with Twitter.  Once you're done,
// a consumer key and secret will be given to you.
define("TWITTER_ENABLED", false);
define("CONSUMER_KEY", "");
define("CONSUMER_SECRET", "");
define("OAUTH_CALLBACK", "http://www.successwhale.com/twitter-callback/");

// Facebook API stuff.  If you're hosting your own version of SuccessWhale (or
// something based on it), register your app with Facebook.  Once you're done,
// an app ID and secret will be given to you.
define("FACEBOOK_ENABLED", false);
define("FACEBOOK_APP_ID", "");
define("FACEBOOK_SECRET", "");

// MySQL Database Stuff.
define("DB_SERVER", "");
define("DB_NAME", "");
define("DB_USER", "");
define("DB_PASS", "");

// Google Analytics stuff.
define("ANALYTICS_ENABLED", false);
define("ANALYTICS_ID", "");

// Debug mode.  Turns on a bunch of diagnostic print-outs in case you are having
// trouble getting authentication etc. to work.
define("DEBUG", false);

?>
