<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$MOD_FILETYPE_configVars = array('MOD_FILETYPE_chairoverride', 'MOD_FILETYPE_allow_rtfforword');

if (isset($_POST['submit']) && ($_POST['submit'] == 'Save Settings')) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	// Update config & status settings
	updateAllConfigSettings($MOD_FILETYPE_configVars, $_POST, OCC_MODULE_ID);

	// notify user		
	print '<p style="text-align: center" class="note">Settings Saved</p>';
}

print '
<form method="post" action="' . OCC_SELF . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />

<p><strong>Skip Check if ' . OCC_WORD_CHAIR . ':</strong> ' .
generateRadioOptions('MOD_FILETYPE_chairoverride', $yesNoAR, $OC_configAR['MOD_FILETYPE_chairoverride'])
. '<br /><span class="note">Skips file format check if ' . OCC_WORD_CHAIR . ' uploading.</p>

<p><strong>Allow RTF for Word Doc:</strong> ' .
generateRadioOptions('MOD_FILETYPE_allow_rtfforword', $yesNoAR, $OC_configAR['MOD_FILETYPE_allow_rtfforword'])
. '<br /><span class="note">Checking Yes will allow Word documents (.doc) in RTF format.</p>

<br />
<p><input type="submit" name="submit" class="submit" value="Save Settings" /></p>
</form>
';
