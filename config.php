<?php

// Twitter API stuff.  If you're hosting your own version of SuccessWhale (or
// something based on it), register your app with Twitter.  Once you're done,
// a consumer key and secret will be given to you.
define("CONSUMER_KEY", "");
define("CONSUMER_SECRET", "");

// Change this to point to your web server
define("OAUTH_CALLBACK", "http://www.successwhale.com/callback.php");

// MySQL Database Stuff.  Leave these blank if you don't want to use a database.
// (with no DB, you won't be able to save user column settings, cache links, or
// cache authentication tokens.)
define("DB_SERVER", "");
define("DB_NAME", "");
define("DB_USER", "");
define("DB_PASS", "");
?>
