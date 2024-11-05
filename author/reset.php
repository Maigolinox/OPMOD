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

printHeader(oc_('Reset Password'), 3);

if (isset($_POST['ocaction']) && ($_POST['ocaction'] == "Reset Password") && preg_match("/^\d+$/",$_POST['pid']) && !empty($_POST['email'])) {
	// check for valid email
	if (!validEmail($_POST['email'])) {
		print '<p style="text-align: center" class="warn">' . oc_('Email address entered is invalid') . '</p>';
		printFooter();
		exit;
	}
	$q = "SELECT `" . OCC_TABLE_AUTHOR . "`.`email` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "` WHERE `" . OCC_TABLE_PAPER . "`.`paperid`='" . safeSQLstr($_POST['pid']) . "' AND `" . OCC_TABLE_AUTHOR . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` AND `" . OCC_TABLE_AUTHOR . "`.`position`=`" . OCC_TABLE_PAPER . "`.`contactid` AND `" . OCC_TABLE_AUTHOR . "`.`email`='" . safeSQLstr(oc_strtolower($_POST['email'])) . "'";
	$r = ocsql_query($q) or err(oc_('Error checking submission ID'));
	if (ocsql_num_rows($r) != 1) { 
		print '<p style="text-align: center" class="warn">' . oc_("Submission ID or contact author's email invalid.") . '  ' . sprintf(oc_('Please contact the <a href="%s">Chair</a>.'), 'contact.php') . '</p>'; 
		printFooter();
		exit;
	}
	else {
		$newpwd = oc_password_generate();
		$q2 = "UPDATE `" . OCC_TABLE_PAPER . "` SET `password`='" . safeSQLstr(oc_password_hash($newpwd)) . "' WHERE `paperid`='" . safeSQLstr($_POST['pid']) . "'";
		$r2 = ocsql_query($q2) or err(oc_('Unable to update password'));
		$msg = "\n" . sprintf(oc_('Per your request, we have issued you a new password for accessing the %s OpenConf system.  The new password is:'), $OC_configAR['OC_confName']) . "\n	" . $newpwd . "\n\n" . oc_('You may change this password by signing in to the OpenConf system and editing your submission.');
		sendEmail($_POST['email'], oc_('Author Password Reset'), $msg, $OC_configAR['OC_notifyAuthorReset']);
		print oc_('We have emailed you a new password.');
		printFooter();
		exit;
	}
}
else {
	print '<p style="text-align: center;">' . oc_('Please enter your submission id and the contact author\'s email below') . "</p>\n";
}

print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="resetform">
<input type="hidden" name="ocaction" value="Reset Password" />
<table border="0" style="margin: 0 auto">
<tr><td><strong>' . oc_('Submission ID') . ':</strong></td><td><input size="20" name="pid" value="' . safeHTMLstr(varValue('pid', $_POST)) . '"> ( <a href="email_papers.php">' . oc_('forgot ID?') . '</a> )</td></tr>
<tr><td><strong>' . oc_('Email') . ':</strong></td><td><input size="20" name="email" value="' . safeHTMLstr(varValue('email', $_POST)) . '"></td></tr>
<tr><th align="center" colspan=2><br><input type="submit" name="submit" class="submit" value="' . oc_('Reset Password') . '"></th></tr>
</table>
';

if (oc_hookSet('author-reset-bottom')) {
	foreach ($GLOBALS['OC_hooksAR']['author-reset-bottom'] as $hook) {
		require_once $hook;
	}
}


printFooter();

?>
