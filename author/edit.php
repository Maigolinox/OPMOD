<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$OC_subEdit = true; // flag for designating submission editing

require_once "../include.php";

oc_sendNoCacheHeaders();

$editTimeout = 2; 	// hours

// Cancel edit
if (isset($_GET['ocaction']) && ($_GET['ocaction'] == 'cancel') 
		&& isset($_GET['pid']) && ctype_digit($_GET['pid'])
		&& isset($_GET['edittoken']) && preg_match("/^\w+$/", $_GET['edittoken'])
) {
	ocsql_query("UPDATE `" . OCC_TABLE_PAPER . "` SET `edittoken`=NULL, `edittime`=NULL WHERE `paperid`=" . (int) $_GET['pid'] . " AND `edittoken`='" . safeSQLstr($_GET['edittoken']) . "' LIMIT 1");
	header("Location: " . OCC_BASE_URL);
	exit;
}

if (OCC_CHAIR_PWD_TRUMPS && isset($_REQUEST['c']) && ($_REQUEST['c'] == 1)) {
	$hdrfn = 1;
	beginChairSession();
	$chair = true;
} else {
	$hdrfn = 3;
	$chair = false;
}

$hdr = oc_('Edit Submission');

$showPaper = false; // View (vs Edit) Submission if true

// Edit still allowed?
if (! $chair && ! $OC_statusAR['OC_edit_open']) {
	if ($OC_configAR['OC_authorViewSubIfEditClosed']) {
		$showPaper = true;
		$hdr = oc_('View Submission'); // also used for auth form button
	} else {
		warn(oc_('Submission edits are no longer available.'), $hdr, $hdrfn);
		exit;
	}
}

printHeader($hdr, $hdrfn);

