<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

function oc_algoTopicMatch ($rpp, $ppr, $ppa) {
	global	$pAR, $rAR, $nAR,
			$pInfoAR, $continueVal, $onpc, $pcAR;

	if (isset($GLOBALS['continueFrom']) && !empty($GLOBALS['continueFrom'])) {
		global $continueFrom;
	} else {
		$continueFrom = 0;
	}
	
	if (oc_checkTimeout()) {	// shutdown if close to timeout
		oc_assignTimeoutShutdown();
	}
	
	$q = "SELECT `" . OCC_TABLE_PAPERTOPIC . "`.`paperid`, `" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid`, COUNT(*) AS `totmatch` FROM `" . OCC_TABLE_PAPERTOPIC . "`, `" . OCC_TABLE_REVIEWERTOPIC . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_PAPERTOPIC . "`.`topicid`=`" . OCC_TABLE_REVIEWERTOPIC . "`.`topicid` AND `" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` " . $onpc . " GROUP BY `paperid`, `reviewerid` ORDER BY `totmatch` DESC";
	$r = ocsql_query($q) or err("Unable to retrieve reviewers");
	while ($l=ocsql_fetch_array($r)) {
		if ($continueFrom && ($continueFrom != ($l['paperid']."-".$l['reviewerid']))) { 	// skip to where processing stopped
			continue;
		} else {	// reset continueFrom - excessive, need to clean up
			$continueFrom = 0;
			$continueVal = $l['paperid']."-".$l['reviewerid'];
		}

		// Skip submission (e.g., accepted subs)
		if (oc_skipSubAssignment($l['paperid'])) {
			continue;
		}
		
		if (!in_array($l['paperid']."-".$l['reviewerid'],$nAR)		// reviewer not in conflict
			&& (!in_array($l['reviewerid'],$pAR[$l['paperid']]))	// reviewer not yet assigned to paper
			&& (count($pAR[$l['paperid']]) < $rpp) 					// not enough reviewers yet
			&& (count($rAR[$l['reviewerid']]) < (in_array($l['reviewerid'], $pcAR) ? $ppa : $ppr)) ) 		// not enough papers yet
		{
			array_push($pAR[$l['paperid']],$l['reviewerid']);
			array_push($rAR[$l['reviewerid']],$l['paperid']);
		}
	}
}

oc_algoTopicMatch($reviewersPerPaper, $papersPerReviewer, $papersPerAdvocate);