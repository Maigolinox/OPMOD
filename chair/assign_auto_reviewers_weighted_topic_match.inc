<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

function oc_algoWeightedTopicMatch ($rpp, $ppr, $ppa, $sortorder) {
	global	$pAR, $rAR, $nAR,
			$paperScoreAR, $paperTopicAR, $topicReviewerAR, $reviewerScoreAR,
			$pInfoAR, $continueVal, $onpc, $pcAR;

	$GLOBALS['continueAR'] = array('paperScoreAR', 'paperTopicAR', 'topicReviewerAR', 'reviewerScoreAR');

	if (isset($GLOBALS['continueFrom']) && !empty($GLOBALS['continueFrom'])) {
		global $continueFrom;
	} else {
		$continueFrom = 0;
		$continueVal = 0;
		$reviewerTopicAR = array();
		$topPAR = array();
		$topicScoreAR = array();
		$topicReviewerAR = array();
		$paperScoreAR = array();
		$reviewerScoreAR = array();
		$paperTopicAR = array();

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

		// Get reviewer topics
		$q = "SELECT `" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid`, `" . OCC_TABLE_REVIEWERTOPIC . "`.`topicid` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_REVIEWERTOPIC . "` WHERE `" . OCC_TABLE_REVIEWERTOPIC . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid` " . $onpc . " ORDER BY `topicid`";
		$r = ocsql_query($q) or err("Unable to get reviewer topics");
		while ($l=ocsql_fetch_array($r)) {
			// Add topic to reviewer
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

		// Calculate topic score = #reviewers / #papers
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

		// Calculate reviewer scores	
		foreach ($reviewerTopicAR as $reviewerid => $topiclist) {
			$score = 0;
			foreach ($topiclist as $topicid) {
				$score += $topicScoreAR[$topicid];
			}
			$reviewerScoreAR[$reviewerid] = $score;
		}
		asort($reviewerScoreAR);
	}

	// Iterate through papers (score low to high) assigning 
	// REVIEWERS in order of their score
	foreach (array_keys($paperScoreAR) as $paperid) {
		if ($continueFrom && ($paperid != $continueFrom)) { 	// skip to where processing stopped
			continue;
		}
		else {	// reset continueFrom - excessive, need to clean up
			$continueFrom = 0;
			$continueVal = $paperid;
		}

		if (oc_checkTimeout()) {	// shutdown if close to timeout
			oc_assignTimeoutShutdown();
		}

		// Skip submission (e.g., accepted subs)
		if (oc_skipSubAssignment($paperid)) {
			continue;
		}
		
		// Create a list of reviewers for paper incl. scores
		$Rs = array();
		if (isset($paperTopicAR[$paperid])) {
			foreach($paperTopicAR[$paperid] as $topicid) {
				if (isset($topicReviewerAR[$topicid])) {					// may not have a reviewer for every topic
					foreach ($topicReviewerAR[$topicid] as $reviewerid) {
						if (!in_array($reviewerid,$pAR[$paperid])			// reviewer not yet assigned to paper
							&& !in_array($paperid."-".$reviewerid,$nAR)		// reviewer not in conflict
							&& (count($rAR[$reviewerid]) < (in_array($reviewerid, $pcAR) ? $ppa : $ppr)) ) 		// not enough papers yet
						{
							$Rs[$reviewerid] = $reviewerScoreAR[$reviewerid];
						}
					}
				}
			}
		}
		// Assign reviewers based on reviewer scores (sortorder: 1=highest first; 2=lowest first)
		if (count($Rs) > 0) {
			if ($sortorder=="1") { arsort($Rs); }
			else { asort($Rs); }
			reset($Rs);
			// assign reviewers while available & not enough
			while ((count($Rs)>0) && (count($pAR[$paperid]) < $rpp)) {
				$rid = key($Rs);
				array_push($pAR[$paperid],$rid);
				array_push($rAR[$rid],$paperid);
				unset($Rs[$rid]);
				#next($Rs);
			}
		}	
	}
}

oc_algoWeightedTopicMatch($reviewersPerPaper, $papersPerReviewer, $papersPerAdvocate, 1);
