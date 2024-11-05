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

$OC_extraHeaderAR[] = '
<style type="text/css">
<!--
table.settings { border: 0; }
table.settings th { padding: 5px 5px; vertical-align: top; }
table.settings td { padding: 2px 5px; vertical-align: top; }
table.settings th { font-weight: bold; text-align: center; background-color: #cdd; }
-->
</style>
';

printHeader('Settings Directory', 1);

print '<p style="text-align: center"><a href="set_config_adv.php">Advanced Configuration</a></p>';

$q = "SELECT `setting`, `name`, `description` FROM `" . OCC_TABLE_CONFIG . "` WHERE `module`='OC' ORDER BY `module`, `setting`";
$r = ocsql_query($q) or err('Unable to retrieve OpenConf settings');

$row = 1;
print '<table class="settings"><tr><th>Setting</th><th>Name</th><th>Description</th></tr>';
while ($l = ocsql_fetch_assoc($r)) {
	print '<tr class="row' . $row . '"><td>' . safeHTMLstr($l['setting']) . '</td><td>' . safeHTMLstr($l['name']) . '</td><td>' . $l['description'] . "</td></tr>\n";
	$row = (($row == 1) ? 2 : 1);
}

$q = "SELECT `module`, `setting`, `name`, `description` FROM `" . OCC_TABLE_CONFIG . "` WHERE `module`!='OC' ORDER BY `module`, `setting`";
$r = ocsql_query($q) or err('Unable to retrieve module settings');

$module = '';
while ($l = ocsql_fetch_assoc($r)) {
	if ($l['module'] != $module) {
		$module = $l['module'];
		print '<tr><th colspan="3">' . safeHTMLstr($OC_modulesAR[$l['module']]['name']) . ' Module' . (oc_moduleActive($l['module']) ? '' : ' - Inactive')  . '</th></tr>';
	}
	print '<tr class="row' . $row . '"><td>' . safeHTMLstr($l['setting']) . '</td><td>' . safeHTMLstr($l['name']) . '</td><td>' . $l['description'] . "</td></tr>\n";
	$row = (($row == 1) ? 2 : 1);
}

print '</table>';

printFooter();

?>