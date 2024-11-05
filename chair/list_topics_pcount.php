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

printHeader("Submission Topic Count", 1);

$accOptions = '';
foreach ($OC_acceptanceValuesAR as $idx => $acc) {
	$accOptions .= '<option value="' . safeHTMLstr($idx) . '">' . safeHTMLstr($acc['value']) . '</option>';
}

print '
<p style="text-align: center"><a href="list_topics_p.php?' . $accURL . '">Show Submissions</a></p>

<form method="post" action="list_topics_pcount.php">
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

$q = "SELECT `" . OCC_TABLE_TOPIC . "`.`topicid`, `" . OCC_TABLE_TOPIC . "`.`topicname`, `" . OCC_TABLE_TOPIC . "`.`short`, COUNT(*) AS `num` FROM `" . OCC_TABLE_TOPIC . "`, `" . OCC_TABLE_PAPERTOPIC . "`, `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_TOPIC . "`.`topicid`=`" . OCC_TABLE_PAPERTOPIC . "`.`topicid` AND `" . OCC_TABLE_PAPERTOPIC . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` $accSQL GROUP BY `" . OCC_TABLE_TOPIC . "`.`topicid`, `" . OCC_TABLE_TOPIC . "`.`topicname`, `" . OCC_TABLE_TOPIC . "`.`short` ORDER BY ";
if (!isset($_REQUEST['s']) || ($_REQUEST['s']=="topicname")) {
	$q .= "`topicname`";
	$tsort = 'Topic<br />' . $OC_sortImg;
	$nsort = '<a href="' . $_SERVER['PHP_SELF'] . '?s=num&' . $accURL . '">Count</a>';
} else { 
	$q .= "`num`"; 
	$nsort = 'Count<br />' . $OC_sortImg;
	$tsort = '<a href="' . $_SERVER['PHP_SELF'] . '?s=topicname&' . $accURL . '">Topic</a>';
}
$r = ocsql_query($q) or err('Unable to get information');
if (ocsql_num_rows($r) == 0) {
        print '<span class="warn">No submissions available</span><p>';
} else {
	print '<table border="0" cellpadding="5" cellspacing="1" style="margin: 0 auto"><tr class="rowheader"><th valign="top">' . $tsort . '</th><th valign="top">' . $nsort .'</th></tr>';
	$row = 1;
	while ($l = ocsql_fetch_array($r)) {
	  	print '<tr class="row' . $row . '"><td>' . safeHTMLstr(useTopic($l['short'],$l['topicname'])) . '</td><td align="right">' . $l['num'] . "</td></tr>\n";
		if ($row==1) { $row=2; } else { $row=1; }
	}
	print "</table>";
}

printFooter();

?>
