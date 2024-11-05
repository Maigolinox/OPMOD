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

$hdr = 'Download';
$hdrfn = 1;

beginChairSession();

$dir = $OC_configAR['OC_paperDir'];
$formatDBFldName = 'format';
$zipFilePrefix = 'c';
$urlBase = OCC_BASE_URL . 'chair/download.php?';
$savePath = '1/';

// Update dir/formatfld?
if (oc_hookSet('download-setup')) {
	call_user_func($GLOBALS['OC_hooksAR']['download-setup'][0], $_GET['t']);
}

if (isset($_GET['acc']) && ($_GET['acc']==1)) { // accpted only?
	$urlBase .= 'acc=1&';
	$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`" . $formatDBFldName . "` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_ACCEPTANCE . "` WHERE `". OCC_TABLE_PAPER . "`.`accepted`=`" . OCC_TABLE_ACCEPTANCE . "`.`value` AND `" . OCC_TABLE_ACCEPTANCE . "`.`accepted`=1 AND `" . OCC_TABLE_PAPER . "`.`" . $formatDBFldName . "` IS NOT NULL AND `" . OCC_TABLE_PAPER . "`.`" . $formatDBFldName . "`!='' ORDER BY `paperid`";
} else {
	$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`" . $formatDBFldName . "` FROM `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_PAPER . "`.`" . $formatDBFldName . "` IS NOT NULL AND `" . OCC_TABLE_PAPER . "`.`" . $formatDBFldName . "`!='' ORDER BY `paperid`";
}

require_once '../include-download.inc';

exit;

?>
