<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

function oc_algoTopicMatch ($ppa) {
	global $pAR, $aAR, $nAR, $pInfoAR;
	
	$q = "SELECT `" . OCC_TABLE_PAPERTOPIC . "`.`paperid`, `" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid`, count( * ) AS `totmatch` FROM `" . OCC_TABLE_PAPERTOPIC . "`, `" . OCC_TABLE_REVIEWERTOPIC . "`, `" . OCC_TABLE_REVIEWER . "` WHERE `" . OCC_TABLE_REVIEWER . "`.`onprogramcommittee`='T' AND  `" . OCC_TABLE_REVIEWERTOPIC . "`.`topicid`=`" . OCC_TABLE_PAPERTOPIC . "`.`topicid` AND `" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` GROUP BY `paperid`, `reviewerid` ORDER BY `totmatch` DESC";
	$r = ocsql_query($q) or err("Unable to retrieve advocates");
	while ($l=ocsql_fetch_array($r)) {
		// Skip submission (e.g., accepted subs)
		if (oc_skipSubAssignment($l['paperid'])) {
			continue;
		}

		if (!in_array($l['paperid']."-".$l['reviewerid'],$nAR)	// advocate not in conflict
    		&& empty($pAR[$l['paperid']]) 						// no advocate set yet
    		&& (count($aAR[$l['reviewerid']]) < $ppa) ) 		// not enough papers yet
		{
			$pAR[$l['paperid']] = $l['reviewerid'];
			array_push($aAR[$l['reviewerid']],$l['paperid']);
		}
	}
}

oc_algoTopicMatch($papersPerAdvocate);
