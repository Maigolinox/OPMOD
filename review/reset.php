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

if (isset($_POST['ocaction']) && ($_POST['ocaction'] == "Reset Password") && preg_match("/^[\p{L}\p{Nd}_\.\-\@]+$/u",trim($_POST['uname'])) && !empty($_POST['email'])) {
	$q = "SELECT `reviewerid`, `username`, `email` FROM `" . OCC_TABLE_REVIEWER . "` WHERE `username`='" . safeSQLstr(oc_strtolower(trim($_POST['uname']))) . "'";
	$r = ocsql_query($q) or err("Error checking username");
	if (($rnum=ocsql_num_rows($r)) == 0) { print '<p style="text-align: center" class="warn">Invalid username.</p>'; }
	elseif ($rnum > 1) { err("multiple accounts with this username"); }
	else {
		$e = ocsql_fetch_array($r);
		if (oc_strtolower($e['email']) != oc_strtolower($_POST['email'])) {
			print '<p style="text-align: center" class="warn">' . oc_('Email does not match username.') . '</p>';
		}
		else {  // username valid, reset pwd
			$newpwd = oc_password_generate();
			$q2 = "UPDATE `" . OCC_TABLE_REVIEWER . "` SET `password`='" . oc_password_hash($newpwd) . "' WHERE `reviewerid`='" . safeSQLstr($e['reviewerid']) . "'";
			$r2 = ocsql_query($q2) or err(oc_('Unable to update password'));
			//T: $s = conference short name (e.g., CONF2012)
			$msg = "\n" . sprintf(oc_('Per your request, we have issued you a new password for accessing the %s OpenConf system.  The new password is:'), $OC_configAR['OC_confName']) . "\n\n	" . $newpwd . "\n\n" . oc_('You may change this password at any time by signing in to the OpenConf system and updating your profile.') . "\n\n";
			if (sendEmail($_POST['email'], "Reviewer Password Reset", $msg, $OC_configAR['OC_notifyReviewerReset'])) {
				print sprintf(oc_('We have emailed you a new password.  Once you receive it, please <a href="%s">sign in</a> and change it.'), 'signin.php') . '<br /><br />';
			} else {
				warn(oc_('We have reset your password, but have been unable to email it to you.  Please contact the administrator.'));
			}
			printFooter();
			exit;
		}
	}
}
else {
	print '<p style="text-align: center">' . oc_('Please enter your username and the email you registered with below') . '</p>';
}

print '
<form method="post" action="'.$_SERVER['PHP_SELF'].'">
<input type="hidden" name="ocaction" value="Reset Password" />
<table border="0" style="margin: 0 auto">
<tr><td><strong><label for="uname">' . oc_('Username') . ':</label></strong></td><td><input size=20 name="uname" id="uname" value="' . safeHTMLstr(varValue('uname', $_POST)) . '"> ( <a href="email_username.php" target="_blank" title="' . safeHTMLstr(oc_('Links open in a new window')) . '">' . oc_('forgot username?') . '</a> )</td></tr>
<tr><td><strong><label for="email">' . oc_('Email') . ':</label></strong></td><td><input size=20 name="email" id="email" value="' . safeHTMLstr(varValue('email', $_POST)) . '"></td></tr>
<tr><th align="center" colspan=2><br><input type="submit" name="submit" class="submit" value="' . oc_('Reset Password') . '"></th></tr>
</table>
</form>
<p>
';

printFooter();

?>
