<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once OCC_SUBMISSION_INC_FILE;

function saveTemplate($subject, $body, $templateid) {
	$q = "UPDATE `" . OCC_TABLE_TEMPLATE . "` SET `subject`='" . safeSQLstr($subject) . "', `body`='" . safeSQLstr($body) . "', `updated`='" . safeSQLstr(date('Y-m-d')) . "' WHERE `templateid`='" . safeSQLstr($templateid) . "'";
	ocsql_query($q) or err('Unable to save template');
}

// Variables for use in emails
// - limit keys to [\w-], and values to [\w -]
$OC_emailVarAR['general'] = array(
	'OC_pcemail' => OCC_WORD_CHAIR . ' Email Address',
	'OC_confirmmail' => 'Notification Email Address', 
	'OC_confName' => 'Event/Journal Short Name', 
	'OC_confNameFull' => 'Event/Journal Full Name', 
	'OC_confURL' => 'Event/Journal Web Address',
	'OC_openconfURL' => 'OpenConf Web Address',
);

$OC_emailVarAR['author'] = array(
	'paperid' => 'Submission ID',
	'title' => 'Submission Title', 
	'name_last' => 'Recipient Last Name',
	'name_first' => 'Recipient First Name',
	'email' => 'Recipient Email'
);

$OC_emailVarAR['author_acceptance'] = array(
	'author-comments' => 'Reviewers Comment to Author',
	'advocate-comment' => 'Committee (Adv.) Comment',
	'chair-notes' => OCC_WORD_CHAIR . ' Notes'
);
if ($OC_configAR['OC_paperAdvocates']) {
	$OC_emailVarAR['author_acceptance']['advocate-name'] = 'Advocate Name';
	$OC_emailVarAR['author_acceptance']['advocate-email'] = 'Advocate Email';
}
if (oc_moduleActive('oc_customforms')) {
	$OC_emailVarAR['author_acceptance']['review-fields'] = 'Review Fields set to Show Author in Custom Forms module';
}

$OC_emailVarAR['committee'] = array(
	'name_last' => 'Reviewer Last Name',
	'name_first' => 'Reviewer First Name',
	'username' => 'Reviewer Username',
	'email' => 'Reviewer Email'
);

// Check for addt'l (hook) variables
if (oc_hookSet('chair-email-variables')) {
	foreach ($OC_hooksAR['chair-email-variables'] as $f) {
		require_once $f;
	}
}

// Retrieve templates
$templateAR = array();
$q = "SELECT `templateid`, `name`, `module` FROM `" . OCC_TABLE_TEMPLATE . "` WHERE `type`='email' ORDER BY `name` ASC";
$r = ocsql_query($q) or err('Unable to retrieve templates');
while ($l = ocsql_fetch_assoc($r)) {
	// Skip templates for modules not active
	if (isset($l['module']) && !empty($l['module']) && ($l['module'] != 'OC') && !in_array($l['module'], $OC_activeModulesAR)) {
		continue;
	}
	// Skip PC templates if advocates not used
	if ($OC_configAR['OC_paperAdvocates'] || !preg_match("/^pc_/", $l['templateid'])) {
		$templateAR[$l['templateid']] = $l['name'];
	}
}

// Author(s) to include
if ($OC_configAR['OC_emailAuthorRecipients'] == 0) {
	$authorIncludeSQL = " AND `" . OCC_TABLE_AUTHOR . "`.`position`=`" . OCC_TABLE_PAPER . "`.`contactid` ";
} else {
	$authorIncludeSQL = '';
}

// Set up recipients
// - limit keys to [\w-], and text to [\w -]
$recipientAR = array();
$recipientAR['authors_all']['text']	= OCC_WORD_AUTHOR . 's - All';
$recipientAR['authors_all']['sql']	= "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `name_last`, `name_first`, `title`, `email` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "` WHERE `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` " . $authorIncludeSQL . " ORDER BY `paperid`";
$recipientAR['authors_all']['vars'] = array_merge($OC_emailVarAR['author'], $OC_emailVarAR['author_acceptance']);
$recipientAR['authors_all']['special'] = 'email-authors.inc';
$recipientAR['authors_all']['id']	= "`" . OCC_TABLE_PAPER . "`.`paperid`";

$sr = ocsql_query("SELECT COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPER . "` WHERE `student`='T'") or err('Unable to check student field');
if (($sl = ocsql_fetch_assoc($sr)) && ($sl['count'] > 0)) {
	$recipientAR['authors_students']['text']        = OCC_WORD_AUTHOR . 's - Student submissions';
	$recipientAR['authors_students']['sql'] = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `name_last`, `name_first`, `title`, `email` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "` WHERE `" . OCC_TABLE_PAPER . "`.`student`='T' AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` " . $authorIncludeSQL . " ORDER BY `paperid`";
	$recipientAR['authors_students']['vars'] = array_merge($OC_emailVarAR['author'], $OC_emailVarAR['author_acceptance']);
	$recipientAR['authors_students']['special'] = 'email-authors.inc';
	$recipientAR['authors_students']['id'] = "`" . OCC_TABLE_PAPER . "`.`paperid`";
}

