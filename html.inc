<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <head>
    <title>SuccessWhale</title>
	<link rel="shortcut icon" href="favicon.ico">
	<link type="text/css" rel="stylesheet" media="all" href="style/style.css" />
	<link type="text/css" rel="stylesheet" media="all" href="themes/<?php echo($theme) ?>/style.css" />
	<link rel="stylesheet" type="text/css" href="fancybox/jquery.fancybox-1.3.4.css" media="screen" />
	<script src="https://www.google.com/jsapi?key=ABQIAAAA4f8pRNBT_o7wZoAgoU2cgBSc8NznX29xu-21-GP_yo_9Tviv2RTZ7JdpGqWnaYID1MR2-Pi2rpQkWg" type="text/javascript"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="javascript/jquery.form.js"></script>
	<script type="text/javascript" src="javascript/jquery.blockUI.js"></script>
	<script type="text/javascript" src="javascript/jquery.breakly.js"></script>
	<script type="text/javascript" src="javascript/jquery.impromptu.js"></script>
	<script type="text/javascript" src="javascript/jquery.putCursorAtEnd.js"></script>
	<script type="text/javascript" src="fancybox/jquery.fancybox-1.3.4.pack.js"></script>
	
	<script type="text/javascript" src="javascript/javascript.js"></script>

  </head>
  <body>
	<div id="page">
	<?php echo($content); ?>
	</div>
	
    <SCRIPT LANGUAGE="JavaScript">
    // Trigger init
    window.onload=init;
    </SCRIPT>

<?php if (ANALYTICS_ENABLED) { ?>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("<?php echo(ANALYTICS_ID); ?>");
pageTracker._trackPageview();
} catch(err) {}</script>

<?php } ?>

</body></html>
