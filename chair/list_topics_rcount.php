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

if (isset($_REQUEST['cmt']) && ($_REQUEST['cmt'] == "rev")) {
	$cmtAdd = " AND `onprogramcommittee`='F'";
	$cmt = 'rev';
} elseif (isset($_REQUEST['cmt']) && ($_REQUEST['cmt'] == "pc")) {
	$cmtAdd = " AND `onprogramcommittee`='T'";
	$cmt = 'pc';
} else {
	$cmtAdd = '';
	$cmt = '';
}

printHeader("Committee Member Topic Count",1);

print '<p style="text-align: center;"><a href="list_topics_r.php?cmt=' . $cmt . '">Show Committee Members</a></p>';

if ($OC_configAR['OC_paperAdvocates']) {
	$options = '<option value="">All Committee Members</option><option value="rev">Review Committee</option><option value="pc">Program Committee (Advocates)</option>';
	print '
<form method="post" action="list_topics_rcount.php">
<input type="hidden" name="s" value="' . (isset($_REQUEST['s']) ? safeHTMLstr($_REQUEST['s']) : '') . '" />
<p style="text-align: center;">
<select name="cmt">' . preg_replace('/(value="' . $cmt . '")/', "$1 selected", $options) . '</select>
<input type="submit" value="Filter" />
</p>
</form>
';
}

$q = "SELECT `" . OCC_TABLE_TOPIC . "`.`topicid`, `" . OCC_TABLE_TOPIC . "`.`topicname`, `" . OCC_TABLE_TOPIC . "`.`short`, COUNT(`" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid`) AS `num` FROM `" . OCC_TABLE_TOPIC . "`, `" . OCC_TABLE_REVIEWERTOPIC . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_TOPIC . "`.`topicid`=`" . OCC_TABLE_REVIEWERTOPIC . "`.`topicid` AND `" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` $cmtAdd GROUP BY `" . OCC_TABLE_TOPIC . "`.`topicid`, `" . OCC_TABLE_TOPIC . "`.`topicname`, `" . OCC_TABLE_TOPIC . "`.`short` ORDER BY ";
if (!isset($_REQUEST['s']) || ($_REQUEST['s']=="topic")) {
	$q .= "`topicname`";
	$tsort = 'Topic<br />' . $OC_sortImg;
	$nsort = '<a href="' . $_SERVER['PHP_SELF'] . '?s=num&cmt=' . (isset($_REQUEST['cmt']) ? urlencode($_REQUEST['cmt']) : '') . '">Count</a>';
} else { 
	$q .= "`num`"; 
	$nsort = 'Count<br />' . $OC_sortImg;
	$tsort = '<a href="' . $_SERVER['PHP_SELF'] . '?s=topic&cmt=' . (isset($_REQUEST['cmt']) ? urlencode($_REQUEST['cmt']) : '') . '">Topic</a>';
}
$r = ocsql_query($q) or err("Unable to get information");
if (ocsql_num_rows($r) == 0) {
        print '<span class="warn">No reviewers have signed up yet</span><p>';
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
