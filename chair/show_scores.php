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

// Pre-process hooks
if (oc_hookSet('show_scores-pre')) {
	foreach ($OC_hooksAR['show_scores-pre'] as $h) {
		require_once $h;
	}
}

printHeader("Review Scores",1);

print '
<p align="center"><a href="list_scores.php?s=' . safeHTMLstr(varValue('s', $_GET)) . '">List All Submissions by Score</a></p>
';

$pid = $_REQUEST['pid'];

if (!preg_match("/^\d+$/",$pid)) {
	warn("Invalid Submission ID");
}

if (isset($_POST['submit']) && !empty($_POST['submit'])) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	if ($_POST['submit'] == "Pending") {
		$q = "UPDATE `" . OCC_TABLE_PAPER . "` SET `accepted`=NULL, `decision_date`=NULL WHERE `paperid`='" . $pid . "'";
		ocsql_query($q) or err("Unable to set submission acceptance");
	} elseif (isset($OC_acceptanceColorAR[$_POST['submit']])) {
		$q = "UPDATE `" . OCC_TABLE_PAPER . "` SET `accepted`='" . safeSQLstr($_POST['submit']) . "', `decision_date`='" . safeSQLstr(date('Y-m-d')) . "' WHERE `paperid`='" . $pid . "'";
		ocsql_query($q) or err("Unable to set submission acceptance");
	} elseif ($_POST['submit'] == "Submit Notes") {
		$q = "UPDATE `" . OCC_TABLE_PAPER . "` SET `pcnotes`='" . safeSQLstr($_POST['pcnotes']) . "' WHERE `paperid`='" . $pid . "'";
		ocsql_query($q) or err("Unable to update notes");
	}
}

$q2 = "SELECT CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, `advocateid`, `adv_recommendation`, `adv_comments` FROM `" . OCC_TABLE_PAPERADVOCATE . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`='" . $pid . "' AND `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
$r2 = ocsql_query($q2) or err("Unable to get recommendation");
if (ocsql_num_rows($r2) == 0) {
	$l2['adv_recommendation'] = '<em>No advocate was assigned to this submission</em><p>';
	$l2['advocateid'] = NULL;
	$l2['name'] = NULL;
	$advcmts = '';
} else {
	$l2=ocsql_fetch_array($r2);
	if (empty($l2['adv_recommendation'])) {
		$l2['adv_recommendation'] = '<em>Not yet provided</em>';
		$advcmts = '';
	} else {
		$advcmts = safeHTMLstr($l2['adv_comments']);
		$advcmts = preg_replace("/\n/","<br>\n",$advcmts);
	}
} 

$q3 = "SELECT `accepted`, `format`, `title`, `type`, `pcnotes` FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid`='" . $pid . "'";
$r3 = ocsql_query($q3) or err("Unable to get submission info");
if (ocsql_num_rows($r3) != 1) {
	warn("Unable to retrieve submission info");
	exit;
} else {
	$l3 = ocsql_fetch_array($r3);

	$q = "SELECT `" . OCC_TABLE_PAPERREVIEWER . "`.*, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name` FROM `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`='" . $pid . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` ORDER BY `score`, `reviewerid`";
	$r = ocsql_query($q) or err("Unable to get scores");

	if (oc_hookSet('show_scores')) {
		foreach ($OC_hooksAR['show_scores'] as $h) {
			require_once $h;
		}
	}

	print '
<strong>Submission ID:</strong> ' . $pid . '<br />
<strong>Title:</strong> <a href="show_paper.php?pid=' . $pid . '">' . safeHTMLstr($l3['title']) . '</a><br />';

	if (!empty($l3['type'])) {
		print '<strong>Type:</strong> ' . safeHTMLstr($l3['type']) . '<br />';
	}

	print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="pid" value="' . $pid . '">
<dl>
<dt><strong>Decision:</strong> <em>' . (!empty($l3['accepted']) ? $l3['accepted'] : 'Pending') . '</em>
</dt><dd><br />Change to: &nbsp; 
';

	foreach ($OC_acceptanceValuesAR as $acc) {
		if ($l3['accepted'] != $acc['value']) {
			print '<input type="submit" name="submit" value="' . safeHTMLstr($acc['value']) . '"> &nbsp; &nbsp; ';
		}
	}

	if (!empty($l3['accepted'])) {
		print '<input type="submit" name="submit" value="Pending">';
	}

	print '
</dd>
<br />
<dt><strong>' . OCC_WORD_CHAIR . ' Notes:</strong><br /><br /></dt>
<dd><textarea rows=5 cols=60 name="pcnotes">' . safeHTMLstr($l3['pcnotes']) . '</textarea></dd>
<dd><input type="submit" name="submit" value="Submit Notes"></dd></dl>
</form>
';

	if ($OC_configAR['OC_paperAdvocates']) {
		print '
<p><hr /></p>
<dl>
<dt><strong>Advocate: <a href="show_reviewer.php?rid=' . $l2['advocateid'] . '">'. $l2['advocateid'] . ' - ' . safeHTMLstr($l2['name']) . '</a></strong></dt>
<dt><strong>Recommendation:</strong> ' . $l2['adv_recommendation'] . '</dt>
<dt><strong>Comments:</strong></dt>
<dd>'.$advcmts.'</dd>
</dl>
';
	}

	require_once OCC_REVIEW_INC_FILE;

	displayReviews($pid, $r, $l3['type']);

	// Additional data?
	if (oc_hookSet('show_scores-bottom')) {
		foreach ($OC_hooksAR['show_scores-bottom'] as $h) {
			require_once $h;
		}
	}

} // else show paper info

printFooter();

?>
