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

// accepted or all submissions?
if (isset($_REQUEST['acc']) && preg_match("/\d+/", $_REQUEST['acc']) && isset($OC_acceptanceValuesAR[$_REQUEST['acc']])) {
	$accSQL = "AND `" . OCC_TABLE_PAPER . "`.`accepted`='" . safeSQLstr($OC_acceptanceValuesAR[$_REQUEST['acc']]['value']) . "'";
	$accURL = 'acc=' . $_REQUEST['acc'];
	$accReq = $_REQUEST['acc'];
}
else {
	$accSQL = '';
	$accURL = '';
	$accReq = '';
}

// sort order?
if (isset($_REQUEST['s']) && ($_REQUEST['s'] == 'paperid')) {
	$sort = '`paperid`, `topicid`';
	$psort = 'Submission ID. Title<br />' . $OC_sortImg;
	$tsort = '<a href="' . $_SERVER['PHP_SELF'] . '?s=topicid&' . $accURL . '">Topic (ID)</a>';
	$sortfld = 'paperid';
} else {
	$sort = '`topicid`, `paperid`';
	$psort = '<a href="' . $_SERVER['PHP_SELF'] . '?s=paperid&' . $accURL . '">Submission ID. Title</a>';
	$tsort = 'Topic (ID)<br />' . $OC_sortImg;
	$sortfld = 'topicid';
}

printHeader("Submission Topics", 1);

$accOptions = '';
foreach ($OC_acceptanceValuesAR as $idx => $acc) {
	$accOptions .= '<option value="' . safeHTMLstr($idx) . '">' . safeHTMLstr($acc['value']) . '</option>';
}

print '
<p style="text-align: center"><a href="list_topics_pcount.php?' . $accURL . '">Show Count Only</a></p>

<form method="post" action="list_topics_p.php">
<input type="hidden" name="s" value="' . (isset($_REQUEST['s']) ? safeHTMLstr($_REQUEST['s']) : '') . '" />
<p style="text-align: center;">
<select name="acc">
<option value="">All Submissions</option>
' . preg_replace('/(value="' . preg_quote($accReq) . '")/', "$1 selected", $accOptions) . '
</select>
<input type="submit" value="Filter" />
</p>
</form>
';

$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_TOPIC . "`.`topicid`, `" . OCC_TABLE_PAPER . "`.`title`, `" . OCC_TABLE_TOPIC . "`.`topicname`, `" . OCC_TABLE_TOPIC . "`.`short` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_TOPIC . "`, `" . OCC_TABLE_PAPERTOPIC . "` WHERE `" . OCC_TABLE_PAPERTOPIC . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` AND `" . OCC_TABLE_PAPERTOPIC . "`.`topicid`=`" . OCC_TABLE_TOPIC . "`.`topicid` $accSQL ORDER BY $sort";
$r = ocsql_query($q) or err("Unable to get information");
if (ocsql_num_rows($r) == 0) {
        print '<span class="warn">No submissions available</span><p>';
} else {
	print '
<table border="0" cellspacing="1" cellpadding="4" cols="2" style="margin: 0 auto">
<tr class="rowheader"><th>' . $tsort . '</th><th colspan=2>' . $psort . '</th></tr>
	';
	$currid = null;
	$row = 1;

	if (isset($_REQUEST['s']) && ($_REQUEST['s'] == 'paperid')) {
		while ($l = ocsql_fetch_array($r)) {
			if ($l['paperid'] == $currid) {
				print '<tr class="row' . $row . '"><td>' . safeHTMLstr(useTopic($l['short'], $l['topicname']) . ' (' . $l['topicid'] . ')') . '</td><td>&nbsp;</td></tr>';
			} else {
				$row = $rowAR[$row];
				print '<tr class="row' . $row . '"><td>' . safeHTMLstr(useTopic($l['short'], $l['topicname']) . ' (' . $l['topicid'] . ')') . '</td><td><a href="show_paper.php?pid=' . $l['paperid'] . '">' . $l['paperid'] . '. ' . safeHTMLstr($l['title']) . '</a></td></tr>';
			}
			$currid = $l['paperid'];
		}
	} else {
		while ($l = ocsql_fetch_array($r)) {
			if ($l['topicid'] == $currid) {
				print '<tr class="row' . $row . '"><td>&nbsp;</td><td><a href="show_paper.php?pid=' . $l['paperid'] . '">' . $l['paperid'] . '. ' . safeHTMLstr($l['title']) . '</a></td></tr>';
			} else {
				$row = $rowAR[$row];
				print '<tr class="row' . $row . '"><td>' . safeHTMLstr(useTopic($l['short'], $l['topicname']) . ' (' . $l['topicid'] . ')') . '</td><td><a href="show_paper.php?pid=' . $l['paperid'] . '">' . $l['paperid'] . '. ' . safeHTMLstr($l['title']) . '</a></td></tr>';
			}
			$currid = $l['topicid'];
		}
	}
	print '</table>';
}

printFooter();

?>
