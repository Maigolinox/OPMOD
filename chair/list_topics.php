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

printHeader("All Submission Topics by Score", 1);

// Get all papers title, keywords, and score
$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `keywords`, ABS(FORMAT(AVG(`score`),2)) AS `recavg`, `title` FROM `" . OCC_TABLE_PAPER . "` LEFT JOIN `" . OCC_TABLE_PAPERREVIEWER . "` ON `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERREVIEWER . "`.`paperid` GROUP BY `paperid`, `title`, `keywords` ORDER BY `recavg` DESC, `paperid`";
$r = ocsql_query($q) or err("Unable to get information");
if (ocsql_num_rows($r) == 0) { print '<span class="warn">No papers to display.</span><p>'; }
else {
	print '
<dl>
<dt><strong>Functions:</strong></dt>
<dd>Click on <em>Score</em> links to see individual reviewer scores and accept/reject submission</dd>
<dd>Click on <em>Submission ID. Title</em> links to see submission information</dd>
<br />
<dt><strong>Definitions:</strong></dt>
<dd><em>score</em> = average score (if no score, ignored)</dd>
</dl>
';

	// Get topic names author listed their paper under
	$topicAR = array();
	$q2 = "SELECT `" . OCC_TABLE_PAPERTOPIC . "`.`paperid`, `topicname`, `short` FROM `" . OCC_TABLE_PAPERTOPIC . "`, `" . OCC_TABLE_TOPIC . "` WHERE `" . OCC_TABLE_PAPERTOPIC . "`.`topicid`=`" . OCC_TABLE_TOPIC . "`.`topicid` ORDER BY `" . OCC_TABLE_PAPERTOPIC . "`.`paperid`";
	$r2 = ocsql_query($q2) or err("Unable to get submission topics");
	while ($l2 = ocsql_fetch_array($r2)) {
		if (array_key_exists($l2['paperid'],$topicAR)) {
			$topicAR[$l2['paperid']] .= "<li>" . useTopic($l2['short'],$l2['topicname']) . "\n";
		} else {
			$topicAR[$l2['paperid']] = "<li>" . useTopic($l2['short'],$l2['topicname']) . "\n";
		}
	}

	// Get topic (sesion) names reviewers listed papers as belonging to
	$sessionAR = array();
	$q3 = "SELECT `" . OCC_TABLE_PAPERSESSION . "`.`paperid`, `topicname`, `short` FROM `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_PAPERSESSION . "`, `" . OCC_TABLE_TOPIC . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`score` IS NOT NULL AND `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`=`" . OCC_TABLE_PAPERSESSION . "`.`paperid` AND `" . OCC_TABLE_TOPIC . "`.`topicid`=`" . OCC_TABLE_PAPERSESSION . "`.`topicid` GROUP BY `" . OCC_TABLE_PAPERSESSION . "`.`paperid`, `topicname`, `short` ORDER BY `" . OCC_TABLE_PAPERSESSION . "`.`paperid`";
	$r3 = ocsql_query($q3) or err("Unable to get reviewer sessions");
	while ($l3 = ocsql_fetch_array($r3)) {
		if (array_key_exists($l3['paperid'],$sessionAR)) {
			$sessionAR[$l3['paperid']] .= "<li>" . useTopic($l3['short'],$l3['topicname']) . "\n";
		} else {
			$sessionAR[$l3['paperid']] = "<li>" . useTopic($l3['short'],$l3['topicname']) . "\n";
		}
	}

	while ($l = ocsql_fetch_array($r)) {
		if (!empty($l['recavg'])) {
			$usescore = number_format($l['recavg'], 2);
		} else {
			$usescore = '&#8211;';
		}

		print '<hr><strong><a href="show_paper.php?pid=' . $l['paperid'] . '">' . $l['paperid'] . ". " . safeHTMLstr($l['title']) . '</a></strong><p>Score: <strong><a href="show_scores.php?pid=' . $l['paperid'] . '">' . $usescore . "</a></strong><p>\nKeywords: " . safeHTMLstr($l['keywords']) . "<p>\n" . OCC_WORD_AUTHOR . " Topics:<ul>\n" . varValue($l['paperid'], $topicAR) . "</ul>\n";

		print "Reviewer Sessions:<ul>\n" . varValue($l['paperid'], $sessionAR) . "</ul>\n";

	}
}

printFooter();

?>
