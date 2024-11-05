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

$hdr = 'Activate Module';
$hdrfn = 1;

if (isset($_REQUEST['module']) && oc_moduleValid($_REQUEST['module'])) {
	$q = "UPDATE `" . OCC_TABLE_MODULES . "` SET `moduleActive`=1 WHERE `moduleId`='" . $_REQUEST['module'] . "' LIMIT 1";
	ocsql_query($q) or err('Unable to activate module',$hdr,$hdrfn);
	
	// Redirect
	session_write_close();
	header("Location: modules.php?" . strip_tags(SID));
	exit;
} else {
	err('Invalid module',$hdr,$hdrfn);
}

?>