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

beginChairSession();

printHeader('Create Submission', 1);

$err = '';
if (isset($_POST['submit']) && ($_POST['submit'] == 'Create Submission')) {
	// Check for valid submission
	if (!validToken('chair')) {
			warn('Invalid submission');
	}
	// Validate fields
	if (!isset($_POST['title']) || !preg_match("/\p{L}/u", $_POST['title'])) {
		$err .= '<li>Title must be entered</li>';
	}
	if (!isset($_POST['name_last']) || !preg_match("/\p{L}/u", $_POST['name_last'])) {
		$err .= '<li>Last Name must be entered</li>';
	}
	if (!isset($_POST['email']) || !validEmail($_POST['email'])) {
		$err .= '<li>Email is not valid</li>';
	}
	if (!isset($_POST['password1']) || !isset($_POST['password2']) || empty($_POST['password1'])) {
		$err .= '<li>' . oc_('Password must be entered twice') . '</li>';
	} elseif ($_POST['password1'] != $_POST['password2']) {
		$err .= '<li>' . oc_('Passwords entered do not match') . '</li>';
	}
	if (empty($err)) {
		$q = "INSERT INTO `" . OCC_TABLE_PAPER . "` SET " .
			"`title`='" . safeSQLstr($_POST['title']) . "', " .
			"`password`='" . safeSQLstr(oc_password_hash($_POST['password1'])) . "', " .
			"`contactid`=1, " .
			"`altcontact`=' '," .
			"`submissiondate`='" . safeSQLstr(date('Y-m-d')) . "'";
		$r = ocsql_query($q) or err('Unable to create submission record.');
		$pid = ocsql_insert_id() or err('unable to get submission ID');

		$q = "INSERT INTO `" . OCC_TABLE_AUTHOR . "` SET " .
			"`paperid`='" . safeSQLstr($pid) . "', " .
			"`position`=1, " .
			"`name_first`='" . safeSQLstr(varValue('name_first', $_POST)) . "', " .
			"`name_last`='" . safeSQLstr($_POST['name_last']) . "', " .
			"`email`='" . safeSQLstr($_POST['email']) . "'";
		if ($r = ocsql_query($q)) {
			if (preg_match("/^(.*)\/chair\/create_sub\.php/", $_SERVER['PHP_SELF'], $match)) {
				$url = 'http' . ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) ? 's' : '') . '://' . safeHTMLstr($_SERVER['SERVER_NAME']) . (ctype_digit($_SERVER['SERVER_PORT']) && (($_SERVER['SERVER_PORT'] != '80')) ? (':' . $_SERVER['SERVER_PORT']) : '') . $match[1] . '/author/edit.php';
			} elseif (!empty($OC_configAR['OC_confURL'])) {
				$url = $OC_configAR['OC_confURL'];
			} else {
				$url = '';
			}
			$subject = urlencode($OC_configAR['OC_confName']) . ' Submission Information';
			$body = 'A submission entry to ' . $OC_configAR['OC_confName'] . ' has been created for you.  Please use the following information to edit your submission' . (empty($url) ? '' : (' at ' . $url)) . '.

	Submission ID: ' . $pid . '
	Password: ' . $_POST['password1'];
			print '<p>Submission ID ' . $pid . ' successfully created.</p><p><a href="mailto:' . rawurlencode($_POST['email']) . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body) . '">Notify ' . safeHTMLstr(oc_strtolower(OCC_WORD_AUTHOR)) . '</a></p>';
			printFooter();
			exit;
		} else {
			ocsql_query("DELETE FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid`=" . (int) $pid . " LIMIT 1");
			err('Unable to add ' . safeHTMLstr(oc_strtolower(OCC_WORD_AUTHOR)) . ' to submission');
		}
	}
}

if (!empty($err)) {
	print '<p class="err">Please correct the following items.  Note that the password fields must be re-entered.<ul>' . $err . '</ul></p><hr />';
}

print '
<p>This form allows you to create a new submission record.  It is intended to permit late submissions once New Submissions have been closed.  Upon creating the submission record, provide the ' . oc_strtolower(OCC_WORD_AUTHOR) . ' with the submission ID and password, and instruct them to edit the submission.  An email link will be provided upon submission of this form for contacting the ' . oc_strtolower(OCC_WORD_AUTHOR) . ' with this information.</p>

<form method="post" action="' . $_SERVER['PHP_SELF'] . '" class="ocform">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<fieldset>
<div class="field"><label for="title">Submission Title:</label><input name="title" id="title" size="60" maxlength="1000" value="' . varValue('title', $_POST, '', true) . '" /></div>
<div class="field"><label for="name_first">Contact First Name:</label><input name="name_first" id="name_first" size="60" maxlength="60" value="' . varValue('name_first', $_POST, '', true) . '" /></div>
<div class="field"><label for="name_last">Contact Last Name:</label><input name="name_last" id="name_last" size="60" maxlength="40" value="' . varValue('name_last', $_POST, '', true) . '" /></div>
<div class="field"><label for="email">Contact Email:</label><input name="email" id="email" size="60" maxlength="100" value="' . varValue('email', $_POST, '', true) . '" /></div>
<div class="field"><label for="password1">Submission Password:</label><input name="password1" id="password1" type="password" size="60" value="" /></div><div class="field"><label for="password2">Re-enter Password:</label><input name="password2" id="password2" type="password" size="60" value="" /></div>
</fieldset>
<p><input type="submit" name="submit" value="Create Submission" class="submit" /></p>
</form>
';

printFooter();

?>
