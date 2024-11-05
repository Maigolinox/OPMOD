<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$MOD_CAPTCHA_configVars = array('MOD_CAPTCHA_hcaptcha_public_key', 'MOD_CAPTCHA_hcaptcha_private_key', 'MOD_CAPTCHA_private_key', 'MOD_CAPTCHA_public_key');

if (isset($_POST['submit']) && ($_POST['submit'] == 'Save Settings')) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	// Validate settings
	$err = array();
	if (!ini_get('allow_url_fopen')) {
		$err[] = 'PHP must be configured to have allow_url_fopen enabled';
	}
	
	// Errors?
	if (!empty($err)) {
		print '<div class="warn"><p>Please re-enter your settings, noting the following:</p><ul><li>' . implode('</li><li>', $err) . '</li></ul></div><br /><hr /><br />';
	} else { // Update settings
		updateAllConfigSettings($MOD_CAPTCHA_configVars, $_POST, OCC_MODULE_ID);
		// notify user		
		print '<p style="text-align: center" class="note">Settings Saved</p>';
	}
}

print '
<form method="post" action="' . OCC_SELF . '" class="ocform">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />

<p>OpenConf supports the <a href="https://hcaptcha.com" target="_blank" rel="noopener noreferrer">hCaptcha</a> and <a href="https://www.google.com/recaptcha" target="_blank" rel="noopener noreferrer">Google reCAPTCHA</a> services. Sign up for an account with either service and enter the public/private keys below. If credentials are filled in for both services, hCAPTCHA will be used by default.</p>

<fieldset id="oc_fs_mod_captcha_hcaptcha">
<legend onclick="oc_fsToggle(this)">hCpatcha</span></legend>
<div class="fieldsetnote note">In order to use hCaptcha, first sign up for an account at <a href="https://hcaptcha.com" target="_blank" rel="noopener noreferrer">https://hcaptcha.com</a></div>
<div id="oc_fs_mod_captcha_hcaptcha_div">

<div class="field">
<label for="MOD_CAPTCHA_hcaptcha_public_key">Public (Site) key:</label>
<input name="MOD_CAPTCHA_hcaptcha_public_key" id="MOD_CAPTCHA_hcaptcha_public_key" value="' . safeHTMLstr($OC_configAR['MOD_CAPTCHA_hcaptcha_public_key']) . '" size="60" />
</div>

<div class="field">
<label for="MOD_CAPTCHA_hcaptcha_private_key">Private (Secret) key:</label>
<input name="MOD_CAPTCHA_hcaptcha_private_key" id="MOD_CAPTCHA_hcaptcha_private_key" value="' . safeHTMLstr($OC_configAR['MOD_CAPTCHA_hcaptcha_private_key']) . '" size="60" />
</div>

</div>
</fieldset>


<fieldset id="oc_fs_mod_captcha_recaptcha">
<legend onclick="oc_fsToggle(this)">Google reCAPTCHA v2</span></legend>
<div class="fieldsetnote note">In order to use reCAPTCHA, first sign up for an account at <a href="https://google.com/recaptcha" target="_blank" rel="noopener noreferrer">https://google.com/recaptcha</a></div>
<div id="oc_fs_mod_captcha_recaptcha_div">

<div class="field">
<label for="MOD_CAPTCHA_public_key">Public key:</label>
<input name="MOD_CAPTCHA_public_key" id="MOD_CAPTCHA_public_key" value="' . safeHTMLstr($OC_configAR['MOD_CAPTCHA_public_key']) . '" size="60" />
</div>

<div class="field">
<label for="MOD_CAPTCHA_private_key">Private key:</label>
<input name="MOD_CAPTCHA_private_key" id="MOD_CAPTCHA_private_key" value="' . safeHTMLstr($OC_configAR['MOD_CAPTCHA_private_key']) . '" size="60" />
</div>

</div>
</fieldset>

<p><input type="submit" name="submit" value="Save Settings" class="submit" /></p>
</form>
';
