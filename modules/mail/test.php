<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

beginChairSession();

print '<!DOCTYPE html>
<html lang="en">
<head>
<title>Send Test Message</title>
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../openconf.css" />
</head>
<body>
<div class="mainbody">
';

if (isset($_POST['submit']) && ($_POST['submit'] == 'Send Message') && isset($_POST['email']) && validEmail($_POST['email'])) {
	if (!validToken('chair')) {
		print '<p class="err">invalid submission</p>';
	} else {
		$GLOBALS['mod_mail_debug'] = 3;
		if (oc_mail($_POST['email'], 'Test Message', 'This is a test message.')) {
			print '<p class="note2">Message sent successfully</p>';
		} else {
			print '<p class="warn">Message failed</p>';
		}
	}
	print '<hr />';
}

print '
<form method="post" action="' . OCC_SELF . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<p>Enter an email address to send a test message using the ' . safeHTMLstr($OC_configAR['MOD_MAIL_mailer']) . ' mailer to:</p>
<p><input type="email" size="50" name="email" placeholder="email address" autofocus value="' . safeHTMLstr(varValue('email', $_POST)) . '" /></p>
<p><input type="submit" name="submit" class="submit" value="Send Message" /></p>
</form>
</div>
</body>
</html>
';

?>
