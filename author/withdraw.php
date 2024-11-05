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
require_once "../include-submissions.inc";

printHeader(oc_('Withdraw Submission'), 3);

// Withdraw allowed?
if (! $OC_statusAR['OC_withdraw_open']) {
	print '<strong>' . oc_('Submission withdraw is not available.') . '</strong><p>';
	printFooter();
	exit;
}

// Is this a post?
if (isset($_POST['ocaction']) && ($_POST['ocaction'] == 'Withdraw Submission')) {
	// Check for paper ID & password
	if (! isset($_POST['pid']) || 
		! preg_match("/^\d+$/", $_POST['pid']) ||
		! isset($_POST['pwd']) || 
		empty($_POST['pwd'])
	) {
		warn(oc_('Submission ID or password entered is incorrect'));
		printFooter();
		exit;
	}

	$pq = "SELECT `" . OCC_TABLE_PAPER . "`.`title`, `" . OCC_TABLE_PAPER . "`.`password`, `" . OCC_TABLE_AUTHOR . "`.`email` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "` WHERE `" . OCC_TABLE_PAPER . "`.`paperid`='" . safeSQLstr($_POST['pid']) . "' AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` AND `" . OCC_TABLE_PAPER . "`.`contactid`=`" . OCC_TABLE_AUTHOR . "`.`position`";
	$pr = ocsql_query($pq) or err("Unable to retrieve submission");
	if (ocsql_num_rows($pr) != 1) {
		warn(oc_('Submission ID or password entered is incorrect')); 
		printFooter();
		exit;
	}
	$pl = ocsql_fetch_array($pr);
	if (!oc_password_verify($_POST['pwd'], $pl['password'])) {
		warn(oc_('Submission ID or password entered is incorrect'));
		printFooter();
		exit;
	}

	// Withdraw submission
	if (withdrawPaper($_POST['pid'], OCC_WORD_AUTHOR)) {
		$mailto = '';
		if ($OC_configAR['OC_emailAuthorRecipients'] == 1) {
			$ar = ocsql_query("SELECT `email` FROM `" . OCC_TABLE_AUTHOR . "` WHERE `paperid`='" . safeSQLstr($_POST['pid']) . "'") or err('Unable to retrieve author email addresses');
			while ($al = ocsql_fetch_assoc($ar)) {
				$mailto .= $al['email'] . ',';
			}
			$mailto = rtrim($mailto, ',');
		}

		deletePaper($_POST['pid'], false);
		print '<p>' . oc_('Your submission has been withdrawn.  If this is not what you intended to do, please contact the Chair.') . '</p>';
		// Notify via email
		// ocIgnore included so poEdit picks up (DB) template translation
		//T: [:sid:] is the numeric submission ID
		$ocIgnoreSubject = oc_('Submission Withdraw - ID [:sid:]');
		$ocIgnoreBody = oc_('The submission below has been withdrawn at the author\'s request.  If you did not intend to withdraw the submission, please reply back.

[:submission:]');
		list($mailsubject, $mailbody) = oc_getTemplate('author-withdraw');
		$templateExtraAR = array(
			'sid' => $_POST['pid'],
			'submission' => oc_('Submission ID') . ': ' . $_POST['pid'] . "\n" . oc_('Title') . ': ' . $pl['title']
		);
		$mailsubject = oc_replaceVariables($mailsubject, $templateExtraAR);
		$mailbody = oc_replaceVariables($mailbody, $templateExtraAR);

		if (empty($mailto)) {
			$mailto = $pl['email'];
		}
		sendEmail($mailto, $mailsubject, $mailbody, $OC_configAR['OC_notifyAuthorWithdraw']);
	} else {
		print '<p>' . oc_('We encountered a problem withdrawing your submission.  Please contact the Chair.') . '</p>';
	}

	printFooter();
	exit;
} // if submit

// display sub id/password form
print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="withdrawform">
<input type="hidden" name="ocaction" value="Withdraw Submission" />
<table border=0 cellspacing=0 cellpadding=5>
<tr><td><strong><label for="pid">' . oc_('Submission ID') . '</label>:</strong></td><td><input name="pid" id="pid" size="10" tabindex="1"> ( <a href="email_papers.php" tabindex="4">' . oc_('forgot ID?') . '</a> )</td></tr>
<tr><td><strong><label for="password">' . oc_('Password') . '</label>:</strong></td><td><input name="pwd" id="password" type="password" tabindex="2" size="20" maxlength="255"> ( <a href="reset.php" tabindex="5">' . oc_('forgot password?') . '</a> )</td></tr>
</table>
<p class="warn">' . oc_('Clicking the button below will result in your submission being withdrawn.') . '</p>
<p><input type="submit" name="submit" value="' . oc_('Withdraw Submission') . '" tabindex="3" class="submit" onclick="return(confirm(\'' . oc_('Proceed with withdrawing submission?') . '\'))" /></p>
</form>
<script language="javascript">
<!--
document.forms[0].elements[0].focus();
// -->
</script>
';

if (oc_hookSet('author-withdraw-bottom')) {
	foreach ($GLOBALS['OC_hooksAR']['author-withdraw-bottom'] as $hook) {
		require_once $hook;
	}
}

printFooter();

exit;

?>
