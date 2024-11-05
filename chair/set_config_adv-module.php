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
elseif (isset($_POST['m']) && (($_POST['m'] == 'OC') || in_array($_POST['m'], $OC_activeModulesAR))) {
	$q = "SELECT `setting` FROM `" . OCC_TABLE_CONFIG . "` WHERE `module`='" . safeSQLstr(urldecode($_POST['m'])) . "' ORDER BY `setting`";
	if ($r = ocsql_query($q)) {
		if (ocsql_num_rows($r) == 0) {
			$res = array('error' => 'Module has no configuration settings');
		} else {
			$res = array('error' => '', 'settings' => array());
			while ($l = ocsql_fetch_assoc($r)) {
				if (preg_match("/^OC_hide/", $l['setting'])) { continue; }
				$res['settings'][] = $l['setting'];
			}
		}
	} else {
		$res = array('error' => 'DB Error: ' . safeHTMLstr(ocsql_error()));
	}
} else {
	$res = array('error' => 'Invalid module');
}

echo json_encode($res);

exit;

?>