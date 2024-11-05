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

printHeader("Advocate Recommendation",1);

if (!isset($_GET['p']) || !preg_match("/^\d+$/", $_GET['p'])) {
	err('Submission ID invalid');
}

$q = "SELECT `" . OCC_TABLE_PAPER . "`.`title`, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, `" . OCC_TABLE_PAPERADVOCATE . "`.`adv_recommendation`, `" . OCC_TABLE_PAPERADVOCATE . "`.`adv_comments` FROM `" . OCC_TABLE_PAPERADVOCATE . "`, `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`='" . safeSQLstr($_GET['p']) . "' AND `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`='" . safeSQLstr($_GET['a']) . "' AND `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` AND `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
$r = ocsql_query($q) or err("Unable to get advocate review");
if (ocsql_num_rows($r) != 1) { err("Invalid paper/advocate match"); }

$l=ocsql_fetch_array($r);

print '
<table border=1 cellspacing=0 cellpadding=3>
<tr><td>Submission ID:</td><td>' . safeHTMLstr($_GET['p']) . '</td></tr>
<tr><td>Title:</td><td><a href="show_paper.php?pid=' . safeHTMLstr($_GET['p']) . '">' . safeHTMLstr($l['title']) . '</a></td></tr>
<tr><td>Advocate:</td><td><a href="show_reviewer.php?rid=' . safeHTMLstr($_GET['a']) . '">' . safeHTMLstr($l['name']) . '</a></td></tr>
<tr><td>Recommendation:</td><td>'.$l['adv_recommendation'].'</td></tr>
<tr><td>Comments:</td><td>';
if (!empty($l['adv_comments'])) { print safeHTMLstr($l['adv_comments']); } else { print "&nbsp;"; }
print '</td></tr>
</table>
';

print '<p><a href="list_advocates.php">Return to Advocate Listings</a><p>';

printFooter();

?>
