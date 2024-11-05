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

printHeader(oc_('Review'), 2);

if ( ! $OC_statusAR['OC_rev_signin_open'] || ! $OC_statusAR['OC_reviewing_open']) {
	warn(oc_('This feature is currently disabled'));
}

$showEmailCopy = 2; // 0 = do not display; 1 = display unchecked; 2 = display checked
$showCompletedReview = 1; // 0 = do not display; 1 = display unchecked; 2 = display checked

$useFieldValueForScore = false; // index (1-#) is used by default; setting to true allows values of 0+ (must be integers)

function saveReviewForm($review, $thepid) {
	global $OC_reviewQuestionsAR;
	
	// Check for valid submission
	if (!validToken('ac')) {
		$w = sprintf(oc_('This submission failed our security check, possibly due to you have signed in again, or a third-party having redirected you here.  Below is the information provided.  If you were attempting to submit a review, print this information out or copy/paste it to a new document so it can be re-entered; then <a href="%s">try again</a>.  If the problem persists, please contact the Chair.'), ($_SERVER['PHP_SELF'] . '?pid=' . (is_numeric($_POST['pid']) ? $_POST['pid'] : ''))) . '<div style="color: #000; margin-top: 1em; font-weight: normal;">';
		$OC_reviewQuestionsARkeys = array_keys($OC_reviewQuestionsAR);
		foreach ($_POST as $k => $v) {
			if (($k == 'submit') || ($k == 'token')) { continue; }
			if (in_array($k, $OC_reviewQuestionsARkeys)) {
				$w .= "<br />\n<hr /><br />\n<strong>" . safeHTMLstr($OC_reviewQuestionsAR[$k]['short']) . "</strong> ";
				if ($OC_reviewQuestionsAR[$k]['usekey']) {
					$w .= $OC_reviewQuestionsAR[$k]['values'][$v];
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

	// Email review copy - do it here in case of errors/problems below
	if (isset($_POST['emailcopy']) && ($_POST['emailcopy'] == "1")) {
		// ocIgnore included so poEdit picks up (DB) template translation
		//T: [:sid:] is the numeric submission ID
		$ocIgnoreSubject = oc_('Review of submission [:sid:]');
		//T: [:OC_confName:] is the event name; [:sid:] is the numeric submission ID
		$ocIgnoreBody = oc_('Following is a copy of your review for submission number [:sid:] submitted to [:OC_confName:].  Note that you will receive this email even if an error occurred during submission.

[:fields:]');
		list($mailsubject, $msg) = oc_getTemplate('committee-review');
		$fields = oc_genFieldMessage($GLOBALS['OC_reviewQuestionsFieldsetAR'], $GLOBALS['OC_reviewQuestionsAR'], $_POST);
		$templateExtraAR = array(
			'sid' => $thepid,
			'fields' => $fields
		);
		$mailsubject = oc_replaceVariables($mailsubject, $templateExtraAR);
		$msg = oc_replaceVariables($msg, $templateExtraAR);

		if (oc_hookSet('committee-review-msg')) {
			foreach ($GLOBALS['OC_hooksAR']['committee-review-msg'] as $v) {
				require_once $v;
			}
		}

		if (!sendEmail($review['email'], $mailsubject, $msg)) {
			print '<p class="err">' . oc_('We were unable to send copy of the review via email') . '</p>';
		}
	}

	// Validate fields
	$qfields = array();
	$err = '';
	foreach ($GLOBALS['OC_reviewQuestionsFieldsetAR'] as $fsid => $fs) {
		foreach ($fs['fields'] as $fid) {
			if (isset($GLOBALS['OC_reviewQuestionsAR'][$fid])) {
				oc_validateField($fid, $GLOBALS['OC_reviewQuestionsAR'], $qfields, $err);
			}
		}
	}
	// Check for completion and calculate score
	$score = null;
	if (isset($_POST['completed']) && ($_POST['completed'] == 1)) {
		$completed = 'T';
	} else {
		$completed = 'F';
	}
	foreach ($OC_reviewQuestionsAR as $fid => $far) {
		if (isset($far['score']) && $far['score'] && isset($_POST[$fid]) && preg_match("/^\d+$/", $_POST[$fid])) {
			if (
				$useFieldValueForScore 
				&& isset($far['values']) 
				&& isset($far['values'][$_POST[$fid]]) 
				&& preg_match("/^[0-9]+$/", $far['values'][$_POST[$fid]])
			) {
				$scoreValue = $far['values'][$_POST[$fid]];
			} else {
				$scoreValue = $_POST[$fid];
			}
			if ($score === null) {
				$score = $scoreValue;
			} else {
				$score += $scoreValue;
			}
		}
		if (($completed == 'T') && isset($far['required']) && $far['required'] && !isset($qfields[$fid])) {
			$completed = 'F';
		}
	}

	// Hooks
	if (oc_hookSet('committee-review-validate')) {
		foreach ($GLOBALS['OC_hooksAR']['committee-review-validate'] as $v) {
			require_once $v;
		}
	}
	
	// Error?
	if (!empty($err)) {
		print '<div class="warn">' . oc_('Your review has not been saved.') . ' ' . oc_('Please check the following:') . '<ul>' . $err . '</ul></div><hr />';
		printReviewForm($_POST, $thepid);
		return;
	}

	// Update sessions
	$sfields = array();
	if (isset($qfields['sessions'])) {
		if ($qfields['sessions'] != 'NULL') {
			if (!preg_match("/^'[\d\,]*'$/", $qfields['sessions'])) {
				$err .= '<li>' . sprintf(oc_('%s field does not appear to be valid'), oc_('Session(s)')) . '</li>'; // should only trigger if validation above fails
			} else {
				$sfields = explode(',', trim($qfields['sessions'], "'"));
			}
		}
		unset($qfields['sessions']);
	}

	// Compose sql and update
	$q = "UPDATE `" . OCC_TABLE_PAPERREVIEWER . "` SET `updated`='" . safeSQLstr(date('Y-m-d')) . "', ";
	if ($score !== null) {
		$q .= "`score`=" . (int) $score . ", ";
	}
	foreach ($qfields as $qid => $qval) {
		$q .= "`" . $qid . "`=" . $qval . ", ";
	}
	$q .= "`completed`='" . safeSQLstr($completed) . "' WHERE `paperid`='" . safeSQLstr($thepid) . "' AND `reviewerid`='" . safeSQLstr(safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'])) . "'";
	ocsql_query($q) or err("Unable to submit review");

	// Update papersession
	if (isset($OC_reviewQuestionsAR['sessions'])) {
		$q2 = "DELETE FROM `" . OCC_TABLE_PAPERSESSION . "` WHERE `paperid`='" . safeSQLstr($thepid) . "' AND `reviewerid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "'";
		ocsql_query($q2) or err("Unable to update sessions");
		if (!empty($sfields) && ($sfields != 'NULL')) {
			$q3 = "INSERT INTO `" . OCC_TABLE_PAPERSESSION . "` (`paperid`,`reviewerid`,`topicid`) VALUES";
			foreach ($sfields as $s) {
				$q3 .= " ('" . safeSQLstr($thepid) . "','" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "','" . safeSQLstr($s) . "'),";
			}
			ocsql_query(rtrim($q3, ',')) or err(oc_('Unable to add sessions'));
		}
	}
	
	if (oc_hookSet('committee-review-save')) {
		foreach ($GLOBALS['OC_hooksAR']['committee-review-save'] as $v) {
			require_once $v;
		}
	}

	print '<p>' . oc_('Review has been submitted.') . '</p>';
	if (isset($_POST['completed']) && ($_POST['completed'] == 1) && ($completed == 'F')) {
		print '<p class="warn">' . oc_('However as not all required questions were answered, the review was not marked as completed.') . '</p>';
	}
	print '<p>&#187; <a href="review.php?pid=' . safeHTMLstr($thepid) . '">' . oc_('Return to Review') . '</a></p>';
	//T: Member = Committee Member -- see "Member Home" string
	print '<p>&#187; <a href="reviewer.php">' . oc_('Return to Member home page') . '</a></p>';
}// function saveReviewForm

function printReviewForm($review, $thepid) {
	global $OC_configAR, $OC_reviewQuestionsAR;

	print '<p style="text-align: center"><span style="font-size: 1.05em; font-weight: bold; font-style: italic;">' . safeHTMLstr($review['title']) .  '</span>';
	
	if (isset($review['type']) && !empty($review['type'])) {
		print '<br />(' . safeHTMLstr($review['type']) . ')';
	}
	
	print '<br />' . oc_('Submission ID') . ': ' . safeHTMLstr($thepid);

	if (isset($OC_configAR['OC_reviewerSeeAdvocate']) && $OC_configAR['OC_reviewerSeeAdvocate'] && isset($review['advocate_name']) && !empty($review['advocate_name'])) {
		print '<br />' . oc_('Advocate') . ': <a href="mailto:' . safeHTMLstr($review['advocate_email']) . '">' . safeHTMLstr($review['advocate_name']) . '</a>';
	}

	print '</p>';
	
	$tip = '<hr /><span style="color: #060; font-style: italic">' . oc_("TIP: Use a local text editor to write your review, and then select/copy the information below.  This way, in case of a network outage, you won't lose the review.") . '</span><hr /><br />';
	
	print '
<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" class="ocform ocreviewform">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['actoken'] . '" />
<input type="hidden" name="pid" value="' . safeHTMLstr($thepid) . '">
<input type="hidden" name="title" value="' . safeHTMLstr($review['title']) . '">
<input type="hidden" name="format" value="' . safeHTMLstr($review['format']) . '">
<input type="hidden" name="type" value="' . safeHTMLstr($review['type']) . '">
<input type="hidden" name="ocaction" value="Submit Review" />
	';
	
	if (oc_hookSet('committee-review-fields')) {
		foreach ($GLOBALS['OC_hooksAR']['committee-review-fields'] as $v) {
			require_once $v;
		}
	}

	print $tip;

	oc_displayFieldSet($GLOBALS['OC_reviewQuestionsFieldsetAR'], $GLOBALS['OC_reviewQuestionsAR'], $review);
	
	if (oc_hookSet('committee-review-extra')) {
		foreach ($GLOBALS['OC_hooksAR']['committee-review-extra'] as $v) {
			require_once $v;
		}
	}
	
	if ($GLOBALS['showEmailCopy']) {
		print '<dl><dt><label><input type="checkbox" name="emailcopy" value="1" ' . (($GLOBALS['showEmailCopy'] === 1) ? '' : 'checked') . ' /> ' . oc_('Email me a copy of this review') . '</label></dt><dd><span class="note">' . oc_('Useful for your own record or in case there is some kind of error during updating.') . ' ';
		if ($OC_configAR['OC_ReviewerTimeout'] > 0) {
			print oc_('Note that if your session times out, you may not receive an email; you should log back in right away to recover the review.');
		}
		print "</span></dd></dl>\n";
	}
	
	if ($GLOBALS['showCompletedReview']) {
		print '<dl><dt><label><input type="checkbox" name="completed" value="1"';
		if ((varValue('completed', $review) == "T") || ($GLOBALS['showCompletedReview'] === 2)) { print ' checked'; }
		print '> ' . oc_('I have completed the review') . '</label></dt><dd><span class="note">' . oc_('Check this box when you have finished the review for this submission.  This is used only to track how many outstanding reviews there are.  You will still be able to edit this review after checking this box, until the review deadline date.') . "</span></dd></dl>\n";
	}
	
	print "<br />\n";
	
	if ($thepid != "blank") {
		print '<p><input type="submit" name="submit" class="submit" value="' . oc_('Submit Review') . '"></p>';
	}
	else {
		print '<p>[ ' . oc_('Sample Review Form - Fill in and submit review by clicking the submission title on main reviewer page') . ' ]</p>';
	}
	
	if ($OC_configAR['OC_ReviewerTimeout'] > 0) {
		print '<p class="note">' . oc_('Should your session timeout while filling out this review, log back in right away as we may be able to recover your review.') . '</p>';
	}
	
} // function printReviewForm


if (isset($_POST['ocaction']) && ($_POST['ocaction'] == "Submit Review")) {
	if (!isset($_POST['pid']) || !preg_match("/^\d+$/", $_POST['pid'])) {
		warn('Invalid submission ID');
	}
	$q = "SELECT `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`, `" . OCC_TABLE_REVIEWER . "`.`email` FROM `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`='" . safeSQLstr($_POST['pid']) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
	$thepid = $_POST['pid'];
} else {
	if (!isset($_GET['pid']) || (($_GET['pid'] != 'blank') && !preg_match("/^\d+$/", $_GET['pid']))) {
		warn(oc_('Submission ID is invalid'));
	}
	$q = "SELECT `title`, `format`, `type`, `" . OCC_TABLE_PAPERREVIEWER . "`.* FROM `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`='" . safeSQLstr($_GET['pid']) . "' AND `reviewerid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid`";
	$thepid = $_GET['pid'];
}

require_once OCC_FORM_INC_FILE;
require_once OCC_REVIEW_INC_FILE;

if ($thepid == "blank") {	// display blank form
	$review = array();
	$review['title'] = oc_('Sample Review');
	$review['format'] = "";
	$review['type'] = "";
	printReviewForm($review, 0);
} elseif (!preg_match("/^\d+$/", $thepid)) {
	print '<span class="err">' . oc_('Submission ID is invalid') . '</span><p>';
} else {
	$r = ocsql_query($q) or err("Unable to retrieve submission for review");
	if (ocsql_num_rows($r) == 0) { 
		//T: Use care with href - "mailto" and "subject" should not be translated
		print '<span class="err">' . sprintf(oc_('Either the submission does not exist, or you have not been assigned it for review.  If this is in error, please contact the <a href="mailto:%s?subject=Review error">Chair</a>.'), $OC_configAR['OC_pcemail']) . '</span><p>';
	} else {
		$review = ocsql_fetch_array($r); 

		// remove fields with matching hidesubtypes
		$subtype = varValue('type', $_POST, varValue('type', $review));
		if (!empty($subtype)) {
			foreach ($OC_reviewQuestionsAR as $fid => $far) {
				if (isset($far['hidesubtypes']) && is_array($far['hidesubtypes']) && in_array($subtype, $far['hidesubtypes'])) {
					unset($OC_reviewQuestionsAR[$fid]);
				}
			}
		}
		
		// process action
		if (isset($_POST['ocaction']) && ($_POST['ocaction'] == "Submit Review")) {
			saveReviewForm($review, $thepid); // save form
		} else {
			// Add sessions to review array
			$sq = "SELECT `topicid` FROM `" . OCC_TABLE_PAPERSESSION . "` WHERE `paperid`='" . safeSQLstr($thepid) . "' AND `reviewerid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "'";
			$sr = ocsql_query($sq);
			$review['sessions'] = array();
			while ($sl = ocsql_fetch_array($sr)) { 
				$review['sessions'][] = $sl['topicid'];
			}
			if ( isset($OC_reviewQuestionsAR['sessions']['type']) && ($OC_reviewQuestionsAR['sessions']['type'] == 'radio') && isset($review['sessions'][0]) ) {
				$review['sessions'] = $review['sessions'][0];
			}
			// Add advocate to review array
			if (isset($OC_configAR['OC_reviewerSeeAdvocate']) && $OC_configAR['OC_reviewerSeeAdvocate']) {
				$aq = "SELECT CONCAT_WS(' ', `name_first`, `name_last`) AS `name`, `email` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`='" . safeSQLstr($thepid) . "' AND `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
				$ar = ocsql_query($aq) or err('Unable to retrieve advocate');
				if (ocsql_num_rows($ar) == 1) {
					$al = ocsql_fetch_assoc($ar);
					$review['advocate_name'] = $al['name'];
					$review['advocate_email'] = $al['email'];
				}
			}
			// print form		
			printReviewForm($review, $thepid);
		}
    }
}

printFooter();

?>
