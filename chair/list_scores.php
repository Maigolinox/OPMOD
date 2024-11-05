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

oc_addJS('chair/list_scores.js');

// Retrieve submission types - do it here as used for filtering validation below
$subTypeAR = array();
$r = ocsql_query("SELECT DISTINCT `type` FROM `" . OCC_TABLE_PAPER . "` WHERE `type` IS NOT NULL AND `type`!='' ORDER BY `type`") or err('Unable to retrieve submission types', 'Submission Scores', 1);
while ($l = ocsql_fetch_assoc($r)) {
	$subTypeAR[$l['type']] = substr($l['type'], 0, 50);
}

// Filter?
$atype = (isset($_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['accfilter']) ? $_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['accfilter'] : ''); // accepteance type
$stype = (isset($_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['typefilter']) ? $_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['typefilter'] : ''); // submission type
if (isset($_POST['fsubmit']) && ($_POST['fsubmit'] == 'Filter')) {
	if (!isset($_POST['atype']) || empty($_POST['atype'])) {
		$atype = '';
	} elseif (($_POST['atype'] == 'Pending') || isset($OC_acceptanceColorAR[$_POST['atype']])) {
		$atype = $_POST['atype'];
	}
	$_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['accfilter'] = $atype;
	if (!isset($_POST['stype']) || empty($_POST['stype']) || !isset($subTypeAR[$_POST['stype']])) {
		$stype = '';
	} else {
		$stype = $_POST['stype'];
	}
	$_SESSION[OCC_SESSION_VAR_NAME]['chairvars']['typefilter'] = $stype;
	session_write_close();
}

printHeader("Submission Scores",1);

