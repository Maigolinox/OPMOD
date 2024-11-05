<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Check for PHPMailer
if (!is_dir(MOD_MAIL_PHPMAILER_DIR)) {
	warn('PHPMailer plugin is missing. <a href="https://github.com/PHPMailer/PHPMailer" target="_blank" rel="noopener noreferrer">Download</a> and install it to the directory plugins/PHPMailer/.');
	exit;
}

$MOD_MAIL_configVars = array('MOD_MAIL_mailer', 'MOD_MAIL_from_email', 'MOD_MAIL_from_name', 'MOD_MAIL_return_path', 'MOD_MAIL_smtp_host', 'MOD_MAIL_smtp_port', 'MOD_MAIL_smtp_encryption');

if (!defined('MOD_MAIL_SMTP_USERNAME')) {
	$MOD_MAIL_configVars[] = 'MOD_MAIL_smtp_username';
}

$mod_mail_mailerOptions = array(
	'php'	=> 'PHP mail() function',
	'smtp'	=> 'SMTP server'
);

$mod_mail_encryptionOptions = array(
	'none'	=> 'none',
	'ssl'	=> 'SSL',
	'tls'	=> 'TLS'
);

$mod_mail_returnPathOptions = array(
	'1' => 'use From Email',
	'0'	=> 'do not set'
);

$err = array();
	
// Save settings?
if (isset($_POST['submit']) && ($_POST['submit'] == 'Save Settings')) { 

	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	// Check input
	if (!isset($_POST['MOD_MAIL_mailer']) || !isset($mod_mail_mailerOptions[$_POST['MOD_MAIL_mailer']])) {
		$err[] = 'Mailer invalid';
	} elseif (($_POST['MOD_MAIL_mailer'] == 'smtp') && (empty(trim($_POST['MOD_MAIL_smtp_host'])) || empty(trim($_POST['MOD_MAIL_smtp_port'])))) {
		$err[] = 'SMTP Host and Port must be filled in when SMTP selected';
	}
	if (!empty($_POST['MOD_MAIL_from_email']) && !validEmail(trim($_POST['MOD_MAIL_from_email']))) {
		$err[] = 'From Email invalid';
	}
	if (!isset($_POST['MOD_MAIL_return_path']) || !isset($mod_mail_returnPathOptions[$_POST['MOD_MAIL_return_path']])) {
		$err[] = 'Return Path invalid';
	}
	if (!empty($_POST['MOD_MAIL_smtp_port']) && !preg_match("/^\d+$/", $_POST['MOD_MAIL_smtp_port'])) {
		$err[] = 'SMTP Port invalid';
	}
	if (!isset($_POST['MOD_MAIL_smtp_encryption']) || !isset($mod_mail_encryptionOptions[$_POST['MOD_MAIL_smtp_encryption']])) {
		$err[] = 'Encryption invalid';
	}
	
	if (!empty($err)) {
		print '<div class="warn"><p>Please re-enter your settings, noting the following:</p><ul><li>' . implode('</li><li>', $err) . '</li></ul></div><br /><hr /><br />';
	} else {
		if (
			isset($_POST['MOD_MAIL_smtp_password']) 
			&& ($_POST['MOD_MAIL_smtp_password'] != '***') 
			&& !empty($_POST['MOD_MAIL_smtp_password'])
		) {
			$_POST['MOD_MAIL_smtp_password'] = oc_encrypt($_POST['MOD_MAIL_smtp_password']);
			$MOD_MAIL_configVars[] = 'MOD_MAIL_smtp_password';
		} 
		elseif (
			!empty($OC_configAR['MOD_MAIL_smtp_password'])
			&&
			(!isset($_POST['MOD_MAIL_smtp_password']) || ($_POST['MOD_MAIL_smtp_password'] == '')) 
		) {
			$_POST['MOD_MAIL_smtp_password'] = '';
			$MOD_MAIL_configVars[] = 'MOD_MAIL_smtp_password';
		}
	
		// Update config settings
		updateAllConfigSettings($MOD_MAIL_configVars, $_POST, OCC_MODULE_ID);

		// notify user		
		print '<p style="text-align: center" class="note">Settings Saved</p>';
	}
} else {
	foreach ($MOD_MAIL_configVars as $var) {
		$_POST[$var] = $OC_configAR[$var];
	}
	$_POST['MOD_MAIL_smtp_password'] = $OC_configAR['MOD_MAIL_smtp_password'];
}

// Show settings
print '
<form method="post" action="' . OCC_SELF . '" class="ocform occonfigform">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />

