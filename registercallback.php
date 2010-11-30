<?php

/* Load required lib files. */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

if ((isset($_POST['username'])) && (isset($_POST['password']))) {
    if (DB_SERVER != '') {
        mysql_connect(DB_SERVER,DB_USER,DB_PASS);
        @mysql_select_db(DB_NAME) or die( "Unable to select database");

        // Add the user to the table
        $query = "SELECT * FROM users WHERE username='" . mysql_real_escape_string($_POST['username']) . "'";
        $result = mysql_query($query);
        if (!mysql_num_rows($result) ) {
            $query = "INSERT INTO users VALUES ('" . mysql_real_escape_string($_POST['username']) . "','" . mysql_real_escape_string(md5($_POST['password'])) . "','')";
            mysql_query($query);
            header('Location: ./index.php');
            die();
        } else {
            $content .= '<div id="header">';
            $content .= '<img src="images/logo.png" alt="SuccessWhale"/></div>';
            $content .= '<p align="center" style="margin-top:100px;"><strong>Cache Authentication Token</strong></p>';
            $content .= '<p align="center" style="margin-top:50px;">This user is already having their authentication token cached.</p>';
            /* Include HTML to display on the page */
            include('html.inc');
        }
        mysql_close();
    }
} else {
    header('Location: ./register.php');
    die();
}
