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
require_once "../include-submissions.inc";

$fileTableHeader = '<th scope="col">File</th>'; // table header used for file column
$formatField = '`format`'; // paper table format field

beginChairSession();

oc_addJS('chair/list_papers.js');

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

printHeader("Submissions",1);

$verbAR = array(
	'Delete Submissions'			=> 'deleted',
	'Delete Withdrawn Submissions'	=> 'deleted',
	'Withdraw Submissions'			=> 'withdrawn',
	'Restore Submissions'			=> 'restored'
);

if (isset($_POST['subaction']) && isset($verbAR[$_POST['subaction']])) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	if (preg_match("/^(Delete|Withdraw) Submissions$/", $_POST['subaction']) && isset($_POST['papers']) && !empty($_POST['papers'])) {
		foreach ($_POST['papers'] as $paperid) {
			if (preg_match("/^\d+$/",$paperid)) {
				// withdraw?
				$log = true;
				if ($_POST['subaction'] == 'Withdraw Submissions') {
					print "<p>withdrawing id $paperid ... ";
					if (! withdrawPaper($paperid, OCC_WORD_CHAIR)) {
						print '<span class="warn">SUBMISSION NOT FOUND</span>';
					}
					print "</p>\n";
					$log = false;
				} else {
					print "<p>deleting id $paperid ...</p>\n";
				}
				// delete paper
				deletePaper($paperid, $log);
			} else {
				print "Unable to process submission id " . safeHTMLstr($paperid) . ".<br /><br />\n";
			}
		}
		if (($miv = ini_get('max_input_vars')) && (count($_POST['papers']) > ($miv - 10))) {
			print '<p class="warn">The number of submissions selected may have been greater than supported by this server. Additional passes may be required.</p>';
		}
	} elseif (preg_match("/^(Restore|Delete Withdrawn) Submissions$/", $_POST['subaction']) && isset($_POST['wpapers']) && !empty($_POST['wpapers'])) {
		foreach ($_POST['wpapers'] as $paperid) {
			if (preg_match("/^\d+$/",$paperid)) {
				if ($_POST['subaction'] == 'Restore Submissions') { // restore?
					print "<p>restoring id $paperid ... ";
					$ret = restorePaper($paperid);
					if ($ret != $paperid) {
						if ($ret === null) {
							print '<span class="warn">SUBMISSION NOT FOUND</span>';
						} else {
							print '<span class="warn">Duplicate ID -- New ID Assigned: ' . safeHTMLstr($ret) . '</span>';
						}
					}
					print "</p>\n";
				} elseif ($_POST['subaction'] == 'Delete Withdrawn Submissions') { // delete withdrawn?
					print "<p>deleting withdrawn id $paperid ... ";
					if ( ! ocsql_query("DELETE FROM `" . OCC_TABLE_WITHDRAWN . "` WHERE `paperid`='" . safeSQLstr($paperid) . "' LIMIT 1") ) {
						print '<span class="warn">DELETION FAILED</span>';
					}
					print "</p>\n";
				}
			} else {
				print "Unable to process submission id " . safeHTMLstr($paperid) . ".<br /><br />\n";
			}
		}
		if (($miv = ini_get('max_input_vars')) && (count($_POST['wpapers']) > ($miv - 10))) {
			print '<p class="warn">The number of submissions selected may have been greater than supported by this server. Additional passes may be required.</p>';
		}
	}
		
	print '<p><a href="list_papers.php">Return to Submission Listings</a></p>';
	printFooter();
	exit;
}

// Headers & Sorting
$rsortstr = '<a href="'.$_SERVER['PHP_SELF'].'?s=id" title="sort by submission ID">ID</a>';
$tsortstr = '<a href="'.$_SERVER['PHP_SELF'].'?s=title" title="sort by submission title">Title</a>';
$nsortstr = '<a href="'.$_SERVER['PHP_SELF'].'?s=name" title="sort by contact author name">Contact ' . OCC_WORD_AUTHOR . '</a>';
$ssortstr = '<a href="'.$_SERVER['PHP_SELF'].'?s=student" title="sort by Student">Stud.</a>';
$stsortstr = '<a href="'.$_SERVER['PHP_SELF'].'?s=type" title="sort by type">Type</a>';

