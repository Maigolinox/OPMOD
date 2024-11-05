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

printHeader("Advocates", 1);

if (isset($_POST['submit']) && ($_POST['submit'] == "Unassign Advocates") && !empty($_POST['drop'])) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	foreach ($_POST['drop'] as $val) {
		if (preg_match("/^\d+,\d+$/",$val)) {
			list($pid,$aid) = explode(",", $val);
			oc_deleteAssignments($pid, $aid, 'advocate');
			// Also delete as reviewer?
			if (isset($_POST['droprev']) && ($_POST['droprev'] == "yes")) {
				oc_deleteAssignments($pid, $aid);
			}
		}
		else {
			print "Unable to process " . safeHTMLstr($val) . ".<p>\n";
		}
	}
	print "<p align=\"center\" class=\"note\">Advocate(s) have been unassigned.</p>\n";
	if (isset($_POST['s'])) {
		print '<p align="center"><a href="list_advocates.php">Return to Advocate Listings</a></p>';
	}
	printFooter();
	exit;
}

if (!isset($_GET['s']) || ($_GET['s'] == "pid")) {
	$sortby = "`paperid`";
	$pidsort='<span style="white-space: nowrap;">S-ID</span><br />' . $OC_sortImg; 
	$psort='<a href="'.$_SERVER['PHP_SELF'].'?s=paper" title="sort by submission title">Submission</a>'; 
	$nsort='<a href="'.$_SERVER['PHP_SELF'].'?s=name" title="sort by advocate name">Advocate</a>'; 
	$aidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=aid" title="sort by advocate ID">A-ID</a></span>'; 
	$_GET['s'] = 'pid';
} elseif ($_GET['s'] == "paper") {
	$sortby = "`title`";
	$pidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=pid" title="sort by submission ID">S-ID</a></span>'; 
	$psort="Submission<br />" . $OC_sortImg; 
	$nsort='<a href="'.$_SERVER['PHP_SELF'].'?s=name" title="sort by advocate name">Advocate</a>'; 
	$aidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=aid" title="sort by advocate ID">A-ID</a></span>'; 
} elseif ($_GET['s'] == "name") { 
	$sortby = "`name_last`, `name_first`";
	$pidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=pid" title="sort by submission ID">S-ID</a></span>'; 
	$psort='<a href="'.$_SERVER['PHP_SELF'].'?s=paper" title="sort by submission title">Submission</a>'; 
	$nsort="Advocate<br />" . $OC_sortImg; 
	$aidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=aid" title="sort by advocate ID">A-ID</a></span>'; 
} elseif ($_GET['s'] == "aid") { 
	$sortby = "`advocateid`";
	$pidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=pid" title="sort by submission ID">S-ID</a></span>'; 
	$psort='<a href="'.$_SERVER['PHP_SELF'].'?s=paper" title="sort by submission title">Submission</a>'; 
	$nsort='<a href="'.$_SERVER['PHP_SELF'].'?s=name" title="sort by advocate name">Advocate</a>'; 
	$aidsort='<span style="white-space: nowrap;">A-ID</span><br />' . $OC_sortImg; 
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

$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`, `adv_recommendation`, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, `title` FROM `" . OCC_TABLE_PAPER . "` LEFT JOIN `" . OCC_TABLE_PAPERADVOCATE . "` ON `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_PAPERADVOCATE . "`.`paperid` LEFT JOIN `" . OCC_TABLE_REVIEWER . "` ON `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
switch($atype) {
	case '': 
		break;
	case 'Pending': 
		$q .= " WHERE (`" . OCC_TABLE_PAPER . "`.`accepted`='' OR `" . OCC_TABLE_PAPER . "`.`accepted` IS NULL) ";
		break;
	default:
		$q .= " WHERE `" . OCC_TABLE_PAPER . "`.`accepted`='" . safeSQLstr($atype) . "' ";
		break;
}
switch($stype) {
	case '':
		break;
	default:
		$q .= (preg_match("/ WHERE /", $q) ? ' AND ' : ' WHERE ') . "`" . OCC_TABLE_PAPER . "`.`type`='" . safeSQLstr($stype) . "' ";
		break;
}
$q .= " ORDER BY " . $sortby;
$r = ocsql_query($q) or err("Unable to get information");
if (ocsql_num_rows($r) == 0) {
	print '<span class="warn">No advocates found.</span><p>';
} else {
	print '
<dl>
<dt><strong>Links:</strong></dt>
<dd><em>Recom.</em> &#8211; Show recommendation</dd>
<dd><em>Submission</em> &#8211; Show Submission info</dd>
<dd><em>Advocate</em> &#8211; Show Advocate info</dd>
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

<form method="post" action="list_advocates.php">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="s" value="' . safeHTMLstr(varValue('s', $_GET)) . '">
<table border=0 cellspacing=1 cellpadding=4>
<tr class="rowheader"><th scope="col" valign="top" title="Advocate Recommendation">Recom.</th><th scope="col" valign="top" title="Submission ID">' . $pidsort . '</th><th scope="col" valign="top">' . $psort . '</th><th scope="col" valign="top" title="Advocate ID">' . $aidsort . '</th><th scope="col" valign="top">' . $nsort . '</th><th scope="col" bgcolor="#ccccff">';
	if (($atype == '') && ($stype == '')) {
		print '&nbsp;';
	} else {
		print '<input type="checkbox" title="check/uncheck all boxes" onclick="checkAllBoxes(this);" />';
	}
	print '</th></tr>';
	$row = 1;	
	while ($l = ocsql_fetch_array($r)) {
		print '<tr class="row' . $row . '">';
 		if (empty($l['adv_recommendation'])) {
			print "<td>&nbsp;</td>";
		} else {
			print '<td><a href="show_adv_review.php?p=' . urlencode($l['paperid']) . '&a=' . urlencode($l['advocateid']) . '&s=' . urlencode(varValue('s', $_GET)) . '">' . safeHTMLstr($l['adv_recommendation']) . '</a></td>';
		}
		print '<td align="right">' . safeHTMLstr($l['paperid']) . '</td><td><a href="show_paper.php?pid=' . urlencode($l['paperid']) . '">' . safeHTMLstr($l['title']) . '</a></td>';
		if (empty($l['advocateid'])) {
			print "<td>&nbsp;</td><td>&nbsp;</td><td bgcolor=\"#ccccff\">&nbsp;</td>";
		} else {
			print '<td align="right">' . safeHTMLstr($l['advocateid']) . '</td><td><a href="show_reviewer.php?rid=' . urlencode($l['advocateid']) . '">' . safeHTMLstr($l['name']) . '</a></td>' .
					'<td align="center" bgcolor="#ccccff"><input type="checkbox" name="drop[]" value="' . safeHTMLstr($l['paperid'] . ',' . $l['advocateid']) . '" title="SID ' . safeHTMLstr($l['paperid']) . ', AID ' . safeHTMLstr($l['advocateid']) . '"></td>';
		}
		print "</tr>";
		if ($row==1) { $row=2; } else { $row=1; }
	}
	print '
<tr><td align="right" colspan="6"><label><input type="checkbox" name="droprev" value="yes"> Check to also unassign review</label><br /><br /><span style="background-color: #ccf; border: 12px solid #ccf;"> &nbsp; <input type="submit" name="submit" value="Unassign Advocates" onclick="return confirm(\'Data for unassigned advocates will be deleted. Proceed?\');" /> &nbsp; </span></td></tr>
</table>
</form>
';
}

printFooter();

?>
