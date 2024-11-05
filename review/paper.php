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

$dir = $OC_configAR['OC_paperDir'];

$hdr = oc_('File Retrieval');

if (isset($_GET['c']) && ($_GET['c'] == 1)) {
	beginChairSession();
	$hdrfn = 1;   // chair
} else {    // not chair
	beginSession();
	$hdrfn = 2;   //reviewer
}

// Check for valid file name
if (!preg_match("/^(\d+)\.(\w+)$/",$_GET['p'],$matches)) {
	//T: %s = filename (e.g., 1.pdf)
	warn(sprintf(oc_('Invalid submission file: %s'), safeHTMLstr($_GET['p'])), $hdr, $hdrfn);
}

// Extract paper ID
$pid = $matches[1];

// Permission checks for reviewers
if ($hdrfn == 2) {
	// Check for conflict
	$conflictAR = getConflicts($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']);
	if (oc_inConflict($conflictAR, $pid, $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'])) {
		warn(oc_('You appear to have a conflict with this submission'), $hdr, $hdrfn);
	}

	$ok = 0;
	// Check that reviewer has permission
	if ($OC_configAR['OC_reviewerReadPapers']) {
		$ok = 1;
	} else {    // make sure reviewer is assigned
		$q = "SELECT `paperid` FROM `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `paperid`='" . safeSQLstr($pid) . "' AND `reviewerid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "'";
		$r = ocsql_query($q) or err("Unable to check reviewer permissions", $hdr, $hdrfn);
		if (ocsql_num_rows($r) == 1) {
 			$ok = 1;
		}
	}
	// If not ok, check if advocate & has permission
	if (!$ok && ($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == "T")) {
		if ($OC_configAR['OC_advocateReadPapers']) {
			$ok = 1;
		} else { // make sure advocate is assigned
			$q = "SELECT `paperid` FROM `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `paperid`='" . safeSQLstr($pid) . "' AND `advocateid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "'";
			$r = ocsql_query($q) or err("Unable to check advocate permissions", $hdr, $hdrfn);
			if (ocsql_num_rows($r) == 1) {
	  			$ok = 1;
	  		}
		}
	}
	
	// If still not ok, show error
	if (!$ok) {
		warn(oc_('You do not have permission to retrieve this submission'), $hdr, $hdrfn);
	}
} // reviewer

if (oc_hookSet('committee-paper-predisplay')) {
	foreach ($OC_hooksAR['committee-paper-predisplay'] as $v) {
		require_once $v;
	}
}

if (! oc_displayFile($dir . $_GET['p'], $matches[2])) {
	warn(oc_('File does not exist'), $hdr, $hdrfn);
}

exit;
?>
