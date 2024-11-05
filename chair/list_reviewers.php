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

if (isset($_REQUEST['cmt']) && ($_REQUEST['cmt'] == "rev")) {
	$cmtAdd = " WHERE `onprogramcommittee`='F'";
	$cmt = 'rev';
} elseif (isset($_REQUEST['cmt']) && ($_REQUEST['cmt'] == "pc")) {
	$cmtAdd = " WHERE `onprogramcommittee`='T'";
	$cmt = 'pc';
} else {
	$cmtAdd = '';
	$cmt = '';
	$_REQUEST['cmt'] = '';
}

printHeader('Committee Members', 1);

if ($OC_configAR['OC_paperAdvocates']) {
	$options = '<option value="">All Committee Members</option><option value="rev">Review Committee</option><option value="pc">Program Committee (Advocates)</option>';
	print '
<form method="post" action="list_reviewers.php">
<input type="hidden" name="s" value="' . (isset($_REQUEST['s']) ? safeHTMLstr($_REQUEST['s']) : '') . '" />
<p style="text-align: center;">
<select name="cmt">' . preg_replace('/(value="' . $cmt . '")/', "$1 selected", $options) . '</select>
<input type="submit" value="Filter" />
</p>
</form>
';
}

if (isset($_POST['faction']) && isset($_POST['revids']) && !empty($_POST['revids'])) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}
	
	if  ($_POST['faction'] == "Delete Members") {	// delete members
		foreach ($_POST['revids'] as $reviewerid) {
			if (preg_match("/^\d+$/", $reviewerid)) {
				oc_deleteAssignments(null, $reviewerid, 'advocate');
				oc_deleteAssignments(null, $reviewerid);
				issueSQL("DELETE FROM `" . OCC_TABLE_REVIEWERTOPIC . "` WHERE `reviewerid`='" . safeSQLstr($reviewerid) . "'");
				issueSQL("DELETE FROM `" . OCC_TABLE_REVIEWER . "` WHERE `reviewerid`='" . safeSQLstr($reviewerid) . "'");
				
				if (oc_hookSet('delete_reviewer')) {
					foreach ($GLOBALS['OC_hooksAR']['delete_reviewer'] as $inc) {
						include $inc;
					}
				}
			}
		}
	} elseif  ($_POST['faction'] == "Add to PC") {	// add to program committee
		foreach ($_POST['revids'] as $reviewerid) {
			if (preg_match("/^\d+$/", $reviewerid)) {
				issueSQL("UPDATE " . OCC_TABLE_REVIEWER . " SET `onprogramcommittee`='T' WHERE `reviewerid`='" . safeSQLstr($reviewerid) . "' LIMIT 1");
			}
		}
	} elseif  ($_POST['faction'] == "Remove from PC") {	// remove from program committee
		foreach ($_POST['revids'] as $reviewerid) {
			if (preg_match("/^\d+$/", $reviewerid)) {
				oc_deleteAssignments(null, $reviewerid, 'advocate');
				issueSQL("UPDATE `" . OCC_TABLE_REVIEWER . "` SET `onprogramcommittee`='F' WHERE `reviewerid`='" . safeSQLstr($reviewerid) . "' LIMIT 1");
			}
		}
	}
}

if (!isset($_REQUEST['s']) || empty($_REQUEST['s']) || ($_REQUEST['s'] == "id")) {
	$sortby = "`reviewerid`";
	$rsortstr = 'ID<br />' . $OC_sortImg;
	$pcsortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=pc&cmt=' . $cmt . '" title="sort by program committee status">PC</a>';
	$nsortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=name&cmt=' . $cmt . '" title="sort by name">Name</a>';
} elseif ($_REQUEST['s'] == "pc") {
	$sortby = "`onprogramcommittee`, `name_last`, `name_first`";
	$pcsortstr = 'PC<br />' . $OC_sortImg;
	$rsortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=id&cmt=' . $cmt . '" title="sort by reviewer ID">ID</a>';
	$nsortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=name&cmt=' . $cmt . '" title="sort by name">Name</a>';
} else {	// name sort
	$sortby = "`name_last`, `name_first`";
	$nsortstr = 'Name<br />' . $OC_sortImg;
	$rsortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=id&cmt=' . $cmt . '" title="sort by reviewer ID">ID</a>';
	$pcsortstr = '<a href="' . $_SERVER['PHP_SELF'] . '?s=pc&cmt=' . $cmt . '" title="sort by program committee status">PC</a>';
}

