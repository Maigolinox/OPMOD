<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$deprecatedModuleAR = array('oc_subtype');

require_once "../include.php";

beginChairSession();

printHeader("Modules",1);

?>
<p>Additional OpenConf functionality is available through modules.  In order to use a module, you must first install it, and then activate it.  If a module is installed, but not active, its data will still be preserved.  Uninstalling a module will result in the module's data being deleted.</p>
<p>The <em>Module Name</em> link provides access to the module's configuration settings.<br />
The <em>Version</em> link provides information about the module.</p>
<br />

<div class="legend" title="Legend" id="mod_legend">
Status Legend: &nbsp;
<span style="background-color: #0f0; color: #000; padding: 3px">&#8226;</span> Active
&nbsp; &nbsp;
<span style="background-color: #f99; color: #000; padding: 3px">o</span> Disabled
&nbsp; &nbsp;
<span style="background-color: #f00; color: #000; padding: 3px">x</span> Not Installed
</div>
<br />

<table border="0" cellspacing="1" cellpadding="5">
<tr class="rowheader"><th title="module status" scope="col" aria-describedby="mod_legend">&nbsp;</th><th scope="col">Module Name</th><th scope="col">Version</th><th scope="col">Source</th><th scope="col">Description</th><th colspan="3" scope="col">Action</th></tr>

<?php

$row = 1;

// Display installed modules sorted by name
$moduleNamesAR = array();
foreach ($OC_modulesAR as $mID => $mAR) {
	$moduleNamesAR[$mID] = $mAR['name'];
}
asort($moduleNamesAR);
foreach ($moduleNamesAR as $mID => $mName) {
	// is module activate or disabled?
	if (in_array($mID,$OC_activeModulesAR)) {
		$color = '#0f0';
		$symbol = '&#8226;';
		$action = 'deactivate';
	} else {
		$color = '#f99';
		$symbol = 'o';
		$action = 'activate';
	}
	// Is module configurable?
	if (is_file($mID . '/settings.inc')) {
		$configURL = 'request.php?module=' . $mID . '&action=settings.inc';
	} else {
		$configURL = '';
	}
	// Readme?
	if (preg_match("/^oc_/",$mID)&&(!preg_match("/^(\w+) /", constant('OCC_LI'.'CENSE_T'.'YPE'), $m)|| 
	!preg_match("/\b" . strtolower($m[1]) . "\b/", $OC_modulesAR[$mID]['sup'.'ported']))){continue;}
	if (is_file($mID . '/README.html')) {
		$version = '<a href="readme.php?m=' . $mID . '" title="Click for module info (new window)" target="_blank">' . safeHTMLstr($OC_modulesAR[$mID]['version']) . '</a>';
	} else {
		$version = safeHTMLstr($OC_modulesAR[$mID]['version']);
	}
	print '<tr class="row' . $row . ' rowselect"><td style="background-color: ' . $color . '; color: #000">' . $symbol . '</td><td scope="row">' . (empty($configURL) ? safeHTMLstr($OC_modulesAR[$mID]['name']) : ('<a href="' . $configURL . '" title="click to config">' . safeHTMLstr($OC_modulesAR[$mID]['name']) . '</a>')) . '</td><td style="text-align: center">' . $version . '</td><td>' . (($OC_modulesAR[$mID]['developer'] == 'OpenConf') ? 'OpenConf' : 'Third-Party') . '</td><td>' . safeHTMLstr($OC_modulesAR[$mID]['description']) . '</td><td>'  . (empty($configURL) ? '</td>' : ('<a href="' . $configURL . '">config</a></td>')) . '<td style="text-align: center"><a href="' . $action . '.php?module=' . $mID . '">' . $action . '</a></td><td><a href="uninstall.php?module=' . $mID . '" onclick="return confirm(\'Please confirm your request to uninstall this module.  All data stored by module will be deleted.\')">uninstall</a></td></tr>';
	$row = $rowAR[$row];
}

