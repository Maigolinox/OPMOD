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

printHeader(oc_('Advocate'), 2);

if ( ! $OC_statusAR['OC_pc_signin_open'] || ! $OC_statusAR['OC_advocating_open']) {
	warn(oc_('This feature is currently disabled'));
}

// Advocate?
if ($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] != "T") {
	warn(oc_('You are not listed as being on the program committee'));
	exit;
}

// Valid PID?
if (isset($_POST['submit'])) {
	if (!isset($_POST['pid']) || !preg_match("/^\d+$/",$_POST['pid'])) {
		warn(oc_('Submission ID is invalid'));
	} else {
		$pid = $_POST['pid'];
	}
} elseif (!isset($_GET['pid']) || !preg_match("/^\d+$/",$_GET['pid'])) {
	warn(oc_('Submission ID is invalid'));
} else {
	$pid = $_GET['pid'];
}

// Make sure advocate is assigned paper and get info
$q3 = "SELECT `title`, `type` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `" . OCC_TABLE_PAPER . "`.`paperid`='" . safeSQLstr($pid) . "' AND `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` AND `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`='" . $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'] . "'";
$r3 = ocsql_query($q3) or err("Unable to get submission info");
if (ocsql_num_rows($r3) != 1) { // bail if not found
	warn('Invalid request');
	exit;
}
$subinfo = ocsql_fetch_array($r3);

require_once OCC_FORM_INC_FILE;
require_once OCC_ADVOCATE_INC_FILE;
require_once OCC_REVIEW_INC_FILE; // used for displaying reviews