$q = "SELECT `reviewerid`, CONCAT_WS(' ',`name_first`,`name_last`) AS `name`, `username`, `email`, `onprogramcommittee`, `comments` FROM `" . OCC_TABLE_REVIEWER . "` $cmtAdd ORDER BY $sortby";
$r = ocsql_query($q) or err("Unable to get information");
if (ocsql_num_rows($r) == 0) {
	print '<span class="warn">No committee members have signed up yet</span><p>';
} else {
	print '
<p style="text-align: center">Count: ' . ocsql_num_rows($r) . '</p>
<p style="text-align: center;" class="note">Note: If you choose to delete committee member(s), all review ' . ($OC_configAR['OC_paperAdvocates'] ? 'and advocacy ' : '') . ' data will also be deleted.</p>
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="membersform">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="cmt" value="' . safeHTMLstr($_REQUEST['cmt']) . '" />
<table border="0" style="margin: 0 auto;"><tr><td>
';
	if (isset($_REQUEST['s'])) {
		print '<input type="hidden" name="s" value="' . safeHTMLstr($_REQUEST['s']) . '" />';
	}
	print '
<table border="0" cellspacing="1" cellpadding="4">
<tr><td colspan="10" style="background-color: #ccf; padding-left: 30px;"><input type="submit" name="faction" value="Delete Members" onclick="return confirm(\'Delete all checked member account(s) and associated data (e.g., reviews)?\')" />
';
	if ($OC_configAR['OC_paperAdvocates']) {
		if ($cmt != 'pc') {
			print ' &nbsp; &nbsp; <input type="submit" name="faction" value="Add to PC" />';
		}
		if ($cmt != 'rev') {
			print ' &nbsp; &nbsp; <input type="submit" name="faction" value="Remove from PC" onclick="return confirm(\'Delete checked member(s) advocacy data?\')" />';
		}
	}
	print '
</td></tr>
<tr class="rowheader"><th scope="col" class="del"><input type="checkbox" title="check/uncheck all boxes" onclick="oc_toggleCheckboxes(this.checked, \'revids[]\');" /></th><th scope="col">' . $rsortstr . '</th>
';
	if (empty($_REQUEST['cmt'])) {
		print '<th scope="col">' . $pcsortstr . '</th>';
	}		
	print '<th scope="col">' . $nsortstr . '</th><th scope="col">Username</th><th scope="col">Comments</th></tr>';
	$row = 1;
	while ($l = ocsql_fetch_array($r)) {
	  	print '<tr class="row' . $row . '"><td class="del"><input type="checkbox" name="revids[]" id="revids' . $l['reviewerid'] . '" value="' . $l['reviewerid'] . '"></td><td align="right" scope="row"><label for="revids' . $l['reviewerid'] . '">' . $l['reviewerid'] . '</label></td>';
		if (empty($_REQUEST['cmt'])) {
			print '<td style="text-align: center;">';
	  		if ($l['onprogramcommittee'] == 'T') {
    			print '<span title="on program committee">&#10003;</span>';
			} else {
  				print "&nbsp;";
	  		}
	  		print '</td>';
		}
		print '<td><a href="show_reviewer.php?rid='.$l['reviewerid'].'">' . safeHTMLstr($l['name']) . '</a></td><td>'.$l['username'].'</td><td>' . safeHTMLstr($l['comments']) . " &nbsp;</td></tr>\n";
		if ($row==1) { $row=2; } else { $row=1; }
}
	print '
<tr><td colspan="10" style="background-color: #ccf; padding-left: 30px;"><input type="submit" name="faction" value="Delete Members" onclick="return confirm(\'Delete all checked member account(s) and associated data (e.g., reviews)?\')" />
';
	if ($OC_configAR['OC_paperAdvocates']) {
		if ($cmt != 'pc') {
			print ' &nbsp; &nbsp; <input type="submit" name="faction" value="Add to PC" />';
		}
		if ($cmt != 'rev') {
			print ' &nbsp; &nbsp; <input type="submit" name="faction" value="Remove from PC" onclick="return confirm(\'Delete checked member(s) advocacy data?\')" />';
		}
	}
	print '
</td></tr>
</table>

</td></tr></table>
</form>
';
}

printFooter();

?>
