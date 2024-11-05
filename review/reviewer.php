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
require_once OCC_REVIEW_INC_FILE;

//T: File = table header used for file column
$fileTableHeader = '<th scope="col">' . oc_('File') . '</th>'; // table header used for file column
$formatField = '`format`'; // paper table format field

$extraCols = 0;  // extra columns to skip when displaying ZIP download icons (e.g., abstract, type)

$abstractCol = 'Abstract'; // name for abstract column -- set to empty to not display

$advocateOrder = '!ISNULL(`adv_recommendation`), `paperid`'; // Submission to Advocate ORDER BY fields

$showReviewScore = false; // EXPERIMENTAL. Displays review score in Submissions to Review table

beginSession();

printHeader(oc_('Committee Member'), 2);

print "<dl>\n";

$conflictAR = getConflicts($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']);

if (oc_hookSet('committee-menu-preprocess')) {
	foreach ($OC_hooksAR['committee-menu-preprocess'] as $v) {
		require_once $v;
	}
}

if (!empty($abstractCol)) { $extraCols++; } // it's here so it's checked after the hook

// Track Type?
$OC_trackType = false;
$sr = ocsql_query("SELECT COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPER . "` WHERE `type`!='' AND `type` IS NOT NULL") or err('Unable to check type field');
if (($sl = ocsql_fetch_assoc($sr)) && ($sl['count'] > 0)) {
	$OC_trackType = true;
	$extraCols++;
}

// Can reviewers still sign-in?
if ($OC_statusAR['OC_rev_signin_open']) {
	$extraFields = '';
	
	print '<dt id="ocrevviewsubs">&#8226; <strong><a href="list_papers.php">' . safeHTMLstr(oc_('View Submissions')) . '</a></strong><br /><br /></dt>';
	
	if (oc_hookSet('committee-menu-prereview')) {
		foreach ($OC_hooksAR['committee-menu-prereview'] as $v) {
			print '<dt>&#8226; ' . $v . '<br /><br /></dt>';
		}
	}
	if (oc_hookSet('committee-menu-pc-prereview') && ($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == "T") && $OC_statusAR['OC_pc_signin_open']) {
		foreach ($OC_hooksAR['committee-menu-pc-prereview'] as $v) {
			print '<dt>&#8226; ' . $v . '<br /><br /></dt>';
		}
	}
	
	// Is reviewing open?
	if ($OC_statusAR['OC_reviewing_open']) {
		print '
<dt style="margin-top: 1.5em; font-size: 1.3em; font-weight: bold;">' . safeHTMLstr(oc_('Submissions to Review:')) . '</dt>
<dd><br />
';
		// delete assignments?
		if (($OC_configAR['OC_reviewerUnassignReviews'] == 1) 
				&& isset($_POST['ocaction']) && ($_POST['ocaction'] == 'Delete Review Assignments') 
				&& isset($_POST['submissions']) && is_array($_POST['submissions']) && (count($_POST['submissions']) > 0)
		) {
			// Check for valid submission
			if (!validToken('ac')) {
					warn(oc_('Invalid submission'));
			}
			// iterate through subs
			foreach ($_POST['submissions'] as $sid) {
				if (!preg_match("/^[1-9]\d*$/", $sid)) {
					warn(oc_('Invalid request'));
				}
				// verify assignment
				if (($unassign_r1 = ocsql_query("SELECT `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid` AS `sid`, `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`, `" . OCC_TABLE_PAPER . "`.`title`, `" . OCC_TABLE_REVIEWER . "`.`username`, CONCAT_WS(' ', `" . OCC_TABLE_REVIEWER . "`.`name_first`, `" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`='" . safeSQLstr($sid) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`"))
					&& (ocsql_num_rows($unassign_r1) == 1)
					&& ($unassign_l1 = ocsql_fetch_assoc($unassign_r1))
				) {
					$mailto = '';
					// retrieve advocate email for notification
					if (
						($unassign_r2 = ocsql_query("SELECT `" . OCC_TABLE_REVIEWER . "`.`email` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`='" . safeSQLstr($sid) . "' AND `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`"))
						&& (ocsql_num_rows($unassign_r2) == 1)
						&& ($unassign_l2 = ocsql_fetch_assoc($unassign_r2))
					) {
						$mailto = $unassign_l2['email'];
					}
					// delete assignment
					oc_deleteAssignments($sid, $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'], 'reviewer', 'reviewer');
					// notify
					list($mailsubject, $mailbody) = oc_getTemplate('committee-reviewunassign');
					$mailsubject = oc_replaceVariables($mailsubject, $unassign_l1);
					$mailbody = oc_replaceVariables($mailbody, $unassign_l1);
					sendEmail($mailto, $mailsubject, $mailbody, 1);
				}
			}
		}
		// show reviews
		$q = "SELECT `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`, `" . OCC_TABLE_PAPERREVIEWER . "`.`score`, " . $formatField . ", `title`, `type`, `completed`" . $extraFields . " FROM `" . OCC_TABLE_PAPERREVIEWER . "`, `" . OCC_TABLE_PAPER . "` WHERE `reviewerid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` ORDER BY CAST(`completed` AS CHAR), `paperid`";
		$r = ocsql_query($q) or err("Unable to retrieve submissions for review");
		if (ocsql_num_rows($r) == 0) { 
			print '<span class="warn">' . safeHTMLstr(oc_('You do not have any submissions to review.')) . '</span><p>';
		} else {
			print '<p>' . sprintf(oc_('A <a href="%s" target="_blank">blank review form</a> (that opens in a separate window) is available for you to print out if you prefer writing it out before typing it in.'), 'review.php?pid=blank') . '</p>';
			print '<table border="0" cellspacing="0" cellpadding="0"><tr><td><em>' . safeHTMLstr(oc_('Legend:')) . '</em> &nbsp; </td><td bgcolor="#afa"> &nbsp;o&nbsp; </td><td> &nbsp; <em>' . safeHTMLstr(oc_('Review completed')) . ($showReviewScore ? (' (' . oc_('Score') . ')') : '') . '</em> &nbsp; &nbsp; </td><td bgcolor="#fcc"> &nbsp;x&nbsp; </td><td> &nbsp; <em>' . safeHTMLstr(oc_('Review not yet completed')) . '</em></td></tr></table><br /><br />';
			if ($OC_configAR['OC_reviewerUnassignReviews'] == 1) {
				print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '"><input type="hidden" name="token" value="' . safeHTMLstr($_SESSION[OCC_SESSION_VAR_NAME]['actoken']) . '" /><input type="hidden" name="ocaction" value="Delete Review Assignments" />';
			}
			print '<table border="0" cellspacing="1" cellpadding="3" style="margin-bottom: 5px;"><tr class="rowheader">';
			if ($OC_configAR['OC_reviewerUnassignReviews'] == 1) {
				print '<th style="background-color: #ccf;" title="check boxes and click Delete button below to unassign reviews" scope="col">*</th>';
				$extraCols++; // do it here so it doesn't impact advocate table
			}
			print '<th title="' . safeHTMLstr(oc_('Status')) . ' / ' . safeHTMLstr(oc_('Score')) . '" scope="col">&nbsp;</th><th scope="col">' . safeHTMLstr(oc_('Title - click for review form')) . '</th>' . (!empty($abstractCol) ? ('<th scope="col">' . safeHTMLstr(oc_($abstractCol)) . '</th>') : '');
			if ($OC_trackType) {
				//T: Type = Submission Type (e.g., paper, poster)
				print '<th scope="col">' . safeHTMLstr(oc_('Type')) . '</th>';
			}
			print $fileTableHeader . '</tr>';
			$row = 1;
			$OC_downloadZipAR = array();
			while ($p = ocsql_fetch_array($r)) {
				if ($p['completed'] == "T") { 
					$bgcolor = '#afa';
					$symbol = (($showReviewScore && preg_match("/^\d+$/", $p['score'])) ? $p['score'] : 'o');
				}
				else {
					$bgcolor = '#fcc';
					$symbol = 'x';
				}
				print '<tr class="row' . $row . '">';
				if ($OC_configAR['OC_reviewerUnassignReviews']) {
					print '<td style="text-align: center; background-color: #ccf;"><input type="checkbox" name="submissions[]" value="' . safeHTMLstr($p['paperid']) . '" title="' . safeHTMLstr($p['paperid']) . '" /></td>';
				}
				print '<td valign="top" style="background-color:' . $bgcolor . '; color:#555; text-align: center;">&nbsp;' . $symbol . '&nbsp;</td><td valign="top" scope="row"><a href="review.php?pid=' . $p['paperid'] . '" alt="' . safeHTMLstr(sprintf(oc_('review form for submission ID %d'), $p['paperid'])) . '">' . $p['paperid'] . ' - ' .  safeHTMLstr($p['title']) . '</a></td>';
				if (!empty($abstractCol)) {
					print '<td align="center"><a href="show_abstract.php?pid=' . safeHTMLstr($p['paperid']) . '"><img src="../images/document-sm.gif" border="0" alt="' . safeHTMLstr(oc_('view abstract')) . '" title="' . safeHTMLstr(oc_('view abstract')) . '" width="13" height="16" /></a></td>';
				}
				if ($OC_trackType) {
					print '<td>' . varValue('type', $p, '&nbsp;', true) . '</td>';
				}
				print oc_printFileCells($p) . "</tr>\n";
				$row = $rowAR[$row];
			}
			if (class_exists('ZipArchive')) {
				if (oc_hookSet('print_file_cells_zip')) {
					$str = call_user_func($GLOBALS['OC_hooksAR']['print_file_cells_zip'][0], $row, ($extraCols + 2));	// only one hook allowed here
					print $str;
				} elseif (isset($OC_downloadZipAR[1]) && ($OC_downloadZipAR[1] > 1)) {
					print '<tr><td colspan="' . ($extraCols + 2) . '">&nbsp;</td><td class="row' . $row . '" style="text-align: center; font-size: 0.8em;"><a href="download.php?t=1&s=' . urlencode($OC_downloadZipAR[1]['size']) . '"><img src="../images/documentmulti-sm.gif" border="0" alt="' . safeHTMLstr(oc_('Download All')) . '" title="' . safeHTMLstr(oc_('Download All')) . '" width="17" height="20" /><br />ZIP</a></td></tr>';
				}
			}
			print "</table>\n";
			if ($OC_configAR['OC_reviewerUnassignReviews']) {
				print '<div><span style="background-color: #ccf; padding: 8px 5px;"><span style="font-weight:bold;" title="check boxes above then click Delete button">*</span> <input type="submit" name="submit" value="' . safeHTMLstr(oc_('Delete Review Assignments')) . '" onclick="return confirm(\'' . safeHTMLstr(oc_('Delete review data and unassign review(s)?')) . '\');" /></span></div></form>';
				$extraCols--; // undo it here so it doesn't impact advocate table
			}
		}
		print '</dd>';
	}
}

