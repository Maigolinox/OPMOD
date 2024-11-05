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

$hdr = 'Uninstall Module';
$hdrfn = 1;

if (isset($_REQUEST['module']) && oc_moduleValid($_REQUEST['module'])) {
	if (!isset($OC_modulesAR[$_REQUEST['module']]['allowuninstall']) || $OC_modulesAR[$_REQUEST['module']]['allowuninstall']) {
		// Check module is installed
		if (!oc_module_installed($_REQUEST['module'])) {
			err('Module ' . safeHTMLstr($_REQUEST['module']) . ' is not installed',$hdr,$hdrfn);
		}
	
		// Module specific uninstall
		if (is_file($_REQUEST['module'] . '/uninstall.inc')) {
			require_once $_REQUEST['module'] . '/uninstall.inc';
		}
		
		// Unload module schema
		if (is_file($_REQUEST['module'] . '/uninstall-db.sql')) {
			oc_loadSchema($_REQUEST['module'] . '/uninstall-db.sql');
		}
		
		// Delete from modules table
		$q = "DELETE FROM `" . OCC_TABLE_MODULES . "` WHERE `moduleId` = '" . $_REQUEST['module'] . "' LIMIT 1";
		$r = ocsql_query($q) or err('Unable to uninstall module (' . safeHTMLstr(ocsql_error()) . ')',$hdr,$hdrfn);
		
		// Redirect
		session_write_close();
		header("Location: modules.php?" . strip_tags(SID));
		exit;
	} else {
		warn('Module may not be deactivated',$hdr,$hdrfn);
	}
} else {
	err('Invalid module',$hdr,$hdrfn);
}

?>
