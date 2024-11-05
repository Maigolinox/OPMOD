<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2021 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once '../include.php';

beginChairSession((isset($_POST['ocsubmit']) ? true : false));

require_once 'search.inc';
require_once OCC_SUBMISSION_INC_FILE;

$skipFieldTypeAR = array('password', 'file'); // field types to be skipped
$skipFieldIdAR = array('consent', 'contactid'); // field IDs to be skipped
$searchFieldNum = 3; // number of search fields to display

// Setup searchable fields
$fieldsAR = array();
$dateFieldsAR = array();
$intFieldsAR = array();
$decFieldsAR = array();

$fieldsAR['paperid'] = array(
	'short' => 'Submission ID',
	'type' => 'int',
	'fieldset' => '',
);
$intFieldsAR[] = 'paperid';

setupFieldsAR($fieldsAR, $OC_submissionFieldSetAR, $OC_submissionFieldAR, $dateFieldsAR, $skipFieldTypeAR, $skipFieldIdAR);

$fieldsAR['submissiondate'] = array(
	'short' => 'Submission Date',
	'type' => 'date',
	'fieldset' => 'Extras',
);
$dateFieldsAR[] = 'submissiondate';

$fieldsAR['lastupdate'] = array(
	'short' => 'Last Updated',
	'type' => 'date',
	'fieldset' => 'Extras',
);
$dateFieldsAR[] = 'lastupdate';

$fieldsAR['accepted'] = array(
	'short' => 'Acceptance Decision',
	'type' => 'dropdown',
	'fieldset' => 'Extras',
	'values' => array()
);
// values also used for adv_recommendation below
foreach ($OC_acceptanceValuesAR as $acc) {
	$fieldsAR['accepted']['values'][$acc['value']] = $acc['value'];
}
$fieldsAR['accepted']['values']['_NULL_'] = 'pending';

if ($OC_configAR['OC_paperAdvocates']) {
	$fieldsAR['adv_recommendation'] = array(
		'short' => 'Advocate Recommendation',
		'type' => 'dropdown',
		'fieldset' => 'Extras',
		'values' => $fieldsAR['accepted']['values']
	);
	$fieldsAR['advocateid'] = array(
		'short' => 'Advocate ID (assigned)',
		'type' => 'int',
		'fieldset' => 'Extras'
	);
	$intFieldsAR[] = 'advocateid';
}

$fieldsAR['reviewerid'] = array(
	'short' => 'Reviewer ID (assigned)',
	'type' => 'int',
	'fieldset' => 'Extras'
);
$intFieldsAR[] = 'reviewerid';

$fieldsAR['score'] = array(
	'short' => 'Reviews Score (average)',
	'type' => 'int',
	'fieldset' => 'Extras',
);
$decFieldsAR[] = 'score';