if (($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == "T") && $OC_statusAR['OC_pc_signin_open']) {
	$extraFields = '';
	$extraGroupByFields = '';

	if (! $OC_statusAR['OC_rev_signin_open']) {
		print '<dt id="ocrevviewsubs">&#8226; <strong><a href="list_papers.php">' . safeHTMLstr(oc_('View Submissions')) . '</a></strong><br /><br /></dt>';
		if (oc_hookSet('committee-menu-prereview')) {
			foreach ($OC_hooksAR['committee-menu-prereview'] as $v) {
				print '<dt>&#8226; ' . $v . '<br /><br /></dt>';
			}
		}
		if (oc_hookSet('committee-menu-pc-prereview') && ($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == "T") && $OC_statusAR['OC_pc_signin_open']) {
			foreach ($OC_hooksAR['committee-menu-pc-prereview'] as $v) {
				print '<dt>&#8226; ' . $v . '<br /><br /></dt>';
			}
		}
	}
    if ($OC_configAR['OC_paperAdvocates'] && $OC_statusAR['OC_advocating_open']) {
		print '
<dt style="margin-top: 1.5em; font-size: 1.3em; font-weight: bold;">' . safeHTMLstr(oc_('Submissions to Advocate:')) . '</dt>
<dd><br />
';
		$q = "SELECT `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`, `" . OCC_TABLE_PAPERADVOCATE . "`.`adv_recommendation`, " . $formatField . ", `title`, `type`, AVG(`score`) AS `paperavg`" . $extraFields . " FROM (`" . OCC_TABLE_PAPERADVOCATE . "`, `" . OCC_TABLE_PAPER . "`) LEFT JOIN `" . OCC_TABLE_PAPERREVIEWER . "` ON `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`=`" . OCC_TABLE_PAPERREVIEWER . "`.`paperid` WHERE `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "' AND `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` GROUP BY `paperid`, `adv_recommendation`, " . $formatField . ", `title`, `type`" . $extraGroupByFields . " ORDER BY " . $advocateOrder;
		$r = ocsql_query($q) or err("Unable to retrieve submissions for advocating");
		if (ocsql_num_rows($r) == 0) { 
			print '<span class="warn">' . safeHTMLstr(oc_('You do not have any submissions to advocate.')) . '</span><p>';
		} else {
			//T: Recom. = Recommendation (e.g., Accept, Reject) [abbreviate if possible]; Score = Average reviews score
			print '<table border="0" cellspacing="1" cellpadding="3"><tr class="rowheader"><th scope="col" title="Recommendation">' . safeHTMLstr(oc_('Recom.')) . '</th><th scope="col">' . safeHTMLstr(oc_('Score')) . '</th><th scope="col">' . safeHTMLstr(oc_('Title - click for recommendation form')) . '</th>' . (!empty($abstractCol) ? ('<th scope="col">' . safeHTMLstr(oc_($abstractCol)) . '</th>') : '');
			if ($OC_trackType) {
				//T: Type = Submission Type (e.g., paper, poster)
				print '<th scope="col">' . safeHTMLstr(oc_('Type')) . '</th>';
			}
			print $fileTableHeader . '</tr>';
			$row = 1;
			$OC_downloadZipAR = array();
			while ($p = ocsql_fetch_array($r)) {
				if ($p['paperavg'] != '') {
					$usescore = number_format($p['paperavg'], 2);
				} else {
					$usescore = '&#8211;';
				}

				print '<tr class="row' . $row . '"><td valign="top" align="center" style="color: #555; ' . (!empty($p['adv_recommendation']) ? ('background-color: #' . $OC_acceptanceColorAR[$p['adv_recommendation']]) : '') . '">'. safeHTMLstr($p['adv_recommendation']) . '</td><td valign="top" align="center">' . $usescore . '</td><td valign="top" scope="row"><a href="advocate.php?pid='.$p['paperid'].'" title="' . safeHTMLstr(sprintf(oc_('see reviews and make recommendation for submission ID %d'), $p['paperid'])) . '">' . $p['paperid'] . ' - ' .  safeHTMLstr($p['title']) . '</a></td>';
				if (!empty($abstractCol)) {
					print '				<td align="center"><a href="show_abstract.php?pid=' . $p['paperid'] . '"><img src="../images/document-sm.gif" border="0" alt="' . safeHTMLstr(oc_('view abstract')) . '" title="' . safeHTMLstr(oc_('view abstract')) . '" width="13" height="16" /></a></td>';
				}

				if ($OC_trackType) {
					print '<td>' . varValue('type', $p, '&nbsp;', true) . '</td>';
				}
				print oc_printFileCells($p) . "</tr>\n";
				$row = $rowAR[$row];
			}
			if (class_exists('ZipArchive')) {
				if (oc_hookSet('print_file_cells_zip')) {
					$str = call_user_func($GLOBALS['OC_hooksAR']['print_file_cells_zip'][0], $row, ($extraCols + 3), 1);	// only one hook allowed here
					print $str;
				} elseif (isset($OC_downloadZipAR[1]) && ($OC_downloadZipAR[1]['count'] > 1)) {
					print '<tr><td colspan="' . ($extraCols + 3) . '">&nbsp;</td><td class="row' . $row . '" style="text-align: center; font-size: 0.8em;"><a href="download.php?t=1&pc=1&s=' . urlencode($OC_downloadZipAR[1]['size']) . '"><img src="../images/documentmulti-sm.gif" border="0" alt="' . safeHTMLstr(oc_('Download All')) . '" title="' . safeHTMLstr(oc_('Download All')) . '" width="17" height="20" /><br />ZIP</a></td></tr>';
				}
			}
			print "</table>\n";
		}
		print '</dd>';
	}
}
print "</dl>\n";

if (!empty($OC_configAR['OC_committeeFooter'])) {
	print '<div style="margin-top: 2em; padding-top: 1em; border-top: 2px solid #666;">' . (preg_match("/\<(?:p|br) ?\/?\>/", $OC_configAR['OC_committeeFooter']) ? oc_($OC_configAR['OC_committeeFooter']) : nl2br(oc_($OC_configAR['OC_committeeFooter']))) . '</div>';
}

printFooter();

?>
