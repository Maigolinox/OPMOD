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

$uploadDir = $OC_configAR['OC_paperDir'];
$uploadOpen = $OC_statusAR['OC_upload_open'];
$extAR = $OC_configAR['OC_extar'];
$fileNotice = ( isset($OC_configAR['OC_paperFldNote']) ? $OC_configAR['OC_paperFldNote'] : '' );
$formatDBFldName = 'format';

if (OCC_CHAIR_PWD_TRUMPS && isset($_REQUEST['c']) && ($_REQUEST['c'] == 1)) {
	$hdrfn = 1;
	beginChairSession();
	$chair = TRUE;
} else {
	$hdrfn = 3;
	$chair = FALSE;
}

// Print appropriate header
printHeader(oc_('Upload File'), $hdrfn);

if (oc_hookSet('author-upload-preprocess')) {
	foreach ($GLOBALS['OC_hooksAR']['author-upload-preprocess'] as $hook) {
		require_once $hook;
	}
}

if ($chair) { // display back links
	print '<p style="text-align: center"><a href="../chair/show_paper.php?pid=' . safeHTMLstr($_REQUEST['pid']) . '">View This Submission</a> | <a href="../chair/list_papers.php">View All Submissions</a></p><br />';
} elseif (! $uploadOpen) { // Check that we're still open
	warn(oc_('File upload is not available'));
}

