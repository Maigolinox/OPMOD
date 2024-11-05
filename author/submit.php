<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$OC_subNew = true; // flag for designating new submission

require_once "../include.php";
 
oc_sendNoCacheHeaders();

// Set number of author fields to display
if (isset($_POST['authornum']) && ctype_digit($_POST['authornum'])) {
	$oc_authorNum = $_POST['authornum'];
} else {
	$oc_authorNum = $OC_configAR['OC_authorsMinDisplay'];
}

printHeader(oc_('Submission'), 3);

// Check whether cfp still open
if (! $OC_statusAR['OC_submissions_open'] || ((defined('OCC_LICENSE_EXPIRES')) && (strtotime(OCC_LICENSE_EXPIRES) < time()))) {
	print '<p class="warn">' . oc_('Submissions are closed.') . '</p>';
	printFooter();
	exit;
}

// File upload settings
$uploadDir = $OC_configAR['OC_paperDir'];
$extAR = $OC_configAR['OC_extar'];
$fileNotice = ( isset($OC_configAR['OC_paperFldNote']) ? oc_($OC_configAR['OC_paperFldNote']) : '' );
$formatDBFldName = 'format';
$fileFldName = 'File';

if (oc_hookSet('author-submit-preprocess')) {
	foreach ($GLOBALS['OC_hooksAR']['author-submit-preprocess'] as $hook) {
		require_once $hook;
	}
}

require_once OCC_FORM_INC_FILE;
require_once OCC_SUBMISSION_INC_FILE;

