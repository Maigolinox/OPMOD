<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once "../include.php";

printHeader(oc_('Email Username'), 3);

if (isset($_POST['ocaction']) && ($_POST['ocaction'] == "Email Username") && (!empty($_POST['email']))) { 
	// check for valid email
	if (!validEmail($_POST['email'])) {
		print '<p style="text-align: center" class="warn">' . oc_('Email address entered is invalid') . '</p>';
		printFooter();
		exit;
	}
	$q = "SELECT `reviewerid`, `username`, `email` FROM `" . OCC_TABLE_REVIEWER . "` WHERE `email`='" . safeSQLstr(oc_strtolower($_POST['email'])) . "'";
	$r = ocsql_query($q) or err("Error checking email");
	if (($rnum=ocsql_num_rows($r)) == 0) {
		print '<p style="text-align: center" class="warn">' . oc_('Email address entered is invalid') . ' (2)</p>';
	}
	elseif ($rnum > 1) {
		err("multiple accounts with this email address");
	} else {
		$e = ocsql_fetch_array($r);
		//T: %s = conference short name (e.g., CONF2012)
		$msg = "\n" . sprintf(oc_('Your username for accessing the %s OpenConf system is:'), $OC_configAR['OC_confName']) . "\n\n	" . $e['username'] . "\n\n";
		sendEmail($_POST['email'], oc_('Username Recovery'), $msg, $OC_configAR['OC_notifyReviewerEmailUsername']);
		print '<p>' . sprintf(oc_('We have emailed your username.  Once you receive it, you may <a href="%s">sign in here</a>.'), 'signin.php') . '</p>';
		printFooter();
		exit;
	}
}
else {
	print '<p style="text-align: center">' . oc_('Please enter the email you registered with below') . '</p>';
}

print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="ocaction" value="Email Username" />
<table border="0" style="margin: 0 auto">
<tr><td><strong><label for="email">' . oc_('Email') . ':</label></strong></td><td><input size=20 name="email" id="email" value="' . safeHTMLstr(varValue('email', $_POST)) . '"></td></tr>
<tr><th align="center" colspan=2><br><input type="submit" name="submit" class="submit" value="' . oc_('Email Username') . '"></th></tr>
</table>
</form>
<p>
';

printFooter();

?>
