<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Check for existing/valid email registration
if ( isset($OC_reviewerFieldAR['email']) && isset($_POST['email']) && validEmail(trim($_POST['email'])) ) {
	if (preg_match("/signup\.php/", $_SERVER['PHP_SELF'])) {	// signup
		$eq = "SELECT `reviewerid` FROM `" . OCC_TABLE_REVIEWER . "` WHERE `email`='" . safeSQLstr(trim($_POST['email'])) . "'";
	} else { // profile update
		$eq = "SELECT `reviewerid` FROM `" . OCC_TABLE_REVIEWER . "` WHERE `email`='" . safeSQLstr(trim($_POST['email'])) . "' AND `reviewerid`!='" . safeSQLstr(((isset($chair) && $chair && isset($rid)) ? $rid : $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'])) . "'";			
	}
	$er = ocsql_query($eq) or err("could not check email address");
	if (ocsql_num_rows($er) != 0) {
		print '<p class="warn" style="text-align: center">' . sprintf(oc_('An account for the email address entered already exists.  Would you like to <a href="%1$s">Sign In</a> or <a href="%2$s">Recover Username</a>?'), 'signin.php', 'email_username.php') . '</p>';
		printFooter();
		exit;
	}
}

// Validate fields
foreach ($GLOBALS['OC_reviewerFieldSetAR'] as $fsid => $fs) {
	foreach ($fs['fields'] as $fid) {
		if (!preg_match("/^(?:username|password\d)$/", $fid)) { // skip validation of special fields
			oc_validateField($fid, $GLOBALS['OC_reviewerFieldAR'], $qfields, $err);
		}
	}
}

// Check account
if (isset($OC_reviewerFieldAR['username']) && oc_fieldEnabled('username', $GLOBALS['OC_reviewerFieldAR'])) {
	if (!isset($_POST['username']) || !preg_match("/^[\p{L}\p{Nd}_\.\-\@]{5,50}$/u", trim($_POST['username']))) {
		//T: $1$d and $2$d = range of characters permitted (e.g., 5 and 50)
		$err .= '<li>' . sprintf(oc_('Username must be between %1$d and %2$d characters: letters, numbers, period, hyphen, @'), 5, 50) . '</li>';
	} elseif (preg_match("/signup\.php/", $_SERVER['PHP_SELF'])) {
		// check that user does not yet have an account; notify & bail if they do
		$uq = "SELECT `reviewerid` FROM `" . OCC_TABLE_REVIEWER . "` WHERE `username`='" . safeSQLstr(oc_strtolower(trim($_POST['username']))) . "'";
		$ur = ocsql_query($uq) or err("could not check username");
		if (ocsql_num_rows($ur) != 0) {
			$err .= '<li>' . sprintf(oc_('Username is already taken; select a different username (or <a href="%s">Sign In</a> if you already registered)'), 'signin.php') . '</li>';
		} else {
			$qfields['username'] = "'" . safeSQLstr(oc_strtolower(trim($_POST['username']))) . "'";
		}
	}
}

// Check password if either field is not empty or it's an original signup
if ( (isset($_POST['password1']) && !empty($_POST['password1']))		// fld pwd1 set
		|| (isset($_POST['password2']) && !empty($_POST['password2']))	// fld pwd 2 set
		|| ( ! isset($OC_cmtEdit) )	// profile update
) {
	if (!isset($_POST['password1']) || !isset($_POST['password2']) || empty($_POST['password1'])) {
		$err .= '<li>' . oc_('Password must be entered twice') . '</li>';
	} elseif ($_POST['password1'] != $_POST['password2']) {
		$err .= '<li>' . oc_('Passwords entered do not match') . '</li>';
	} elseif ( ! preg_match("/^.{8}/",$_POST['password1'])) {
		//T: %d = number of characters
		$err .= '<li>' . sprintf(oc_('Password must be at least %d characters'), 8) . '</li>';
	} else {
		$qfields['password'] = "'" . safeSQLstr(oc_password_hash($_POST['password1'])) . "'";
	}
}

// Update topics
if (isset($qfields['topics'])) {
	if ($qfields['topics'] != 'NULL') {
		if (!preg_match("/^'[\d\,]*'$/", $qfields['topics'])) {
			$err .= '<li>' . sprintf(oc_('%s field does not appear to be valid'), oc_('Topic')) . '</li>'; // should only trigger if validateField above fails
		} else {
			$tfields = explode(',', trim($qfields['topics'], "'"));
		}
	}
	unset($qfields['topics']);
}

// Add datetime to consent
if (isset($qfields['consent']) && preg_match("/^\'.*\'$/", $qfields['consent'])) {
	$qfields['consent'] = rtrim($qfields['consent'], "'") . safeSQLstr(" (" . gmdate('Y-m-d H:i:s') . " UTC)") . "'"; // add datetime to consent field
}

// hook
if (oc_hookSet('committee-profile-validate')) {
	foreach ($GLOBALS['OC_hooksAR']['committee-profile-validate'] as $hook) {
		require_once $hook;
	}
}
