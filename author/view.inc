<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// included from edit.php

$spq = "SELECT * FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid`='" . safeSQLstr($pid) . "'";
$spr = ocsql_query($spq) or err(oc_('Unable to retrieve submission'));
if (ocsql_num_rows($spr) != 1) { 
	warn(oc_('Submission ID is invalid'));
	exit;
}
$spl = ocsql_fetch_assoc($spr);

ocsql_query("UPDATE `" . OCC_TABLE_PAPER . "` SET `edittoken`=NULL, `edittime`=NULL WHERE `paperid`='" . safeSQLstr($pid) . "' LIMIT 1");

// Get authors
$oc_authorNum = 0;
$qa = "SELECT * FROM `" . OCC_TABLE_AUTHOR . "` WHERE `paperid`='" . safeSQLstr($pid) . "' ORDER BY `position`";
$ra = ocsql_query($qa) or err(oc_('Unable to retrieve author(s) information'));
while ($a = ocsql_fetch_array($ra)) {
	$apos = $a['position'];
	foreach ($a as $akey => $aval) {
		if (preg_match("/^(?:paperid|position)$/", $akey)) { continue; }
		$spl[$akey . $apos] = $aval;
	}
}
$oc_authorNum = $apos;

// Get topics
$qt = "SELECT `topicid` FROM `" . OCC_TABLE_PAPERTOPIC . "` WHERE `paperid`='" . safeSQLstr($pid) . "'";
$rt = ocsql_query($qt) or err(oc_('Unable to retrieve topic(s) information'));
$spl['topics'] = array();
while ($t = ocsql_fetch_array($rt)) {
  $spl['topics'][] = $t['topicid'];
}

require_once OCC_FORM_INC_FILE;
require_once OCC_SUBMISSION_INC_FILE;

print '
<table class="ocfields">
<tr><th>' . safeHTMLstr(oc_('Submission ID')) . ':</th><td>' . safeHTMLstr($pid) . '</td></tr>
';

oc_showFieldSet($OC_submissionFieldSetAR, $OC_submissionFieldAR, $spl);

if (oc_hookSet('author-show_paper')) {
	foreach ($GLOBALS['OC_hooksAR']['author-show_paper'] as $hook) {
		require_once $hook;
	}
}

print '</table>';

?>
