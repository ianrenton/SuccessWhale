<?php

require_once('common.php');
session_start();

$content .= '<div id="replybox">';
$content .= '<textarea autocomplete="off" class="reply">' . $_GET['initialtext'] . '</textarea>';

$content .= '<input type="hidden" class="replyid" value="' . $_GET['replyid'] . '" />';
$content .= '<input type="hidden" class="account" value="' . $_GET['account'] . '" />';
$content .= '<span class="counter" style="left:0px;padding:0 5px;">' . strlen($_GET['initialtext']) . '</span>';
$content .= '<input type="submit" class="replybutton" value="Post" />';
$content .= '</div>';

echo $content;

?>
