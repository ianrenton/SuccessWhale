<?php

require_once('common.php');
session_start();

if ((isset($_POST['username'])) && (isset($_POST['password'])) && (isset($_SESSION['sw_uid']))) {
        mysql_connect(DB_SERVER,DB_USER,DB_PASS);
        @mysql_select_db(DB_NAME) or die( "Unable to select database");

        // Add the username and password to the user's entry
        $query = "UPDATE sw_users SET username='" . mysql_real_escape_string($_POST['username']) . "', password='" . mysql_real_escape_string(md5($_POST['password'] . PASSWORD_SALT)) . "' WHERE sw_uid='" . mysql_real_escape_string($_SESSION['sw_uid']) . "'";
        $result = mysql_query($query);
        mysql_close();
        header('Location: ./index.php');
        die();
              
} else {
    header('Location: ./register.php');
    die();
}
