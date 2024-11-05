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

beginChairSession();

// Retrieve submission types - do it here as used for filtering validation below
$subTypeAR = array();
$r = ocsql_query("SELECT DISTINCT `type` FROM `" . OCC_TABLE_PAPER . "` WHERE `type` IS NOT NULL AND `type`!='' ORDER BY `type`") or err('Unable to retrieve submission types', 'Submissions', 1);
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

printHeader("Reviews",1);

$pq = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`, `" . OCC_TABLE_PAPERREVIEWER . "`.`recommendation`, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, `" . OCC_TABLE_PAPERREVIEWER . "`.`completed`, `" . OCC_TABLE_PAPERREVIEWER . "`.`updated`, `title` FROM `" . OCC_TABLE_PAPER . "` LEFT JOIN `" . OCC_TABLE_PAPERREVIEWER . "` ON `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERREVIEWER . "`.`paperid` LEFT JOIN `" . OCC_TABLE_REVIEWER . "` ON `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";

switch($atype) {
	case '': 
		break;
	case 'Pending': 
		$pq .= " WHERE (`" . OCC_TABLE_PAPER . "`.`accepted`='' OR `" . OCC_TABLE_PAPER . "`.`accepted` IS NULL) ";
		break;
	default:
		$pq .= " WHERE `" . OCC_TABLE_PAPER . "`.`accepted`='" . safeSQLstr($atype) . "' ";
		break;
}
switch($stype) {
	case '':
		break;
	default:
		$pq .= (preg_match("/ WHERE /", $pq) ? ' AND ' : ' WHERE ') . "`" . OCC_TABLE_PAPER . "`.`type`='" . safeSQLstr($stype) . "' ";
		break;
}