if (!isset($_GET['s'])) {
	$_GET['s'] = 'id';
}
switch ($_GET['s']) {
	case 'id':
		$sortby = "`paperid`";
		$rsortstr = 'ID<br />' . $OC_sortImg;
		break;
	case 'title':
		$sortby = "`title`";
		$tsortstr = 'Title<br />' . $OC_sortImg;
		break;
	case 'student':
        $sortby = "`student`, `paperid`";
        $ssortstr = '<span title="Student">Stud.</span><br />' . $OC_sortImg;
		break;
	case 'type':
		$sortby = "`type`, `paperid`";
		$stsortstr = 'Type<br />' . $OC_sortImg;
		break;
	case 'name':
	default:
		$sortby = "`name_last`, `name_first`";
		$nsortstr = 'Contact ' . OCC_WORD_AUTHOR . '<br />' . $OC_sortImg;
		$_GET['s'] = 'id';
		break;
}

// Display Filter
$aAR = array_keys($OC_acceptanceColorAR);
$aAR[] = 'Pending';
print '
<div style="text-align: center">
<form method="post" action="' . $_SERVER['PHP_SELF'] . '?s=' . safeHTMLstr($_GET['s']) . '">
<select name="atype" title="submission acceptance types to be displayed when Filter button clicked"><option value="">All Acceptance Types</option>' . generateSelectOptions($aAR, $atype, false) . '</select> &nbsp;';
if (count($subTypeAR) > 0) {
	print '<select name="stype" title="submission types to be displayed when Filter button clicked"><option value="">All Submission Types</option>' . generateSelectOptions($subTypeAR, $stype, true) . '</select> &nbsp;';
}
print '<input type="submit" name="fsubmit" value="Filter" />
</form>
</div>
';

