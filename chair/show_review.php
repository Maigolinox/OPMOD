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

printHeader("Review",1);

if (!isset($_GET['pid']) || !preg_match("/^\d+$/", $_GET['pid'])) {
	err('Invalid submission id');
} elseif(!isset($_GET['rid']) || !preg_match("/^\d+$/", $_GET['rid'])) {
	err('Invalid reviewer id');
}

$q = "SELECT `" . OCC_TABLE_PAPERREVIEWER . "`.*, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, `" . OCC_TABLE_PAPER . "`.`title`, `" . OCC_TABLE_PAPER . "`.`type` FROM `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`='" . safeSQLstr($_GET['rid']) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`='" . safeSQLstr($_GET['pid']) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` AND `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid`";
$r = ocsql_query($q) or err("Unable to get information");
if (ocsql_num_rows($r)!=1) {
	err("Review not found");
}
$l = ocsql_fetch_array($r);

require_once OCC_REVIEW_INC_FILE;

print '
<p><strong>Submission:</strong> <a href="show_paper.php?pid=' . safeHTMLstr($_GET['pid']) . '">' . safeHTMLstr($_GET['pid']) . ' - ' . safeHTMLstr($l['title']) . '</a></p>
<p><strong>Reviewer:</strong> <a href="show_reviewer.php?rid=' . safeHTMLstr($_GET['rid']) . '">' . safeHTMLstr($_GET['rid']) . ' - ' . safeHTMLstr($l['name']) . '</a></p>
';

displayReview($l, $_GET['rid'], $l['type']);

printFooter();

?>
