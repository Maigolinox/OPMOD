<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once OCC_FORM_INC_FILE;
require_once OCC_REVIEW_INC_FILE;

$authorcomments = array();
$reviewfields = array();
$advocatecomment = array();
$advocatename = array();
$advocateemail = array();
$chairnotes = array();

// get acceptance type
if (preg_match("/^authors\_(\d+)$/", $_POST['recipient'], $matches) && isset($OC_acceptanceValuesAR[$matches[1]])) {
	$accval = $OC_acceptanceValuesAR[$matches[1]]['value'];
	$accSQL = "`" . OCC_TABLE_PAPER . "`.`accepted`='" . safeSQLstr($accval) . "'";
} else {
	$accSQL = '';
}

// get review fields to display
$displayFieldAR = array();
foreach ($OC_reviewQuestionsAR as $fid => $far) {
	if (isset($far['showauthor']) && $far['showauthor']) {
		$displayFieldAR[] = $fid;
	}
}

// get review fields and reviewer comments
$tempq = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPERREVIEWER . "`.* FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_PAPERREVIEWER . "` WHERE " . (!empty($accSQL) ? ($accSQL . " AND ") : '') . "`" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`";
$tempr = ocsql_query($tempq) or err("Unable to get " . oc_strtolower(OCC_WORD_AUTHOR) . " comments");
while ($templ = ocsql_fetch_array($tempr)) {
	// init vars for paperid
	if (!isset($authorcomments[$templ['paperid']])) {
		$authorcomments[$templ['paperid']] = "";
	}
	if (!isset($reviewfields[$templ['paperid']])) {
		$reviewfields[$templ['paperid']] = "";
	}
	// set author comments
	if (!empty($templ['authorcomments'])) {
		$authorcomments[$templ['paperid']] .= $commentSeparator . "\n" . $templ['authorcomments'] . "\n";
	}
	// set review fields
	$reviewInfo = '';
	foreach ($displayFieldAR as $fid) {
		if (!empty($templ[$fid])) {
			$reviewInfo .= oc_strtoupper(oc_($OC_reviewQuestionsAR[$fid]['short'])) . ': ' . oc_getFieldValue($OC_reviewQuestionsAR, $templ, $fid) ."\n\n";
		}
	}
	if (!empty($reviewInfo)) {
		$reviewfields[$templ['paperid']] .= $commentSeparator . "\n" . $reviewInfo . "\n";
	}
}

// get advocate and comments
$tempq = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_REVIEWER . "`.`name_first`, `" . OCC_TABLE_REVIEWER . "`.`name_last`, `" . OCC_TABLE_REVIEWER . "`.`email`, `" . OCC_TABLE_PAPERADVOCATE . "`.`adv_comments` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_PAPERADVOCATE . "`, `" . OCC_TABLE_REVIEWER . "` WHERE " . (!empty($accSQL) ? ($accSQL . " AND ") : '') . "`" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERADVOCATE . "`.`paperid` AND `" . OCC_TABLE_REVIEWER . "`.`reviewerid`=`" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`";
$tempr = ocsql_query($tempq) or err("Unable to get advocate comments");
while ($templ = ocsql_fetch_array($tempr)) {
	if ($OC_configAR['OC_paperAdvocates'] && !empty($templ['email'])) {
		$advocatename[$templ['paperid']] = $templ['name_first'] . ' ' . $templ['name_last'];
		$advocateemail[$templ['paperid']] = $templ['email'];
	}
	if (!empty($templ['adv_comments'])) {
		$advocatecomment[$templ['paperid']] = "\n" . $templ['adv_comments'] . "\n";
	}
}

// get chair comments
$tempq = "SELECT `paperid`, `pcnotes` FROM `" . OCC_TABLE_PAPER . "`" . (!empty($accSQL) ? ( " WHERE " . $accSQL) : '');
$tempr = ocsql_query($tempq) or err("Unable to get chair comments");
while ($templ = ocsql_fetch_array($tempr)) {
	if (!empty($templ['pcnotes'])) {
		$chairnotes[$templ['paperid']] = "\n" . $templ['pcnotes'] . "\n";
	}
}

$specialIndexAR['authorcomments'] = 'paperid';
$specialIndexAR['reviewfields'] = 'paperid';
$specialIndexAR['advocatecomment'] = 'paperid';
$specialIndexAR['advocatename'] = 'paperid';
$specialIndexAR['advocateemail'] = 'paperid';
$specialIndexAR['chairnotes'] = 'paperid';

// Check for addt'l (hook) special variables
if (oc_hookSet('chair-email-authors')) {
	foreach ($OC_hooksAR['chair-email-authors'] as $f) {
		require_once $f;
	}
}