// Hooks
// -- Use 'Extras' for fieldset name
if (oc_hookSet('search-submissions')) {
	foreach ($GLOBALS['OC_hooksAR']['search-submissions'] as $v) {
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
		$includeAdvocate = false;
		$includeReviewer = false;
		for ($i=1; $i <= $searchFieldNum; $i++) {
			if (validateSearchFields($i, $fieldsAR, $intFieldsAR, $dateFieldsAR, $decFieldsAR)) {
				$f = $_POST['searchfield'.$i];
				$fval = $_POST['searchvalue'.$i];
				if ($f == 'topics') {
					$q .= " AND " . queryFieldWrapper(OCC_TABLE_PAPERTOPIC, 'topicid', 'dropdown', $fval, false);
					$includeTopic = true;
				} elseif ( ($f == 'accepted') && ($fval == '_NULL_') ) {
					$q .= " AND `" . OCC_TABLE_PAPER . "`.`accepted` IS NULL";
				} elseif ($OC_configAR['OC_paperAdvocates'] && ($f == 'adv_recommendation')) {
					$q .= " AND " . queryFieldWrapper(OCC_TABLE_PAPERADVOCATE, 'adv_recommendation', 'dropdown', $fval, false);
					$includeAdvocate = true;
				} elseif ($OC_configAR['OC_paperAdvocates'] && ($f == 'advocateid')) {
					$q .= " AND " . queryFieldWrapper(OCC_TABLE_PAPERADVOCATE, 'advocateid', 'int', $fval, $_POST['searchoperator'.$i]);
					$includeAdvocate = true;
				} elseif ($f == 'reviewerid') {
					$q .= " AND " . queryFieldWrapper(OCC_TABLE_PAPERREVIEWER, 'reviewerid', 'int', $fval, $_POST['searchoperator'.$i]);
					$includeReviewer = true;
				} elseif ($f == 'score') {
					$q .= " AND `" . OCC_TABLE_PAPER . "`.`paperid` IN (SELECT `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid` FROM `" . OCC_TABLE_PAPERREVIEWER . "` GROUP BY `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid` HAVING " . queryFieldWrapper(OCC_TABLE_PAPERREVIEWER, 'score', 'dropdown', $fval, $_POST['searchoperator'.$i], "AVG(`" . OCC_TABLE_PAPERREVIEWER . "`.`score`)") . ")";
				} elseif (in_array($f, $OC_submissionFieldSetAR['fs_authors']['fields'])) {
					$q .= " AND " . queryFieldWrapper(OCC_TABLE_AUTHOR, $f, $fieldsAR[$f]['type'], $fval, $_POST['searchoperator'.$i]);
				} else {
					$q .= " AND " . queryFieldWrapper(OCC_TABLE_PAPER, $f, $fieldsAR[$f]['type'], $fval, $_POST['searchoperator'.$i]);
				}
			}
		}

		// Display results
		if (!empty($q)) {
			$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`title`, `" . OCC_TABLE_PAPER . "`.`type` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "`" . ($includeTopic ? (", `" . OCC_TABLE_PAPERTOPIC . "`") : '')  . ($includeAdvocate ? (", `" . OCC_TABLE_PAPERADVOCATE . "`") : '') . ($includeReviewer ? (", `" . OCC_TABLE_PAPERREVIEWER . "`") : '') . " WHERE `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` " . ($includeTopic ? ("AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERTOPIC . "`.`paperid` ") : '') . ($includeAdvocate ? ("AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERADVOCATE . "`.`paperid` ") : '') . ($includeReviewer ? ("AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERREVIEWER . "`.`paperid` ") : '') . $q . " GROUP BY `paperid`, `title`, `type` ORDER BY `paperid`";
			if ($r = ocsql_query($q)) {
				if (($num = ocsql_num_rows($r)) > 0) {
					print '
<p style="font-size: 0.9em">Matches: ' . safeHTMLstr($num) . '</p>
<table border="0" cellspacing="1" cellpadding="4" cols="4">
<tr class="rowheader"><th scope="col">ID</th><th scope="col">Title</th>' . (isset($fieldsAR['type']) ? '<th scope="col">Type</th>' : '') . '</tr>';
					$row = 1;
					$subsAR = array();
					while ($l = ocsql_fetch_assoc($r)) {
						print '<tr class="row' . $row . '"><td align="right">' . safeHTMLstr($l['paperid']) . '</td><td><a href="show_paper.php?pid=' . safeHTMLstr($l['paperid']) . '" target="_blank" title="open submission info page in new tab/window">' . safeHTMLstr($l['title']) . '</a></td>' . (isset($fieldsAR['type']) ? ('<td>' . safeHTMLstr($l['type']) . '</td>') : '') . '</tr>';
						$subsAR[] = $l['paperid'];
						if ($row==1) { $row=2; } else { $row=1; }
					}
					print '
</table>
';
					$emailAddresses = array();
					if ($r = ocsql_query("SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_AUTHOR . "`.`email` FROM `" . OCC_TABLE_AUTHOR . "`, `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_PAPER . "`.`paperid` IN (" . implode(',', $subsAR) . ") AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` AND `" . OCC_TABLE_PAPER . "`.`contactid`=`" . OCC_TABLE_AUTHOR . "`.`position`")) {
						while ($l = ocsql_fetch_assoc($r)) {
							$emailAddresses[] = $l['paperid'] . '/' . $l['email'];
						}
						displayEmailForm($emailAddresses, 'authors_all', 'Email Contact Author(s)');
					}
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
printHeader('Submissions Search', 1);
displaySearchForm($fieldsAR, $dateFieldsAR, $intFieldsAR, $decFieldsAR, $searchFieldNum);
printFooter();

?>
