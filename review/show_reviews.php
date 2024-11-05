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
require_once OCC_REVIEW_INC_FILE;

beginSession();

printHeader(oc_('Reviews'), 2);

if (!isset($_GET['pid']) || !preg_match("/^\d+$/",$_GET['pid'])) {
	warn(oc_('Submission ID is invalid'));
}

// Warn if conflict
$conflictAR = getConflicts($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']);
if (oc_inConflict($conflictAR, $_GET['pid'], $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'])) {
	warn(oc_('You appear to have a conflict with this submission'));
}

// Check whether review assigned/completed?
$assigned = 0;
$completed = 0;
$q = "SELECT `paperid`, `score`, `completed` FROM `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `paperid`='" . safeSQLstr($_GET['pid']) . "' AND `reviewerid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "'";
$r = ocsql_query($q) or err("Unable to check reviewer permissions");
if (ocsql_num_rows($r) == 1) {
  	$assigned = 1;
  	$l = ocsql_fetch_array($r);
  	if ($l['score'] && ($l['completed'] == 'T')) { $completed = 1; }
}

$ok = 0;
// Check that reviewer has permission
if (!$assigned && $OC_configAR['OC_reviewerSeeOtherReviews'] && $OC_configAR['OC_reviewerReadPapers']) {
	$ok = 1;
} elseif ($assigned && $OC_configAR['OC_reviewerSeeAssignedReviews']) {    // make sure reviewer is assigned
	if (!$OC_configAR['OC_reviewerCompleteBeforeSAR'] || $completed) {     // and doesn't hinge on completed review
		$ok = 1;
	}
}
// If not ok, check if advocate & has permission
if (!$ok && $_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == "T") {
	if ($OC_configAR['OC_advocateSeeOtherReviews'] && $OC_configAR['OC_advocateReadPapers']) {
		$ok = 1;
	} else { // make sure advocate is assigned
		$q = "SELECT `paperid` FROM `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `paperid`='" . safeSQLstr($_GET['pid']) . "' AND `advocateid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "'";
		$r = ocsql_query($q) or err("Unable to check advocate permissions");
		if (ocsql_num_rows($r) == 1) {
 			$ok = 1;
		}
	}
}
if (!$ok) {
	warn(oc_('You do not have permission to see the reviews for this submission'));
}

// Get/display sub info
$sr = ocsql_query("SELECT `title`, `type` FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid`='" . safeSQLstr($_GET['pid']) . "'") or err('Unable to retrieve submission type');
$sl = ocsql_fetch_assoc($sr);
print '<p style="text-align: center"><span style="font-size: 1.05em; font-weight: bold; font-style: italic;">' . safeHTMLstr($sl['title']) . '</span><br />' . sprintf(oc_('Submission ID %s'), safeHTMLstr($_GET['pid']));
if (!empty($sl['type'])) {
	$subtype = $sl['type'];
	print '<br />(' . safeHTMLstr($subtype) . ')';
} else {
	$subtype = '';
}
print '</p>';

if ($OC_configAR['OC_reviewerSeeOtherReviewers'] && $assigned && ($emailList = getPaperReviewersEmail($_GET['pid']))) {
	print '<p style="text-align: center"><a href="mailto:' . $emailList . '">' . oc_('Email Reviewers') . '</a></p>';
}

// Display reviews
$q = "SELECT `" . OCC_TABLE_PAPERREVIEWER . "`.*, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) as `name` FROM `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`='" . safeSQLstr($_GET['pid']) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
$r = ocsql_query($q) or err("Unable to get information");
if (ocsql_num_rows($r) == 0) {
	warn(oc_('No reviews found'));
}
displayReviews($_GET['pid'], $r, $subtype);

printFooter();

?>