function printAdvocateForm($recommendation, $pid, $subinfo) {
	print '<p style="text-align: center"><span style="font-size: 1.05em; font-weight: bold; font-style: italic;">' . safeHTMLstr($subinfo['title']) .  '</span><br />' . oc_('Submission ID') . ': ' . $pid;
	
	if (isset($subinfo['type']) && !empty($subinfo['type'])) {
		print '<br />(' . safeHTMLstr($subinfo['type']) . ')<br />';
	}
	
	print '
<br />
<form method="POST" ACTION="' . $_SERVER['PHP_SELF'] . '" class="ocform ocreviewform">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['actoken'] . '" />
<input type="hidden" name="pid" value="' . safeHTMLstr($pid) . '">
<input type="hidden" name="ocaction" value="Submit Recommendation" />
';

	if (oc_hookSet('committee-advocate-fields')) {
		foreach ($GLOBALS['OC_hooksAR']['committee-advocate-fields'] as $v) {
			require_once $v;
		}
	}

	oc_displayFieldSet($GLOBALS['OC_advocateQuestionsFieldsetAR'], $GLOBALS['OC_advocateQuestionsAR'], $recommendation);
	
	if (oc_hookSet('committee-advocate-extra')) {
		foreach ($GLOBALS['OC_hooksAR']['committee-advocate-extra'] as $v) {
			require_once $v;
		}
	}
	
	print '
<input type="submit" name="submit" class="submit" value="' . oc_('Submit Recommendation') . '">
</form>
';

	// Display Reviews
	print '<p style="text-align: center; border-top: 2px solid #000; font-size: 11pt; font-weight: bold; text-align: center; margin-top: 2em; padding-top: 1em;">Reviews</p>';
	if ($emailList = getPaperReviewersEmail($pid)) {  // email reviewers link
		print '<p style="text-align: center;">(<a href="mailto:' . $emailList . '">' . oc_('Email Reviewers') . '</a>)</p>';
	}

	$q = "SELECT `" . OCC_TABLE_PAPERREVIEWER . "`.*, CONCAT_WS(' ', `" . OCC_TABLE_REVIEWER . "`.`name_first`, `" . OCC_TABLE_REVIEWER . "`.`name_last`) as `name`, `" . OCC_TABLE_REVIEWER . "`.`email` FROM `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`='" . safeSQLstr($pid) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` ORDER BY `score`, `reviewerid`";
	$r = ocsql_query($q) or err("Unable to get information");
	if (ocsql_num_rows($r)==0) { 
		print '<p>' . oc_('No reviews found') . ' (2)</p>';
	} else {
		displayReviews($pid, $r, $subinfo['type']);
	}
}

// submission?
if (isset($_POST['ocaction']) && ($_POST['ocaction'] == "Submit Recommendation")) {
	// Check for valid submission
	if (!validToken('ac')) {
		$w = sprintf(oc_('This submission failed our security check, possibly due to you have signed in again, or a third-party having redirected you here.  Below is the information provided.  If you were attempting to submit a review, print this information out or copy/paste it to a new document so it can be re-entered; then <a href="%s">try again</a>.  If the problem persists, please contact the Chair.'), ($_SERVER['PHP_SELF'] . '?pid=' . (is_numeric($_POST['pid']) ? $_POST['pid'] : ''))) . '<div style="color: #000; margin-top: 1em; font-weight: normal;">';
		$OC_advocateQuestionsARkeys = array_keys($OC_advocateQuestionsAR);
		foreach ($_POST as $k => $v) {
			if (($k == 'submit') || ($k == 'token')) { continue; }
			if (in_array($k, $OC_advocateQuestionsARkeys)) {
				$w .= "<br />\n<hr /><br />\n<strong>" . safeHTMLstr($OC_advocateQuestionsAR[$k]['short']) . "</strong> ";
				if ($OC_advocateQuestionsAR[$k]['usekey']) {
					$w .= $OC_advocateQuestionsAR[$k]['values'][$v];
				} else {
					$w .= safeHTMLstr($v);
				}
			} else {
				$w .= "<br />\n<hr /><br />\n<strong>" . safeHTMLstr($k) . ":</strong> " . safeHTMLstr($v);
			}
		}
		$w .= '<hr /></div>';
		warn($w);
	}

	// Validate fields
	$qfields = array();
	$err = '';
	foreach ($GLOBALS['OC_advocateQuestionsFieldsetAR'] as $fsid => $fs) {
		foreach ($fs['fields'] as $fid) {
			oc_validateField($fid, $GLOBALS['OC_advocateQuestionsAR'], $qfields, $err);
		}
	}

	// Hooks
	if (oc_hookSet('committee-advocate-validate')) {
		foreach ($GLOBALS['OC_hooksAR']['committee-advocate-validate'] as $v) {
			require_once $v;
		}
	}
	
	// Error?
	if (!empty($err)) {
		print '<div class="warn">' . oc_('Please check the following:') . '<ul>' . $err . '</ul></div><hr />';
		printAdvocateForm($_POST, $pid, $subinfo);
		return;
	}

	// Compose sql and update
	$q = "UPDATE `" . OCC_TABLE_PAPERADVOCATE . "` SET ";
	foreach ($qfields as $qid => $qval) {
		$q .= "`" . $qid . "`=" . $qval . ",";
	}
	$q = rtrim($q, ',');
	$q .= " WHERE `paperid`='" . safeSQLstr($pid) . "' AND `advocateid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "'";
	ocsql_query($q) or err("Unable to submit recommendation");


	if (oc_hookSet('committee-advocate-save')) {
		foreach ($GLOBALS['OC_hooksAR']['committee-advocate-save'] as $v) {
			require_once $v;
		}
	}


	print '<p>' . oc_('Recommendation has been submitted.') . '</p>';
	print '<p>&#187; <a href="advocate.php?pid=' . $pid . '">' . oc_('Return to Recommendation') . '</a></p>';
	//T: Member = Committee Member -- see "Member Home" string
	print '<p>&#187; ' . sprintf('<a href="%s">Return to Member home page</a>', 'reviewer.php') . '</p>';
	printFooter();
	exit;
}


// Retrieve advocate recommendation
$advq = "SELECT * FROM `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `paperid`='" . safeSQLstr($pid) . "' AND `advocateid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "'";
$advr = ocsql_query($advq) or err("Unable to get advocate info");
$recommendation = ocsql_fetch_array($advr);

// Display form
printAdvocateForm($recommendation, $pid, $subinfo);

printFooter();

?>
