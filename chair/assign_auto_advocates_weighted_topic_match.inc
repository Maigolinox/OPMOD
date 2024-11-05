<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

function oc_algoAdvocateWeightedTopicMatch ($ppa,$sortorder) {
	global $pAR, $aAR, $nAR, $pInfoAR;

	$topPAR = array();
	$topicReviewerAR = array();
	$topicScoreAR = array();
	$paperScoreAR = array();
	$reviewerScoreAR = array();
	$paperTopicAR = array();
	$reviewerTopicAR = array();
	
	// Get paper topics	
	$q = "SELECT `" . OCC_TABLE_PAPERTOPIC . "`.`paperid`, `" . OCC_TABLE_PAPERTOPIC . "`.`topicid` FROM `" . OCC_TABLE_PAPERTOPIC . "` ORDER BY `topicid`";
	$r = ocsql_query($q) or err("Unable to get submission topics");
	while ($l=ocsql_fetch_array($r)) {
		// Add topic to paper
		if (!isset($paperTopicAR[$l['paperid']])) {
			$paperTopicAR[$l['paperid']] = array();
		}
		array_push($paperTopicAR[$l['paperid']],$l['topicid']);
		$paperScoreAR[$l['paperid']] = 0;
		
		// Increment paper-topic count		
		if (isset($topPAR[$l['topicid']])) {
			$topPAR[$l['topicid']]++;
		} else {
			$topPAR[$l['topicid']] = 1;
		}
	}

	// Get advocate topics
	$q = "SELECT `" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid`, `" . OCC_TABLE_REVIEWERTOPIC . "`.`topicid`, `" . OCC_TABLE_REVIEWER . "`.`onprogramcommittee` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_REVIEWERTOPIC . "` WHERE `" . OCC_TABLE_REVIEWER . "`.`onprogramcommittee`='T' AND `" . OCC_TABLE_REVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid` ORDER BY `topicid`";
	$r = ocsql_query($q) or err("Unable to get advocate topics");
	while ($l=ocsql_fetch_array($r)) {
		// Add topic to advocate
		if (!isset($reviewerTopicAR[$l['reviewerid']])) {
			$reviewerTopicAR[$l['reviewerid']] = array();
		}
		array_push($reviewerTopicAR[$l['reviewerid']],$l['topicid']);
		$reviewerScoreAR[$l['topicid']] = 0;

		// Increment reviewer-topic count		
		if (!isset($topicReviewerAR[$l['topicid']])) {
			$topicReviewerAR[$l['topicid']] = array();
		}
		array_push($topicReviewerAR[$l['topicid']],$l['reviewerid']);

	}
	
	// Get list of topics used
	$topList = array_unique(array_merge(array_keys($topPAR), array_keys($topicReviewerAR)));

	// Calculate topic score = #advocates / #papers
	foreach($topList as $t) {
		if (isset($topPAR[$t]) && ($topPAR[$t] > 0) && isset($topicReviewerAR[$t]) && (count($topicReviewerAR[$t]) > 0)) {
			$topicScoreAR[$t] = count($topicReviewerAR[$t]) / $topPAR[$t];
		}
		else $topicScoreAR[$t] = 0;
	}

	// Calculate paper scores
	foreach ($paperTopicAR as $paperid => $topiclist) {
		$score = 0;
		foreach ($topiclist as $topicid) {
			$score += $topicScoreAR[$topicid];
		}
		$paperScoreAR[$paperid] = $score;
	}
	asort($paperScoreAR);
	
	// Calculate advocate scores	
	foreach ($reviewerTopicAR as $reviewerid => $topiclist) {
		$score = 0;
		foreach ($topiclist as $topicid) {
			$score += $topicScoreAR[$topicid];
		}
		$reviewerScoreAR[$reviewerid] = $score;
	}
	asort($reviewerScoreAR);

	// Iterate through papers (score low to high) assigning 
	// advocates in order of their score

	foreach (array_keys($paperScoreAR) as $paperid) {
		// Skip if paper already has advocate
		if (!empty($pAR[$paperid])) { continue; }
		// Skip submission (e.g., accepted subs)
		if (oc_skipSubAssignment($paperid)) {
			continue;
		}
		// Create a list of advocates for paper incl. scores
		$Rs = array();
		if (isset($paperTopicAR[$paperid])) {
			foreach($paperTopicAR[$paperid] as $topicid) {
				if (isset($topicReviewerAR[$topicid])) {	// may not have a reviewer for every topic
					foreach ($topicReviewerAR[$topicid] as $reviewerid) {
						if (!in_array($paperid."-".$reviewerid,$nAR)	// advocate not in conflict
							&& (count($aAR[$reviewerid]) < $ppa) ) 		// not enough papers yet
						{
							$Rs[$reviewerid] = $reviewerScoreAR[$reviewerid];
						}
					}
				}
			}
		}
		// Assign advocate based on reviewer score high->low
		if (count($Rs) > 0) {
			if ($sortorder=="1") { arsort($Rs); }
			else { asort($Rs); }
			reset($Rs);
			$pAR[$paperid] = key($Rs);
			array_push($aAR[key($Rs)],$paperid);
		}	
	}
}

oc_algoAdvocateWeightedTopicMatch($papersPerAdvocate,1);
