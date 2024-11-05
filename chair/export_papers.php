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

$hdr = 'Export Submissions';
$hdrfn = 1;

require_once "../include.php";

beginChairSession();

require_once 'export.inc';

// accepted or all papers?
if (isset($_REQUEST['acc']) && preg_match("/\d+/", $_REQUEST['acc']) && isset($OC_acceptanceValuesAR[$_REQUEST['acc']])) {
	$accSQL = "AND `" . OCC_TABLE_PAPER . "`.`accepted`='" . safeSQLstr($OC_acceptanceValuesAR[$_REQUEST['acc']]['value']) . "'";
	$accURL = 'acc=' . $_REQUEST['acc'];
	$scope = 'submissions-' . safeHTMLstr(oc_strtolower(preg_replace("/[^\w]/", "_", $OC_acceptanceValuesAR[$_REQUEST['acc']]['value'])));
}
else {
	$accSQL = '';
	$accURL = '';
	$scope = 'submissions-all';
}

// check that matching submissions exist
$r = ocsql_query("SELECT COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPER . "` WHERE 1=1 " . $accSQL);
$l = ocsql_fetch_assoc($r);
if ($l['count'] == 0) {
	warn('There are no submissions to export', $hdr, $hdrfn);
	exit;
}

require_once OCC_FORM_INC_FILE;
require_once OCC_SUBMISSION_INC_FILE;

$skip = 1;		// skip field id

$fieldAR = array(
	'paperid'    		=> 'Submission ID',
	'submissiondate'	=> 'Submission Date',
	'lastupdate'		=> 'Last Updated',
    'accepted'          => 'Acceptance',
	'_score'			=> 'Score',
);

$authorFieldsAR = array();
foreach ($OC_submissionFieldSetAR as $fsKey => $fsAR) {
	if (count($fsAR['fields']) == 0) { continue; }
	if ($fsKey == 'fs_authors') {
		$fieldAR['skip' . $skip++] = '';
		$fieldAR['name'] = 'Contact ' . OCC_WORD_AUTHOR . ' Full Name';
		$authorFieldsAR['author_name'] = 'All ' . OCC_WORD_AUTHOR . 's Full Name';
		foreach ($fsAR['fields'] AS $fieldID) {
			$fieldAR[$fieldID] = 'Contact ' . OCC_WORD_AUTHOR . ' ' . (empty($OC_submissionFieldAR[$fieldID]['short']) ? $OC_submissionFieldAR[$fieldID]['name'] : $OC_submissionFieldAR[$fieldID]['short']);
			$authorFieldsAR['author_' . $fieldID] = 'All ' . OCC_WORD_AUTHOR . 's ' . (empty($OC_submissionFieldAR[$fieldID]['short']) ? $OC_submissionFieldAR[$fieldID]['name'] : $OC_submissionFieldAR[$fieldID]['short']);
		}
		$fieldAR['skip' . $skip++] = '';
	} else {
		foreach ($fsAR['fields'] AS $fieldID) {
			if (preg_match("/^password/i", $fieldID) || ($fieldID == 'file') || ($fieldID == 'topics') || empty($OC_submissionFieldAR[$fieldID]['name'])) { continue; }
			$fieldAR[$fieldID] = (empty($OC_submissionFieldAR[$fieldID]['short']) ? $OC_submissionFieldAR[$fieldID]['name'] : $OC_submissionFieldAR[$fieldID]['short']);
		}
	}
}

if (isset($OC_submissionFieldAR['topics']['short'])) {
	$fieldAR['_topics'] = $OC_submissionFieldAR['topics']['short'];
}

$fieldAR['skip' . $skip++]	= '';

$fieldAR = array_merge($fieldAR, $authorFieldsAR);

$fieldAR['skip' . $skip++]	= '';

// Default list of checked fields
$checkedFieldAR = array('paperid','title','name','email');

// Advocate/Committee fields
if ($OC_configAR['OC_paperAdvocates']) {
	$fieldAR['skip' . $skip++]	= '';
	$fieldAR['_advocateid'] = 'Advocate ID';
	$fieldAR['_advocate'] = 'Advocate';
	$fieldAR['_adv_recommendation'] = 'Advocate Recommendation';
	$fieldAR['_adv_comments'] = 'Advocate (Committee) Notes';
	$fieldAR['pcnotes'] = OCC_WORD_CHAIR . ' Notes';
	$fieldAR['skip' . $skip++]	= '';
} else {
	$fieldAR['skip' . $skip++]	= '';
	$fieldAR['pcnotes'] = OCC_WORD_CHAIR . ' Notes';
	$fieldAR['skip' . $skip++]	= '';
}

// Include extra fields
if (oc_hookSet('chair-export-fields')) {
	foreach ($OC_hooksAR['chair-export-fields'] as $v) {
		require_once $v;
	}
}

if (isset($_POST['submit']) && ($_POST['submit'] == "Generate File") && isset($_POST['fields']) && !empty($_POST['fields'])) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
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

	// All author data to display
	$incAuthorFieldsAR = array_intersect($_POST['fields'], array_keys($authorFieldsAR));
	if (!empty($incAuthorFieldsAR)) {
		$maxPosition = 1; // tracks max number of authors per submission
		
		// Get all authors info
		$q = "SELECT `" . OCC_TABLE_AUTHOR . "`.*, CONCAT_WS(' ', `" . OCC_TABLE_AUTHOR . "`.`name_first`, `" . OCC_TABLE_AUTHOR . "`.`name_last`) AS `name` FROM `" . OCC_TABLE_AUTHOR . "`, `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` " . $accSQL . " ORDER BY `paperid`, `position`";
		$r = ocsql_query($q) or err("Unable to retrieve data for export (1)", $hdr, $hdrfn);
		while ($l = ocsql_fetch_assoc($r)) {
			foreach ($authorFieldsAR as $afKey => $afVal) {
				if (preg_match("/^author_(\w+)$/", $afKey, $matches)) {
					if (isset($OC_submissionFieldAR[$matches[1]]['values']) && is_array($OC_submissionFieldAR[$matches[1]]['values']) 
							&& isset($OC_submissionFieldAR[$matches[1]]['usekey']) && $OC_submissionFieldAR[$matches[1]]['usekey'] 
							&& !empty($l[$matches[1]])
							&& isset($OC_submissionFieldAR[$matches[1]]['values'][$l[$matches[1]]])
					) {
						$extraAR[$l['paperid']]['_' . $afKey . $l['position']] = $OC_submissionFieldAR[$matches[1]]['values'][$l[$matches[1]]];
					} else {
						$extraAR[$l['paperid']]['_' . $afKey . $l['position']] = $l[$matches[1]];
					}
					$fieldAR['_' . $afKey . $l['position']] = preg_replace("/All " . OCC_WORD_AUTHOR . "s /", "", (OCC_WORD_AUTHOR . ' ' . $l['position'] . ' ' . $fieldAR[$afKey]));
				}
			}
			if ($l['position'] > $maxPosition) {
				$maxPosition = $l['position'];
			}
		}

		$newAuthorFieldsAR = array();
		for ($i=1; $i<=$maxPosition; $i++) {
			foreach($incAuthorFieldsAR as $f) {
				$newAuthorFieldsAR[] = '_' . $f . $i;
			}
		}
		reset($incAuthorFieldsAR);
		array_splice(
			$exportFieldsAR, 
			array_search(current($incAuthorFieldsAR), $_POST['fields']),
			count($incAuthorFieldsAR), 
			$newAuthorFieldsAR
		);
	}

	// Topics
	if (in_array('_topics', $exportFieldsAR)) {
		$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_TOPIC . "`.`topicname`, `" . OCC_TABLE_TOPIC . "`.`short` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_TOPIC . "`, `" . OCC_TABLE_PAPERTOPIC . "` WHERE `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERTOPIC . "`.`paperid` AND `" . OCC_TABLE_PAPERTOPIC . "`.`topicid`=`" . OCC_TABLE_TOPIC . "`.`topicid` " . $accSQL;
		$r = ocsql_query($q) or err("Unable to retrieve data for export (t)", $hdr, $hdrfn);
		while ($l = ocsql_fetch_assoc($r)) {
			if (isset($extraAR[$l['paperid']]['_topics'])) {
				$extraAR[$l['paperid']]['_topics'] .= "\n" . useTopic($l['short'], $l['topicname']);
			} else {
				$extraAR[$l['paperid']]['_topics'] = useTopic($l['short'], $l['topicname']);
			}
		}
	}
	
	// Score
	if (in_array('_score', $exportFieldsAR)) {
		$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, ABS(FORMAT(AVG(`score`),2)) AS `recavg` FROM `" . OCC_TABLE_PAPER . "` LEFT JOIN `" . OCC_TABLE_PAPERREVIEWER . "` ON `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` WHERE 1=1 " . $accSQL . " GROUP BY `paperid`";
		$r = ocsql_query($q) or err("Unable to retrieve data for export (s)", $hdr, $hdrfn);
		while ($l = ocsql_fetch_assoc($r)) {
			$extraAR[$l['paperid']]['_score'] = $l['recavg'];
		}		
	}
	
	// Committee Comments
	if (preg_match("/\b_adv/", implode(',', $exportFieldsAR))) {
		// $accSQL is left out below as OCC_TABLE_PAPER is not included, meaning we use a little more memory
		$q = "SELECT `" . OCC_TABLE_PAPERADVOCATE . "`.*, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `advocate`  FROM `" . OCC_TABLE_PAPERADVOCATE . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` ORDER BY `paperid`";
		$r = ocsql_query($q) or err("Unable to retrieve data for export (c)", $hdr, $hdrfn);
		while ($l = ocsql_fetch_assoc($r)) {
			$extraAR[$l['paperid']]['_adv_comments'] = $l['adv_comments'];
			$extraAR[$l['paperid']]['_advocateid'] = $l['advocateid'];
			$extraAR[$l['paperid']]['_advocate'] = $l['advocate'];
			$extraAR[$l['paperid']]['_adv_recommendation'] = $l['adv_recommendation'];
		}		
	}
	
	// Get extra fields data
	if (oc_hookSet('chair-export-data')) {
		foreach ($OC_hooksAR['chair-export-data'] as $v) {
			require_once $v;
		}
	}

	// Get sub. data & iterate through each
	$q = "SELECT `" . OCC_TABLE_PAPER . "`.*, `" . OCC_TABLE_PAPER . "`.`paperid` AS `id`, `" . OCC_TABLE_AUTHOR . "`.*, CONCAT_WS(' ',`" . OCC_TABLE_AUTHOR . "`.`name_first`,`" . OCC_TABLE_AUTHOR . "`.`name_last`) AS `name` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "` WHERE `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` AND `" . OCC_TABLE_AUTHOR . "`.`position`=`" . OCC_TABLE_PAPER . "`.`contactid` " . $accSQL .  " ORDER BY `" . OCC_TABLE_PAPER . "`.`paperid`";
	$r = ocsql_query($q) or err("Unable to retrieve data for export", $hdr, $hdrfn);
	if (ocsql_num_rows($r) == 0) {
		warn("There are no papers to export", $hdr, $hdrfn);
		exit;
 	} else {  // Export file
		oc_export($scope, $exportFieldsAR, $fieldAR, $r, $extraAR, $OC_submissionFieldAR);
		exit;
	}
	
}

// Display form
printHeader($hdr, $hdrfn);

print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<p>Export will generate one row/entry per submission.  When selecting <em>All ' . OCC_WORD_AUTHOR . 's</em>, each ' . oc_strtolower(OCC_WORD_AUTHOR) . '\'s data will appear as separate fields.</p>
<script language="javascript" type="text/javascript">
<!--
function checkAllBoxes() {
	var boxObj = document.getElementsByName(\'fields[]\');
	for (var i=0; i<boxObj.length; i++) {
		boxObj[i].checked = true;
	}
}
document.write(\'<p><a href="#" onclick="checkAllBoxes(); return false;" style="margin-left: 25px; padding: 1px 3px; background-color: #eee; color: #00f; text-decoration: underline;" >check all</a></p>\');
// -->
</script>
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
<br />
<label>Format: <select name="format">
';

foreach ($OC_exportFormatAR as $fmt => $fmtAR) {
	print '<option value="' . $fmt . '">' . $fmtAR['name'] . '</option>';
}

print '
</select></label>
&nbsp; &nbsp;
<label>Submissions: <select name="acc">
<option value="">All</option>
';

foreach ($OC_acceptanceValuesAR as $idx => $acc) {
	print '<option value="' . safeHTMLstr($idx) . '">' . safeHTMLstr($acc['value']) . '</option>';
}

print '
</select></label>
<input type="submit" name="submit" value="Generate File" style="float: left; margin-right: 2em;" class="submit" />
<br />

<p style="margin-left: 30px;"><label><input type="checkbox" name="charlimit" value="1" /> Limit cells to ' . $OC_exportCharLimit . ' characters (Excel/CSV limit)</label></p>

</form>

<p class="note">Note: When opening up a CSV or Tab-delimited file in a spreadsheet, you may need to specify the character encoding: UTF-8.</p>

';

printFooter();

?>
