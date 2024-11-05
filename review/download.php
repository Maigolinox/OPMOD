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

$hdr = oc_('Download');
$hdrfn = 2;

beginSession();

$urlBase = OCC_BASE_URL . 'review/download.php?';

if (isset($_GET['pc']) && ($_GET['pc'] == 1)) {
	if ( $_SESSION[OCC_SESSION_VAR_NAME]['acpc'] != "T" ) {
		warn(oc_('Invalid request'), $hdr, $hdrfn);
	}
	$table = OCC_TABLE_PAPERADVOCATE;
	$field = 'advocateid';
	$urlBase .= 'pc=1&';
} else {
	$table = OCC_TABLE_PAPERREVIEWER;
	$field = 'reviewerid';
}

$dir = $OC_configAR['OC_paperDir'];
$formatDBFldName = 'format';
$zipFilePrefix = $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'];
$savePath = '';

// MultiFile hook - verifies reviewers allowed access to file type & updates $dir
if (oc_hookSet('committee-paper-predisplay')) {
	foreach ($OC_hooksAR['committee-paper-predisplay'] as $v) {
		require_once $v;
	}
} elseif (!isset($_GET['t']) || ($_GET['t'] != 1)) {
	warn(oc_('Invalid file type'), $hdr, $hdrfn);
}

if ($_GET['t'] > 1) {
	$formatDBFldName = 'oc_multifile_' . $_GET['t'] . '_format';
}

$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`" . $formatDBFldName . "` FROM `" . OCC_TABLE_PAPER . "`, `" . $table . "` WHERE `" . $table . "`.`" . $field . "`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "' AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . $table . "`.`paperid` AND `" . OCC_TABLE_PAPER . "`.`" . $formatDBFldName . "` IS NOT NULL AND `" . OCC_TABLE_PAPER . "`.`" . $formatDBFldName . "`!=''  ORDER BY `paperid`";

require_once '../include-download.inc';

exit;

?>