// Is this a post?
if (isset($_POST['ocaction'])) {
	if (! isset($_POST['pid']) || ! preg_match("/^\d+$/", $_POST['pid'])) {
		warn(oc_('Submission ID is invalid'));
	}

	if ($_POST['ocaction'] == 'Edit Submission') {
		// Check password
		if (! $chair && (! isset($_POST['passwordfld']) || empty($_POST['passwordfld']))) {
			warn(oc_('Submission ID or password entered is incorrect'));
			exit;
		}
		
		// verify login and acceptance status if not chair
		if (! $chair) {
			$pq = "SELECT `" . OCC_TABLE_PAPER . "`.`password`, `" . OCC_TABLE_ACCEPTANCE . "`.`accepted` FROM `" . OCC_TABLE_PAPER . "` LEFT JOIN `" . OCC_TABLE_ACCEPTANCE . "` ON (`" . OCC_TABLE_PAPER . "`.`accepted`=`" . OCC_TABLE_ACCEPTANCE . "`.`value`) WHERE `" . OCC_TABLE_PAPER . "`.`paperid`='" . safeSQLstr($_POST['pid']) . "'";
			$pr = ocsql_query($pq) or err(oc_('Unable to retrieve submission'));
			if (ocsql_num_rows($pr) != 1) {
				warn(oc_('Submission ID or password entered is incorrect'));
				exit;
			}
			$pl = ocsql_fetch_assoc($pr);
			if (!oc_password_verify($_POST['passwordfld'], $pl['password'])) {
				warn(oc_('Submission ID or password entered is incorrect'));
				exit;
			}
			unset($_POST['passwordfld']);

			if ( ! $showPaper ) {
				// Edit limited to accepted subs only?
				if (($OC_configAR['OC_editAcceptedOnly'] == 1) && ($pl['accepted'] != 1)) {
					if ($OC_configAR['OC_authorViewSubIfEditClosed']) {
						$showPaper = true;
						print '<p class="warn" style="text-align: center;">' . oc_('Submission edits are no longer available.') . '</p>';
					} else {
						warn(oc_('Submission edits are no longer available.'));
						exit;
					}
				} else {
					// set token
					$token = oc_idGen();
					$pr = ocsql_query("UPDATE `" . OCC_TABLE_PAPER . "` SET `edittoken`='" . safeSQLstr($token) . "', `edittime`='" . safeSQLstr(time()) . "' WHERE `paperid`=" . (int) $_POST['pid'] . " LIMIT 1") or err(oc_('Unable to edit submission') . ' (token)');
				}
			}

			if ($showPaper) {
				$pid = $_POST['pid'];
				require_once 'view.inc';
				printFooter();
				exit;
			}
			
		}
	} elseif ($_POST['ocaction'] != 'Submit Changes') {
		warn(oc_('Invalid request'));
		exit;
	}
	
	if ($chair) { // display back links
		print '<p style="text-align: center"><a href="../chair/show_paper.php?pid=' . urlencode($_POST['pid']) . '">View This Submission</a> | <a href="../chair/list_papers.php">View All Submissions</a></p>';
	} elseif ($_POST['ocaction'] == 'Submit Changes') { 	// check token
		$pq = "SELECT `edittoken`, `edittime` FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid`='" . safeSQLstr($_POST['pid']) . "'";
		$pr = ocsql_query($pq) or err(oc_('Unable to retrieve submission') . ' (tokeninfo)');
		if (ocsql_num_rows($pr) != 1) { err(oc_('Submission ID or password entered is incorrect')); }
		$pl = ocsql_fetch_assoc($pr);
		if (!isset($_POST['edittoken']) 
				|| ($_POST['edittoken'] != $pl['edittoken']) 
				|| ((time() - $pl['edittime']) > (60 * 60 * $editTimeout))
		) {
			warn(sprintf(oc_('There is a %1$d hour timeout for editing the submission.  Please <a href="%2$s">edit submission</a> once again'), $editTimeout, $_SERVER['PHP_SELF']));
			exit;
		}
	}

	// Set number of author fields to display if Submit Changes, else populate $_POST with database fields
	if (isset($_POST['authornum']) && ctype_digit($_POST['authornum'])) {
		$oc_authorNum = $_POST['authornum'];
	} else {
		// get sub
		$anr = ocsql_query("SELECT * FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid`=" . (int) $_POST['pid']) or err("Unable to retrieve submission information");
		if (ocsql_num_rows($anr) != 1) {
			err(oc_('Submission ID or password entered is incorrect'));
		}
		$_POST = array_merge((array)$_POST, ocsql_fetch_assoc($anr));
		
		// get authors
		$authorCount = 0;
		$anr = ocsql_query("SELECT * FROM `" . OCC_TABLE_AUTHOR . "` WHERE `paperid`=" . (int) $_POST['pid'] . " ORDER BY `position`") or err(oc_('Unable to retrieve author(s) information'));
		while ($anl = ocsql_fetch_assoc($anr)) {
			foreach ($anl as $anli => $anlv) {
				if (($anli == 'paperid') || ($anli == 'position')) { continue; }
				$_POST[$anli . $anl['position']] = $anlv;
			}
			$authorCount = $anl['position']; // track highest position
		}
		
		// get topics
		$anr = ocsql_query("SELECT `topicid` FROM `" . OCC_TABLE_PAPERTOPIC . "` WHERE `paperid`=" . (int) $_POST['pid']) or err(oc_('Unable to retrieve topic(s) information'));
		$_POST['topics'] = array();
		while ($anl = ocsql_fetch_assoc($anr)) {
			$_POST['topics'][] = $anl['topicid'];
		}
		
		// set author num to either use min display or actual author count, whichever is greater
		$oc_authorNum = (($authorCount > $OC_configAR['OC_authorsMinDisplay']) ? $authorCount : $OC_configAR['OC_authorsMinDisplay']);
		
		// set token
		if (! $chair) {
			$_POST['edittoken'] = $token;
		}
	}
	
	if (oc_hookSet('author-edit-preprocess')) {
		foreach ($GLOBALS['OC_hooksAR']['author-edit-preprocess'] as $hook) {
			require_once $hook;
		}
	}

	require_once OCC_FORM_INC_FILE;
	require_once OCC_SUBMISSION_INC_FILE;

	// Set non-editable fields to disabled if submissions closed (and it's not Chair)
	if (! $chair && ! $OC_statusAR['OC_submissions_open']) {
		foreach ($OC_submissionFieldAR as $fid => $far) {
			if (isset($far['closeedit']) && ! $far['closeedit']) {
				$OC_submissionFieldAR[$fid]['enabled'] = false;
			}
		}
	}

	// remove consent field if already given
	if (isset($OC_submissionFieldAR['consent'])) {
		$cr = ocsql_query("SELECT `consent` FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid`='" . safeSQLstr($_POST['pid']) . "'") or err('Unable to query consent status');
		$cl = ocsql_fetch_assoc($cr);
		if (!empty($cl['consent'])) {
			unset($OC_submissionFieldAR['consent']);
			foreach ($OC_submissionFieldSetAR as $fsid => $fsar) { // remove from fieldset
				if (in_array('consent', $fsar['fields'])) {
					$OC_submissionFieldSetAR[$fsid]['fields'] = array_diff($OC_submissionFieldSetAR[$fsid]['fields'], array('consent'));
					continue; // field should only be in one fieldset
				}
			}
		}
	}

	// Update topic field?
	if ( isset($OC_submissionFieldAR['topics']['type']) && ($OC_submissionFieldAR['topics']['type'] == 'radio') && isset($_POST['topics']) && is_array($_POST['topics']) && isset($_POST['topics'][0]) ) {
		$_POST['topics'] = $_POST['topics'][0]; // change from array to single value
	}

	// Update password fieldset
	$OC_submissionFieldSetAR['fs_passwords']['fieldset'] = oc_('Change Password');
	$OC_submissionFieldSetAR['fs_passwords']['note'] = oc_('Leave these fields blank if you do not want to change the password');
	$OC_submissionFieldAR['password1']['name'] = oc_('New Password');
	
	// Check whether we're submitting changes
	if ($_POST['ocaction'] == "Submit Changes") {
		if ($chair && !validToken('chair')) {
			warn(oc_('Invalid submission'));
		}

		$err = '';
		$errInc = '';
		$qfields = array();	// fields to insert into submission table
		$afields = array(); // fields to insert into authors table
		$tfields = array(); // fields to insert into topics table
		$fileUploaded = false;
		
		require_once 'submission-validate.inc';
		
		// process if no errors
		if (!empty($err)) {
	        print '<p><span class="err">' . oc_('Please check the following:') . '<ul>' . $err . $errInc . '</ul></span><br /><hr /><br />';
		} else {
			$q = "UPDATE `" . OCC_TABLE_PAPER . "` SET `lastupdate`='" . safeSQLstr(date("Y-m-d")) . "', `edittoken`=NULL, `edittime`=NULL";
			foreach ($qfields as $qid => $qval) {
				$q .= ", `" . $qid . "`=" . $qval;
			}
			$q .= " WHERE `paperid`=" . (int) $_POST['pid'];
			$r = ocsql_query($q) or err(oc_('Unable to update submission'));

			$q = "DELETE FROM `" . OCC_TABLE_AUTHOR . "` WHERE `paperid`=" . (int) $_POST['pid'];
			$r = ocsql_query($q) or err(oc_('Unable to update authors or topics (2)'));
			foreach ($afields as $qid => $qar) {
				$q = "INSERT INTO `" . OCC_TABLE_AUTHOR . "` SET `paperid`=" . (int) $_POST['pid'] . ", `position`=" . (int) $qid;
				foreach ($qar as $qqid => $qqval) {
					$q .= ", `" . $qqid . "`=" . $qqval;
				}
				$r = ocsql_query($q) or err(oc_('Unable to add one or more authors or topics.'));
			}
	
			if (!empty($tfields)) {
				$q = "DELETE FROM `" . OCC_TABLE_PAPERTOPIC . "` WHERE `paperid`='" . safeSQLstr($_POST['pid']) . "'";
				$r = ocsql_query($q) or err(oc_('Unable to update topics'));
				$q = "INSERT INTO `" . OCC_TABLE_PAPERTOPIC . "` (`paperid`,`topicid`) VALUES";
				foreach ($tfields as $t) {
					$q .= " (" . safeSQLstr($_POST['pid']) . ",$t),";
				}
				$r = ocsql_query(rtrim($q, ',')) or err(oc_('Unable to add topics'));
			}
	
			// Get and update notification template
			// ocIgnore included so poEdit picks up (DB) template translation
			//T: [:sid:] is the numeric submission ID
			$ocIgnoreSubject = oc_('Submission Update ID [:sid:]');
			$ocIgnoreBody = '[:fields:]'; // don't bother with translation
			$fields = oc_genFieldMessage($OC_submissionFieldSetAR, $OC_submissionFieldAR, $_POST);
			list($mailsubject, $mailbody) = oc_getTemplate('author-edit');
			$templateExtraAR = array(
				'sid' => $_POST['pid'],
				'fields' => (oc_('Submission ID') . ': ' . $_POST['pid'] . "\n\n" . $fields)
			);
			$mailsubject = oc_replaceVariables($mailsubject, $templateExtraAR);
			$mailbody = oc_replaceVariables($mailbody, $templateExtraAR);

			// Set up confirmation
			$confirmmsg = '<p><strong>' . safeHTMLstr(oc_('The submission has been updated.  Below is the information submitted.')) . '</strong></p><pre>' . safeHTMLstr($fields) . '</pre>';
			if (! $chair) {
				$confirmmsg .= '<p><strong>' . sprintf(oc_('A copy has also been emailed to the contact author.  If you notice any problems or do <em>not</em> receive the email within 24 hours, please contact the <a href="%s">Chair</a>.'), 'contact.php') . '</strong></p>';
			}
			
			if (oc_hookSet('author-edit-save')) {
				foreach ($GLOBALS['OC_hooksAR']['author-edit-save'] as $hook) {
					require_once $hook;
				}
			}

			//confirm it
			print $confirmmsg;
			if (! $chair) {
				if (($OC_configAR['OC_emailAuthorRecipients'] == 1) && !empty($allemails)) {
					$mailto = $allemails;
				} else {
					$mailto = $contactemail;
				}				
				sendEmail($mailto, $mailsubject, $mailbody, $OC_configAR['OC_notifyAuthorEdit']);
 			}

			printFooter();

			// log
			oc_logit('submission', 'Submission ID ' . $_POST['pid'] . ' edited' . ($chair ? ' by Chair' : '') . '.  Title: ' . $_POST['title']);
	
			exit;
  		} // else no $err
	} // if Submit Changes
	
	
	// Display form
if (isset($OC_configAR['OC_paperSubNote']) && !empty($OC_configAR['OC_paperSubNote'])) {
    print '<div>' . (preg_match("/\<(?:p|br) ?\/?\>/", oc_($OC_configAR['OC_paperSubNote'])) ? oc_($OC_configAR['OC_paperSubNote']) : nl2br(oc_($OC_configAR['OC_paperSubNote']))) . '</div><p><hr /></p>';
}

	print '
<form method="post" id="editsub" enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" class="ocform">
<input type="hidden" name="ocaction" value="Submit Changes" />
<input type="hidden" name="pid" value="' . safeHTMLstr($_POST['pid']) . '">
<input type="hidden" name="authornum" id="authornum" value="' . $oc_authorNum . '" />
';

	if ($chair) {
		print '
<input type="hidden" name="c" value="1">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
';
	} else {
		print '
<input type="hidden" name="edittoken" value="' . safeHTMLstr($_POST['edittoken']) . '" />
';
	}

	oc_displayFieldSet($OC_submissionFieldSetAR, $OC_submissionFieldAR, $_POST);

	if (oc_hookSet('author-edit-fields')) {
		foreach ($GLOBALS['OC_hooksAR']['author-edit-fields'] as $hook) {
			require_once $hook;
		}
	}

	if (! $chair) {
		print '
<div id="oc_submit_emailConfirmOuter">
<div id="oc_submit_emailConfirmInner" aria-live="polite">
<p class="note">' . oc_('Emails will be sent to:') . '</p>
<p id="oc_submit_emailConfirm"></p>
</div>
</div>
';
	}

	print '<p><input type="submit" id="submit" name="submit" value="' . oc_('Submit Changes') . '" class="submit" />';
	
	if (! $chair) {
		print '
&nbsp; &nbsp; &nbsp; &nbsp;
<a href="' . $_SERVER['PHP_SELF'] . '?ocaction=cancel&pid=' . urlencode($_POST['pid']) . '&edittoken=' . urlencode(varValue('edittoken', $_POST)) . '&c=' . ($chair ? 1 : 0) . '">' . oc_('Cancel Changes') . '</a>
';
	}
	
	print '</p>
<span id="processing" style="position: relative; visibility: hidden;">' . oc_('Processing...') . '</span>
</form>
<script type="text/javascript">
oc_setupProcessingForm("editsub");
';

	if (! $chair) {
		print '
document.getElementById("editsub").addEventListener("change", function (evt) { oc_updateSubmitEmailAddresses(evt, true, ' . $OC_configAR['OC_emailAuthorRecipients'] . '); });
oc_updateSubmitEmailAddresses(null, true, ' . $OC_configAR['OC_emailAuthorRecipients'] . ');
';
	}

	print '</script>';
	
	printFooter();

	exit;

} // if Submission

