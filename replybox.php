<?php

require_once('common.php');
session_start();

$content .= '<div id="replybox">';
$content .= '<input type="text" autocomplete="off" class="reply" value="' . $_GET['initialtext'] . ' ">';

$content .= '<input type="hidden" class="replyid" value="' . $_GET['replyid'] . '" />';
$content .= '<input type="hidden" class="account" value="' . $_GET['account'] . '" />';
$content .= '<input type="submit" class="replybutton" value="Post" />';
$content .= '<span class="counter">' . strlen($_GET['initialtext']) . '</span>';
$content .= '</div>';

echo $content;

?>
