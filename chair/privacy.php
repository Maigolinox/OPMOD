<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once '../include.php';
require_once OCC_PLUGINS_DIR . 'ckeditor.inc';

$OC_extraHeaderAR[] = '
<script language="javascript" type="text/javascript">
<!--
function oc_showHideDiv(fldName, divID) {
	if (document.getElementById) {
		if (document.getElementById(fldName).checked) {
			document.getElementById(divID).style.display="block";
		} else {
			document.getElementById(divID).style.display="none";
		}
	}
}
// -->
</script>
';

beginChairSession();
printHeader("Privacy Settings", 1);

$OC_configVars = array('OC_privacy_display', 'OC_privacy_link', 'OC_privacy_banner_options');

$OC_privacyDisplayOptionsAR = array(
	0 => 'none',
	1 => 'menu',
	2 => 'page footer'
);

function ef($fsf, $ochsf) {
	if (preg_match("/(,?)" . $fsf . "(,?)/", $ochsf, $efmatches)) {
		if (($efmatches[1] == ',') && ($efmatches[2] == ',')) {
			$efreplace = ',';
		} else {
			$efreplace = '';
		}
		$ochsf = preg_replace("/,?" . $fsf . ",?/", $efreplace, $ochsf);
	}
	return($ochsf);
}

$oc_bannerAR = json_decode($OC_configAR['OC_privacy_banner_options'], true) or err('Invalid banner options setting');

if (isset($_POST['submit']) && ($_POST['submit'] == 'Save Settings')) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	$err = array();
	
	// Check input
	if (!preg_match("/^[01]$/", $_POST['OC_privacy_banner_display'])) {
		$err[] = 'Banner Display option invalid';
	}
	if (!isset($_POST['OC_privacy_display']) || !isset($OC_privacyDisplayOptionsAR[$_POST['OC_privacy_display']])) {
		$err[] = 'Policy Link Display invalid';
	}
	if (isset($_POST['OC_privacy_link']) && (oc_strlen($_POST['OC_privacy_link']) > 250)) {
		$err[] = 'Policy Web Address too long';
	}

	if (!empty($err)) {
		print '<p class="warn">Please re-enter your settings, noting the following:<ul><li>' . implode('</li><li>', $err) . '</li></ul></p><hr /><br />';
	} else {
		// Update bannerAR
		$oc_bannerAR['display'] = $_POST['OC_privacy_banner_display'];
		$oc_bannerAR['message'] = $_POST['OC_privacy_banner_message'];
		$oc_bannerAR['dismiss'] = $_POST['OC_privacy_banner_dismiss'];
		$_POST['OC_privacy_banner_options'] = json_encode($oc_bannerAR);
		// Update config
		updateAllConfigSettings($OC_configVars, $_POST, 'OC');
		// Update Template
		if ( ! ocsql_query("UPDATE `" . OCC_TABLE_TEMPLATE . "` SET `subject`='', `body`='" . safeSQLstr(varValue('OC_privacy_policy', $_POST)) . "', `updated`='" . safeSQLstr(date("Y-m-d")) . "' WHERE `templateid`='privacy_policy' AND `type`='other' LIMIT 1") ) {
			warn('Failed to save privacy policy');
		}
		// Update consent form fields if necessary
		if (OCC_LICENSE == 'Public') {
			if (($_POST['OC_privacy_display'] == 0)) {
				if ( ! preg_match("/fs_consent:consent/", $OC_configAR['OC_hideSubFields']) ) {
					ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`=CONCAT(`value`, ',fs_consent:consent') WHERE `module`='OC' AND `setting`='OC_hideSubFields' LIMIT 1");
				}
				if ( ! preg_match("/fs_consent:consent/", $OC_configAR['OC_hideCmtFields']) ) {
					ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`=CONCAT(`value`, ',fs_consent:consent') WHERE `module`='OC' AND `setting`='OC_hideCmtFields' LIMIT 1");
				}
			} else {
				if (preg_match("/fs_consent:consent/", $OC_configAR['OC_hideSubFields'])) {
					ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='" . safeSQLstr(ef('fs_consent:consent', $OC_configAR['OC_hideSubFields'])) . "' WHERE `module`='OC' AND `setting`='OC_hideSubFields' LIMIT 1");
				}
				if (preg_match("/fs_consent:consent/", $OC_configAR['OC_hideCmtFields'])) {
					ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='" . safeSQLstr(ef('fs_consent:consent', $OC_configAR['OC_hideCmtFields'])) . "' WHERE `module`='OC' AND `setting`='OC_hideCmtFields' LIMIT 1");
				}
			}
		}
		// notify user		
		print '<p style="text-align: center" class="note">Settings Saved</p>';
	}

	$OC_privacy_policy = $_POST['OC_privacy_policy'];
} else {
	// retrieve policy template
	list($none, $OC_privacy_policy) = oc_getTemplate('privacy_policy');
}

