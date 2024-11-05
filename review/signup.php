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

$hdr = oc_('Committee Signup');
$hdrfn = 3;

if (isset($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'])) {
	warn(sprintf(oc_('You already appear to be signed into a committee account.  Please <a href="%s">sign out</a> first before trying to create a new account.'), 'signout.php'), $hdr, $hdrfn);
	printFooter();
	exit;
}

oc_sendNoCacheHeaders();

require_once OCC_FORM_INC_FILE;
require_once OCC_COMMITTEE_INC_FILE;

if (oc_hookSet('committee-signup-pre')) {
	   foreach ($GLOBALS['OC_hooksAR']['committee-signup-pre'] as $hook) {
			   require_once $hook;
	   }
}

// Verify keycode
$programKeycodeAR = explode(',', $OC_configAR['OC_keycode_program']);
$reviewerKeycodeAR = explode(',', $OC_configAR['OC_keycode_reviewer']);
if ($OC_configAR['OC_paperAdvocates'] && isset($_POST['keycode']) && in_array($_POST['keycode'], $programKeycodeAR)) {
	printHeader(oc_('Program Committee Signup'), 3);
	if (! $OC_statusAR['OC_pc_signup_open']) { 
			warn(oc_('Committee sign-up is closed'));
	}
	$committee = "program";
	$committeeTrans = oc_('Program Committee');
	$oncommittee = "T";
	$signUpNotice = $OC_configAR['OC_programSignUpNotice'];
}
elseif (isset($_POST['keycode']) && in_array($_POST['keycode'], $reviewerKeycodeAR)) {
	printHeader(oc_('Reviewer Committee Signup'), 3);
	if (! $OC_statusAR['OC_rev_signup_open']) {
		warn(oc_('Committee sign-up is closed'));
	}
	$committee = "reviewer";
	$committeeTrans = oc_('Review Committee');
	$oncommittee = "F";
	$signUpNotice = $OC_configAR['OC_reviewerSignUpNotice'];
} else {
	warn(oc_('The keycode entered is incorrect.  Please click on the Back button to try again.  If you are still unable to access the committee sign up page, please contact the Chair.'), $hdr, $hdrfn);
}

if (isset($_POST['ocaction']) && ($_POST['ocaction'] == "Sign Up")) {
	$err = '';
	$qfields = array();
	$tfields = array();

	require_once 'committee-validate.inc';

	if (!empty($err)) {
		print '<div class="warn">' . oc_('Please check the following:') . '<ul>' . $err . '</ul></div>';
	} else { // let's submit
		$q = "INSERT INTO `" . OCC_TABLE_REVIEWER . "` SET `onprogramcommittee`='" . $oncommittee . "', `signupdate`='" . safeSQLstr(date("Y-m-d")) . "'";
		foreach ($qfields as $qid => $qval) {
			$q .= ", `" . $qid . "`=" . $qval;
		}
		$r = ocsql_query($q) or err("unable to submit form");
		$rid = ocsql_insert_id() or err("unable to get reviewer id");

	    // add topic(s)
		if (!empty($tfields)) {
			$q = "INSERT INTO `" . OCC_TABLE_REVIEWERTOPIC . "` (`reviewerid`,`topicid`) VALUES";
			foreach ($tfields as $t) {
				$q .= " ($rid,$t),";
			}
			$r = ocsql_query(rtrim($q, ',')) or err("unable to add reviewer topic, but account created ");
		}

		$confirmmsg = '
<p>' . oc_('Thank you for signing up.   We have emailed you a confirmation with your information.') . '</p>
<p>' . 
//T: Use care when translating the href - "mailto" and "subject" are not translated.  %2%s = event short name (e.g., CONF2012)
sprintf(oc_('If you have any questions, please contact the <a href="mailto:%1$s?subject=%2$s Committee Sign Up Question">Chair</a>'), $OC_configAR['OC_pcemail'], $OC_configAR['OC_confName']) . '</p>
';

		// ocIgnore included so poEdit picks up (DB) template translation
		$ocIgnoreSubject = oc_('Committee Signup');
		//T: [:OC_confName:] is the event name; [:committee:] will be either "Program Committee" or "Review Committee"; [:OC_pcemail:] is an email address
		$ocIgnoreBody = oc_('Thank you for signing up for the [:OC_confName:] [:committee:].  Below is the information you provided.  If you have any questions, please contact [:OC_pcemail:] or reply to this email.

[:fields:]');

		list($mailsubject, $mailbody) = oc_getTemplate('committee-signup');
		$fields = oc_genFieldMessage($OC_reviewerFieldSetAR, $OC_reviewerFieldAR, $_POST);
		$templateExtraAR = array(
			'committee' => $committeeTrans,
			'fields' => oc_('Reviewer ID') . ': ' . $rid . "\n\n" . $fields
		);
		$mailsubject = oc_replaceVariables($mailsubject, $templateExtraAR);
		$mailbody = oc_replaceVariables($mailbody, $templateExtraAR);

		if (oc_hookSet('committee-signup-add')) {
			foreach ($GLOBALS['OC_hooksAR']['committee-signup-add'] as $hook) {
				require_once $hook;
			}
		}

		print $confirmmsg;

		sendEmail($_POST['email'], $mailsubject, $mailbody, $OC_configAR['OC_notifyReviewerSignup']);

		printFooter();
		exit;
	}
}

// Display notice & form
if (!empty($signUpNotice)) {
	print '<div>' . (preg_match("/\<(?:p|br) ?\/?\>/", $signUpNotice) ? oc_($signUpNotice) : nl2br(oc_($signUpNotice))) . '</div><p><hr /></p>';
}

print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" class="ocform" id="cmtform">
<input type="hidden" name="keycode" value="' . safeHTMLstr(varValue('keycode', $_POST)) . '">
<input type="hidden" name="ocaction" value="Sign Up" />
';

oc_displayFieldSet($OC_reviewerFieldSetAR, $OC_reviewerFieldAR, $_POST);

if (oc_hookSet('committee-signup-fields')) {
	foreach ($GLOBALS['OC_hooksAR']['committee-signup-fields'] as $hook) {
		require_once $hook;
	}
}

print '
<div id="oc_submit_emailConfirmOuter">
<div id="oc_submit_emailConfirmInner" aria-live="polite">
<p class="note">' . oc_('The confirmation email will be sent to:') . '</p>
<p id="oc_submit_emailConfirm"></p>
</div>
</div>

<p><input type="submit" name="submit" value="' . oc_('Sign Up') . '" class="submit" /></p></form>

<script type="text/javascript">
document.getElementById("cmtform").addEventListener("change", function (evt) { oc_updateSubmitEmailAddresses(evt); });
oc_updateSubmitEmailAddresses(null);
</script>
';

printFooter();

?>
