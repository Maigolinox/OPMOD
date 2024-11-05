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

//T: File = table header used for file column
$fileTableHeader = '<th>' . oc_('File') . '</th>'; // table header used for file column
$formatField = '`format`'; // paper table format field
$blanks = '<td>&nbsp;</td><td>&nbsp;</td>'; // cell padding (see below)

beginSession();

printHeader(oc_('View Submissions'), 2);

// Get permissions
$readOtherPapers = 0;
$seeAssignedReviews = 0;
$seeOtherReviews = 0;
$seeIncomplete = 1;
$seeDecision = 0;
// If advocate, apply advocate permissions
if ($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == "T") {
	$seeAssignedReviews = 1;
	if ($OC_configAR['OC_advocateReadPapers']) {
		$readOtherPapers = 1;
		if ($OC_configAR['OC_advocateSeeOtherReviews']) {
			$seeOtherReviews = 1;
		}
	}
	if ($OC_configAR['OC_advocateSeeDecision']) { $seeDecision = 1; }
}
if ($OC_configAR['OC_reviewerReadPapers']) {
	$readOtherPapers = 1;
	if ($OC_configAR['OC_reviewerSeeOtherReviews']) {
		$seeOtherReviews = 1;
	}
}
if ($OC_configAR['OC_reviewerSeeAssignedReviews']) { $seeAssignedReviews = 1; }
if ($OC_configAR['OC_reviewerCompleteBeforeSAR']) { $seeIncomplete = 0; }
if ($OC_configAR['OC_reviewerSeeDecision']) { $seeDecision = 1; }
// Anything left to see?
if (!$readOtherPapers && !$seeAssignedReviews && !$seeOtherReviews) {
	warn(oc_('Settings prohibit viewing additional submission information'));
}

// Get list of assigned papers & incomplete reviews
$assignedPaperAR = array();
$incompleteReviewAR = array();
$q = "SELECT `paperid`, `completed`, `score` FROM `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `reviewerid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "'";
$r = ocsql_query($q) or err("Unable to retrieve permitted reviews");
while ($l=ocsql_fetch_array($r)) {
	$assignedPaperAR[] = $l['paperid'];
	if (($l['completed'] == 'F') || !$l['score']) {
		$incompleteReviewAR[] = $l['paperid'];
	}
}

// If advocate, get list of advocating papers
$advocatingPaperAR = array();
if ($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == "T") {
	$q = "SELECT `paperid` FROM `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `advocateid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "'";
	$r = ocsql_query($q) or err("Unable to retrieve advocating submissions");
	while ($l=ocsql_fetch_array($r)) {
		$advocatingPaperAR[] = $l['paperid'];
	}
}

// Get list of conflicts
$conflictAR = getConflicts($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']);

if (oc_hookSet('committee-list_papers-preprocess')) {
	foreach ($OC_hooksAR['committee-list_papers-preprocess'] as $v) {
		require_once $v;
	}
}

// List papers
$q = "SELECT `paperid`, `title`, `accepted`, " . $formatField . " FROM `" . OCC_TABLE_PAPER . "` ORDER BY `paperid`";
$r = ocsql_query($q) or err("Unable to get submissions");
if (ocsql_num_rows($r) == 0) {
	print '<p class="warn">' . oc_('No submissions have been made yet.') . '</p>';
} else {
	$row = 1;
	$count = 0;
	print '<div id="reviewSubmissions"><p class="note">' . oc_('Note: <b>bold</b> submission titles indicate ones assigned to you') . '</p>';
	print '<table border="0" cellspacing="1" cellpadding="4"><thead><tr class="rowheader">';
	if ($seeAssignedReviews || $seeOtherReviews) { print '<th>' . oc_('Reviews') . '</th>'; }
	print '<th>' . oc_('Abstract') . '</th>' . $fileTableHeader . '<th>' . oc_('Submission') . '</th>';
	if ($seeDecision) { print '<th>' . oc_('Status') . '</th>'; }
	print "</tr></thead>\n<tbody>\n";
	while ($l = ocsql_fetch_array($r)) {
		// Skip if in conflict
		if (in_array($l['paperid'].'-'.$_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'],$conflictAR)
			&& !in_array($l['paperid'], $assignedPaperAR)
			&& !in_array($l['paperid'], $advocatingPaperAR)
		) {
			continue;
		}
		
		if (in_array($l['paperid'],$assignedPaperAR)) { $assigned = 1; }
		else { $assigned = 0; }
		if (in_array($l['paperid'],$incompleteReviewAR)) { $completed = 0; }
		else { $completed = 1; }
		if (in_array($l['paperid'],$advocatingPaperAR)) { $advocating = 1; }
		else { $advocating = 0; }

		$i = 0;
		$tr = '';

		if ($seeAssignedReviews || $seeOtherReviews) {
			if ($advocating || (($seeIncomplete || $completed) && (($seeOtherReviews && !$assigned) || ($seeAssignedReviews && $assigned)))) {
				$tr .= '<td align="center"><a href="show_reviews.php?pid=' . $l['paperid'] . '">' . safeHTMLstr(oc_('view')) . '</a></td>';
				$i++;
			} else {
				$tr .= '<td>&nbsp;</td>';
			}
	  	}
		if (($assigned || $advocating || $readOtherPapers)) {
			$i++;
			$tr .= '<td align="center"><a href="show_abstract.php?pid=' . $l['paperid'] . '"><img src="../images/document-sm.gif" border="0" alt="' . safeHTMLstr(oc_('view abstract')) . '" title="' . safeHTMLstr(oc_('view abstract')) . '" width="13" height="16" /></a></td>' . oc_printFileCells($l);
		} else {
			$tr .= $blanks;
		}
		if ($i > 0) {
			print '<tr class="row' . $row . '">' . $tr . '<td>';
			$paperstr = $l['paperid'] . '. ' . safeHTMLstr($l['title']);
			if ($assigned || $advocating) { print "<strong><em>$paperstr</em></strong>"; }
			else { print $paperstr; }
			print '</td>';
			if ($seeDecision) { 
				if (!empty($l['accepted'])) {
					print '<td' . (isset($OC_acceptanceColorAR[$l['accepted']]) ? (' style="background-color: #' . $OC_acceptanceColorAR[$l['accepted']] . ';"') : '') . '>' . safeHTMLstr($l['accepted']) . '</td>';
				} else {
					print '<td style="font-style: italic;">' . safeHTMLstr(oc_('Pending')) . '</td>';
				}
			}
			print '</tr>';
			$row = $rowAR[$row];
			$count++;
		}
	}
	print '</tbody></table></div>';
	if ($count == 0) {
		print '
<p class="warn">' . oc_('There are no submissions available for you to view.') . '</p>
<script language="javascript" type="text/javascript">
<!--
document.getElementById("reviewSubmissions").style.display="none";
// -->
</script>
';
	}
}

printFooter();

?>
