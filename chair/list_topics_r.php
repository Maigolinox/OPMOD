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

printHeader('Committee Member Topics', 1);

print '<p style="text-align: center;"><a href="list_topics_rcount.php?cmt=' . $cmt . '">Show Count Only</a></p>';

if ($OC_configAR['OC_paperAdvocates']) {
	$options = '<option value="">All Committee Members</option><option value="rev">Review Committee</option><option value="pc">Program Committee (Advocates)</option>';
	print '
<form method="post" action="list_topics_r.php">
<input type="hidden" name="s" value="' . (isset($_REQUEST['s']) ? safeHTMLstr($_REQUEST['s']) : '') . '" />
<p style="text-align: center;">
<select name="cmt">' . preg_replace('/(value="' . $cmt . '")/', "$1 selected", $options) . '</select>
<input type="submit" value="Filter" />
</p>
</form>
';
}

if (!isset($_REQUEST['s']) || empty($_REQUEST['s']) || ($_REQUEST['s'] == "topic")) {
	$sortby = ($OC_configAR['OC_topicDisplayAlpha'] ? '`short`, `topicname`' : '`topicid`') . ', `reviewerid`';
	$topicsortstr = 'Topic ' . $OC_sortImg;
	$memberidsortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=memberid&cmt=' . $cmt . '">ID</a>';
	$membernamesortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=member&cmt=' . $cmt . '">Name</a>';
} elseif ($_REQUEST['s'] == "memberid") {
	$sortby = "`reviewerid`";
	$topicsortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=topic&cmt=' . $cmt . '">Topic</a>';
	$memberidsortstr = 'ID ' . $OC_sortImg;
	$membernamesortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=member&cmt=' . $cmt . '">Name</a>';
} else {	// member sort
	$sortby = "`name_last`, `name_first`";
	$topicsortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=topic&cmt=' . $cmt . '">Topic</a>';
	$memberidsortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=memberid&cmt=' . $cmt . '">ID</a>';
	$membernamesortstr = 'Name ' . $OC_sortImg;
}

$q = "SELECT `" . OCC_TABLE_REVIEWER . "`.`reviewerid`, `" . OCC_TABLE_TOPIC . "`.`topicid`, CONCAT_WS(' ', `" . OCC_TABLE_REVIEWER . "`.`name_first`, `" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, `onprogramcommittee`, `" . OCC_TABLE_TOPIC . "`.`topicname`, `" . OCC_TABLE_TOPIC . "`.`short` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_TOPIC . "`, `" . OCC_TABLE_REVIEWERTOPIC . "` WHERE `" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` AND `" . OCC_TABLE_REVIEWERTOPIC . "`.`topicid`=`" . OCC_TABLE_TOPIC . "`.`topicid` $cmtAdd ORDER BY " . $sortby;
$r = ocsql_query($q) or err('Unable to get information ');
if (ocsql_num_rows($r) == 0) {
        print '<span class="warn">No committee members with topics found.</span><p>';
} else {
	print '
<table border=0 cellspacing="1" cellpadding="4" cols="2" style="margin: 0 auto">
<tr class="rowheader"><th>' . $topicsortstr . '</th><th colspan=2>Committee Member ' . $memberidsortstr . '. ' . $membernamesortstr . '</th></tr>
';
	$currid = null;
	$row = 2; // Set to 2 to handle same IDs

	if (!isset($_REQUEST['s']) || empty($_REQUEST['s']) || ($_REQUEST['s'] == "topic")) {

		while ($l = ocsql_fetch_array($r)) {
			if ($currid == $l['topicid']) {
				print '<tr class="row' . $row . '"><td>&nbsp;</td>';
			} else { 
				$row = $rowAR[$row];
				print '<tr class="row' . $row . '"><td>' . safeHTMLstr(useTopic($l['short'],$l['topicname'])) . '</td>';
				$currid = $l['topicid'];
			}
			print '<td><a href="show_reviewer.php?rid=' . $l['reviewerid'] . '">' . $l['reviewerid'] . '. ' . safeHTMLstr($l['name']) . '</a>';
			if (($cmt != 'pc') && ($l['onprogramcommittee'] == 'T')) {
				print " [PC]";
			}
			print "</td></tr>\n";
		}
	} else {
		while ($l = ocsql_fetch_array($r)) {
			if ($currid == $l['reviewerid']) {
				print '<tr class="row' . $row . '"><td>' . safeHTMLstr(useTopic($l['short'],$l['topicname'])) . '</td><td>&nbsp;</td>';
			} else { 
				$row = $rowAR[$row];
				$currid = $l['reviewerid'];
				print '<tr class="row' . $row . '"><td>' . safeHTMLstr(useTopic($l['short'],$l['topicname'])) . '</td><td><a href="show_reviewer.php?rid=' . $l['reviewerid'] . '">' . $l['reviewerid'] . '. ' . safeHTMLstr($l['name']) . '</a>';
				if (($cmt != 'pc') && ($l['onprogramcommittee'] == 'T')) {
					print " [PC]";
				}
				print '</td>';
			}
			print "</tr>\n";
		}
	}
	
	print '</table>';
}

printFooter();

?>
