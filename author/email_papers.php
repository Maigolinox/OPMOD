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

printHeader(oc_('Email Submissions'), 3);

if (isset($_POST['ocaction']) && ($_POST['ocaction'] == "Email Submissions") && (!empty($_POST['email']))) { 
	// check for valid email
	if (!validEmail($_POST['email'])) {
		print '<p style="text-align: center" class="warn">' . oc_('Email address entered is invalid') . '</p>';
		printFooter();
		exit;
	}
	$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`title` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "` WHERE `" . OCC_TABLE_AUTHOR . "`.`email`='" . safeSQLstr(oc_strtolower($_POST['email'])) . "' AND `" . OCC_TABLE_AUTHOR . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` AND `" . OCC_TABLE_AUTHOR . "`.`position`=`" . OCC_TABLE_PAPER . "`.`contactid` ORDER BY `" . OCC_TABLE_PAPER . "`.`paperid`";
	$r = ocsql_query($q) or err(oc_('Error checking for submissions'));
	if (($rnum=ocsql_num_rows($r)) == 0) { 
		print '<p style="text-align: center" class="warn">' . sprintf(oc_("We did not find any submissions where the contact author's email is %s"), safeHTMLstr($_POST['email'])) . '.</p>';
		printFooter();
		exit;
	}
	else {
		$msg = "\n" . sprintf(oc_('Per your request, here is a list of submissions made to the %s OpenConf system with you listed as the contact:'), $OC_configAR['OC_confName']) . "\n";

		while ($e = ocsql_fetch_array($r)) {
			$msg .= '
ID:    ' . $e['paperid'] . '
Title: ' . $e['title'] . '
			';
		}
		sendEmail($_POST['email'], oc_('List of submissions made'), $msg, $OC_configAR['OC_notifyAuthorEmailPapers']);
		print oc_('We have emailed the list of submissions for which you are the contact author.');
		printFooter();
		exit;
	}
}
else {
	print '<p style="text-align: center; font-weight: bold">' . oc_('Please enter your email address below and click on <em>Email Submissions</em>.  We will then email you a list of submissions for which you are the contact author.') . "</p>\n";
}

print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="email_papersform">
<input type="hidden" name="ocaction" value="Email Submissions" />
<table border="0" style="margin: 0 auto">
<tr><td><strong>' . oc_('Email') . ':</strong></td><td><input size="20" name="email" id="email" value="' . safeHTMLstr(varValue('email', $_POST)) . '"></td></tr>
<tr><th align="center" colspan=2><br><input type="submit" name="submit" class="submit" value="' . oc_('Email Submissions') . '"></th></tr>
</table>
</form>
';

if (oc_hookSet('author-email_papers-bottom')) {
	foreach ($GLOBALS['OC_hooksAR']['author-email_papers-bottom'] as $hook) {
		require_once $hook;
	}
}

printFooter();

?>
