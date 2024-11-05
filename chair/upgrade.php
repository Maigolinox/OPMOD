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

// Upgrade paths (e.g., 3.00 => 3.10 -- requires a file openconf/upgrade/upgrade-3.0-3.1.inc)
$OC_upgradeAR = array(
	'5.31'	=> '6.00',
	'6.00'	=> '6.01',
	'6.01'	=> '6.10',
	'6.10'	=> '6.20',
	'6.20'	=> '6.30',
	'6.30'	=> '6.40',
	'6.40'	=> '6.50',
	'6.50'	=> '6.60',
	'6.60'	=> '6.70',
	'6.70'	=> '6.71',
	'6.71'	=> '6.80',
	'6.80'	=> '6.81',
	'6.81'	=> '6.90',
	'6.90'	=> '7.00',
	'7.00'	=> '7.10',
	'7.10'	=> '7.20',
	'7.20'	=> '7.30',
	'7.30'	=> '7.40',
	'7.40'	=> 'done'
);

beginChairSession();

printHeader("OpenConf Upgrade", 1);

if (!isset($OC_upgradeAR[$OC_configAR['OC_version']])) {	// valid current version?
	warn('Current version unknown or no upgrade available.');
} elseif ($OC_upgradeAR[$OC_configAR['OC_version']] == 'done') {	// done?
	print '<p>The upgrade process appears to have been previously completed.</p>';
} elseif (isset($_POST['a']) && ($_POST['a'] == 'u')) {	// ready to upgrade?
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}
	// Upgrade
	while ($OC_upgradeAR[$OC_configAR['OC_version']] != 'done') {
		print '<p>Upgrading from ' . $OC_configAR['OC_version'] . ' to ' . $OC_upgradeAR[$OC_configAR['OC_version']] . ' ... ';
		$upgradeFile = 'upgrade-' . $OC_configAR['OC_version'] . '-' . $OC_upgradeAR[$OC_configAR['OC_version']] . '.inc';
		if (is_file('../upgrade/' . $upgradeFile)) {
			require_once('../upgrade/' . $upgradeFile);
			// check installed modules for upgrades
			foreach ($OC_modulesAR as $module => $moduleinfo) {
				$moduleUpgradeFile = '../modules/' . $module . '/upgrade/' . $upgradeFile;
				if (is_file($moduleUpgradeFile)) {
					require_once($moduleUpgradeFile);
				}
			}
			print 'Done</p>';
			oc_loadConfig($OC_configAR); // update settings in case of dependencies and to update $OC_configAR['OC_version']
		} else {
			warn('The upgrade file ' . safeHTMLstr($upgradeFile) . ' appears to be missing');
		}
	}
	if ($OC_upgradeAR[$OC_configAR['OC_version']] == 'done') {	// done?
		print '
<p>The upgrade process has completed.  You may delete the <em>upgrade</em> directory.</p>
<p><a href="./">Proceed to the main ' . safeHTMLstr(OCC_WORD_CHAIR) . ' Page</a></p>
<p style="text-align: center"><img src="//www.openconf.com/images/openconf-install.gif?u=1" alt="OpenConf logo" title="OpenConf" /></p>
';
		// try to delete upgrade/v so it's not checked for again
		unlink('../upgrade/v');
	}
} else {	// first script call
	print '
<p>This upgrade will take you through updating your OpenConf installation. Click (once) on the <em>Upgrade</em> button below to get started.</p>

<form method="post" action="upgrade.php">
<input type="hidden" name="a" value="u" />
<input type="hidden" name="token" value="' . safeHTMLstr($_SESSION[OCC_SESSION_VAR_NAME]['chairtoken']) . '" />
<p><input type="submit" name="submit" class="submit" value="Begin Upgrade" /></p>
</form>
';
}

printFooter();

?>
