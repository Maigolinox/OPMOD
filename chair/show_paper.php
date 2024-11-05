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

beginChairSession();

printHeader("Submission", 1);

print '<p style="text-align: center"><a href="list_papers.php">View All Submissions</a></p>';

if (!isset($_GET['pid']) || !ctype_digit($_GET['pid'])) {
	err("Submission id is invalid");
}

// Get sub info
$spq = "SELECT * FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid`='" . safeSQLstr($_GET['pid']) . "'";
$spr = ocsql_query($spq) or err("Unable to get submissions ");

if (ocsql_num_rows($spr)!=1) {
	err("There does not appear to be a submission with that id (or there is more than one)");
}

$spl = ocsql_fetch_array($spr);

// Get authors
$oc_authorNum = 0;
$qa = "SELECT * FROM `" . OCC_TABLE_AUTHOR . "` WHERE `paperid`='" . safeSQLstr($_GET['pid']) . "' ORDER BY `position`";
$ra = ocsql_query($qa) or err("Unable to get " . oc_strtolower(OCC_WORD_AUTHOR) . "s ");
while ($a = ocsql_fetch_array($ra)) {
	$apos = $a['position'];
	foreach ($a as $akey => $aval) {
		if (preg_match("/^(?:paperid|position)$/", $akey)) { continue; }
		$spl[$akey . $apos] = $aval;
	}
}
$oc_authorNum = $apos;

// Get topics
$qt = "SELECT `topicid` FROM `" . OCC_TABLE_PAPERTOPIC . "` WHERE `paperid`='" . safeSQLstr($_GET['pid']) . "'";
$rt = ocsql_query($qt) or err("Unable to get topics ");
$spl['topics'] = array();
while ($t = ocsql_fetch_array($rt)) {
  $spl['topics'][] = $t['topicid'];
}

require_once OCC_FORM_INC_FILE;
require_once OCC_SUBMISSION_INC_FILE;

$files = '<tr><th>File</th><td>';
if ($spl['format'] && oc_isFile($pfile = $OC_configAR['OC_paperDir'] . preg_replace("/\D/", "", $_GET['pid']) . "." . $spl['format'])) {
	$files .= "<a href=\"../review/paper.php?c=1&p=" . safeHTMLstr($_GET['pid']) . "." . $spl['format']."\">" . safeHTMLstr($_GET['pid']) . ".".$spl['format']."</a> (" . oc_formatNumber(oc_fileSize($pfile)) . ')';
} else {
	$files .= "not uploaded";
}
$files .= '</td></tr>';

$advocate = '';
if ($OC_configAR['OC_paperAdvocates']) {
	$qadv = "SELECT `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, `" . OCC_TABLE_REVIEWER . "`.`organization` FROM `" . OCC_TABLE_PAPERADVOCATE . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`='" . safeSQLstr($_GET['pid']) . "' AND `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
	$radv = ocsql_query($qadv) or err("Unable to get advocate info ");
	if (ocsql_num_rows($radv) == 1) {
		$spladv = ocsql_fetch_array($radv);
		$advocate .= '<tr><th>Advocate:</th><td><a href="show_adv_review.php?s=&p=' . safeHTMLstr($_GET['pid']) . '&a=' . $spladv['advocateid'] . '">' . safeHTMLstr($spladv['name']) . '</a>, ' . $spladv['organization'] . '</td></tr>';
	}
}

$reviewers = '';
$qrev = "SELECT `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, `" . OCC_TABLE_REVIEWER . "`.`organization` FROM `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`='" . safeSQLstr($_GET['pid']) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
$rrev = ocsql_query($qrev) or err("Unable to get reviewers info ");
if (($rows = ocsql_num_rows($rrev)) > 0) {
	$reviewers .= '<tr><th>Reviewer(s):</th><td>';
	while ($splrev = ocsql_fetch_array($rrev)) {
		$reviewers .= (($rows > 1) ? '&#8226; ' : '') . '<a href="show_review.php?pid=' . safeHTMLstr($_GET['pid']) . '&rid=' . safeHTMLstr($splrev['reviewerid']) . '">' . safeHTMLstr($splrev['name']) . '</a>, ' . safeHTMLstr($splrev['organization']) . '<br />';
	}
	$reviewers .= '</td></tr>';
}

if (OCC_CHAIR_PWD_TRUMPS) {
	print '
<div style="text-align: center"><form method="post" action="../author/edit.php" style="display: inline; margin: 0; padding: 0;"><input type="hidden" name="ocaction" value="Edit Submission" /><input type="hidden" name="c" value="1" /><input type="hidden" name="pid" value="' . safeHTMLstr($_GET['pid']) . '" /><input type="submit" name="submit" value="Edit Submission" /></form> &nbsp; &nbsp; &nbsp; <form method="get" action="../author/upload.php" style="display: inline; margin: 0; padding: 0;"><input type="hidden" name="ocaction" value="Upload File" /><input type="hidden" name="c" value="1" /><input type="hidden" name="pid" value="' . safeHTMLstr($_GET['pid']) . '" /><input type="submit" name="submit" value="Upload File" /></form></div>
<br />
';
}

print '
<table class="ocfields">
<tr><th>ID:</th><td>' . safeHTMLstr($_GET['pid']) . '</td></tr>
<tr><th>Submitted:</th><td>' . safeHTMLstr($spl['submissiondate']) . '</td></tr>
<tr><th>Last Updated:</th><td>' . safeHTMLstr($spl['lastupdate']) . '</td></tr>
';

oc_showFieldSet($OC_submissionFieldSetAR, $OC_submissionFieldAR, $spl);

$extra = '';
if (oc_hookSet('chair-show_paper')) {
	foreach ($GLOBALS['OC_hooksAR']['chair-show_paper'] as $hook) {
		require_once $hook;
	}
}

print $files . $extra . $advocate . $reviewers;

print '</table>';

printFooter();

?>
