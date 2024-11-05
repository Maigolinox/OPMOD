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

$formatDBFldName = 'format';
$uploadDir = $OC_configAR['OC_paperDir'];
$extAR = $OC_configAR['OC_extar'];

beginChairSession();

printHeader("Set File Format",1);

if (oc_hookSet('chair-set_format-preprocess')) {
	foreach ($GLOBALS['OC_hooksAR']['chair-set_format-preprocess'] as $hook) {
		require_once $hook;
	}
}

print '<p style="text-align: center"><a href="list_paper_dir.php">List Files Directory</a></p>';

$format = $extAR[0];
if (isset($_POST['submit']) && ($_POST['submit'] == "Set Format")) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}
	// Validate fields
	if (!preg_match("/^\d+$/",$_POST['id'])) {  // check for valid paper ID format
		$e = "Invalid submission ID";
	} elseif (!in_array($_POST['format'], $extAR)) {  // check for valid paper format
		$e = "File format not in list of accepted formats";
	} else {
		// check paper exists
		$q = "SELECT `" . $formatDBFldName . "` FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid`='" . safeSQLstr($_POST['id']) . "'";
		$r = ocsql_query($q) or err("Unable to retrieve submission ID " . $_POST['id']);
		if (ocsql_num_rows($r) != 1) {
			$e = "Submission ID not found";
		} else {
			// rename file?  only if file name w/new ext does not exist (but w/old ext does)
			$l = ocsql_fetch_array($r);
			if (in_array($l[$formatDBFldName], $extAR)) {
				$oldFileName = $uploadDir .  $_POST['id'] . '.' . $l[$formatDBFldName];
				$newFileName = $uploadDir .  $_POST['id'] . '.' . $_POST['format'];
				if (!oc_isFile($newFileName) && oc_isFile($oldFileName)) {
					oc_renameFile($oldFileName, $newFileName) or err("Unable to update file name");
				} else {
					print '<p class="warn">Failed to update file name</p>';
				}
			}
			// update format in db
			$q = "UPDATE `" . OCC_TABLE_PAPER . "` SET `" . $formatDBFldName . "`='" . safeSQLstr($_POST['format']) . "' WHERE `paperid`='" . safeSQLstr($_POST['id']) . "'";
			ocsql_query($q) or err("Unable to update submission format");
			print '<p class="note">File format has been updated</p>';
		}
	}
}

if (!empty($e)) { print '<p class="warn">' . $e . "</p>\n"; }

print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . safeHTMLstr($_SESSION[OCC_SESSION_VAR_NAME]['chairtoken']) . '" />

<table border="0" cellspacing="10" cellpadding="0" style="margin: 0 auto">
';

if (oc_hookSet('chair-set_format-formtop')) {
	foreach ($GLOBALS['OC_hooksAR']['chair-set_format-formtop'] as $hook) {
		require_once $hook;
	}
}

print '
<tr id="subid">
<td><strong>Submission ID:</strong></td>
<td><input name="id" size="4" value="' . safeHTMLstr(varValue('id', $_POST)) . '" /></td>
</tr>
<tr id="formatrow">
<td><strong>Format:</strong></td>
<td><select name="format" id="format">';

foreach ($extAR as $format) {
	print '<option value="' . safeHTMLstr($format) . '">' . safeHTMLstr($OC_formatAR[$format]) . '</option>';
}

print '
</select></td>
</tr>
<tr><td>&nbsp;</td><td style="padding-top: 1em"><input type="submit" name="submit" id="sub" class="submit" value="Set Format" /></td></tr>
</table>

</form>

<p style="text-align: center" class="note">This will set the submission\'s file format in the database.<br />
Also, if a file with the new extension does not already exist,<br />the file is renamed from the old format extension to the new.</p>
';

printFooter();
?>
