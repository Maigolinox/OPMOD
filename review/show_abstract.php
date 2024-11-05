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

beginSession();

printHeader(oc_('Submission Information'), 2);

if (!preg_match("/^\d+$/",$_REQUEST['pid'])) {
	warn(oc_('Submission ID is invalid'));
}

$pid = $_REQUEST['pid'];

$showReferenceSites = $OC_configAR['OC_includeReferenceSearchLinks'];

// Check for conflict
$conflictAR = getConflicts($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']);
if (oc_inConflict($conflictAR, $pid, $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'])) {
	warn(oc_('You appear to have a conflict with this submission'));
}

$ok = 0;
$blind = true;
// Check that reviewer has permission
if ($OC_configAR['OC_reviewerReadPapers']) {
	$ok = 1;
} else {    // make sure reviewer is assigned
	$q = "SELECT `paperid` FROM `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `paperid`=" . (int) $pid . " AND `reviewerid`=" . (int) $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'];
	$r = ocsql_query($q) or err("Unable to check reviewer permissions");
	if (ocsql_num_rows($r) == 1) {
		$ok = 1;
	}
}
if ($OC_configAR['OC_reviewerSeeAuthors']) {
	$blind = false;
}
// If not ok, check if advocate & has permission
if ($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == "T") {
	if ( ! $ok ) {
		if ($OC_configAR['OC_advocateReadPapers']) {
			$ok = 1;
		} else { // make sure advocate is assigned
			$q = "SELECT `paperid` FROM `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `paperid`=" . (int) $pid . " AND `advocateid`=" . (int) $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'];
			$r = ocsql_query($q) or err("Unable to check advocate permissions");
			if (ocsql_num_rows($r) == 1) {
				$ok = 1;
			}
		}
	}
	if ( $blind && $OC_configAR['OC_advocateSeeAuthors'] ) {
		$blind = false;
	}
}
// If still not ok, show error
if (!$ok) {
	warn(oc_('You do not have permission to retrieve this submission'));
}

require_once OCC_FORM_INC_FILE;
require_once OCC_SUBMISSION_INC_FILE;

if (oc_hookSet('committee-show-abstract-preprocess')) {
	foreach ($GLOBALS['OC_hooksAR']['committee-show-abstract-preprocess'] as $v) {
		require_once $v;
	}
}

// Get sub fields
$q = "SELECT * FROM `" . OCC_TABLE_PAPER . "` WHERE paperid='" . $pid . "'";
$r = ocsql_query($q) or err("Unable to retrieve abstract");
if (ocsql_num_rows($r) != 1) {
	warn(sprintf(oc_('Submission ID %d was not found'), $pid));
}
$spl = ocsql_fetch_array($r);

// Get authors
$oc_authorNum = 0;
$qa = "SELECT *, CONCAT_WS(' ', `name_first`, `name_last`) AS `name` FROM `" . OCC_TABLE_AUTHOR . "` WHERE `paperid`='" . safeSQLstr($pid) . "'";
$ra = ocsql_query($qa) or err("Unable to get " . oc_strtolower(OCC_WORD_AUTHOR) . "s ");
while ($a = ocsql_fetch_array($ra)) {
	$apos = $a['position'];
	foreach ($a as $akey => $aval) {
		if (preg_match("/^(?:paperid|position)$/", $akey)) { continue; }
		$spl[$akey . $apos] = $aval;
	}
}
$oc_authorNum = $apos;

// Get topics
$qt = "SELECT `topicid` FROM `" . OCC_TABLE_PAPERTOPIC . "` WHERE `paperid`='" . safeSQLstr($pid) . "'";
$rt = ocsql_query($qt) or err("Unable to get topics ");
$spl['topics'] = array();
while ($t = ocsql_fetch_array($rt)) {
  $spl['topics'][] = $t['topicid'];
}

// Display fields
print '
<table class="ocfields">
<tr><th>' . oc_('Submission ID') . ':</th><td>' . safeHTMLstr($pid) . '</td></tr>
';

oc_showFieldSet($OC_submissionFieldSetAR, $OC_submissionFieldAR, $spl, $blind, true, $showReferenceSites);

print '</table>';

if (oc_hookSet('committee-show-abstract')) {
	foreach ($GLOBALS['OC_hooksAR']['committee-show-abstract'] as $v) {
		require_once $v;
	}
}

printFooter();

?>
