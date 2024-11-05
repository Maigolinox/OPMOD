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

beginChairSession();

printHeader("Change Password", 1);

if (! $OC_configAR['OC_chairChangePassword']) {
  warn('Config settings do not permit ' . OCC_WORD_CHAIR . ' to change password');
}

$mfaAddresses = array(
	'' => 'disabled',
	'$OC_pcemail' => 'Chair Email Address: ' . $OC_configAR['OC_pcemail'],
	'$OC_confirmmail' => 'Notification Address: ' . $OC_configAR['OC_confirmmail']
);
if (!empty($OC_configAR['OC_chairMFA']) && !isset($mfaAddresses[$OC_configAR['OC_chairMFA']]) && validEmail($OC_configAR['OC_chairMFA'])) {
	$mfaAddresses[$OC_configAR['OC_chairMFA']] = $OC_configAR['OC_chairMFA'];
}

// setup mfa?
if (isset($_GET['a']) && ($_GET['a'] == 'mfasetup')) {
	if (
		isset($_GET['c']) 
		&& (strlen($_GET['c']) == 32) 
		&& preg_match("/^SETUP\|\|([^\|]+)\|\|([^\|]+)$/", $OC_configAR['OC_chairMFAcode'], $matches)
		&& ($_GET['c'] == $matches[1])
		&& isset($mfaAddresses[$matches[2]])
	) {
		updateConfigSetting('OC_chairMFA', $matches[2]) or err('Unable to configure authentication address');
		$OC_configAR['OC_chairMFA'] = $matches[2];
		updateConfigSetting('OC_chairMFAcode', '');
		print '<p style="text-align: center;" class="note2">Authentication Address Set</p>';
	} else {
		print '<p style="text-align: center;" class="warn">Authentication Address verification failed</p>';
	}
	printFooter();
	exit;
}

$e = "";
if (isset($_POST['submit']) && ($_POST['submit'] == "Submit Changes")) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}
	
	if (!oc_password_verify($_POST['currpwd'], $OC_configAR['OC_chair_pwd'])) {
		$e = 'Current password is incorrect';
	} else {
		// password change?
		if (isset($_POST['pwd1']) && !empty($_POST['pwd1']) && isset($_POST['pwd2']) && !empty($_POST['pwd2'])) {
			if ($_POST['pwd1'] != $_POST['pwd2']) {
				$e = 'New passwords do not match';
			} elseif ($_POST['pwd1'] == $OC_configAR['OC_chair_uname']) {
				$e = 'Password may not match ' . OCC_WORD_CHAIR . ' username';
			} elseif (oc_strlen($_POST['pwd1']) < 10) {
				$e = 'Password must be 10+ characters long';
			} else {
				updateConfigSetting('OC_chair_pwd', oc_password_hash($_POST['pwd1'])) or err('Unable to change password');
				print '<p style="text-align: center;" class="note">Password has been changed</p>';
			}
		}
		// auth change?
		if (isset($_POST['mfa']) && isset($mfaAddresses[$_POST['mfa']]) && ($_POST['mfa'] != $OC_configAR['OC_chairMFA'])) {
			if ($_POST['mfa'] == '') {
				updateConfigSetting('OC_chairMFA', '') or err('Unable to remove authentication address');
				$OC_configAR['OC_chairMFA'] = '';
			} elseif (preg_match("/^\\\$(?:OC_pcemail|OC_confirmmail)$/", $_POST['mfa'])) {
				$code = oc_idGen(32);
				$link = OCC_BASE_URL . 'chair/set_password.php?a=mfasetup&c=' . urlencode($code);
				if (strlen($code) == 32) {
					ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='SETUP||" . safeHTMLstr($code) . '||' . safeHTMLstr($_POST['mfa']) . "' WHERE `module`='OC' AND `setting`='OC_chairMFAcode' LIMIT 1") or err('Unable to set authentication code');
					$subject = $OC_configAR['OC_confName'] . ' Multi-Factor Authentication Setup -- action required';
					$body = 'Hello,
					
A request has been received to setup multi-factor authentication for the ' . $OC_configAR['OC_confName'] . ' ' . OCC_WORD_CHAIR . ' account. In order to complete the request, please click the link below prior to signing out of the account:

' . $link . '

Thank you
';
					if (oc_mail($OC_configAR[substr($_POST['mfa'], 1)], $subject, $body)) {
						print '<p style="text-align: center;" class="warn">An email has been sent to the Authentication Address below.<br />Click the link in the email prior to signing out.</p>';
					} else {
						$e .= '<br />Sending of message to complete multi-factor authentication failed';
					}
				} else {
					$e .= '<br />Unable to generate authentication code';
				}
			} else {
				$e .= '<br />Invalid auth setting';
			}
			// to change to a custom email address, use the advanced settings feature
		}
	}
}

if (!empty($e)) {
	print '<p style="text-align: center;" class="warn">' . $e . '</p>';
}

print '
<br />
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" class="ocform occonfigform">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />

<fieldset id="oc_fs_pw" role="header">
<legend onclick="oc_fsToggle(this)">New Password <span>(collapse)</span></legend>
<div id="oc_fs_pw_div">
<div class="note" style="margin-bottom: 2em;">Enter your new password twice. May be left blank if only changing multi-factor authentication setting below.</div>

<div class="field"><label for="pwd1">New Password:</label><input type="password" size="60" maxlength="250" name="pwd1" id="pwd1" /><div class="fieldnote note">10+ characters</div></div>

<div class="field"><label for="pwd1">Confirm New:</label><input type="password" size="60" maxlength="250" name="pwd2" id="pwd2" /></div>

</div>
</fieldset>


<fieldset id="oc_fs_mfa" role="header">
<legend id="oc_fs_mfa_legend" onclick="oc_fsToggle(this)">Multi-Factor Authentication <span>(collapse)</span></legend>
<div id="oc_fs_mfa_div">
<div class="note" style="margin-bottom: 2em;">With an authentication address set, when the Chair enters their username and password a code is sent to the selected address which must be entered to complete the sign in process.</div>

<div class="field"><label for="mfa">Authentication Address:</label><select name="mfa" id="mfa">' . generateSelectOptions($mfaAddresses, (!empty($OC_configAR['OC_chairMFA']) ? $OC_configAR['OC_chairMFA'] : 'disabled')) . '</select><div class="fieldnote note">When changing this option to a new address, a message is sent to the address with a link that must be clicked in order to confirm messages are received prior to the new address taking effect; click the link right away and before signing out.</div></div>

</div>
</fieldset>


<fieldset id="oc_fs_submit" role="header">
<legend onclick="oc_fsToggle(this)">Submit <span>(collapse)</span></legend>
<div id="oc_fs_submit_div">
<div class="note" style="margin-bottom: 2em;">Enter your current password and click the Submit Changes button.</div>

<div class="field"><label for="currpwd">Current Password:</label><input type="password" size="60" maxlength="250" name="currpwd" id="currpwd" /></div>

<input type="submit" name="submit" value="Submit Changes" class="submit" style="margin-left: 200px;" />

</div>
</fieldset>

</form>
';

if (empty($OC_configAR['OC_chairMFA'])) {
	print '
<script>
oc_fsToggle(document.getElementById("oc_fs_mfa_legend"));
</script>
';
}

printFooter();
?>
