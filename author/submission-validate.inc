<?php
// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$firstBlankAuthor = 0;
$lastAuthor = 0;

// Force contact author = 1
if ($author1contact) {
	$_POST['contactid'] = 1;
}

// validate fields
$emailAddressAR = array();
foreach ($GLOBALS['OC_submissionFieldSetAR'] as $fsid => $fs) {
	if ($fsid != 'fs_authors') { // non-author field
		foreach ($fs['fields'] as $fid) {
			if (!preg_match("/^(?:file|password\d)$/", $fid)) { // skip validation of special fields
				oc_validateField($fid, $GLOBALS['OC_submissionFieldAR'], $qfields, $err);
			}
		}
	} else { // author field
		for ($a=1; $a<=$OC_configAR['OC_authorsMax']; $a++) {
			if (!isset($_POST['name_last' . $a]) || empty($_POST['name_last' . $a])) {
				if ($firstBlankAuthor == 0) { 
					$firstBlankAuthor = $a;
				}
				if ((isset($_POST['name_first' . $a]) && !empty($_POST['name_first' . $a])) 
						|| (isset($_POST['organization' . $a]) && !empty($_POST['organization' . $a])) 
						|| (isset($_POST['email' . $a]) && !empty($_POST['email' . $a]))
				) {
					$err .= '<li>' . sprintf(oc_('Author %d missing last name'), $a) . '</li>';	
				} 
			} else {
				$afields[$a] = array('name_last' => "'" . safeSQLstr(varValue('name_last' . $a, $_POST)) . "'");
				foreach ($fs['fields'] as $fid) {
					if ($fid == 'name_last') { continue; }
					// override required field attribute?
					$requiredOverride = false;
					if ($OC_configAR['OC_authorsRequiredData'] > 0) {
						if (($OC_configAR['OC_authorsRequiredData'] == 1) && ($a != 1)) { // first author
							$requiredOverride = true;
						} elseif (($OC_configAR['OC_authorsRequiredData'] == 2) && ($a != $_POST['contactid'])) { // contact author
							$requiredOverride = true;
						}
					}
					// validate
					oc_validateField($fid, $GLOBALS['OC_submissionFieldAR'], $afields[$a], $err, $a, $requiredOverride);
				}
				$lastAuthor = $a;
				if (isset($_POST['email'.$a]) && !empty($_POST['email'.$a])) {
					$allemails .= $_POST['email'.$a] . ',';
					if (in_array($_POST['email'.$a], $emailAddressAR)) { // duplicate?
						$err .= '<li>' . sprintf(oc_('Author %d email is not unique'), $a) . '</li>';
					}
					$emailAddressAR[$a] = $_POST['email'.$a];
				}
			}	
		}
	}
}
$allemails = rtrim($allemails, ',');

// Additional author checks
if ($lastAuthor == 0) {	// no author info
	$err .= '<li>' . oc_('Authors information missing') . '</li>';
} elseif (($firstBlankAuthor > 0) && ($firstBlankAuthor < $lastAuthor)) {	// blank author - can't skip otherwise ordering will be messed up
	$err .= '<li>' . oc_('One or more author\'s data skipped.  Please enter authors sequentially.') . '</li>';
} elseif (!preg_match("/^\d+$/", $_POST['contactid']) || ($_POST['contactid'] < 1) || ($_POST['contactid'] > $OC_configAR['OC_authorsMax'])) {  // Check that we have a valid contact author & email
	$err .= '<li>' . oc_('Contact author invalid') . '</li>';
} else {
	$contactemail = $_POST['email' . $_POST['contactid']];
	if (!validEmail($contactemail)) {
		$err .= '<li>' . oc_('Contact author email does not seem valid') . '</li>';
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

// Check file
if (isset($OC_submissionFieldAR['file'])) {
	if ( isset($_FILES['file']['error'])					// good upload
		&& ($_FILES['file']['error'] == UPLOAD_ERR_OK) 		// no error
		&& is_uploaded_file($_FILES['file']['tmp_name'])	// legitimate upload
		&& ($_FILES['file']['size'] > 0)					// file not empty
	) { // file uploaded ok
		if (!empty($OC_configAR['OC_fileLimit']) && ($_FILES['file']['size'] > ($OC_configAR['OC_fileLimit'] * 1024 * 1024))) {
			$err .= '<li>' . oc_('File size too large') . '</li>';
		} elseif ( ! isset($_POST['format']) || ! in_array($_POST['format'], $extAR) ) { // invalid format
			$err .= '<li>' . oc_('File format invalid') . '</li>';
		} else {  // upload good
			$fileUploaded = true;
			$errInc .= '<br /><li>' . oc_('Also, re-select the file') . '</li>';  // let user know file must be re-selected if there was another error
			if (oc_hookSet('author-file-validate')) {
				foreach ($GLOBALS['OC_hooksAR']['author-file-validate'] as $hook) {
					require_once $hook;
				}
			}
		}
	} elseif ( isset($_FILES['file']['error']) ) { // notify of error
		switch($_FILES['file']['error']) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$err .= '<li>' . oc_('File size too large') . '</li>';
				break;
			case UPLOAD_ERR_NO_FILE:
				if (isset($OC_submissionFieldAR['file']['required']) && $OC_submissionFieldAR['file']['required']) {  // file required? if not, no error
					$err .= '<li>' . oc_('File missing') . '</li>';
				}
				break;
			default:
				$err .= '<li>' . oc_('File did not upload properly') . '</li>';
				break;
		} 
	}
}

// Check password if either field is not empty or it's an original submission
if ( (isset($_POST['password1']) && !empty($_POST['password1']))
		|| (isset($_POST['password2']) && !empty($_POST['password2'])) 
		|| ( ! isset($_POST['pid']) )
) {
	if (!isset($_POST['password1']) || !isset($_POST['password2']) || empty($_POST['password1'])) {
		$err .= '<li>' . oc_('Password must be entered twice') . '</li>';
	} elseif ($_POST['password1'] != $_POST['password2']) {
		$err .= '<li>' . oc_('Passwords entered do not match') . '</li>';
	} else {
		$qfields['password'] = "'" . oc_password_hash($_POST['password1']) . "'";
	}
}

// Add datetime to consent and strip tags
if (isset($qfields['consent']) && preg_match("/^\'.*\'$/", $qfields['consent'])) {
	$qfields['consent'] = rtrim(strip_tags($qfields['consent']), "'") . safeSQLstr(" (" . gmdate('Y-m-d H:i:s') . " UTC)") . "'";
}

if (oc_hookSet('author-submission-validate')) {
	foreach ($GLOBALS['OC_hooksAR']['author-submission-validate'] as $hook) {
		require_once $hook;
	}
}
