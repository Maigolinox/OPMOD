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

function oc_chairSignIn() {
	$_SESSION[OCC_SESSION_VAR_NAME]['chairlast'] = time(); 		// last time Chair accessed site
	$_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] = oc_idGen();	// Chair token for form submission verification
	$_SESSION[OCC_SESSION_VAR_NAME]['chairvars'] = array();		// temporary storage of Chair-related variables during session

	// Store latest software version number for update notification
	if (isset($GLOBALS['v']) && preg_match("/^\d+\.[\d\.]+$/", $GLOBALS['v']) && ($GLOBALS['v'] != $GLOBALS['OC_configAR']['OC_versionLatest'])) {
		updateConfigSetting('OC_versionLatest', $GLOBALS['v']);
	}
	
	// Reset failed sign in counter
	if ($GLOBALS['OC_configAR']['OC_chairFailedSignIn'] != 'skip') {
		updateConfigSetting('OC_chairFailedSignIn', '');
	}
	
	// log sign in
	oc_logit('signin', 'Chair sign in from ' . varValue('REMOTE_ADDR', $_SERVER));

	// re-route user
	session_write_close();
	header('Location: index.php?' . strip_tags(SID));
	exit;
}

if (oc_hookSet('chair-signin')) {
	   foreach ($GLOBALS['OC_hooksAR']['chair-signin'] as $hook) {
			   require_once $hook;
	   }
}

session_regenerate_id(); // prevent login session fixation

$errmsg = "";

// MFA?
if (isset($_POST['ocaction']) && ($_POST['ocaction'] == 'Enter') && !empty($OC_configAR['OC_chairMFA'])) {
	if (
		isset($_POST['code'])
		&& !empty($_POST['code'])
		&& isset($_POST['verifier'])
		&& !empty($_POST['verifier'])
		&& preg_match("/^(\d+)\|(.*)$/", $OC_configAR['OC_chairMFAcode'], $matches)		// MFA code set
		&& ((time() - $matches[1]) < (60 * 15)) 										// 15 minute from sign in
		&& (hash('sha256', (trim($_POST['code']) . $_POST['verifier'])) == $matches[2])	// code valid
	) {
		ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='' WHERE `setting`='OC_chairMFAcode' AND `module`='OC' LIMIT 1");
		oc_chairSignIn();
	} else {
		warn('Invalid authentication', 'Sign In', 3);
	}
	exit;
}

if (isset($_POST['submit']) && ($_POST['submit'] == "Sign In") && isset($_POST['uname']) && !empty($_POST['uname']) && isset($_POST['upwd']) && !empty($_POST['upwd'])) {
	// Check for too many failed attempts
	$failedNum = 0;
	$failedTime = 0;
	if (!empty($OC_configAR['OC_chairFailedSignIn']) && ($OC_configAR['OC_chairFailedSignIn'] != 'skip')) {
		list($lastFailedNum, $lastFailedTime) = explode(':', $OC_configAR['OC_chairFailedSignIn']);
		if ((time() - $lastFailedTime) < (60 * 5)) { 	// is last failed attempt < 5 minutes ago
			$failedNum = $lastFailedNum;
			$failedTime = $lastFailedTime;
			if ($failedNum == 3) {
				warn('There have been too many failed attempts at signing in.  Please wait 5 minutes before trying again', 'Sign In', 3);
			}
		}
	}
	// Check for bad user/pwd
	$lowusername = oc_strtolower(trim($_POST['uname']));
	if ((oc_strtolower($OC_configAR['OC_chair_uname']) != $lowusername) || !oc_password_verify($_POST['upwd'], $OC_configAR['OC_chair_pwd'], 'chair')) {
		$errmsg =  '
<span class="err">Incorrect login.  Please try again below or contact your OpenConf administrator.</span>
<p>
		';
		$failedNum++;
		if ($OC_configAR['OC_chairFailedSignIn'] != 'skip') {
			updateConfigSetting('OC_chairFailedSignIn', $failedNum . ':' . time());	
		}
	}
	elseif (!empty($OC_configAR['OC_chairMFA'])) { // multi-factor enabled
		if (preg_match("/^\\\$(OC_pcemail|OC_confirmmail)\$/", $OC_configAR['OC_chairMFA'])) {
			$tovar = substr($OC_configAR['OC_chairMFA'], 1);
			$to = $OC_configAR[$tovar];
		} elseif (validEmail($OC_configAR['OC_chairMFA'])) {
			$to = $OC_configAR['OC_chairMFA'];
		} else {
			warn('Invalid multi-factor setup', 'Sign In', 3);
			exit;
		}
		$verifier = oc_idGen(32);
		if (function_exists('random_int')) {
			$code = random_int(100000, 999999);
		} else {
			$code = mt_rand(100000, 999999);
		}
		printHeader(OCC_WORD_CHAIR . ' Sign In', 3);
		ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='" . safeSQLstr(time() . '|' . hash('sha256', $code . $verifier)) . "' WHERE `setting`='OC_chairMFAcode' AND `module`='OC' LIMIT 1") or err('Unable to setup authentication code');
		$subject = $OC_configAR['OC_confName'] . ' Sign In';
		$body = 'Hello,

The requested code is:

' . $code . '

Please enter it within 15 minutes of requesting the code.
';
		oc_mail($to, $subject, $body) or err('Unable to email authenticate code');
		print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="verifier" value="' . safeHTMLstr($verifier) . '" />
<div style="text-align: center;">
<p>Enter the code received via email and click the button within 15 minutes:</p>
<p><input name="code" id="code" placeholder="code" size="6" maxlength="6" style="font-size: 1.5em; text-align:center; " /></p>
<p><input type="submit" name="ocaction" class="submit" value="Enter" /></p>
</div>
</form>
<script language="javascript">
<!--
document.getElementById("code").focus();
// -->
</script>
';
		printFooter();
		exit;
	}
	else {  // We have a winner!
		oc_chairSignIn();
	}
}

printHeader(OCC_WORD_CHAIR . ' Sign In', 3);

if (!empty($errmsg)) { 
	print $errmsg;
}
elseif (isset($_GET['e']) && ($_GET['e'] == "exp")) { print '<span class="err">Your session has timed out or you did not sign in properly.  Please sign in again.</span><p>'; }

print '
<br>
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<table border="0" style="margin: 0 auto">
<tr><td><strong><label for="uname">Username:</label></strong></td><td><input size=20 name="uname" id="uname" value="' . safeHTMLstr(varValue('uname', $_POST)) . '" tabindex="1" />';

if ($OC_configAR['OC_chairUsernameForgot']) {
	print ' (<a href="email_username.php" tabindex="4">forgot username?</a>)';
}

print '</td></tr>
<tr><td><strong><label for="upwd">Password:</label></strong></td><td><input type="password" size=20 name="upwd" id="upwd" tabindex="2" />';

if ($OC_configAR['OC_chairPasswordForgot']) {
	print ' (<a href="reset.php" tabindex="5">forgot password?</a>)';
}

print '</td></tr>
<tr><th align="center" colspan="3"><br><input type="submit" name="submit" class="submit" value="Sign In" tabindex="3" /></th></tr>
</table>
</form>
<br />
<script language="javascript">
<!--
document.getElementById("uname").focus();
// -->
</script>
';

if ($OC_configAR['OC_ChairTimeout'] > 0) {
    print '
<p style="text-align: center" class="note">Note: Session times out after ' . $OC_configAR['OC_ChairTimeout'] . ' minutes of inactivity</p>
';
}

printFooter();

?>
