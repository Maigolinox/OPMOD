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

if (!OCC_ADVANCED_CONFIG) {
	$res = array('error' => 'Advanced configuration disabled');
}
elseif (!validToken('chair')) { // Check for valid submission
	$res = array('error' => 'Invalid Token');
}
elseif (isset($_POST['m']) && (($_POST['m'] == 'OC') || in_array($_POST['m'], $OC_activeModulesAR)) && isset($_POST['s']) && array_key_exists($_POST['s'], $OC_configAR) && isset($_POST['v'])) {
	if ($OC_configAR[$_POST['s']] == $_POST['v']) {
		$res = array('error' => 'Setting unchanged');
	} else {
		$q = "UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='" . safeSQLstr(urldecode($_POST['v'])) . "' WHERE `module`='" . safeSQLstr($_POST['m']) . "' AND `setting`='" . safeSQLstr($_POST['s']) . "' LIMIT 1";
		if ($r = ocsql_query($q)) {
			if (ocsql_affected_rows() != 1) {
				$res = array('error' => 'Setting failed to update properly');
			} else {
				$res = array('error' => '', 'success' => 'Setting updated');
			}
		} else {
			$res = array('error' => 'DB Error: ' . safeHTMLstr(ocsql_error()));
		}
	}
} else {
	$res = array('error' => 'Invalid module, setting, or value');
}

echo json_encode($res);

exit;

?>