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

$hdr = ''; // set these so req OCC_COMMITTEE_INC_FILE below skips printHeader
$hdrfn = 0;

printHeader("Committee Member Profile",1);

print '<p style="text-align: center"><a href="list_reviewers.php">View All Committee Members</a></p>';

if (!isset($_GET['rid']) || !ctype_digit($_GET['rid'])) {
	err('Reviewer ID is invalid');
}

require_once OCC_FORM_INC_FILE;
require_once OCC_COMMITTEE_INC_FILE;

$extra = '';

// Get reviewer
$q = "SELECT * FROM `" . OCC_TABLE_REVIEWER . "` WHERE `reviewerid`='" . safeSQLstr($_GET['rid']) . "'";
$r = ocsql_query($q) or err("Unable to get information");
$l = ocsql_fetch_array($r);

// Get topics
$qt = "SELECT `topicid` FROM `" . OCC_TABLE_REVIEWERTOPIC . "` WHERE `reviewerid`='" . safeSQLstr($_GET['rid']) . "'";
$rt = ocsql_query($qt) or err("Unable to get topics ");
$l['topics'] = array();
while ($t = ocsql_fetch_array($rt)) {
  $l['topics'][] = $t['topicid'];
}

$advocating = '';
if ($OC_configAR['OC_paperAdvocates']) {
	$aq = "SELECT `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`title` FROM `" . OCC_TABLE_PAPERADVOCATE . "`, `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`='" . safeSQLstr($_GET['rid']) . "' AND `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid`";
	$ar = ocsql_query($aq) or err("Unable to get advocating info");
	if (ocsql_num_rows($ar) > 0) {
		$advocating .= '<tr><th>Advocating:</th><td>';
		while ($al = ocsql_fetch_array($ar)) {
			$advocating .= '<a href="show_adv_review.php?a=' . safeHTMLstr($_GET['rid']) . '&p=' . $al['paperid'] . '">' . $al['paperid'] . '. ' . safeHTMLstr($al['title']) . '</a><br />';
		}
		$advocating .= '</td></tr>';
	}
}

$reviewing = '';
$rq = "SELECT `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`title` FROM `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`='" . safeSQLstr($_GET['rid']) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid`";
$rr = ocsql_query($rq) or err("Unable to get reviewing info");
if (ocsql_num_rows($rr) > 0) {
	$reviewing .= '<tr><th>Reviewing:</th><td>';
	while ($rl = ocsql_fetch_array($rr)) {
		$reviewing .= '<a href="show_review.php?rid=' . safeHTMLstr($_GET['rid']) . '&pid=' . $rl['paperid'] . '">' . $rl['paperid'] . '. ' . safeHTMLstr($rl['title']) . '</a><br />';
	}
	$reviewing .= '</td></tr>';
}

if (OCC_CHAIR_PWD_TRUMPS) {
	print '
<form method="post" action="../review/update.php">
<input type="hidden" name="c" value="1" />
<input type="hidden" name="rid" value="' . safeHTMLstr($_GET['rid']) . '" />
<p style="text-align: center;"><input type="submit" name="submit" value="Edit Profile" /></p>
</form>

<table class="ocfields">
<tr><th>Member ID:</th><td>' . safeHTMLstr($_GET['rid']) . '</td></tr>
<tr><th>Username:</th><td>' . safeHTMLstr($l['username']) . '</td></tr>
<tr><th>Signed Up:</th><td>' . safeHTMLstr($l['signupdate']) . '</td></tr>
<tr><th>Last Signed In:</th><td>' . safeHTMLstr($l['lastsignin']) . '</td></tr>
';
}

if ($OC_configAR['OC_paperAdvocates']) {
	print '<tr><th>Advocate/PC:</th><td> ' . (($l['onprogramcommittee'] == 'T') ? 'Yes' : 'No') . '</td></tr>';
}

oc_showFieldSet($OC_reviewerFieldSetAR, $OC_reviewerFieldAR, $l);

$extra = '';
if (oc_hookSet('chair-show_reviewer')) {
	foreach ($GLOBALS['OC_hooksAR']['chair-show_reviewer'] as $hook) {
		require_once $hook;
	}
}

print $extra . $advocating . $reviewing;


print '</table><br />';

printFooter();

?>
