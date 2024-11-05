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

$hdr = oc_('View File');
$hdrfn = 3;

$formatDBFldName = 'format';
$fileDir = $OC_configAR['OC_paperDir'];
$uploadOpen = $OC_statusAR['OC_view_file_open'];

if (oc_hookSet('author-viewfile-preprocess')) {
	foreach ($GLOBALS['OC_hooksAR']['author-viewfile-preprocess'] as $hook) {
		require_once $hook;
	}
}

// Check that we're still open
if (! $uploadOpen) {
	warn(oc_('Files may no longer be viewed'), $hdr, $hdrfn);
}

// Check whether this is a submission
if (isset($_POST['ocaction']) && ($_POST['ocaction'] == "View File")) {
	// Check inputs
	if (!preg_match("/^\d+$/",$_POST['pid']) || empty($_POST['pwd'])) {
		warn(oc_('Submission ID or password entered is incorrect'), $hdr, $hdrfn);
	}

	if (oc_hookSet('author-viewfile-validate')) {
		foreach ($GLOBALS['OC_hooksAR']['author-viewfile-validate'] as $hook) {
			require_once $hook;
		}
	}
	
	// Valid pid/pwd?
	$pq = "SELECT `" . $formatDBFldName . "` AS `format`, `password` FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid`='" . safeSQLstr($_POST['pid']) . "'";
	$pr = ocsql_query($pq) or err(oc_('Unable to view file'), $hdr, $hdrfn);
	if (ocsql_num_rows($pr) != 1) {
		warn(sprintf(oc_('Submission ID or password entered is incorrect'), safeHTMLstr($_POST['pid'])), $hdr, $hdrfn);
	}
	$pl = ocsql_fetch_array($pr);
	if (!oc_password_verify($_POST['pwd'], $pl['password'])) {
		warn(sprintf(oc_('Submission ID or password entered is incorrect'), safeHTMLstr($_POST['pid'])), $hdr, $hdrfn);
	}
	
	$filename = $_POST['pid'] . '.' . $pl['format'];

	if (! oc_displayFile($fileDir . $filename, $pl['format'])) {
		warn(oc_('File does not exist'), $hdr, $hdrfn);
	}
}

printHeader($hdr, $hdrfn);

print '
<form method="POST" enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '" id="paperform">
<input type="hidden" name="ocaction" value="View File" />
<table border=0 cellspacing=0 cellpadding=5>
';

if (oc_hookSet('author-viewfile-formtop')) {
	foreach ($GLOBALS['OC_hooksAR']['author-viewfile-formtop'] as $hook) {
		require_once $hook;
	}
}

print '
<tr><td><strong><label for="pid">' . oc_('Submission ID') . ':</label></strong></td><td><input name="pid" id="pid" size="10" tabindex="2"> ( <a href="email_papers.php" tabindex="5">' . oc_('forgot ID?') . '</a> )</td></tr>
<tr><td><strong><label for="pwd">' . oc_('Password') . ':</label></strong></td><td><input name="pwd" id="pwd" type="password" size="20" maxlength="255" tabindex="3"> ( <a href="reset.php" tabindex="6">' . oc_('forgot password?') . '</a> )</td></tr>
</table>
<p><input type="submit" name="submit" value="' . oc_('View File') . '" class="submit" tabindex="4" /></p>
</form>
';

if (oc_hookSet('author-paper-bottom')) {
	foreach ($GLOBALS['OC_hooksAR']['author-paper-bottom'] as $hook) {
		require_once $hook;
	}
}

printFooter();

?>