<script>
document.write(\'<p style="margin: 0 0 2em 1em;"><span style="color: #66f; text-decoration: underline; cursor: pointer;" onclick="oc_fsCollapseExpand(0)">collapse all</span> &nbsp; &nbsp; <span style="color: #66f; text-decoration: underline; cursor: pointer;" onclick="oc_fsCollapseExpand(1)">expand all</span></p>\');
</script>

<fieldset id="oc_fs_mod_mail_main">
<legend onclick="oc_fsToggle(this)">Mailer <span>(collapse)</span></legend>
<div id="oc_fs_mod_mail_main_div">

<div class="field"><label for="MOD_MAIL_mailer">Mailer:</label>
<fieldset class="radio">' . generateRadioOptions('MOD_MAIL_mailer', $mod_mail_mailerOptions, varValue('MOD_MAIL_mailer', $_POST)) . '</fieldset><div class="fieldnote note"><br />If selecting SMTP server, fill out section below</div>
</div>

<input type="submit" name="submit" value="Save Settings" class="submit" />
</div>
</fieldset>


<fieldset id="oc_fs_mod_mail_smtp">
<legend onclick="oc_fsToggle(this)">SMTP Server Options <span>(collapse)</span></legend>
<div id="oc_fs_mod_mail_smtp_div">

<div class="field"><label for="MOD_MAIL_smtp_host">SMTP Host:</label> <input name="MOD_MAIL_smtp_host" class="ocinput" size="80" value="' . safeHTMLstr(varValue('MOD_MAIL_smtp_host', $_POST)) . '" /><div class="fieldnote note">The use of an IP address is recommended</div></div>

<div class="field"><label for="MOD_MAIL_smtp_port">SMTP Port:</label> <input name="MOD_MAIL_smtp_port" class="ocinput" size="6" value="' . safeHTMLstr(varValue('MOD_MAIL_smtp_port', $_POST)) . '" /></div>

<div class="field"><label for="MOD_MAIL_smtp_encryption">Encryption:</label>
<fieldset class="radio">' . generateRadioOptions('MOD_MAIL_smtp_encryption', $mod_mail_encryptionOptions, varValue('MOD_MAIL_smtp_encryption', $_POST)) . '</fieldset>
</div>
';

if (!defined('MOD_MAIL_SMTP_USERNAME')) {
	print '
<div class="field"><label for="MOD_MAIL_smtp_username">Username:</label> <input name="MOD_MAIL_smtp_username" class="ocinput" size="80" value="' . safeHTMLstr(varValue('MOD_MAIL_smtp_username', $_POST)) . '" /><div class="fieldnote note">Leave username and password blank if server does not require authentication</div></div>

<div class="field"><label for="MOD_MAIL_smtp_password">Password:</label> <input name="MOD_MAIL_smtp_password" class="ocinput" size="80" value="' . ((empty($err) && !empty($OC_configAR['MOD_MAIL_smtp_password'])) ? '***' : varValue('MOD_MAIL_smtp_password', $_POST)) . '" /><div class="fieldnote note">If password saved, *** will display above</div></div>';
}

print '

<div class="field"><label for="MOD_MAIL_return_path">Return Path:</label>
<fieldset class="radio">' . generateRadioOptions('MOD_MAIL_return_path', $mod_mail_returnPathOptions, varValue('MOD_MAIL_return_path', $_POST)) . '</fieldset>
</div>

<div class="field"><label for="MOD_MAIL_from_name">From Name:</label> <input name="MOD_MAIL_from_name" class="ocinput" size="80" value="' . safeHTMLstr(varValue('MOD_MAIL_from_name', $_POST)) . '" /><div class="fieldnote note">Leave blank to use Event/Journal Short Name</div></div>

<div class="field"><label for="MOD_MAIL_from_email">From Email:</label> <input name="MOD_MAIL_from_email" class="ocinput" size="80" value="' . safeHTMLstr(varValue('MOD_MAIL_from_email', $_POST)) . '" /><div class="fieldnote note">Leave blank to use Chair Email Address</div></div>

<input type="submit" name="submit" value="Save Settings" class="submit" />

</div>
</fieldset>

</form>
';

if (($OC_configAR['MOD_MAIL_mailer'] == 'php') || (($OC_configAR['MOD_MAIL_mailer'] == 'smtp') && !empty($OC_configAR['MOD_MAIL_smtp_host']))) {
	print '
<p style="font-style: italic;">Once settings are saved, <a href="../modules/request.php?module=mail&action=test.php" onclick="window.open(\'../modules/request.php?module=mail&action=test.php\', \'mail\',\'width=700,height=300,toolbar=0,status=0,menubar=0,resizable=1,scrollbars=1,location=0\'); return false;" title="opens new window to send a test message">test by sending a message</a></p>
<p>For troubleshooting assistance, see our <a href="https://www.openconf.com/documentation/email.php#troubleshooting" title="opens guide in new window" target="_blank">email guide</a>. If the SMTP mailer is enabled, also see the <a href="https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting" title="opens wiki in new window" target="_blank" rel="noopener noreferrer">PHPMailer wiki</a>.</p>
';
}