// Students?
$OC_trackStudent = false;
$sr = ocsql_query("SELECT COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPER . "` WHERE `student`='T'") or err('Unable to check student field');
if (($sl = ocsql_fetch_assoc($sr)) && ($sl['count'] > 0)) {
	$OC_trackStudent = true;
}

// Type?
$OC_trackType = false;
$sr = ocsql_query("SELECT COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPER . "` WHERE `type`!='' AND `type` IS NOT NULL") or err('Unable to check type field');
if (($sl = ocsql_fetch_assoc($sr)) && ($sl['count'] > 0)) {
	$OC_trackType = true;
}

// Extra fields init for hook
$extraFields = '';

// Hook
if (oc_hookSet('chair-list_papers-preprocess')) {
	foreach ($OC_hooksAR['chair-list_papers-preprocess'] as $v) {
		require_once $v;
	}
}

// Iterate through subs
$q = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, CONCAT_WS(' ', `name_first`, `name_last`) AS `name`, `" . OCC_TABLE_PAPER . "`.`accepted`, `" . OCC_TABLE_PAPER . "`.`title`, " . $formatField . ", `" . OCC_TABLE_PAPER . "`.`student`, `" . OCC_TABLE_PAPER . "`.`type`" . $extraFields . " FROM `" . OCC_TABLE_PAPER . "` LEFT JOIN `" . OCC_TABLE_AUTHOR . "` ON (`" . OCC_TABLE_AUTHOR . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` AND `" . OCC_TABLE_AUTHOR . "`.`position`=`" . OCC_TABLE_PAPER . "`.`contactid`) WHERE 1=1 ";
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
$q .= "ORDER BY $sortby";
$r = ocsql_query($q) or err("Unable to get submissions");
if (ocsql_num_rows($r) == 0) {
	print '<p class="warn">No submissions available.</p>';
} else {
	print '
	<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="subsForm">
	<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />

	<p>Number of Submissions: ' . ocsql_num_rows($r) . '</p> 
	<p style="margin-bottom: 1.5em;">
	<select name="boxselect" id="boxselect" title="submissions to be selected when Select button clicked">
	<option value="all">all submissions</option>
';
	if (oc_hookSet('file_select_options')) {
		print call_user_func($GLOBALS['OC_hooksAR']['file_select_options'][0]);	// only one hook allowed here
	} else {
		print '<option value="ft1">submissions missing File</option>';
	}
	$accCountAR = array();
	$accCountAR['Pending'] = array();
	foreach ($OC_acceptanceValuesAR as $acc) {
		print '<option value="' . safeHTMLstr($acc['value']) . '">submissions where decision is ' . safeHTMLstr($acc['value']) . '</option>';
		$accCountAR[$acc['value']] = array();
	}
	print '
	<option value="Pending">submissions where decision is Pending</option>
	</select>
	<input type="button" value="Select" onclick="selectBoxes()" style="float: left; margin-right: 0.5em; vertical-align: top;" />
	</p>

	<table border="0" cellspacing="1" cellpadding="4" cols="4">
	<tr class="rowheader"><th class="del" scope="col"><input type="checkbox" title="check/uncheck all boxes" onclick="oc_toggleCheckboxes(this.checked, \'papers[]\');" /></th><th scope="col">' . $rsortstr . '</th><th scope="col">' . $tsortstr . '</th><th scope="col">' . $nsortstr . '</th>';
	
	if ($OC_trackStudent) {
		print '<th scope="col">' . $ssortstr . '</th>';
	}

	if ($OC_trackType) {
		print '<th scope="col">' . $stsortstr . '</th>';
	}

	print $fileTableHeader . '</tr>
';

	$row = 1;
	$missingFileAR = array(); // global var hack bec. call_user_func does not allow pass by reference for non-object
	$OC_downloadZipAR = array();
	while ($l = ocsql_fetch_array($r)) {
		print '<tr class="row' . $row . '"><td class="del"><input type="checkbox" name="papers[]" id="papers' . $l['paperid'] . '" value="' . $l['paperid'] . '" title="Submission ID ' . $l['paperid'] . '"></td><td align="right" width="40">' . $l['paperid'] . '</td><td><a href="show_paper.php?pid=' . $l['paperid'] . '">' . safeHTMLstr($l['title']) . '</a></td><td>' . safeHTMLstr($l['name']) . '</td>';

		if ($OC_trackStudent) {
			print '<th>'. (($l['student'] == 'T') ? '<span title="Student">&#10003;</span>' : '&nbsp;') . '</th>';
		}
				
		if ($OC_trackType) {
			print '<td>' . varValue('type', $l, '&nbsp;', true) . '</td>';
		}

		print oc_printFileCells($l, true) . "</tr>\n";
		
		$accCountAR[(empty($l['accepted']) ? 'Pending' : $l['accepted'])][] = $l['paperid'];
		
		if ($row==1) { $row=2; } else { $row=1; }
	}
	
	$skipCells = 4 + ($OC_trackStudent ? 1 : 0) + ($OC_trackType ? 1 : 0);
	print '<tr><td colspan="' . $skipCells . '" style="padding:0; margin:0;" valign="top"><table border="0" cellpadding="5" cellspacing="0" bgcolor="#ccccff"><tr><td><span style="white-space: nowrap;"><input type="submit" name="subaction" value="Delete Submissions" onclick="return confirm(\'Once deleted, submission data cannot be recovered.  Proceed?\');" /> &nbsp; <input type="submit" name="subaction" value="Withdraw Submissions" onclick="return confirm(\'Upon withdraw, review data and uploaded files will be permanently deleted, and assigned committee members may be notified.  Proceed?\');" /></span></td></tr></table></td>';
	if (class_exists('ZipArchive')) {
		if (oc_hookSet('print_file_cells_zip')) {
			$str = call_user_func($GLOBALS['OC_hooksAR']['print_file_cells_zip'][0], $row, 0, 0, false);	// only one hook allowed here
			print $str;
		} elseif (isset($OC_downloadZipAR[1]) && ($OC_downloadZipAR[1] > 1)) {
			print '<td class="row' . $row . '" style="text-align: center; font-size: 0.8em;"><a href="download.php?t=1"><img src="../images/documentmulti-sm.gif" border="0" alt="' . oc_('Download All') . '" title="' . oc_('Download All') . '" width="17" height="20" /><br />ZIP</a></td>';
		}
	}
	print '</tr>
	</table>
	<p class="note"><strong>Note:</strong> When deleting a submission, all records associated with the submission are permanently removed.  When withdrawing a submission, all reviews and assignments, uploaded files, etc, are permanently removed; the submission and ' . oc_strtolower(OCC_WORD_AUTHOR) . ' table data however may be restored if submission-related modules have not been uninstalled.</p>
	</form>

<script language="javascript" type="text/javascript">
// <!--
';
	foreach ($missingFileAR as $k => $v) {
		print 'oc_ftAR["' . $k . '"] = [' . implode(',', $v) . "];\n";
	}
	foreach ($accCountAR as $k => $v) {
		print 'oc_accAR["' . $k . '"] = [' . implode(',', $v) . "];\n";
	}
	print '
// -->
</script>
';	
}

$q = "SELECT `paperid`, `title`, `contact_author`, `contact_email`, `withdraw_date`, `withdrawn_by` FROM `" . OCC_TABLE_WITHDRAWN . "` ORDER BY `paperid`";
$r = ocsql_query($q) or err('Unable to check for withdrawn submissions');
if ((ocsql_num_rows($r) > 0) && empty($atype) && empty($stype)) {
	print '
<a name="withdrawn"></a>
<p><hr /></p>

<p style="text-align: center; font-weight: bold; font-size: 1.1em">Withdrawn Submissions</p>

<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
	<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<table border="0" cellspacing="1" cellpadding="4" cols="4">
<tr class="rowheader"><td class="del"><input type="checkbox" title="check/uncheck all boxes" onclick="oc_toggleCheckboxes(this.checked, \'wpapers[]\');" /></td><th>ID</th><th>Title</th><th>Contact ' . OCC_WORD_AUTHOR . '</th><th>Withdrawn By / On</th></tr>
';

	$row = 1;
	while ($l = ocsql_fetch_assoc($r)) {
		print '<tr class="row' . $row . '"><td class="del" width="25"><input type="checkbox" name="wpapers[]" value="' . $l['paperid'] . '" title="Withdrawn Submission ID ' . $l['paperid'] . '"></td><td align="right" width="40">' . $l['paperid'] . '</td><td>' . safeHTMLstr($l['title']) . '</td><td><a href="mailto:' . safeHTMLstr($l['contact_email']) . '">' . safeHTMLstr($l['contact_author']) . '</a></td><td>' . safeHTMLstr($l['withdrawn_by']) . ' / ' . safeHTMLstr($l['withdraw_date']) . '</tr>';
		if ($row==1) { $row=2; } else { $row=1; }
	}

print '
</table>
<table border=0 cellpadding=5 cellspacing=0 bgcolor="#ccccff"><tr><td><input type="submit" name="subaction" value="Delete Withdrawn Submissions" onclick="return confirm(\'Once deleted, submission data cannot be recovered.  Proceed?\');" />&nbsp; <input type="submit" name="subaction" value="Restore Submissions" /></td></tr></table>
<p class="note"><strong>Note:</strong> Restoring a withdrawn submission will not restore all the submission data, only the information stored in the submission (paper) and ' . oc_strtolower(OCC_WORD_AUTHOR) . 's (author) table.</p>
</form>
';
}

printFooter();

?>