print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" class="ocform occonfigform">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />

<script>
document.write(\'<p style="margin: 0 0 2em 1em;"><span style="color: #66f; text-decoration: underline; cursor: pointer;" onclick="oc_fsCollapseExpand(0)">collapse all</span> &nbsp; &nbsp; <span style="color: #66f; text-decoration: underline; cursor: pointer;" onclick="oc_fsCollapseExpand(1)">expand all</span></p>\');
</script>

<fieldset id="oc_fs_banner">
<legend onclick="oc_fsToggle(this)">Banner <span>(collapse)</span></legend>

<div id="oc_fs_banner_div">
<div class="fieldsetnote note">When Display Banner is set to Yes, a banner with the Message below is shown until the Dismiss Button is clicked. If the Privacy Policy is enabled, a link to the policy is automatically included in the banner.</div>

<div class="field"><label for="OC_privacy_banner_display">Display Banner:</label><fieldset class="radio">' . generateRadioOptions('OC_privacy_banner_display', $yesNoAR, $oc_bannerAR['display']) . '</fieldset></div>

<div class="field"><label for="OC_privacy_banner_message">Message:</label><textarea name="OC_privacy_banner_message" id="OC_privacy_banner_message" rows="2" cols="70">' . safeHTMLstr($oc_bannerAR['message']) . '</textarea></div>

<div class="field"><label for="OC_privacy_banner_dismiss">Dismiss Button:</label><input name="OC_privacy_banner_dismiss" id="OC_privacy_banner_dismiss" value="' . safeHTMLstr($oc_bannerAR['dismiss']) . '" size="20" maxlength="20" /></div>

<input type="submit" name="submit" value="Save Settings" class="submit" />
</div>
</fieldset>

<fieldset id="oc_fs_privacy">
<legend onclick="oc_fsToggle(this)">Privacy Policy <span>(collapse)</span></legend>
<div id="oc_fs_privacy_div">
<div class="fieldsetnote note" style="margin-bottom: 2em;">Select whether to include a Privacy Policy link in the menu or page footer, and either provide a link to the policy on your own web site or customize the one below. Although not required, it is strongly recommended that a privacy policy be provided. Translations of the privacy policy are not included, however may be entered below. A Display option other than "none" must be set for the Privacy Policy to be viewed.</div>

<div class="field"><label for="OC_privacy_display">Policy Link Display:</label><fieldset class="radio">' . generateRadioOptions('OC_privacy_display', $OC_privacyDisplayOptionsAR, varValue('OC_privacy_display', $OC_configAR)) . '</fieldset><div class="fieldnote note">Location to display Privacy Policy link.' . ((OCC_LICENSE == 'Public') ? ' If location set, consent fields are added to non-custom forms.' : '') . '</div></div>

<div class="field"><label for="OC_privacy_link">Policy Web Address:</label><input name="OC_privacy_link" id="OC_privacy_link" value="' . safeHTMLstr(varValue('OC_privacy_link', $OC_configAR)) . '" size="80" maxlength="250" placeholder="https://" /><div class="fieldnote note">Enter the full URL of the privacy policy on your web site or leave blank to display the policy below</div></div>

<div class="field"><label for="OC_privacy_policy">Privacy Policy:</label><textarea name="OC_privacy_policy" id="OC_privacy_policy" rows="20" cols="70">' . safeHTMLstr($OC_privacy_policy) . '</textarea></div>

<input type="submit" name="submit" value="Save Settings" class="submit" />
</div>
</fieldset>

</form>
';

oc_replaceCKEditor(array('OC_privacy_policy'), true, 600, 400);

printFooter();

?>
