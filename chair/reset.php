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

printHeader("Reset Password", 3);

if (! $OC_configAR['OC_chairPasswordForgot']) {
	print '<p class="err" style="text-align: center;">Functionality disabled</p>';
}
elseif (isset($_POST['submit']) && ($_POST['submit'] == "Reset Password") && preg_match("/^\w+$/",$_POST['uname'])) {
	if (oc_strtolower($OC_configAR['OC_chair_uname']) != oc_strtolower($_POST['uname'])) {
		print '<p style="text-align: center" class="warn">' . OCC_WORD_CHAIR . ' username entered is invalid.</p>';  
	}
    else {  // username valid
		$newpwd = oc_password_generate();
		updateConfigSetting('OC_chair_pwd', oc_password_hash($newpwd)) or err('Unable to create new password');
		$msg = '
Per your request, we have issued you a new ' . OCC_WORD_CHAIR. ' password for accessing the ' . $OC_configAR['OC_confName'] . ' OpenConf system.  The new password is:

	' . $newpwd . '

You may change this password at any time by signing in to the OpenConf system and updating your profile.

';
		if (sendEmail($OC_configAR['OC_pcemail'], OCC_WORD_CHAIR . " Password Reset", $msg)) {
			print '<p>We have emailed you a new password.  Once you receive it, please <a href="signin.php">sign in</a> and change it.</p>';
		} else {
			warn('We have generated a new password for you, but were unable to email it.  Please contact the OpenConf administrator');
		}
	}
} else {
	print '
<p class="note2" style="text-align: center"">Enter ' . OCC_WORD_CHAIR . '\'s username and click the <em>Reset Password</em> button</p>
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<table border="0" style="margin: 0 auto">
<tr><td><strong><label for="uname">' . OCC_WORD_CHAIR . ' Username:</label></strong></td><td><input size=20 name="uname" id="uname" value="' . (isset($_POST['uname']) ? safeHTMLstr($_POST['uname']) : '') . '"></td></tr>
<tr><th align="center" colspan=2><br><input type="submit" name="submit" class="submit" value="Reset Password"></th></tr>
</table>
</form>
<p>
';
}

printFooter();

?>
