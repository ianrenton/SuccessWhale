<?php

require_once('config.php');
session_start();

// DB connection
mysql_connect(DB_SERVER,DB_USER,DB_PASS);
@mysql_select_db(DB_NAME) or die( "Unable to select database");

$query = file_get_contents('tablesetup.sql');
mysql_query($query);

mysql_close();
header('Location: ./index.php');
die();

?>