$recipientAR['authors_nofile']['text'] = OCC_WORD_AUTHOR . 's - Missing file';
$recipientAR['authors_nofile']['sql'] = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `name_last`, `name_first`, `title`, `email` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "` WHERE `format` is NULL AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` " . $authorIncludeSQL . " ORDER BY `paperid`";
$recipientAR['authors_nofile']['vars'] = array_merge($OC_emailVarAR['author'], $OC_emailVarAR['author_acceptance']);
$recipientAR['authors_nofile']['special'] = 'email-authors.inc';
$recipientAR['authors_nofile']['id'] = "`" . OCC_TABLE_PAPER . "`.`paperid`";

if (count($OC_acceptanceValuesAR) > 1) {
	$recipientAR['authors_accepted_all']['text']	= OCC_WORD_AUTHOR . 's - All accepted';
	$recipientAR['authors_accepted_all']['sql']	= "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_AUTHOR . "`.`name_last`, `" . OCC_TABLE_AUTHOR . "`.`name_first`, `" . OCC_TABLE_PAPER . "`.`title`, `" . OCC_TABLE_AUTHOR . "`.`email` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "`, `" . OCC_TABLE_ACCEPTANCE . "` WHERE `" . OCC_TABLE_PAPER . "`.`accepted`=`" . OCC_TABLE_ACCEPTANCE . "`.`value` AND `" . OCC_TABLE_ACCEPTANCE . "`.`accepted`=1 AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` " . $authorIncludeSQL . " ORDER BY `paperid`";
	$recipientAR['authors_accepted_all']['vars'] = array_merge($OC_emailVarAR['author'], $OC_emailVarAR['author_acceptance']);
	$recipientAR['authors_accepted_all']['special'] = 'email-authors.inc';
	$recipientAR['authors_accepted_all']['id']	= "`" . OCC_TABLE_PAPER . "`.`paperid`";
}

// acceptance break down
foreach ($OC_acceptanceValuesAR as $idx => $acc) {
	$recipientAR['authors_' . $idx]['text'] = OCC_WORD_AUTHOR . 's - Decision - ' . $acc['value'];
	$recipientAR['authors_' . $idx]['sql']	= "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `name_last`, `name_first`, `title`, `email` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "` WHERE `" . OCC_TABLE_PAPER . "`.`accepted`='" . safeSQLstr($acc['value']) . "' AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` " . $authorIncludeSQL . " ORDER BY `paperid`";
	$recipientAR['authors_' . $idx]['vars'] = array_merge($OC_emailVarAR['author'], $OC_emailVarAR['author_acceptance']);
	$recipientAR['authors_' . $idx]['special'] = 'email-authors.inc';
	$recipientAR['authors_' . $idx]['id']	= "`" . OCC_TABLE_PAPER . "`.`paperid`";
}

// type break down if in use
if (isset($OC_submissionFieldAR['type']['values']) && is_array($OC_submissionFieldAR['type']['values']) && (count($OC_submissionFieldAR['type']['values']) > 0)) {
	foreach ($OC_submissionFieldAR['type']['values'] as $typeidx => $typeval) {
		$recipientAR['authors_type_' . $typeidx]['text'] = OCC_WORD_AUTHOR . 's - Type - ' . $typeval;
		$recipientAR['authors_type_' . $typeidx]['sql']	= "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `name_last`, `name_first`, `title`, `email` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "` WHERE `" . OCC_TABLE_PAPER . "`.`type`='" . safeSQLstr($typeval) . "' AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` " . $authorIncludeSQL . " ORDER BY `paperid`";
		$recipientAR['authors_type_' . $typeidx]['vars'] = array_merge($OC_emailVarAR['author'], $OC_emailVarAR['author_acceptance']);
		$recipientAR['authors_type_' . $typeidx]['special'] = 'email-authors.inc';
		$recipientAR['authors_type_' . $typeidx]['id']	= "`" . OCC_TABLE_PAPER . "`.`paperid`";
	}
}

if (isset($OC_submissionFieldAR['presenter']) && in_array('presenter', $OC_submissionFieldSetAR['fs_authors']['fields'])) {
	$recipientAR['presenters']['text']	= 'Presenting ' . OCC_WORD_AUTHOR . 's - Accepted';
	$recipientAR['presenters']['sql']	= "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`title`, `" . OCC_TABLE_AUTHOR . "`.`name_last`, `" . OCC_TABLE_AUTHOR . "`.`name_first`, `" . OCC_TABLE_AUTHOR . "`.`email` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_AUTHOR . "`, `" . OCC_TABLE_ACCEPTANCE . "` WHERE `" . OCC_TABLE_PAPER . "`.`accepted`=`" . OCC_TABLE_ACCEPTANCE . "`.`value` AND `" . OCC_TABLE_ACCEPTANCE . "`.`accepted`=1 AND `" . OCC_TABLE_AUTHOR . "`.`presenter`='T' AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` ORDER BY `paperid`";
	$recipientAR['presenters']['vars'] = $OC_emailVarAR['author'];		
	$recipientAR['presenters']['id']	= "`" . OCC_TABLE_PAPER . "`.`paperid`";
}

if ($OC_configAR['OC_paperAdvocates']) {
	$recipientAR['reviewer_pc_all']['text'] = 'Review and Program Committee Members - All';
	$recipientAR['reviewer_pc_all']['text'] = 'Review and Program Committee Members - All';
	$recipientAR['reviewer_pc_all']['sql']	= "SELECT `reviewerid`, `name_last`, `name_first`, `username`, `email` FROM `" . OCC_TABLE_REVIEWER . "` WHERE 1=1 ORDER BY `reviewerid`";
	$recipientAR['reviewer_pc_all']['vars'] = $OC_emailVarAR['committee'];
	$recipientAR['reviewer_pc_all']['id'] = '`reviewerid`';

	$recipientAR['reviewers_all']['text'] = 'Reviewers - All (except Program Committee members)';
} else {
	$recipientAR['reviewers_all']['text'] = 'Reviewers - All';
}
$recipientAR['reviewers_all']['sql'] = "SELECT `reviewerid`, `name_last`, `name_first`, `username`, `email` FROM `" . OCC_TABLE_REVIEWER . "` WHERE `onprogramcommittee`='F' ORDER BY `reviewerid`";
$recipientAR['reviewers_all']['vars'] = $OC_emailVarAR['committee'];
$recipientAR['reviewers_all']['id'] = '`reviewerid`';

$recipientAR['reviewers_noreview']['text'] = 'Reviewers - Reviews not yet completed or no score';
$recipientAR['reviewers_noreview']['sql']	= "SELECT `" . OCC_TABLE_REVIEWER . "`.`reviewerid`, `name_last`, `name_first`, `username`, `email` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_PAPERREVIEWER . "` WHERE (`" . OCC_TABLE_PAPERREVIEWER . "`.`completed` != 'T' OR `" . OCC_TABLE_PAPERREVIEWER . "`.`score` IS NULL) AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` GROUP BY `reviewerid`, `name_last`, `name_first`, `username`, `email` ORDER BY `reviewerid`";
$recipientAR['reviewers_noreview']['vars'] = $OC_emailVarAR['committee'];
$recipientAR['reviewers_noreview']['id'] = "`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";

$recipientAR['reviewers_reviewscomplete']['text'] = 'Reviewers - Reviews all completed';
$recipientAR['reviewers_reviewscomplete']['sql']	= "SELECT `" . OCC_TABLE_REVIEWER . "`.`reviewerid`, `name_last`, `name_first`, `username`, `email`, MIN(`completed`) AS `minc` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` GROUP BY `reviewerid`, `name_last`, `name_first`, `username`, `email` HAVING MIN(`completed`) NOT LIKE 'F' ORDER BY `reviewerid`";
$recipientAR['reviewers_reviewscomplete']['vars'] = $OC_emailVarAR['committee'];
$recipientAR['reviewers_reviewscomplete']['id'] = "`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";

$recipientAR['reviewers_nocomment']['text'] = 'Reviewers - Missing author comments';
$recipientAR['reviewers_nocomment']['sql']	= "SELECT `" . OCC_TABLE_REVIEWER . "`.`reviewerid`, `name_last`, `name_first`, `username`, `email` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`authorcomments` IS NULL AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` GROUP BY `reviewerid`, `name_last`, `name_first`, `username`, `email` ORDER BY `reviewerid`";
$recipientAR['reviewers_nocomment']['vars'] = $OC_emailVarAR['committee'];
$recipientAR['reviewers_nocomment']['id'] = "`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";

if ($OC_configAR['OC_paperAdvocates']) {
	$recipientAR['pc_all']['text'] = 'Program Committee - All';
	$recipientAR['pc_all']['sql']	= "SELECT `reviewerid`, `name_last`, `name_first`, `username`, `email` FROM `" . OCC_TABLE_REVIEWER . "` WHERE `onprogramcommittee`='T' ORDER BY `reviewerid`";
	$recipientAR['pc_all']['vars'] = $OC_emailVarAR['committee'];
	$recipientAR['pc_all']['id'] = '`reviewerid`';

	$recipientAR['pc_norecommendation']['text'] = 'Program Committee - Missing advocate recommendation';
	$recipientAR['pc_norecommendation']['sql']	= "SELECT `" . OCC_TABLE_REVIEWER . "`.`reviewerid`, `name_last`, `name_first`, `username`, `email` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `" . OCC_TABLE_PAPERADVOCATE . "`.`adv_recommendation` is NULL and `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` GROUP BY `reviewerid`, `name_last`, `name_first`, `username`, `email` ORDER BY `reviewerid`";
	$recipientAR['pc_norecommendation']['vars'] = $OC_emailVarAR['committee'];
	$recipientAR['pc_norecommendation']['id'] = "`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
}

// Check for addt'l (hook) recipients
if (oc_hookSet('chair-email-recipient')) {
	foreach ($OC_hooksAR['chair-email-recipient'] as $f) {
		require_once $f;
	}
}

// Get sorted list of recipients
$recipients = array();
foreach ($recipientAR as $k => $v) {
	$recipients[$k] = $v['text'];
}
natcasesort($recipients);
