<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <head>
    <title>SuccessWhale</title>
	<link rel="shortcut icon" href="favicon.ico">
	<link type="text/css" rel="stylesheet" media="all" href="themes/<?php echo($_COOKIE['theme']) ?>/css/style.css" />
    <link rel="stylesheet" href="themes/<?php echo($_COOKIE['theme']) ?>/css/uniform.css" type="text/css" media="screen" charset="utf-8" />
	<script type="text/javascript" src="javascript/jquery.min.js"></script>
	<script type="text/javascript" src="javascript/jquery.form.js"></script>
	<script type="text/javascript" src="javascript/jquery.blockUI.js"></script>
	<script type="text/javascript" src="javascript/jquery.uniform.min.js"></script>
	
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
