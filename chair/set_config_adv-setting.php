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
elseif (isset($_POST['m']) && (($_POST['m'] == 'OC') || in_array($_POST['m'], $OC_activeModulesAR)) && isset($_POST['s']) && array_key_exists($_POST['s'], $OC_configAR)) {
	$q = "SELECT * FROM `" . OCC_TABLE_CONFIG . "` WHERE `module`='" . safeSQLstr(urldecode($_POST['m'])) . "' AND `setting`='" . safeSQLstr(urldecode($_POST['s'])) . "'";
	if ($r = ocsql_query($q)) {
		if (ocsql_num_rows($r) != 1) {
			$res = array('error' => 'Setting not found');
		} else {
			$res = array(
				'error' => '',
				'setting' => ocsql_fetch_assoc($r)
			);
		}
	} else {
		$res = array('error' => 'DB Error: ' . safeHTMLstr(ocsql_error()));
	}
} else {
	$res = array('error' => 'Invalid module or setting');
}

echo json_encode($res);

exit;

?>