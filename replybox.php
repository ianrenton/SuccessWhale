<?php

require_once('common.php');
session_start();

$content .= '<div id="replybox">';
$content .= '<input type="text" autocomplete="off" class="reply" id="reply" name="reply" value="' . $_GET['initialtext'] . ' "/>';
$content .= '<input type="hidden" class="replyid" value="' . $_GET['replyid'] . '" />';
$content .= '<input type="hidden" class="account" value="' . $_GET['account'] . '" />';
$content .= '<a id="submitreplybutton" class="button right submitreplybutton">Post</a>';
$content .= ' <span class="replycounter">' . strlen($_GET['initialtext']) . ' </span>';
$content .= '</div>';

echo $content;

?>
