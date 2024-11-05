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

beginChairSession((isset($_POST['ocsubmit']) ? true : false));

require_once 'search.inc';
require_once OCC_COMMITTEE_INC_FILE;

$skipFieldTypeAR = array('password'); // field types to be skipped
$skipFieldIdAR = array('consent'); // field IDs to be skipped
$searchFieldNum = 3; // number of search fields to display

// Setup searchable fields
$fieldsAR = array();
$dateFieldsAR = array();
$intFieldsAR = array();
$decFieldsAR = array();

$fieldsAR['reviewerid'] = array(
	'short' => 'Member ID',
	'type' => 'int',
	'fieldset' => '',
);
$intFieldsAR[] = 'reviewerid';

if ($OC_configAR['OC_paperAdvocates']) {
	$fieldsAR['onprogramcommittee'] = array(
		'short' => 'PC Member',
		'type' => 'dropdown',
		'fieldset' => '',
		'values' => array('T' => 'Yes', 'F' => 'No')
	);
}

setupFieldsAR($fieldsAR, $OC_reviewerFieldSetAR, $OC_reviewerFieldAR, $dateFieldsAR, $skipFieldTypeAR, $skipFieldIdAR);

$fieldsAR['signupdate'] = array(
	'short' => 'Sign Up Date',
	'type' => 'date',
	'fieldset' => 'Extras',
);
$dateFieldsAR[] = 'signupdate';

$fieldsAR['lastsignin'] = array(
	'short' => 'Last Sign In',
	'type' => 'date',
	'fieldset' => 'Extras',
);
$dateFieldsAR[] = 'lastsignin';

// Hooks
// -- Use 'Extras' for fieldset name
if (oc_hookSet('search-committee')) {
	foreach ($GLOBALS['OC_hooksAR']['search-committee'] as $v) {
			require_once $v;
	}
}

// Search Submission POST
if (isset($_POST['ocsubmit']) && ($_POST['ocsubmit'] == 'Search')) {
	displayResultsHeader();

	// Check for valid submission
	if (!validToken('chair')) {
		print '<p class="warn">Invalid submission</p>';
	} else {
		$q = '';
		$includeTopic = false;
		for ($i=1; $i <= $searchFieldNum; $i++) {
			if (validateSearchFields($i, $fieldsAR, $intFieldsAR, $dateFieldsAR)) {
				$f = $_POST['searchfield'.$i];
				$fval = $_POST['searchvalue'.$i];
				if ($f == 'topics') {
					$q .= " AND " . queryFieldWrapper(OCC_TABLE_REVIEWERTOPIC, 'topicid', 'dropdown', $fval, false);
					$includeTopic = true;
				} else {
					$q .= " AND " . queryFieldWrapper(OCC_TABLE_REVIEWER, $f, $fieldsAR[$f]['type'], $fval, $_POST['searchoperator'.$i]);
				}
			}
		}

		if (!empty($q)) {
			$q = "SELECT `" . OCC_TABLE_REVIEWER . "`.`reviewerid`, `" . OCC_TABLE_REVIEWER . "`.`username`, `" . OCC_TABLE_REVIEWER . "`.`onprogramcommittee`, CONCAT_WS(' ', `" . OCC_TABLE_REVIEWER . "`.`name_first`, `" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, `" . OCC_TABLE_REVIEWER . "`.`email` FROM `" . OCC_TABLE_REVIEWER . "`" . ($includeTopic ? (", `" . OCC_TABLE_REVIEWERTOPIC . "`") : '') . " WHERE 1=1 " . ($includeTopic ? ("AND `" . OCC_TABLE_REVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid` ") : '') . $q . " GROUP BY `" . OCC_TABLE_REVIEWER . "`.`reviewerid` ORDER BY `reviewerid`";
			if ($r = ocsql_query($q)) {
				if (($num = ocsql_num_rows($r)) > 0) {
					print '
<p style="font-size: 0.9em">Matches: ' . safeHTMLstr($num) . '</p>
<table border="0" cellspacing="1" cellpadding="4" cols="4">
<tr class="rowheader"><th scope="col" title="Reviewer ID">ID</th><th scope="col" title="On Program Committee">PC</th><th scope="col">Name</th><th scope="col">Username</th></tr>';
					$emailAddresses = array();
					$row = 1;
					while ($l = ocsql_fetch_assoc($r)) {
						print '<tr class="row' . $row . '"><td align="right">' . safeHTMLstr($l['reviewerid']) . '</td><td align="center">' . (($l['onprogramcommittee'] == 'T') ? '<span title="on program committee">&#10003</span>' : '') . '</td><td><a href="show_reviewer.php?rid=' . safeHTMLstr($l['reviewerid']) . '" target="_blank" title="open member info page in new tab/window">' . safeHTMLstr($l['name']) . '</a></td><td>' . safeHTMLstr($l['username']) . '</td></tr>';
						if ($row==1) { $row=2; } else { $row=1; }
						$emailAddresses[] = $l['reviewerid'] . '/' . $l['email'];
					}
					print '
</table>
';
					displayEmailForm($emailAddresses, 'reviewer_pc_all', 'Email Committee Member(s)');
				} else {
					print '<p class="warn">No matches found</p>';
				}
			} else {
				print '<p class="warn">Error encoutered while searching</p>';
			}
		} else {
			print '<p class="warn">Missing or invalid search parameters</p>';
		}
	}

	displayResultsFooter();
	exit;
}

// Display search form
printHeader('Committee Members Search', 1);
displaySearchForm($fieldsAR, $dateFieldsAR, $intFieldsAR, $decFieldsAR, $searchFieldNum);
printFooter();

?>