// Check whether this is a submission
if (isset($_POST['ocaction']) && ($_POST['ocaction'] == "Upload File")) {
	if ($chair && !validToken('chair')) {
		warn(oc_('Invalid submission'));
	}

	// Check inputs
	if (! isset($_POST['pid']) || ! preg_match("/^\d+$/", $_POST['pid'])) {
		warn(oc_('Submission ID is invalid') . '. <a href="upload.php">' . oc_('Try again') . '</a>');
	} elseif (
		(! $chair && (!isset($_POST['pwd']) || empty($_POST['pwd'])))
		|| (!isset($_FILES['file']['name']) || empty($_FILES['file']['name']))
		|| (!isset($_POST['format']) || !in_array($_POST['format'], $extAR))
	) {
		warn('<form method="post" action="upload.php">' . oc_('Please fill in all fields.') . '  <input type="hidden" name="c" value="' . ($chair ? 1 : 0) . '" /><input type="hidden" name="pid" value="' . safeHTMLstr(varValue('pid', $_POST)) . '" /><input type="submit" value="' . oc_('Try again') . '" /></form>');
	}

	// Set PID to intval in case of leading 0's
	$usepid = intval($_POST['pid']);

	// Retrieve pwd, format, & contact author email
	$pq = "SELECT `" . OCC_TABLE_PAPER . "`.`" . $formatDBFldName . "`, `" . OCC_TABLE_PAPER . "`.`accepted`, `" . OCC_TABLE_PAPER . "`.`password`, `" . OCC_TABLE_AUTHOR . "`.`email` FROM `" . OCC_TABLE_PAPER . "` LEFT JOIN `" . OCC_TABLE_AUTHOR . "` ON (`" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` AND `" . OCC_TABLE_PAPER . "`.`contactid`=`" . OCC_TABLE_AUTHOR . "`.`position`) WHERE `" . OCC_TABLE_PAPER . "`.`paperid`='" . $usepid . "'";
	$pr = ocsql_query($pq) or err("Unable to upload file");
	if (ocsql_num_rows($pr) != 1) {
		warn(oc_('Submission ID or password entered is incorrect'));
	}
	$pl = ocsql_fetch_array($pr);

	// Valid pid/pwd?; check for chair pwd first to save db call
	if (! $chair
		&& !oc_password_verify($_POST['pwd'], $pl['password'])
	) {
		warn(oc_('Submission ID or password entered is incorrect'));
	}
	
	// Was a file successfully loaded
	if (!isset($_FILES['file']['error']) 						// bad upload
			|| $_FILES['file']['error'] 						// error
			|| ! is_uploaded_file($_FILES['file']['tmp_name']) 	// fake upload
			|| ($_FILES['file']['size'] <= 0)					// empty file
	) {
		if (isset($_FILES['file']['error']) 
			&& (
				($_FILES['file']['error'] == UPLOAD_ERR_INI_SIZE) 
				|| ($_FILES['file']['error'] == UPLOAD_ERR_FORM_SIZE)
			)
		) {
			warn(oc_('File size too large'));
		} else {
			warn(sprintf(oc_('The file failed to load.  Please <a href="%1$s">try again</a>.  If the problem persists, contact the <a href="%2$s">Chair</a>'), $_SERVER['PHP_SELF'], 'contact.php'));
		}
	} elseif (!empty($OC_configAR['OC_fileLimit']) && ($_FILES['file']['size'] > ($OC_configAR['OC_fileLimit'] * 1024 * 1024)))	{
		warn(oc_('File size too large'));
	}

	if (oc_hookSet('author-upload-validate')) {
		foreach ($GLOBALS['OC_hooksAR']['author-upload-validate'] as $hook) {
			require_once $hook;
		}
	}
	
	// Delete old file?
	$oldFileName = $uploadDir . $usepid . '.' . $pl[$formatDBFldName];
	oc_deleteFile($oldFileName);

	// Move new file
	$err = 0;
	$newFileName = $uploadDir . $usepid . '.' . $_POST['format'];
	
    // Check whether file uploaded
    if (is_uploaded_file($_FILES['file']['tmp_name'])
		&& oc_saveFile($_FILES['file']['tmp_name'], $newFileName, $_POST['format'])
	) {
		//T: %s = submission ID (number)
		$confirmmsg = sprintf(oc_('Submission ID %s has been uploaded.'), $usepid);

		// Get and update notification template
		// ocIgnore included so poEdit picks up (DB) template translation
		//T: [:sid:] is the numeric submission ID
		$ocIgnoreSubject = oc_('Submission ID [:sid:] file uploaded');
		//T: [:sid:] is the numeric submission ID
		$ocIgnoreBody = oc_('Submission ID [:sid:] has been uploaded.

[:error:]');
		list($mailsubject, $mailbody) = oc_getTemplate('author-upload');
		$templateExtraAR = array(
			'sid' => $usepid,
			'error' => ''
		);

		// Set lastupdate date, and format if needed
		$eq = "UPDATE `" . OCC_TABLE_PAPER . "` SET `lastupdate`='" . safeSQLstr(date("Y-m-d")) . "'";
		// also update format if changed
		if ($_POST['format'] != $pl[$formatDBFldName]) {
			$eq .= ", `" . $formatDBFldName . "`='" . safeSQLstr($_POST['format']) . "'";
		}
		$eq .= " WHERE `paperid`='" . $usepid . "'";
		if ( ! ocsql_query($eq)) {
			$templateExtraAR['error'] = oc_('However, we were unable to update the format.');
			$confirmmsg .= "\n\n" . oc_('However, we were unable to update the format.');
			$err = 1;
		}

		$mailsubject = oc_replaceVariables($mailsubject, $templateExtraAR);
		$mailbody = oc_replaceVariables($mailbody, $templateExtraAR);

		if (oc_hookSet('author-upload-preconfirm')) {
			foreach ($GLOBALS['OC_hooksAR']['author-upload-preconfirm'] as $hook) {
				require_once $hook;
			}
		}

		// Send email confirmation
		$mailto = '';
		if ( $OC_configAR['OC_emailAuthorOnUpload'] && ! $chair) {
			if ($OC_configAR['OC_emailAuthorRecipients'] == 1) {
				$ar = ocsql_query("SELECT `email` FROM `" . OCC_TABLE_AUTHOR . "` WHERE `paperid`='" . safeSQLstr($_POST['pid']) . "'") or err('Unable to retrieve author email addresses');
				while ($al = ocsql_fetch_assoc($ar)) {
					$mailto .= $al['email'] . ',';
				}
				$mailto = rtrim($mailto, ',');
			}
			if (empty($mailto)) {
				$mailto = $pl['email'];
			}
		}

   		sendEmail($mailto, $mailsubject, $mailbody, $OC_configAR['OC_notifyAuthorUpload']);

		if (!$err) {
			print $confirmmsg;
		} else {
			err($confirmmsg);
		}
		
		// log
		oc_logit('submission', 'Submission ID ' . $usepid . ' file upload' . (isset($_POST['oc_multifile_type']) ? (' (MultiFile Type: ' . $_POST['oc_multifile_type'] . ')') : ''));

	} else { // file failed to upload or move properly
		print '<span class="err">' . sprintf(oc_('The file failed to load properly.  Please email it directly to the <a href="mailto:%1$s?subject=%2$s File failed - submission ID %3$s">Chair</a>'), $OC_configAR['OC_pcemail'], $OC_configAR['OC_confName'], $usepid) . '</span>';
	}

	printFooter();
	exit;
}

print '
<form method="POST" enctype="multipart/form-data" action="upload.php" id="uploadform">
<input type="hidden" name="ocaction" value="Upload File" />
';

if ($chair) {
	print '
<input type="hidden" name="c" value="1">
<input type="hidden" name="token" value="' . safeHTMLstr($_SESSION[OCC_SESSION_VAR_NAME]['chairtoken']) . '" />
<input type="hidden" name="pid" value="' . safeHTMLstr($_REQUEST['pid']) . '" />
';
}

print '<table border="0" cellspacing="0" cellpadding="5" aria-live="polite">';

if (oc_hookSet('author-upload-formtop')) {
	foreach ($GLOBALS['OC_hooksAR']['author-upload-formtop'] as $hook) {
		require_once $hook;
	}
}

if (! $chair) {
	print '
<tr id="subid"><td style="font-weight: bold; white-space: nowrap;"><label for="pid">' . oc_('Submission ID') . ':</label></td><td><input name="pid" id="pid" size="10" tabindex="2" value="' . ((isset($_GET['id']) && ctype_digit($_GET['id'])) ? safeHTMLstr($_GET['id']) : '')  . '"> ( <a href="email_papers.php" tabindex="7">' . oc_('forgot ID?') . '</a> )</td></tr>
<tr id="pwd"><td><strong><label for="pwdfld">' . oc_('Password') . ':</label></strong></td><td><input name="pwd" id="pwdfld" type="password" size="20" maxlength="255" tabindex="3"> ( <a href="reset.php" tabindex="8">' . oc_('forgot password?') . '</a> )</td></tr>
';
} else {
	print '
<div style="display: none;"><div id="subid"></div><div id="pwd"></div></div>
';
}

print '
<tr id="filerow"><td valign="top"><strong><label for="file">' . safeHTMLstr(oc_('File')) . ':</label></strong></td><td><input type="file" name="file" id="file" size="30" tabindex="4" /> &nbsp; &nbsp; <strong><label for="format">' . 
//T: File format
oc_('Format') . ':</label></strong>
';

print '<select name="format" id="format" tabindex="5">';
$formatoptions = "";
foreach ($extAR as $fval) {
	$formatoptions .= '<option value="' . $fval . '"> ' . $OC_formatAR[$fval] . '</option>';
}
print $formatoptions;
print "</select><br /><br />\n";

print '
<div class="note2" id="fldnote">' . nl2br($fileNotice) . '</div>
<p class="note">' . sprintf(oc_('File size limit is %s.'), (empty($OC_configAR['OC_fileLimit']) ? $OC_maxFileSize : toMB($OC_configAR['OC_fileLimit'] . 'M'))) . '</p>
</td></tr>
';

if (oc_hookSet('author-upload-formbottom')) {
	foreach ($GLOBALS['OC_hooksAR']['author-upload-formbottom'] as $hook) {
		require_once $hook;
	}
}

print '
</table>
<p>
<div id="sub"><input type="submit" name="subaction" class="submit" value="' . oc_('Upload File') . '" tabindex="6"></div>
</form>
<p>
';

if (oc_hookSet('author-upload-bottom')) {
	foreach ($GLOBALS['OC_hooksAR']['author-upload-bottom'] as $hook) {
		require_once $hook;
	}
}

printFooter();

?>