// display login form by default
print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="editform">
<input type="hidden" name="ocaction" value="Edit Submission" />
<table border=0 cellspacing=0 cellpadding=5>
<tr><td><strong><label for="pid">' . oc_('Submission ID') . '</label>:</strong></td><td><input name="pid" id="pid" size="10" tabindex="1"> ( <a href="email_papers.php" tabindex="4">' . oc_('forgot ID?') . '</a> )</td></tr>
<tr><td><strong><label for="passwordfld">' . oc_('Password') . '</label>:</strong></td><td><input name="passwordfld" id="passwordfld" type="password" tabindex="2" size="20" maxlength="255"> ( <a href="reset.php" tabindex="5">' . oc_('forgot password?') . '</a> )</td></tr>
</table>
<p><input type="submit" name="submit" class="submit" value="' . $hdr . '" tabindex="3" /></p>
</form>
' .
(
	$showPaper ? 
		''
	:
		'<p class="note">' . sprintf(oc_('There is a %d hour limit to complete updates'), $editTimeout) . '</p>'
) . '
<script language="javascript">
<!--
document.forms[0].elements[0].focus();
// -->
</script>
';

if (oc_hookSet('author-edit-bottom')) {
	foreach ($GLOBALS['OC_hooksAR']['author-edit-bottom'] as $hook) {
		require_once $hook;
	}
}

printFooter();

exit;

?>