if (!isset($_GET['s']) || empty($_GET['s']) || ($_GET['s'] == "pid")) { 
	$q = $pq . " ORDER BY `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
	$sortid = "paper";
	$pidsort='<span style="white-space: nowrap;">S-ID</span><br />' . $OC_sortImg; 
	$psort='<a href="'.$_SERVER['PHP_SELF'].'?s=paper" title="sort by submission title">Submission</a>'; 
	$rsort='<a href="'.$_SERVER['PHP_SELF'].'?s=reviewer" title="sort by reviewer name">Reviewer</a>'; 
	$ridsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=rid" title="sort by reviewer ID">R-ID</a></span>'; 
	$_GET['s'] = 'pid';
} elseif ($_GET['s'] == "paper") {
	$q = $pq . " ORDER BY `" . OCC_TABLE_PAPER . "`.`title`, `" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
	$sortid = "paper";
	$pidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=pid" title="sort by submission ID">S-ID</a></span>';  
	$psort="Submission<br />" . $OC_sortImg;
	$rsort='<a href="'.$_SERVER['PHP_SELF'].'?s=reviewer" title="sort by reviewer name">Reviewer</a>'; 
	$ridsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=rid" title="sort by reviewer ID">R-ID</a></span>'; 
} elseif ($_GET['s'] == "rid") {
	$q = $pq . " ORDER BY `" . OCC_TABLE_REVIEWER . "`.`reviewerid`, `" . OCC_TABLE_PAPER . "`.`paperid`";
	$sortid = "reviewer";
	$pidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=pid" title="sort by submission ID">S-ID</a></span>'; 
	$psort='<a href="'.$_SERVER['PHP_SELF'].'?s=paper" title="sort by submission title">Submission</a>'; 
	$rsort='<a href="'.$_SERVER['PHP_SELF'].'?s=reviewer" title="sort by reviewer name">Reviewer</a>'; 
	$ridsort='<span style="white-space: nowrap;">R-ID</span><br />' . $OC_sortImg; 
} elseif ($_GET['s'] == "reviewer") {
	$q = $pq . " ORDER BY `" . OCC_TABLE_REVIEWER . "`.`name_last`, `" . OCC_TABLE_REVIEWER . "`.`name_first`, `" . OCC_TABLE_PAPER . "`.`paperid`";
	$sortid = "reviewer";
	$pidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=pid" title="sort by submission ID">S-ID</a></span>'; 
	$psort='<a href="'.$_SERVER['PHP_SELF'].'?s=paper" title="sort by submission title">Submission</a>'; 
	$rsort="Reviewer<br />" . $OC_sortImg;
	$ridsort= '<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=rid" title="sort by reviewer ID">R-ID</a></span>'; 
} else {
	err("Unknown sort source");
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
';

$r = ocsql_query($q) or err("Unable to get information");
if (ocsql_num_rows($r) == 0) {
        print '<span class="warn">No reviews found.</span><p>';
} else {
	$s = substr($sortid,0,1);

	print '<dl><dt><strong>Links:</strong></dt>';
	
	if ($s == "p") {
		print '<dd><em>R-ID</em> &#8211; Show review</dd>';
	} else {
		print '<dd><em>S-ID</em> &#8211; Show review</dd>';
	}
	print '
<dd><em>Reviewer</em> &#8211; Show Reviewer info</dd>
<dd><em>Submission</em> &#8211; Show Submission info</dd>
<br />
<dt><strong>Legend:</strong></dt>
<dd><table border="0" cellspacing="0" cellpadding="0"><tr>
<td>Review Status: &nbsp; &nbsp; </td><td bgcolor="#ccffcc" class="box" title="marked as complete"> &nbsp; &nbsp; </td><td>&nbsp; Marked as Complete &nbsp; &nbsp; &nbsp; </td>
<td bgcolor="#ffffcc" class="box" title="started"> &nbsp; &nbsp; </td><td>&nbsp;Started &nbsp; &nbsp; &nbsp; </td>
<td bgcolor="#ffcccc" class="box" title="not yet saved"> &nbsp; &nbsp; </td><td>&nbsp;Not Yet Saved</td>
</tr>
</table>
';

	if (isset($OC_reviewQuestionsAR['recommendation'])) {
		print '<br />
<table border="0" cellspacing="0" cellpadding="0"><tr>
<tr><td valign="top">Recommendation: </td><td>';
		foreach ($OC_reviewQuestionsAR['recommendation']['values'] as $k => $v) {
			print '<span style="white-space: none">(' . $k . ') ' . safeHTMLstr(preg_match('/:/', $v) ? substr($v,0,strpos($v,':')) : substr($v,0,30)) . ' &nbsp; </span>';
		}
		print '
	</td></tr>
	</table>
';
	}
	
	print '
</dd>
</dl>

<script language="javascript" type="text/javascript">
<!--
function checkAllBoxes(boxstate) {
	var boxObj = document.getElementsByName("drop[]");
	for (var i=0; i<boxObj.length; i++) {
		if (boxstate.checked) {
			boxObj[i].checked = true;
		} else {
			boxObj[i].checked = false;
		}
	}
}
// -->
</script>

<form method="post" action="unassign_review.php" id="reviewsform">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="src" value="' . $s . '">
<table border=0 cellspacing=1 cellpadding=4 COLS=3>
<tr><td align="right" colspan="6" style="padding-right: 0;"><span style="border: 6px solid #ccf;"><input type="submit" name="submit" value="Unassign Reviews" onclick="return confirm(\'Unassign checked reviews and delete review data? Data cannot be recovered.\');" /></span></td></tr>
<tr class="rowheader"><th scope="col" valign="top" style="width: 4em;" title="Submission ID">' . $pidsort . '</th><th scope="col" valign="top">' . $psort . '</th>';
	if (isset($OC_reviewQuestionsAR['recommendation'])) {
		print '<th scope="col" style="width: 6em;" title="Recommendation Score">Recom.</th>';
	}
	print '<th scope="col" valign="top" style="width: 4em;" title="Reviewer ID">' . $ridsort . '</th><th scope="col" valign="top">' . $rsort . '</th><th scope="col" bgcolor="#ccccff"><input type="checkbox" title="check/uncheck all boxes" onclick="checkAllBoxes(this);" /></th></tr>
	';
	$currid = -1;
	$row = 1;
	while ($l = ocsql_fetch_array($r)) {
	
		if ($l['completed'] == "F" ) {
			if ($l['updated']) { $reccolor = ' bgcolor="#ffffcc"'; $rectitle = 'started'; }
			else { $reccolor = ' bgcolor="#ffcccc"'; $rectitle = 'not yet saved'; }
		}
		elseif (isset($l['completed'])) { $reccolor = ' bgcolor="#ccffcc"'; $rectitle = 'marked as complete'; }
		else { $reccolor = ''; $rectitle = '';}
		
		if ($sortid == "reviewer") {
			$ptags = '<td align="right"' . $reccolor . ' title="' . safeHTMLstr($rectitle) . '"><a href="show_review.php?pid=' . $l['paperid'] . '&rid=' . $l['reviewerid'] . '">'.$l['paperid'].'</a></td><td><a href="show_paper.php?pid=' . $l['paperid'] . '">' . safeHTMLstr($l['title']) . '</a></td>';
			$rtags = '<td align="right">' . $l['reviewerid'] . '</td><td><a href="show_reviewer.php?rid=' . $l['reviewerid'] . '">' . safeHTMLstr($l['name']) . '</a></td>';
		} else {
			$ptags = '<td align="right">' . $l['paperid'] . '</td><td><a href="show_paper.php?pid=' . $l['paperid'] . '">' . safeHTMLstr($l['title']) . '</a></td>';
			$rtags = '<td align="right"' . $reccolor . ' title="' . safeHTMLstr($rectitle) . '"><a href="show_review.php?pid=' . $l['paperid'] . '&rid=' . $l['reviewerid'] . '">'.$l['reviewerid'].'</a></td><td><a href="show_reviewer.php?rid=' . $l['reviewerid'] . '">' . safeHTMLstr($l['name']) . '</a></td>';
		}
		
		$blanktags = '<td>&nbsp;</td><td>&nbsp;</td>';

		if ($currid != $l[$sortid.'id']) {
			if ($currid != -1) {
	        	if ($row==1) { $row=2; } else { $row=1; }
			}
			$currid = $l[$sortid.'id'];
		} else {
			if ($sortid == "reviewer") { $rtags = $blanktags; }
			else { $ptags = $blanktags; }
		}
		
		print '<tr class="row' . $row . '">' . $ptags;
		if (isset($OC_reviewQuestionsAR['recommendation'])) {
			print '<td align="center">' . safeHTMLstr($l['recommendation']) . '&nbsp;</td>';
		};
		print $rtags . '<td align="center" bgcolor="#ccccff">';
		if (empty($l['reviewerid'])) { print '&nbsp;'; }
		else {
			print '<input type="checkbox" name="drop[]" value="' . safeHTMLstr($l['paperid'] . ',' . $l['reviewerid']) . '" title="SID ' . safeHTMLstr($l['paperid']) . ', RID ' . safeHTMLstr($l['reviewerid']) . '">';
		}
		print '</td>';

		print "</tr>\n";
	}
	print '
<tr><td align="right" colspan="6" style="padding-right: 0;"><span style="border: 6px solid #ccf; "><input type="submit" name="submit" value="Unassign Reviews" onclick="return confirm(\'Unassign checked reviews and delete review data? Data cannot be recovered.\');" /></span></td></tr>
</table>
</form>
';
}

printFooter();

?>
