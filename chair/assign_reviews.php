<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$hdr = 'Assign Reviews';
$hdrfn = 1;

require_once "../include.php";

beginChairSession();

// Get topics
$topicsAR = array();
$q = "SELECT * FROM `" . OCC_TABLE_TOPIC . "`";
$r = ocsql_query($q) or err("Unable to query topics", $hdr, $hdrfn);
while ($l = ocsql_fetch_assoc($r)) {
	$topicsAR[$l['topicid']] = useTopic($l['short'], $l['topicname'], 1);
}

// Filter
$filterOptionsAR = array('Submissions', 'Reviewers');
$srfilter = (isset($_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['srfilter']) ? $_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['srfilter'] : ''); // submissions and/or reviewers
$topicfilter = (isset($_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['topicfilter']) ? $_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['topicfilter'] : ''); // topic
if (isset($_POST['fsubmit']) && ($_POST['fsubmit'] == 'Filter')) {
	if (!isset($_POST['srfilter']) || empty($_POST['srfilter'])) {
		$srfilter = '';
	} elseif (in_array($_POST['srfilter'], $filterOptionsAR)) {
		$srfilter = $_POST['srfilter'];
	}
	$_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['srfilter'] = $srfilter;
	if (!isset($_POST['topicfilter']) || empty($_POST['topicfilter']) || !isset($topicsAR[$_POST['topicfilter']])) {
		$topicfilter = '';
	} else {
		$topicfilter = $_POST['topicfilter'];
	}
	$_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['topicfilter'] = $topicfilter;
	session_write_close();
} elseif (!isset($_GET['s']) && !isset($_POST['submit'])) { // reset filter if not coming from this page
	unset($_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['srfilter']);
	unset($_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['topicfilter']);
	session_write_close();
	$srfilter = '';
	$topicfilter = '';
}

printHeader($hdr, $hdrfn);