// Change final decision
if (isset($_POST['asubmit']) && ($_POST['asubmit'] == 'Set')) {
	if (
		isset($_POST['subs']) 
		&& !empty($_POST['subs']) 
		&& isset($_POST['subaction']) 
	) {
		$decisionDate = "'" . safeSQLstr(date('Y-m-d')) . "'";
		if ($_POST['subaction'] == 'oc_advrec') { // change to advocate recommendation
			foreach ($_POST['subs'] as $sid) {
				if (ctype_digit($sid)) {
					$q = "UPDATE `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_PAPERADVOCATE . "` SET `" . OCC_TABLE_PAPER . "`.`accepted`=`" . OCC_TABLE_PAPERADVOCATE . "`.`adv_recommendation`, `" . OCC_TABLE_PAPER . "`.`decision_date`=" . $decisionDate . " WHERE `" . OCC_TABLE_PAPER . "`.`paperid`='" . safeSQLstr($sid) . "' AND `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid` IS NOT NULL AND `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`"; // LIMIT cannot be set due to multiple database update
					if (!isset($_POST['oc_override']) || ($_POST['oc_override'] != 1)) {
						$q .= " AND `" . OCC_TABLE_PAPER . "`.`accepted` IS NULL";
					}
					ocsql_query($q);
				}
			}
		} elseif (($_POST['subaction'] == 'Pending') || isset($OC_acceptanceColorAR[$_POST['subaction']])) { // change to specific acceptance or pending
			if ($_POST['subaction'] == 'Pending') {
				$accepted = 'null';
				$decisionDate = 'null';
			} else {
				$accepted = "'" . safeSQLstr($_POST['subaction']) . "'";
			}
			foreach ($_POST['subs'] as $sid) {
				if (ctype_digit($sid)) {
					$q = "UPDATE `" . OCC_TABLE_PAPER . "` SET `accepted`=" . $accepted . ", `decision_date`=" . $decisionDate . " WHERE `paperid`='" . safeSQLstr($sid) . "'";
					if (!isset($_POST['oc_override']) || ($_POST['oc_override'] != 1)) {
						$q .= " AND `" . OCC_TABLE_PAPER . "`.`accepted` IS NULL";
					}
					$q .= " LIMIT 1";
					ocsql_query($q);
				}
			}
		} else {
			print '<p class="warn" style="text-align: center">Invalid change request action.</p>';
		}
	} else {
		print '<p class="warn" style="text-align: center">Invalid change request.  Were submissions selected?</p>';
	}
}

// Get accept/reject/pending count
$r = ocsql_query("SELECT `accepted`, COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPER . "` GROUP BY `accepted`") or err("Unable to get score count");
if (ocsql_num_rows($r) == 0) { 
	warn('No submissions have been made yet.');
}
$accCountAR = array();
while ($l = ocsql_fetch_array($r)) {
	if (empty($l['accepted'])) {
		$accCountAR['Pending'] = $l['count'];
	} else {
		$accCountAR[$l['accepted']] = $l['count'];
	}
}


// Get pending papers advocate recommendation counts 
$advcountTotal = 0;
$advCountAR = array();
if (isset($accCountAR['Pending']) && ($accCountAR['Pending'] > 0)) {
	$cq = "SELECT `adv_recommendation`, COUNT(`advocateid`) AS `count` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERADVOCATE . "`.`paperid` AND `" . OCC_TABLE_PAPER . "`.`accepted` IS NULL GROUP BY `adv_recommendation`";
	if ($cr = ocsql_query($cq)) {
		while ($cl = ocsql_fetch_array($cr)) {
			if (empty($cl['adv_recommendation'])) {
				$advCountAR['Pending'] = $cl['count'];
			} else {
				$advCountAR[$cl['adv_recommendation']] = $cl['count'];
			}
			$advcountTotal += $cl['count'];
		}
	} 
}

// Select sort order
$psort='<a href="'.$_SERVER['PHP_SELF'].'?s=paper" title="sort by submission ID">Submission ID. Title</a>';
$ssort='<a href="'.$_SERVER['PHP_SELF'].'?s=score" title="sort by score">Score</a>';
$arsort='<a href="'.$_SERVER['PHP_SELF'].'?s=advrec" title="sort by advocate recommendation">Advocate<br />Recom.</a>';
$asort='<a href="'.$_SERVER['PHP_SELF'].'?s=advocate" title="sort by advocate name">Advocate</a>';
$pdsort='<a href="'.$_SERVER['PHP_SELF'].'?s=pcdecision" title="sort by acceptance decision">Final<br />Decision</a>';
$stsort='<a href="'.$_SERVER['PHP_SELF'].'?s=type" title="sort by submission type">Sub.&nbsp;Type</a>';
if (!isset($_GET['s'])) {
	$_GET['s'] = 'score';
}
switch ($_GET['s']) {
	case 'paper':
		$psort="Submission ID. Title<br />" . $OC_sortImg;
		$sort = "`paperid`";
		break;
	case 'advocate':
		$asort = "Advocate<br />" . $OC_sortImg;
		$sort = "`name_last`, `name_first`, `paperid`";
		break;
	case 'advrec':
		$arsort = '<span title="Advocate Recommendation">Adv&nbsp;Recom</span><br />' . $OC_sortImg;
		$sort = "`adv_recommendation`, `recavg` DESC, `paperid`";
		break;
	case 'pcdecision':
		$pdsort = "Final&nbsp;Decision<br />" . $OC_sortImg;
		$sort = "`accepted`, `recavg` DESC, `paperid`";
		break;
	case 'type':
		$stsort = "Type<br />" . $OC_sortImg;
		$sort = "`type`, `recavg` DESC, `paperid`";
		break;
	case 'scoreasc':
		$ssort = 'Score<br /><a href="' . $_SERVER['PHP_SELF'] . '?s=score">' . $OC_sortImgAsc . '</a>';
		$sort = "`recavg` ASC, `paperid`";
		$_GET['s'] = 'scoreasc';
		break;
	case 'score':
	default:
		$ssort = 'Score<br /><a href="' . $_SERVER['PHP_SELF'] . '?s=scoreasc">' . $OC_sortImg . '</a>';
		$sort = "`recavg` DESC, `paperid`";
		$_GET['s'] = 'score';
		break;
}

// Display Filter
$aAR = array_keys($OC_acceptanceColorAR);
$aAR[] = 'Pending';
print '
<div style="text-align: center">
<form method="post" action="' . $_SERVER['PHP_SELF'] . '?s=' . safeHTMLstr($_GET['s']) . '">
<select name="atype"><option value="">All Acceptance Types</option>' . generateSelectOptions($aAR, $atype, false) . '</select> &nbsp;';
if (count($subTypeAR) > 0) {
	print '<select name="stype"><option value="">All Submission Types</option>' . generateSelectOptions($subTypeAR, $stype, true) . '</select> &nbsp;';
}
print '<input type="submit" name="fsubmit" value="Filter" />
</form>
</div>
<br />
';

// Type?
$OC_trackType = false;
$sr = ocsql_query("SELECT COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPER . "` WHERE `type`!='' AND `type` IS NOT NULL") or err('Unable to check type field');
if (($sl = ocsql_fetch_assoc($sr)) && ($sl['count'] > 0)) {
	$OC_trackType = true;
}

// Get all papers and decisions
$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`type`, `" . OCC_TABLE_REVIEWER . "`.`username`, CONCAT_WS(' ', `" . OCC_TABLE_REVIEWER . "`.`name_first`, `" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, COUNT(`" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`) AS `cr`, COUNT(`score`) AS `crec`, ABS(FORMAT(AVG(`score`),2)) AS `recavg`, MAX(`score`) AS `recmax`, MIN(`score`) AS `recmin`, `title`, `accepted`, `adv_recommendation`, MIN(`completed`) AS `reviewscomplete` FROM `" . OCC_TABLE_PAPER . "` LEFT JOIN `" . OCC_TABLE_PAPERREVIEWER . "` ON `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` LEFT JOIN `" . OCC_TABLE_PAPERADVOCATE . "` ON `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` LEFT JOIN `" . OCC_TABLE_REVIEWER . "` ON `" . OCC_TABLE_REVIEWER . "`.`reviewerid`=`" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid` WHERE 1=1 ";
switch($atype) {
	case '': 
		break;
	case 'Pending': 
		$q .= "AND (`accepted`='' OR `accepted` IS NULL) ";
		break;
	default:
		$q .= "AND `accepted`='" . safeSQLstr($atype) . "' ";
		break;
}
switch($stype) {
	case '':
		break;
	default:
		$q .= "AND `type`='" . safeSQLstr($stype) . "' ";
		break;
}
$q .= "GROUP BY `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`type`, `" . OCC_TABLE_REVIEWER . "`.`username`, `name`, `title`, `accepted`, `adv_recommendation` ORDER BY $sort";
$r = ocsql_query($q) or err("Unable to get scores");
if (ocsql_num_rows($r) == 0) {
	print '<span class="warn">No submissions available.</span><p>';
} else {
	print '
<table border="0" cellspacing="0" cellpadding="0"><tr><td valign="top">
<dl>
<dt><strong>Links:</strong></dt>
<dd><em>Score</em> &#8211; Show reviews and accept/reject submission</dd>
<dd><em>Submission ID. Title</em> &#8211; Show submission info</dd>
<br />
<dt><strong>Definitions:</strong></dt>
<dd><em>Score</em> = average score across reviews (if no score, ignored)</dd>
<dd><em>Weight</em> = Number of reviews with a score</dd>
<dd>Weight<strong>*</strong> = May include reviews marked as incomplete (<a href="list_reviews.php">unassign</a>)</dd>
<dd><em>Range</em> = Min - Max scores</dd>
';

	if ($OC_configAR['OC_paperAdvocates']) {
		print '
<dd><em>Advocate Recom.</em> = Advocate Recommendation</dd>
';
		if ($advcountTotal > 0) {
			print '
<br />
<dt><strong>Pending Submissions\' Advocate Recommendation Count:</strong></dt>
<dd><br />
';
			$c = 0;
			foreach ($OC_acceptanceValuesAR as $acc) {
				if ($c++ == 3) {
					print "<br />\n";
					$c = 0;
				}
				print '<span style="white-space: nowrap">' . safeHTMLstr($acc['value']) . ' - ' . (isset($advCountAR[$acc['value']]) ? $advCountAR[$acc['value']] : 0) . '</span> &nbsp; &nbsp; ';
			}
			print '
None - ' . (isset($advCountAR['Pending']) ? $advCountAR['Pending'] : 0) . '
</dd>
';
		}
	}

	print '
</dl>
</td><td><span style="white-space: nowrap;"> &nbsp; &nbsp; &nbsp; &nbsp; </span></td><td valign="top" style="border: 1px solid #333; padding: 3px;">
<strong>Legend:</strong><br />
<table border=0 cellspacing=10 cellpadding=0>
';

foreach ($OC_acceptanceValuesAR as $acc) {
	print '<tr><td style="background-color: #' . $acc['color'] . '" class="box" title="' . safeHTMLstr($acc['value']) . '"> &nbsp; &nbsp; </td><td>&nbsp; ' . safeHTMLstr($acc['value']) . ' (' . (isset($accCountAR[$acc['value']]) ? $accCountAR[$acc['value']] : 0) . ')</td></tr>';

}

	print '
<tr><td bgcolor="#e6e6e6" class="box" title="Pending"> &nbsp; &nbsp; </td><td>&nbsp; Pending (' . (isset($accCountAR['Pending']) ? $accCountAR['Pending'] : 0) . ')</td></tr>
';

	if ($OC_configAR['OC_paperAdvocates']) {
		print '
<tr><td bgcolor="#ffffcc" class="box" title="Final decision does not match advocate recommendation"> &nbsp; &nbsp; </td><td><span style="white-space: nowrap;">&nbsp; Decision != Recom.</span></td></tr>
';
	}

	print '
</table>
</td>
</tr></table>
<br />

<form method="post" action="' . $_SERVER['PHP_SELF'] . '?s=' . safeHTMLstr($_GET['s']) . '" name="scoresForm">
<input type="hidden" name="s" value="">
<input type="button" value="Select" onclick="selectBoxes()" /> 
<select name="boxselect" id="boxselect">
<option value="all">all submissions</option>
<option value="pending">all pending submissions</option>
<option value="gt">submissions with score &gt;=</option>
<option value="eq">submissions with score =</option>
<option value="lt">submissions with score &lt;=</option>
</select>
<input name="score" id="score" size="2" title="enter a score" onkeypress="return checkNumberFieldKeyPress(event)" />
<br /><br />
<table border=0 cellspacing=1 cellpadding=3><tr class="rowheader"><th scope="col" title="submission selection">&nbsp;</th><th scope="col">' . $pdsort . '</th>';

	if ($OC_configAR['OC_paperAdvocates']) {
		print '<th scope="col">' . $arsort . '</th>';
	}

	print '<th scope="col">' . $ssort . '</th><th scope="col">Weight</th><th scope="col">Range</th><th scope="col">' . $psort . '</th>';
	if ($OC_trackType) {
		print '<th scope="col">' . $stsort . '</th>';
	}
	if ($OC_configAR['OC_paperAdvocates']) {
		print '<th scope="col">' . $asort . '</th>';
	}
	print '</tr>';
	while ($l = ocsql_fetch_array($r)) {
		$advpcmatch = '';
		$scorelink = 'show_scores.php?pid=' . $l['paperid'] . '&s=' . safeHTMLstr($_GET['s']);
		print '<tr';
		if (!empty($l['accepted'])) {
			print ' bgcolor="#' . (isset($OC_acceptanceColorAR[$l['accepted']]) ? $OC_acceptanceColorAR[$l['accepted']] : 'ffffff') . '"><td style="background-color: #ccdddd"><input type="checkbox" name="subs[]" value="' . $l['paperid'] . '" id="subs' . $l['paperid'] . '" /></td><td align="center" onclick="document.location=\'' . $scorelink .'\'" id="decision' . $l['paperid'] . '">' . safeHTMLstr($l['accepted']) . '</td>'; 
			if (isset($l['adv_recommendation']) && !empty($l['adv_recommendation']) && ($l['adv_recommendation'] != $l['accepted'])) {
				$advpcmatch = ' bgcolor="#FFFFCC"';
			}
		}
		else {
			print ' style="background-color: #e6e6e6"><td style="background-color: #ccdddd"><input type="checkbox" name="subs[]" value="' . $l['paperid'] . '" id="subs' . $l['paperid'] . '" /></td><td align="center" onclick="document.location=\'' . $scorelink .'\'" id="decision' . $l['paperid'] . '">&nbsp;</td>';
		}
		if ($l['recavg'] != '') {
			$usescore = number_format($l['recavg'], 2);
			$useweight = $l['crec'];
			if ($l['reviewscomplete'] == 'F') { $useweight .= '*'; }
			else { $useweight .= '&nbsp;'; }
		} else {
			$usescore = '&#8211;';
			$useweight = '&nbsp;';
		}
		if ($l['recmin'] === $l['recmax']) { $userange = $l['recmin']; }
		else { $userange = $l['recmin'] . '-' . $l['recmax']; }
		if ($OC_configAR['OC_paperAdvocates']) {
			print '<td align="center"'. $advpcmatch . '>' . safeHTMLstr($l['adv_recommendation']) . '</td>';
		}
		print '<td align="center"><a href="show_scores.php?pid=' . $l['paperid'].'&s=' . safeHTMLstr($_GET['s']) . '" id="subscore' . $l['paperid'] . '">' . $usescore . '</a></td><td align="center">' . $useweight . '</td><td align="center">' . $userange . '</td><td align="left" scope="row"><a href="show_paper.php?pid=' . $l['paperid'] . '">' . $l['paperid'] . '. ' . safeHTMLstr($l['title']) . '</a></td>';
		if ($OC_trackType) {
			print '<td>' . safeHTMLstr($l['type']) . '</td>';
		}
		if ($OC_configAR['OC_paperAdvocates']) {
			print '<td title="' . safeHTMLstr($l['username']) . '">' . safeHTMLstr($l['name']) . '</td>';
		}

		print "</tr>\n";
	}
	print '
<tr><td colspan="10"><br /><label>Change selected to <select name="subaction">';
	foreach ($OC_acceptanceValuesAR as $acc) {
		print '<option value="' . safeHTMLstr($acc['value']) . '">' . safeHTMLstr($acc['value']) . '</option>';
	}
	print '<option value="Pending">Pending</option>';
	if ($OC_configAR['OC_paperAdvocates']) {
		print '<option value="oc_advrec" title="Advocate Recommendation">Advocate Recom.</option>';
	}
	print '</select></label> <input type="submit" name="asubmit" value="Set" /><br /><label style="font-style: italic;"><input type="checkbox" name="oc_override" value="1" /> override existing decision if not pending</label></td></tr>
</table>
</form>
';
}

printFooter();

?>
