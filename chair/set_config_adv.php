<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require '../include.php';

beginChairSession();

oc_addCSS('chair/set_config_adv.css');
oc_addJS('chair/set_config_adv.js');
oc_addOnLoad('oc_init();');

printHeader("Advanced Configuration", 1);

if (!OCC_ADVANCED_CONFIG) {
	warn('Advanced configuration is disabled.  Enable it in config.php');
}

// Submission?
if (isset($_POST['submit']) && ($_POST['submit'] == "Update Setting")) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}
	if (isset($_POST['module']) && (($_POST['module'] == 'OC') || in_array($_POST['module'], $OC_activeModulesAR)) && isset($_POST['setting']) && isset($OC_configAR[$_POST['setting']]) && isset($_POST['value'])) {
		if ($OC_configAR[$_POST['setting']] == $_POST['value']) {
			print '<p style="text-align: center" class="warn">Setting unchanged</p>';
		} else {
			$q = "UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='" . safeSQLstr($_POST['value']) . "' WHERE `module`='" . safeSQLstr($_POST['module']) . "' AND `setting`='" . safeSQLstr($_POST['setting']) . "' LIMIT 1";
			$r = ocsql_query($q) or err('Unable to update setting');
			if (ocsql_affected_rows() != 1) {
				err('Setting failed to update properly');
			} else {
				print '<p style="text-align: center" class="note2">Setting updated</p>';
			}
		}
	} else {
		err('Invalid module, setting, or value');
	}
}

print '
<p>Use caution when updating settings through the advanced configuration page as no validation of setting value is provided.  If your browser does not support the functionality of this page, you should use the standard configuration update page or edit a setting\'s value in the database config table.  A directory of configuration settings is available (<a href="list_config.php" target="_blank">open in new window</a>).</p><br />

<div id="oc_notice" class=""></div>
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" onsubmit="return updateSettingValue(\'' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '\')">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />

<noscript><p class="warn">JavaScript needs to be enabled to use advanced configuration.</p></noscript>

<p><strong>Module:</strong> <select name="module" id="module" onchange="updateModule(this, \'' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '\')" aria-controls="settingMenu">
<option value=""></option>
<option value="OC">OpenConf</option>
';

foreach ($OC_activeModulesAR as $module) {
	print '<option value="' . safeHTMLstr($module) . '">' . $OC_modulesAR[$module]['name'] . '</option>';
}

print '
</select>
</p>

<div aria-live="polite">
<p><span id="settingMenu" style="display: none">
<strong>Setting:</strong> <select name="setting" id="setting" onchange="updateSetting(this, \'' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '\')" aria-controls="fields">
<option value=""> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </option>
</select>
</span>
</p>
</div>

<div id="fields" style="display: none" aria-live="polite">
<p><hr /></p>
<p><strong>Name:</strong> <span id="name"></span></p>

<p><strong>Description:</strong> <span id="description"></span></p>

<p><strong>Parse for Settings:</strong> <span id="parse"></span></p>

<p><strong>Value:</strong><br />
<textarea name="value" id="value" rows="7" cols="60" style="background-color: #eee"></textarea>
</p>

<p><input type="submit" name="submit" value="Update Setting" /></p>
</div>

</form>
';

printFooter();

?>