// Check whether this is a submission
if (isset($_POST['submit'])) {
	$err = '';
	$errInc = '';
	$qfields = array();	// fields to insert into submission table
	$afields = array(); // fields to insert into authors table
	$tfields = array(); // fields to insert into topics table
	$fileUploaded = false;
	
	require_once 'submission-validate.inc';
	
	// errors?
	if (!empty($err)) {
        print '<p><span class="err">' . oc_('Please check the following:') . '<ul>' . $err . $errInc . '</ul></span><br /><hr /><br />';
		// remove uploaded file?
		if (isset($_FILES['file']['tmp_name']) && is_file($_FILES['file']['tmp_name'])) {
			unlink($_FILES['file']['tmp_name']);
		}
	} else {
		// Check that paper hasn't been submitted yet; if it has notify author and bail
		$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "` WHERE `" . OCC_TABLE_PAPER . "`.`title`='" . safeSQLstr($_POST['title']) . "' AND `" . OCC_TABLE_AUTHOR . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` AND `" . OCC_TABLE_AUTHOR . "`.`position`=`" . OCC_TABLE_PAPER . "`.`contactid` AND `" . OCC_TABLE_AUTHOR . "`.`name_last`='" . safeSQLstr($_POST['name_last'.$_POST['contactid']]) . "'";
		$r = ocsql_query($q) or err(oc_('Unable to verify whether submission has already been made'));
		if (ocsql_num_rows($r) > 0) {
			print '<p><span class="err">' . oc_('This submission appears to already have been made; please check your email for a confirmation. ');
			if ($OC_statusAR['OC_edit_open']) {
				print sprintf(oc_('You may review or edit the submission <a href="%s">here</a>.'), 'edit.php') . '  ';
			}
			print oc_('Please contact the Chair with any questions.') . '</span></p>';
			printFooter();
			exit;
		}
		
		$backupMsg = '';

		do {
			$new_id = rand(100, 9999);
			$result = mysqli_query($conn, "SELECT 1 FROM paper WHERE paperid = $new_id");
		} while (mysqli_num_rows($result) > 0);

		// add paper
		$q = "INSERT INTO `" . OCC_TABLE_PAPER . "` SET `paperid`='". $new_id ."', `submissiondate`='" . safeSQLstr(date("Y-m-d")) . "', `lastupdate`='" . safeSQLstr(date("Y-m-d")) . "'";
		foreach ($qfields as $qid => $qval) {
			$q .= ", `" . $qid . "`=" . $qval;
		}
		
		$r = ocsql_query($q) or err(oc_('unable to process submission'));

        $backupMsg .= "$q\n\n";

		// get paper ID
		// $pid = ocsql_insert_id() or err(oc_('unable to get submission ID'));
		$pid = $new_id;
	    // add authors
		foreach ($afields as $qid => $qar) {
			$q = "INSERT INTO `" . OCC_TABLE_AUTHOR . "` SET `paperid`=" . (int) $pid . ", `position`=" . (int) $qid;
			foreach ($qar as $qqid => $qqval) {
				$q .= ", `" . $qqid . "`=" . $qqval;
			}
			$r = ocsql_query($q) or err(oc_('unable to add one or more authors, but submission added.  Please edit submission.'));
			$backupMsg .= "$q\n\n";
		}
	
	    // add topic(s)
		if (!empty($tfields)) {
			$q = "INSERT INTO `" . OCC_TABLE_PAPERTOPIC . "` (`paperid`,`topicid`) VALUES";
			foreach ($tfields as $t) {
				$q .= " ($pid,$t),";
			}
			$r = ocsql_query(rtrim($q, ',')) or err(oc_('unable to add submission topic, but submission and authors added'));
			$backupMsg .= "$q\n\n";
		}

		if (!empty($OC_configAR['OC_subBackupEmail'])) {
			sendEmail($OC_configAR['OC_subBackupEmail'], "Submission ID $pid SQL", $backupMsg);
		}

		$formFields = oc_('Submission ID') . ": " . $pid . "\n\n" . oc_genFieldMessage($OC_submissionFieldSetAR, $OC_submissionFieldAR, $_POST);

		// confirm it
		$confirmmsg = '';
		
		// File Uploaded?
		if ($fileUploaded) {
			$fileName = $uploadDir . $pid . '.' . $_POST['format'];
			if (oc_saveFile($_FILES['file']['tmp_name'], $fileName, $_POST['format'])) {
				$formFields .= "\n\n" . oc_('File') . ': ' . oc_('uploaded') . "\n"; // confirm to user
				// update format
				$fq = "UPDATE `" . OCC_TABLE_PAPER . "` SET `" . $formatDBFldName . "`='" . safeSQLstr($_POST['format']) . "' WHERE `paperid`='" . $pid . "' LIMIT 1";
				ocsql_query($fq);  // note no error check
			} else {
				$formFields .= "\n\n" . oc_('File') . ':' . oc_('NOT uploaded') . "\n"; // confirm to user
				$confirmmsg .= '<p class="warn">' . sprintf(oc_('Your file failed to load properly.  Please try <a href="%s">uploading just the file</a> or contact the Chair.'), 'upload.php') . '</p>';
			}
		}

		if (isset($OC_configAR['OC_subConfirmNotice'])) {
			if (preg_match("/\<(?:p|br) ?\/?\>/", $OC_configAR['OC_subConfirmNotice'])) { // HTML?
				$confirmmsg .= oc_($OC_configAR['OC_subConfirmNotice']);
			} else {
				$confirmmsg .= nl2br(oc_($OC_configAR['OC_subConfirmNotice']));
			}
		} else {
			//T: [:code:] should be left untranslated
			$confirmmsg .= oc_('<p><strong>Thank you for your submission. Your submission ID number is [:sid:]. Please write this number down and include it in any communications with us.</strong></p>

<p><strong>Below is the information submitted. We have also emailed a copy to the submission contact. If you notice any problems or do <em>not</em> receive the email within 24 hours, please contact us.</strong></p>

<p>[:formfields:]</p>');
		}
		
		// Get and update notification template
		// ocIgnore included so poEdit picks up (DB) template translation
		//T: [:sid:] is the numeric submission ID
		$ocIgnoreSubject = oc_('Submission ID [:sid:]');
		//T: [:OC_confName:] is the event name
		$ocIgnoreBody = oc_('Thank you for your submission to [:OC_confName:].  Below is a copy of the information submitted for your records.

[:fields:]');
		list($mailsubject, $mailbody) = oc_getTemplate('author-submit');
		$templateExtraAR = array(
			'sid' => $pid,
			'fields' => $formFields
		);
		$mailsubject = oc_replaceVariables($mailsubject, $templateExtraAR);
		$mailbody = oc_replaceVariables($mailbody, $templateExtraAR);

		if (oc_hookSet('author-submit-save')) {
			foreach ($GLOBALS['OC_hooksAR']['author-submit-save'] as $hook) {
				require_once $hook;
			}
		}
		
		// Set up confirmation
		$confirmmsg = preg_replace("/\[:sid:\]/", $pid, $confirmmsg);
		$confirmmsg = preg_replace("/\[:formfields:\]/", '<br />' . nl2br(safeHTMLstr($formFields)), $confirmmsg);
		
		print $confirmmsg;

		if (($OC_configAR['OC_emailAuthorRecipients'] == 1) && !empty($allemails)) {
			$mailto = $allemails;
		} else {
			$mailto = $contactemail;
		}
		
		sendEmail($mailto, $mailsubject, $mailbody, $OC_configAR['OC_notifyAuthorSubmit']);
	
		printFooter();

		// log
		oc_logit('submission', 'Submission ID ' . $pid . ' made.  Title: ' . $_POST['title']);

		if (oc_hookSet('author-submit-postsave')) {
			foreach ($GLOBALS['OC_hooksAR']['author-submit-postsave'] as $hook) {
				require_once $hook;
			}
		}
		
		exit;
	} // else no errors
}

if (isset($OC_configAR['OC_paperSubNote']) && !empty($OC_configAR['OC_paperSubNote'])) {
	print '<div>' . (preg_match("/\<(?:p|br) ?\/?\>/", oc_($OC_configAR['OC_paperSubNote'])) ? oc_($OC_configAR['OC_paperSubNote']) : nl2br(oc_($OC_configAR['OC_paperSubNote']))) . '</div><p><hr /></p>';
}

print '
<form method="post" enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" class="ocform" id="makesub">
<input type="hidden" name="authornum" id="authornum" value="' . $oc_authorNum . '" />
';

oc_displayFieldSet($OC_submissionFieldSetAR, $OC_submissionFieldAR, $_POST);

if (oc_hookSet('author-submit-fields')) {
	foreach ($GLOBALS['OC_hooksAR']['author-submit-fields'] as $hook) {
		require_once $hook;
	}
}

print '
<p class="note">' . oc_('Please check over your entries, making sure everything is filled out.  When ready, click on the Make Submission button below once.') . '</p>
<div id="oc_submit_emailConfirmOuter">
<div id="oc_submit_emailConfirmInner" aria-live="polite">
<p class="note">' . oc_('The confirmation email will be sent to:') . '</p>
<p id="oc_submit_emailConfirm"></p>
</div>
</div>
<p><input type="submit" name="submit" id="submit" value="' . oc_('Make Submission') . '" class="submit" /></p>
<span id="processing" style="position: relative; visibility: hidden;">' . oc_('Processing...') . '</span>
</fieldset>
</form>
<script type="text/javascript">
oc_setupProcessingForm("makesub");
document.getElementById("makesub").addEventListener("change", function (evt) { oc_updateSubmitEmailAddresses(evt, true, ' . $OC_configAR['OC_emailAuthorRecipients'] . '); });
oc_updateSubmitEmailAddresses(null, true, ' . $OC_configAR['OC_emailAuthorRecipients'] . ');
</script>
';


printFooter();

?>