if (isset($_POST['submit']) && ($_POST['submit'] == "Assign Reviews")) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}
	// Check that we have at least one paper and reviewer
	if (
		!isset($_POST['papers']) || !is_array($_POST['papers']) || (count($_POST['papers']) == 0) 
		|| 
		!isset($_POST['reviewers']) || !is_array($_POST['reviewers']) || (count($_POST['reviewers']) == 0) 
	) {
		print '<span class="warn">Please go back and select at least one submission and one reviewer</span><p>';
	} else {
		// Get conflicts?
		if ($OC_configAR['OC_allowConflictOverride'] && isset($_POST['conflict_override']) && ($_POST['conflict_override'] == 1)) {
			$conflictAR = array();
		} else {
			$conflictAR = getConflicts();
		}
		// Assign reviews
		$reviewerAssignmentAR = array();    // keep track of reviewers w/successful assignments
		$submissionAssignmentAR = array();	// keep track of submissions assigned
		foreach ($_POST['reviewers'] as $i) {
			// valid rev id?
			if (!preg_match("/^\d+$/", $i)) {
				err('Invalid reviewer selected');
			}
			// iterate through submissions for reviewers
			foreach ($_POST['papers'] as $j) {
				// valid sub id?
				if (!preg_match("/^\d+$/", $j)) {
					err('Invalid submission selected');
				}
				// Check for conflict
				if (in_array("$j-$i", $conflictAR)) {
					print "<p class=\"warn\">! Submission $j is in conflict with reviewer $i.</p>\n";
					continue;
				}
				// Make assignment
				$q = "INSERT INTO `" . OCC_TABLE_PAPERREVIEWER . "` (`paperid`,`reviewerid`,`assigned`) VALUES ('" . safeSQLstr($j) . "','" . safeSQLstr($i) . "','" . safeSQLstr(date('Y-m-d')) . "')";
				ocsql_query($q);
                if (($merr = ocsql_errno()) != 0) {
					if ($merr == 1062) {	// Duplicate entry
					    print "<p class=\"warn\">! Submission $j was already assigned reviewer $i.</p>\n";
					} else {
	                    print "<p class=\"err\">!! Error assigning submission $j to reviewer $i</p>\n";
					}
				} else {
		  			print "<p>Submission $j assigned to reviewer $i.</p>\n";
		  			if (!isset($reviewerAssignmentAR[$i])) {
						$reviewerAssignmentsAR[$i] = array();
					}
					$reviewerAssignmentAR[$i][] = $j;
					if (!in_array($j, $submissionAssignmentAR)) {
						$submissionAssignmentAR[] = $j;
					}

					// Hook
					if (oc_hookSet('chair-assign-review')) {
						foreach ($OC_hooksAR['chair-assign-review'] as $f) {
							require_once $f;
						}
					}

	  			}
			}
		}
		
		print '<p><hr /></p>';
		
		// Notify?
		if ( isset($_POST['notify']) && ($_POST['notify'] == 1) && (count($reviewerAssignmentAR) > 0) ) {
			// Get list of sub titles
			$r = ocsql_query("SELECT `paperid`, `title` FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid` IN (" . implode(',',$submissionAssignmentAR) . ")") or err('Unable to retrieve submission titles for notification');
			$submissionTitleAR = array();
			while ($l = ocsql_fetch_assoc($r)) {
				$submissionTitleAR[$l['paperid']] = $l['paperid'] . '. ' . $l['title'];
			}

			// Get reviewers to notify
			$q = "SELECT `reviewerid`, `name_first`, `name_last`, CONCAT_WS(' ', `name_first`, `name_last`) AS `name`, `username`, `email` FROM `" . OCC_TABLE_REVIEWER . "` WHERE `reviewerid` IN (" . implode(',', array_keys($reviewerAssignmentAR)) . ")";
			$r = ocsql_query($q) or err('Unable not get reviewer email address(es) for notification');
			
			// Get notification template
			// ocIgnore included so poEdit picks up (DB) template translation
			$ocIgnoreSubject = oc_('New Reviewer Assignment(s)');
			//T: [:OC_confName:] is the event name
			$ocIgnoreBody = oc_('New assignments have been made for you to review in the [:OC_confName:] OpenConf system:


[:assignments:]

Thank you.');
			list($subject, $message) = oc_getTemplate('chair-assign_reviews');

			// Hook
			if (oc_hookSet('chair-assign-review-notify')) {
				foreach ($OC_hooksAR['chair-assign-review-notify'] as $f) {
					require_once $f;
				}
			}

			// Iterate through reviewers
			while ($l = ocsql_fetch_assoc($r)) {
				$templateExtraAR = $l;
				$templateExtraAR['assignments'] = '';
				foreach ($reviewerAssignmentAR[$l['reviewerid']] as $sid) {
					$templateExtraAR['assignments'] .= $submissionTitleAR[$sid] . "\n\n";
				}
				$tmpsubject = oc_replaceVariables($subject, $templateExtraAR);
				$tmpmessage = oc_replaceVariables($message, $templateExtraAR);
				if (sendEmail($l['email'], $tmpsubject, $tmpmessage)) {
					print '<p>Notification sent to ' . safeHTMLstr($l['name']) . ' (' . safeHTMLstr($l['reviewerid']) . ')</p>';
				} else {
					print '<p class="err">!! Unable to email notification to <a href="mailto:' . safeHTMLstr($l['email']) . '">' . safeHTMLstr($l['name']) . '</a> (' . safeHTMLstr($l['reviewerid']) . ')</p>';
				}
			}
			print '<p><hr /></p>';
		}
	}
	
	print '
<p>&#187; <a href="' . $_SERVER['PHP_SELF'] . '?s=' . varValue('s', $_GET) . '">Make additional assignments</a></p>
<p>&#187; <a href="list_reviews.php">View/Edit assignments</a></p>
<p>&#187; <a href="list_conflicts.php">Manage conflicts</a></p>
';

} else {
	$pq = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`title`, count(`" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`) AS `pcount` FROM `" . OCC_TABLE_PAPER . "` LEFT JOIN `" . OCC_TABLE_PAPERREVIEWER . "` ON `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERREVIEWER . "`.`paperid` GROUP BY `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`title` ORDER BY `" . OCC_TABLE_PAPER . "`.`paperid`";
	$pr = ocsql_query($pq) or err("Unable to get submissions");
	// Get pad size for paper id's - yes, we really need the max id, but this should do:)
    $rows = ocsql_num_rows($pr);
	$psize = oc_strlen((string) $rows);
	if ($rows == 0) {
		print '<span class="warn">No submissions have been made yet</span><p>';
	}
	else {
		if (!isset($_GET['s']) || empty($_GET['s']) || ($_GET['s'] == "id"))  {
			$idsortstr = 'ID';
		    $nsortstr = '<a href="' . $_SERVER['PHP_SELF'].'?s=name">Name</a>';
			$rsortstr = '<a href="' . $_SERVER['PHP_SELF'].'?s=reviews" title="Number of Reviews">No. Reviews</a>';
			$legend = "[ Reviewer ID - $nsortstr ($rsortstr) ]";
			$sortby = "`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
		} elseif ($_GET['s'] == "reviews") {
			$rsortstr = 'No. Reviews';
		    $idsortstr = '<a href="' . $_SERVER['PHP_SELF'].'?s=id">ID</a>';
		    $nsortstr = '<a href="' . $_SERVER['PHP_SELF'].'?s=name">Name</a>';
			$legend = "[ No. Reviews - Reviewer $nsortstr - $idsortstr ]";
			$sortby = "`rcount`, `" . OCC_TABLE_REVIEWER . "`.`name_last`, `" . OCC_TABLE_REVIEWER . "`.`name_first`";
		} else {
		    $idsortstr = '<a href="' . $_SERVER['PHP_SELF'].'?s=id">ID</a>';
			$nsortstr = 'Name';
			$rsortstr = '<a href="' . $_SERVER['PHP_SELF'].'?s=reviews" title="Number of Reviews">No. Reviews</a>';
			$legend = "[ Reviewer Name - $idsortstr ($rsortstr) ]";
			$sortby = "`" . OCC_TABLE_REVIEWER . "`.`name_last`, `" . OCC_TABLE_REVIEWER . "`.`name_first`";
		}
		$rq = "SELECT `" . OCC_TABLE_REVIEWER . "`.`reviewerid`, `onprogramcommittee`, CONCAT_WS(' ', `" . OCC_TABLE_REVIEWER . "`.`name_first`, `" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, COUNT(`" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`) AS `rcount` FROM `" . OCC_TABLE_REVIEWER . "` LEFT JOIN `" . OCC_TABLE_PAPERREVIEWER . "` ON `" . OCC_TABLE_REVIEWER . "`.`reviewerid`=`" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid` GROUP BY `" . OCC_TABLE_REVIEWER . "`.`reviewerid`, `onprogramcommittee`, `name` ORDER BY $sortby";
		$rr = ocsql_query($rq) or err("Unable to get reviewers");
		// Get pad size for reviewer id's - yes, we really need the max id, but this should do:)
		$rsize = oc_strlen((string) ocsql_num_rows($rr));
		if (ocsql_num_rows($rr) == 0) {
			print '<span class="warn">No reviewers have signed up yet</span><p>';
		}
		else {
			// display filter?
			$subSort = false;
			$revSort = false;
			$subFilterAR = array();
			$revFilterAR = array();
			if (count($topicsAR) > 1) {
				print '
<div style="text-align: center; margin: 1.5em 0;">
<form method="post" action="' . $_SERVER['PHP_SELF'] . '?s=' . safeHTMLstr($_GET['s']) . '" title="filter by topic">
<select name="srfilter" title="what to filter"><option value="">Submissions and Reviewers</option>' . generateSelectOptions($filterOptionsAR, $srfilter, false) . '</select> &nbsp;<select name="topicfilter" title="topic to filter by"><option value="">All Topics</option>' . generateSelectOptions($topicsAR, $topicfilter, true) . '</select> &nbsp;<input type="submit" name="fsubmit" title="Filter" value="Filter" />
</form>
</div>
';
				if (!empty($topicfilter)) {
					if ($srfilter != 'Reviewers') {
						$subSort = true;
						$r = ocsql_query("SELECT `paperid` FROM `" . OCC_TABLE_PAPERTOPIC . "` WHERE `topicid`='" . safeSQLstr($topicfilter) . "'") or err('Unable to filter submissions by topic');
						while ($l = ocsql_fetch_assoc($r)) {
							$subFilterAR[] = $l['paperid'];
						}
					}
					if ($srfilter != 'Submissions') {
						$revSort = true;
						$r = ocsql_query("SELECT `reviewerid` FROM `" . OCC_TABLE_REVIEWERTOPIC . "` WHERE `topicid`='" . safeSQLstr($topicfilter) . "'") or err('Unable to filter reviewers by topic');
						while ($l = ocsql_fetch_assoc($r)) {
							$revFilterAR[] = $l['reviewerid'];
						}
					}
				}
			}
			
			// display assignment form
			print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<div style="float: left; margin-right: 50px;">
<p><strong>Select Submission(s):</strong></p>
<p>[ Submission ID - Title (No. Reviewers) ]</p>
<select multiple size="20" name="papers[]">
';
 
			$count = 0;
			while ($paper = ocsql_fetch_assoc($pr)) {
				if ($subSort && !in_array($paper['paperid'], $subFilterAR)) { continue; }
				$count++;
				print '<option value="' . $paper['paperid'] . '">' . padNumber($paper['paperid'],$psize) . ' - ' . safeHTMLstr(shortenStr($paper['title'],80)) . " (" . $paper['pcount'] . ")</option>\n";
			}
			if ($count == 0) {
				print '<option disabled>no topic match</option>';
			}
  
			print '
</select>
</div>
<div style="float: left;">
<p><strong>Select Reviewer(s):</strong></p>
<p>' . $legend . '</p>
<select multiple size="20" name="reviewers[]">
';
  
			$count = 0;
			while ($reviewer = ocsql_fetch_assoc($rr)) {
				if ($revSort && !in_array($reviewer['reviewerid'], $revFilterAR)) { continue; }
				$count++;
				print '<option value="' . $reviewer['reviewerid'] . '">';
				if (!isset($_GET['s']) || empty($_GET['s']) || ($_GET['s'] == "id")) {
					print padNumber($reviewer['reviewerid'],$rsize) . ' - ';
					if ($reviewer['onprogramcommittee'] == 'T') {
					    print "[PC] ";
					}
					print safeHTMLstr($reviewer['name']) . " (" . $reviewer['rcount'] . ")</option>\n";
				} elseif ($_GET['s'] == "reviews") {
					print padNumber($reviewer['rcount'],2) . ' - ';
					if ($reviewer['onprogramcommittee'] == 'T') {
					    print "[PC] ";
					}
					print safeHTMLstr($reviewer['name']) . " - " . $reviewer['reviewerid'];
				} else {
					if ($reviewer['onprogramcommittee'] == 'T') {
					    print "[PC] ";
					}
					print safeHTMLstr($reviewer['name']) . " - " . $reviewer['reviewerid'] . " (" . $reviewer['rcount'] . ")</option>\n";
				}
			}
			if ($count == 0) {
				print '<option disabled>no topic match</option>';
			}

			print '
</select>
<p class="note">Tip: Click the ID, Name, or Reviews links above<br />to re-sort this list (page will reload)</p>

</div>
<br style="clear: left;" />

<strong>Options:</strong><br />
';
			if ($OC_configAR['OC_allowConflictOverride']) {
		 		print '
<p><input type="checkbox" name="conflict_override" value="1" /> <strong><em>Override Conflicts</em></strong> &#8211; check box to force assignments even if there is a conflict</p>
';
			}
			print '
<p><label><input type="checkbox" name="notify" value="1"> <strong><em>Notify Reviewer(s)</em></strong></label> &#8211; check box to notify reviewer(s) that new assignments have been made</p>
<br />
<p><input type="submit" name="submit" class="submit" value="Assign Reviews" /></p>
</form>
';
		}
	}
}

printFooter();

?>