// Get uninstalled modules
$uninstalledModulesAR = array();
if ($dh = opendir('./')) {
	while (($dir = readdir($dh)) !== false) {
		if ((filetype($dir) == 'dir') && !preg_match("/[\.\/]/",$dir) && is_file($dir . '/module.inc') && !oc_module_installed($dir) && oc_moduleValid($dir) && !in_array($dir, $deprecatedModuleAR)) {
			require_once $dir . '/module.inc';
			if (preg_match("/^oc_/",$dir)&&(!preg_match("/^(\w+) /", OCC_LICENSE_TYPE, $m)|| 
			!preg_match("/\b" . strtolower($m[1]) . "\b/", $OC_modulesAR[$dir]['supported'])))
			{unset($OC_modulesAR[$dir]);}else{$uninstalledModulesAR[] = $dir;}
		}
	}
}
// Show a break between uninstalled modules
if (!empty($moduleNamesAR) && !empty($uninstalledModulesAR)) {
	print '<tr><td class="rowheader" colspan="10" style="height: 10px"></td></tr>';
}
// Sort & display uninstalled modules
$moduleNamesAR = array();
foreach ($uninstalledModulesAR as $mID) {
	if ((OCC_LICENSE != 'Public') || !preg_match("/^oc_/", $mID)) {
		$moduleNamesAR[$mID] = $OC_modulesAR[$mID]['name'];
	}
}
uasort($moduleNamesAR, 'strcasecmp');
$color = '#f33';
$symbol = 'x';
foreach ($moduleNamesAR as $mID => $mName) {
	// Readme?
	if (is_file($mID . '/README.html')) {
		$version = '<a href="readme.php?m=' . $mID . '" title="Click for module info (new window)" target="_blank">' . safeHTMLstr($OC_modulesAR[$mID]['version']) . '</a>';
	} else {
		$version = safeHTMLstr($OC_modulesAR[$mID]['version']);
	}
	print '<tr class="row' . $row . ' rowselect"><td style="background-color: ' . $color . '; color: #000">' . $symbol . '</td><td scope="row">' . safeHTMLstr($OC_modulesAR[$mID]['name']) . '</td><td style="text-align: center">' . $version . '</td><td>' . (($OC_modulesAR[$mID]['developer'] == 'OpenConf') ? 'OpenConf' : 'Third-Party') . '</td><td>' . safeHTMLstr($OC_modulesAR[$mID]['description']) . '</td><td>&nbsp;</td><td>&nbsp;</td><td style="text-align: center"><a href="install.php?module=' . $mID . '">install</a></td></tr>';
	$row = $rowAR[$row];
}

$plusModulesAR = array(
	'Acceptance' => 'Provides Chair with the ability to change acceptance options',
	'Advocate Assign' => 'Allows advocates to assign reviews',
	'Bidding' => 'Bidding on papers by reviewers, and bid-based assignments',
	'Discussion' => 'Online discussion (forum) for committee members',
	'Proceedings' => 'Online proceedings',
	'Rebuttal' => 'Author rebuttal of reviews, and reviewer rebuttal of author comments',
	'Reviewer Upload' => 'Reviewer file upload for assigned reviews (e.g., annotation, feedback)'
);
$proModulesAR = array(
	'Auto Assign' => 'Automatically assigns reviewers/advocate when a submission is made',
	'Custom Forms' => 'Customize submission, review, and committee profile forms',
	'Copyright-ACM' => 'Provides export of submissions in ACM ICPS CSV format',
	'Copyright-IEEE' => 'Provides author referral form to IEEE\'s electronic copyright system',
	'MultiFile' => 'Multiple file type uploads',
	'ORCID' => 'Provides review credit to committee members via ORCID service',
	'Plagiarism-Docoloc' => 'Docoloc plagiarism checking service',
	'Plagiarism-iThenticate' => 'CrossCheck and iThenticate plagiarism checking services',
	'Program' => 'Online and mobile program building and display options',
	'Sub. Pre-Payment' => 'Require payment for making a submission'
);
if (OCC_LICENSE == 'Public') {
	$otherModulesAR = array_merge($plusModulesAR, $proModulesAR);
} elseif (defined('OCC_LICENSE_TYPE') && preg_match("/Plus/", OCC_LICENSE_TYPE))  {
	$otherModulesAR = $proModulesAR;
} else {
	$otherModulesAR = array();
}
if (count($otherModulesAR) > 0) {
	ksort($otherModulesAR);
	print '<tr><td class="rowheader" colspan="10" style="height: 10px"></td></tr>';
	foreach ($otherModulesAR as $k=>$v) {
		print '<tr class="row' . ($row = $rowAR[$row]) . ' rowselect"><td>' . $symbol . '</td><td scope="row">' . safeHTMLstr($k) . '</td><td>&nbsp;</td><td>OpenConf</td><td>' . safeHTMLstr($v) . '</td><td colspan="3" style="text-align: center"><a href="https://www.openconf.com/editions/" target="_blank">upgrade for access</a></td></tr>';
	}
}

?>
</table>
<br /><br />

<?php
printFooter();
?>
