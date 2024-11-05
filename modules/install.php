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

$hdr = 'Install Module';
$hdrfn = 1;

if (isset($_REQUEST['module']) && oc_moduleValid($_REQUEST['module'])) {
	// Check module is not already installed
	if (oc_module_installed($_REQUEST['module'])) {
		err('Module ' . safeHTMLstr($_REQUEST['module']) . ' is already installed', $hdr, $hdrfn);
	}
	
	// Read module info
	require_once $_REQUEST['module'] . '/module.inc';
	
	// Check dir name matches moduleId
	if ($moduleId != $_REQUEST['module']) {
		err('Module ID does not match directory name', $hdr, $hdrfn);
	}
	if (!preg_match("/^o"."c_/",$moduleId)||(preg_match("/^(\w+) /", OCC_LICENSE_TYPE, $m)&& 
		preg_match("/\b" . strtolower($m[1]) . "\b/", $OC_modulesAR[$moduleId]['supported']))) {
	
	// Check for dependencies
	if (!empty($OC_modulesAR[$_REQUEST['module']]['dependencies'])) {
		foreach ($OC_modulesAR[$_REQUEST['module']]['dependencies'] as $m) {
			if (!oc_module_installed($m)) {
				warn('First install and activate other modules this one depends on: ' . implode(', ', $OC_modulesAR[$_REQUEST['module']]['dependencies']), $hdr, $hdrfn);
			}
		}
	}
	
	// Load module schema
	if (is_file($_REQUEST['module'] . '/install-db.sql')) {
		oc_loadSchema($_REQUEST['module'] . '/install-db.sql');
	}
	
	// Module specific install
	if (is_file($_REQUEST['module'] . '/install.inc')) {
		require_once $_REQUEST['module'] . '/install.inc';
	}
	
	// Add to modules table
	$q = "INSERT INTO `" . OCC_TABLE_MODULES . "` SET " .
			"`moduleId` = '" . $_REQUEST['module'] . "', " .
			"`moduleActive` = 0";
	$r = ocsql_query($q) or err('Unable to install module (' . safeHTMLstr(ocsql_error()) . ')',$hdr,$hdrfn);
	
	// Redirect
	session_write_close();
	header("Location: modules.php?" . strip_tags(SID));
	exit;
	}
} else {
	err('Invalid module',$hdr,$hdrfn);
}

?>