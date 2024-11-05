<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$OC_translate = false; // do not translate

$hdr = 'Export Reviews';
$hdrfn = 1;

require_once '../include.php';

beginChairSession();

require_once 'export.inc';

require_once OCC_FORM_INC_FILE;
require_once OCC_REVIEW_INC_FILE;

$skip = 1;
$scope = 'reviews';	// scope of export for filename

$fieldAR = array(
	'paperid' => 'Submission ID',
	'reviewerid' => 'Reviewer ID',
	'title' => 'Submission Title',
	'name' => 'Reviewer Name',
	'score' => 'Score'
);
foreach ($OC_reviewQuestionsAR as $k => $v) {
	$fieldAR[$k] = $v['short'];
}

// value needs special handling as it's stored in DB with multiple values in a single field
if (isset($fieldAR['value'])) {
	unset($fieldAR['value']);
	$fieldAR['_value'] = 'Value';
}

// session needs special handling as it's stored in seaprate table
if (isset($fieldAR['sessions'])) {
	unset($fieldAR['sessions']);
	$fieldAR['_sessions'] = 'Session(s)';
}

$fieldAR['completed'] = 'Review Completed (True/False)';

// Default list of checked fields
$checkedFieldAR = array_keys($fieldAR);

// Include extra fields
if (oc_hookSet('chair-export_reviews-fields')) {
	foreach ($OC_hooksAR['chair-export_reviews-fields'] as $v) {
		require_once $v;
	}
}

if (isset($_POST['submit']) && ($_POST['submit'] == "Generate File") && isset($_POST['fields']) && !empty($_POST['fields'])) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission', $hdr, $hdrfn);
	}
	// Verify Fields
	$fieldARkeys = array_keys($fieldAR);
	foreach ($_POST['fields'] as $f) {
		if (!in_array($f,$fieldARkeys)) {
			err('Invalid field name selection', $hdr, $hdrfn);
		}
	}
	
	// Init extra field AR
	$extraAR = array();

	// List of fields to export
	$exportFieldsAR = $_POST['fields'];

	// Get extra fields data
	//    value
	if (isset($fieldAR['_value'])) {
		$q = "SELECT `paperid`, `reviewerid`, `value` FROM `" . OCC_TABLE_PAPERREVIEWER . "`";
		$r = ocsql_query($q) or err('Unable to retrieve value data for export', $hdr, $hdrfn);
		while ($l = ocsql_fetch_assoc($r)) {
			if (empty($l['value'])) { continue; }
			$val = '';
			$vAR = explode(",", $l['value']);
			foreach ($vAR as $v) {
				$val .= $OC_reviewQuestionsAR['value']['values'][$v] . "; ";
			}
			$extraAR[$l['paperid'] . '-' . $l['reviewerid']]['_value'] = rtrim($val, "; ");
		}
	}
	//    sessions
	if (isset($fieldAR['_sessions'])) {
		$q = "SELECT `" . OCC_TABLE_PAPERSESSION . "`.`paperid`, `" . OCC_TABLE_PAPERSESSION . "`.`reviewerid`, `" . OCC_TABLE_TOPIC . "`.`topicname`, `" . OCC_TABLE_TOPIC . "`.`short` FROM `" . OCC_TABLE_PAPERSESSION . "`, `" . OCC_TABLE_TOPIC . "` WHERE `" . OCC_TABLE_PAPERSESSION . "`.`topicid`=`" . OCC_TABLE_TOPIC . "`.`topicid`";
		$r = ocsql_query($q) or err('Unable to retrieve session data for export', $hdr, $hdrfn);
		while ($l = ocsql_fetch_assoc($r)) {
			if (isset($extraAR[$l['paperid'] . '-' . $l['reviewerid']]['_sessions'])) {
				$extraAR[$l['paperid'] . '-' . $l['reviewerid']]['_sessions'] .= ',' . useTopic($l['short'], $l['topicname']);
			} else {
				$extraAR[$l['paperid'] . '-' . $l['reviewerid']]['_sessions'] = useTopic($l['short'], $l['topicname']);
			}
		}
	}
	

	if (oc_hookSet('chair-export_reviews-data')) {
		foreach ($OC_hooksAR['chair-export_reviews-data'] as $v) {
			require_once $v;
		}
	}

	// Get review data & iterate through each
	$q = "SELECT `" . OCC_TABLE_PAPERREVIEWER . "`.*, CONCAT_WS('-', `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`, `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`) AS `id`, `" . OCC_TABLE_PAPER . "`.`title`, CONCAT_WS(' ', `" . OCC_TABLE_REVIEWER . "`.`name_first`,  `" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name` FROM `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` ORDER BY `paperid`, `reviewerid`";
	$r = ocsql_query($q) or err('Unable to retrieve data for export', $hdr, $hdrfn);
	if (ocsql_num_rows($r) == 0) {
		warn("There is no review data to export", $hdr, $hdrfn);
		exit;
 	} else {  // Export file
		oc_export($scope, $exportFieldsAR, $fieldAR, $r, $extraAR, $OC_reviewQuestionsAR);
		exit;
	}
	
}

// Display form
printHeader($hdr, $hdrfn);

print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<p>Export will generate one row/entry per submission/reviewer pair.</p>

<p style="font-weight: bold">Select Fields to Export:</p>
';

foreach ($fieldAR as $fieldID => $fieldName) {
	if (preg_match("/^skip\d+$/", $fieldID)) {
		print '<br />';
		continue;
	}
	print '<label><input type="checkbox" name="fields[]" value="' . $fieldID . '" ';
	if (in_array($fieldID,$checkedFieldAR)) { print 'checked '; }
	print '/> ' . $fieldName . "</label><br />\n";
}

print '
<p><input type="submit" name="submit" class="submit" value="Generate File" />
&nbsp; &nbsp;
Format: <select name="format">
';

foreach ($OC_exportFormatAR as $fmt => $fmtAR) {
	print '<option value="' . $fmt . '">' . $fmtAR['name'] . '</option>';
}

print '
</select>
</p>

<p style="margin-left: 30px;"><label><input type="checkbox" name="charlimit" value="1" /> Limit cells to ' . $OC_exportCharLimit . ' characters (Excel/CSV limit)</label></p>

</form>

<p class="note">Note: When opening up a CSV or Tab-delimited file in a spreadsheet, you may need to specify the character encoding: UTF-8.</p>

';

printFooter();

?>
