<?php

date_default_timezone_set('UTC');

// Load required lib files.
require_once('twitteroauth/twitteroauth.php');
require_once('facebook-php-sdk/src/facebook.php');
require_once('renderfunctions.php');
require_once('config.php');
session_start();

mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

// Logs in the given user
function logInUser($sw_uid) {
    $_SESSION['sw_uid'] = $sw_uid;
    $query = "SELECT secret FROM sw_users WHERE sw_uid='" . $_SESSION['sw_uid'] . "';";
    $result = mysql_query($query) or die(mysql_error());
    $row = mysql_fetch_assoc($result);
    setcookie('sw_uid', $_SESSION['sw_uid'], mktime()+86400*365);
    setcookie('secret', $row['secret'], mktime()+86400*365);
}

// Adds a new user to the SuccessWhale users table, and returns their ID.
function addSWUser() {
    $secret = createSecret();
    $query = "INSERT INTO sw_users (secret, columns) VALUES ('" . $secret . "', 'New Column;New Column;New Column');";
    $result = mysql_query($query) or die(mysql_error());
    return mysql_insert_id();
}

// Creates a secret to save in the database and in a cookie, for auto-logging-in
// users.
function createSecret() {
    $length = 100;
    $characters = '0123456789abcdef';
    $string = '';    
    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, strlen($characters))];
    }
    return $string;
}

mysql_close();

?>
