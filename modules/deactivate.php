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

$hdr = 'Deactivate Module';
$hdrfn = 1;

if (isset($_REQUEST['module']) 	&& oc_moduleValid($_REQUEST['module'])) {
	if (!isset($OC_modulesAR[$_REQUEST['module']]['allowdeactivate']) || $OC_modulesAR[$_REQUEST['module']]['allowdeactivate']) {
		$q = "UPDATE `" . OCC_TABLE_MODULES . "` SET `moduleActive`=0 WHERE `moduleId`='" . $_REQUEST['module'] . "' LIMIT 1";
		ocsql_query($q) or err('Unable to deactivate module',$hdr,$hdrfn);
		
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