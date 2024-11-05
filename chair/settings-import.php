<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Settings to exclude from import -- also update in settings-export.php
$excludeSettingsAR = array('OC_chair_pwd', 'OC_chair_uname', 'OC_chairChangePassword', 'OC_chairFailedSignIn', 'OC_confirmmail', 'OC_confName', 'OC_confNameFull', 'OC_confURL', 'OC_mailHeaders', 'OC_mailParams', 'OC_pcemail', 'OC_version', 'OC_versionLatest');

require_once '../include.php';

beginChairSession();

printHeader('Settings Import', 1);

// Module pre hook
if (oc_hookSet('settings-import-pre')) {
	foreach ($OC_hooksAR['settings-import-pre'] as $f) {
		require_once $f;
	}
}

if (isset($_POST['submit']) && ($_POST['submit'] == 'Import Settings')) {
	// Check for valid submission
	if (!validToken('chair')) {
			warn('Invalid submission');
	}

	// File uploaded ok?
	if (!isset($_FILES['file']['error']) || $_FILES['file']['error'] || !is_uploaded_file($_FILES['file']['tmp_name']) || ($_FILES['file']['size'] <= 0)) {
		warn('The file failed to load.');
	}
	$file = file_get_contents($_FILES['file']['tmp_name']) or err('Unable to open settings file');
	unlink($_FILES['file']['tmp_name']);

	if (isset($_POST['pw']) && !empty($_POST['pw'])) {
		$file = oc_decrypt($file, $_POST['pw']);
	}

	// File encoded ok?
	$settings = json_decode($file, true);
	if (!is_array($settings)) {
		warn('Settings file is corrupted');
	}

	// Check edition
	if (!isset($settings['license']) || (($settings['license'] != 'Public') && (OCC_LICENSE == 'Public'))) {
		warn('The file is not compatible with OpenConf Community Edition');
	}
	
	// Check version
	if (isset($settings['version']) && ($settings['version'] > $GLOBALS['OC_configAR']['OC_version'])) {
		warn('The file is from a newer version of OpenConf.  Upgrade this installation first, then try importing the settings once agian.');
	}

	// Check modules
	if (isset($settings['modules']) && (count($settings['modules']) > 0)) {
		$modules = array();
		foreach ($settings['modules'] as $module) {
			$modules[] = safeHTMLstr($module);
		}
		sort($modules);
		foreach ($settings['modules'] as $moduleID => $moduleName) {
			if (!preg_match("/^[\w-]+$/", $moduleID) || !oc_module_installed($moduleID)) {
				warn('The file includes settings for uninstalled modules.  Please <a href="../modules/modules.php" target="_blank">install</a> the modules first, then import the settings.  Modules included in the file are:<ul><li>' . implode('</li><li>', $modules) . '</li></ul>');
			}
		}
	}

	// Module prep
	if (oc_hookSet('settings-import-prep')) {
		foreach ($OC_hooksAR['settings-import-prep'] as $f) {
			require_once $f;
		}
	}
	
	// Configuration settings
	if (isset($settings['configuration']) && is_array($settings['configuration']) && (count($settings['configuration']) > 0)) {
		foreach($settings['configuration'] as $k => $v) {
			if (preg_match("/^([^\:]+)\:([^\:]+)\:?(occrypt|)$/", $k, $matches)
				&& !in_array($matches[2], $excludeSettingsAR)
			) {
				$module = $matches[1];
				$setting = $matches[2];
				if (isset($matches[3]) && ($matches[3] == 'occrypt')) {
					$v = oc_encrypt($v);
				}
				updateConfigSetting($setting, $v, $module);
			}
		}
		print '<p>Configuration settings imported (including modules) ...</p>';
	}

	// Topics
	if (isset($settings['topics']) && is_array($settings['topics']) && (count($settings['topics']) > 0)) {
		ocsql_query("TRUNCATE `" . OCC_TABLE_TOPIC . "`") or err('Unable to reset topic table');
		$q = "INSERT INTO `" . OCC_TABLE_TOPIC . "` (`topicid`, `topicname`, `short`) VALUES ";
		foreach($settings['topics'] as $k => $kAR) {
			$q .= "('" . safeSQLstr($k) . "', '" . safeSQLstr($kAR['topicname']) . "', '" . safeSQLstr($kAR['short']) . "'),";
		}
		$q = rtrim($q, ',');
		ocsql_query($q) or err('Unable to load topics - ' . safeHTMLstr(ocsql_error()));
		print '<p>Topics imported ...</p>';
	}

	// Module settings -- run here in case Custom Forms modifies reviewer table
	if (oc_hookSet('settings-import-process')) {
		foreach ($OC_hooksAR['settings-import-process'] as $f) {
			require_once $f;
		}
	}

	// Reviewers
	if (isset($settings['reviewers']) && !empty($settings['reviewers'])) {
		ocsql_query("TRUNCATE `" . OCC_TABLE_REVIEWER . "`") or err('Unable to reset reviewer table');
		foreach($settings['reviewers'] as $k => $kAR) {
			$q = "INSERT INTO `" . OCC_TABLE_REVIEWER . "` SET ";
			foreach ($kAR as $fld => $val) {
				if ($val === null) { // special case for date fields which cannot be ''
					$q .= "`" . $fld . "`=null,";
				} else {
					$q .= "`" . $fld . "`='" . safeSQLstr($val) . "',";
				}
			}
			$q = rtrim($q, ',');
			ocsql_query($q) or err('Unable to import reviewer - ' . safeHTMLstr(ocsql_error()));
		}
		print '<p>Reviewers imported ...</p>';
	}
	
	// Reviewer Topics
	if (isset($settings['reviewertopics']) && is_array($settings['reviewertopics']) && (count($settings['reviewertopics']) > 0)) {
		ocsql_query("TRUNCATE `" . OCC_TABLE_REVIEWERTOPIC . "`") or err('Unable to reset reviewertopic table');
		foreach($settings['reviewertopics'] as $k => $kAR) {
			$q = "INSERT INTO `" . OCC_TABLE_REVIEWERTOPIC . "` (`reviewerid`, `topicid`) VALUES ";
			foreach ($kAR as $topicid) {
				$q .= "('" . safeSQLstr($k) . "', '" . safeSQLstr($topicid) . "'),";
			}
			$q = rtrim($q, ',');
			ocsql_query($q) or err('Unable to import reviewertopics - ' . safeHTMLstr(ocsql_error()));
		}
		print '<p>Reviewer Topics imported ...</p>';
	}

	// Templates
	if (isset($settings['templates']) && !empty($settings['templates'])) {
		foreach($settings['templates'] as $k => $kAR) {
			if (
				isset($kAR['module'])
				&&
				( 
					($kAR['module'] == 'OC')
					||
					oc_module_installed($kAR['module'])
				)
			) {
				$qflds = '';
				foreach ($kAR as $fld => $val) {
					if ( ! empty($val) && ($fld != 'templateid')) {
						$qflds .= "`" . $fld . "`='" . safeSQLstr($val) . "',";
					}
				}
				$qflds = rtrim($qflds, ',');
				$q = "INSERT INTO `" . OCC_TABLE_TEMPLATE . "` SET `templateid`='" . safeSQLstr($kAR['templateid']) . "', " . $qflds . " ON DUPLICATE KEY UPDATE " . $qflds;
				ocsql_query($q) or err('Unable to import template - ' . safeHTMLstr(ocsql_error()));				
			}
		}
		print '<p>Templates imported ...</p>';
	}
	
	// Confirm
	print '<p class="note2">Settings have been imported &ndash; be sure to review your configuration.</p>';
	
} else {
	
	print '
<p style="margin-bottom: 2em;">In order to import settings from another OpenConf installation, select the settings file you previously exported, then click the <i>Import Settings</i> button.  If this is not a new installation, you should first backup your database.  <b>Existing topics and committee member accounts will be deleted if any are in import file.</b></p>

<form method="post" action="settings-import.php" enctype="multipart/form-data">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />

<p><label><b>Settings File:</b> <input type="file" name="file" /></label></p>

<p><label><b>File Password:</b> <input type="password" name="pw" id="pw" style="background: #eee" /></label> <span class="note">if password was entered when exporting; leave blank otherwise</span></p>

<br />

<input type="submit" name="submit" value="Import Settings" class="submit" />
</form>
';

}

printFooter();

?>
