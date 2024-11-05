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

printHeader("Conflicts",1);

if (isset($_POST['ocaction'])) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	if (($_POST['ocaction'] == "Unset Conflicts (UC)") && (!empty($_POST['drop']))) {
		foreach ($_POST['drop'] as $val) {
			if (preg_match("/^\d+,\d+$/",$val)) {
				list($pid,$rid) = explode(",", $val);
				issueSQL("DELETE FROM `" . OCC_TABLE_CONFLICT . "` WHERE `paperid`='" . safeSQLstr($pid) . "' AND `reviewerid`='" . safeSQLstr($rid) . "'");
			}
			else {
				print '<p class="warn">Unable to process ' . safeHTMLstr($val) . ".<p>\n";
			}
		}
	} elseif ($_POST['ocaction'] == 'update') {
		if (isset($_POST['alloworgconflict']) && ($_POST['alloworgconflict'] == 'no')) {
			updateConfigSetting('OC_allowOrgConflict', 0);
			$OC_configAR['OC_allowOrgConflict'] = 0;
		} else {
			updateConfigSetting('OC_allowOrgConflict', 1);			
			$OC_configAR['OC_allowOrgConflict'] = 1;
		}
		if (isset($_POST['allowemailconflict']) && ($_POST['allowemailconflict'] == 'no')) {
			updateConfigSetting('OC_allowEmailConflict', 0);
			$OC_configAR['OC_allowEmailConflict'] = 0;
		} else {
			updateConfigSetting('OC_allowEmailConflict', 1);			
			$OC_configAR['OC_allowEmailConflict'] = 1;
		}
	}
}

$pq = "SELECT `" . OCC_TABLE_CONFLICT . "`.`paperid`, `" . OCC_TABLE_CONFLICT . "`.`reviewerid`, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name`, `title` FROM `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_CONFLICT . "` WHERE `" . OCC_TABLE_CONFLICT . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` AND `" . OCC_TABLE_CONFLICT . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";

if (empty($_GET['s']) || ($_GET['s'] == "pid")) { 
	$q = $pq . " ORDER BY `" . OCC_TABLE_CONFLICT . "`.`paperid`, `" . OCC_TABLE_CONFLICT . "`.`reviewerid`";
	$sortid = "paper";
	$pidsort='<span style="white-space: nowrap;">P-ID</span><br />' . $OC_sortImg; 
	$psort='<a href="'.$_SERVER['PHP_SELF'].'?s=paper">Submission</a>'; 
	$rsort='<a href="'.$_SERVER['PHP_SELF'].'?s=reviewer">Reviewer</a>'; 
	$ridsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=rid">R-ID</a></span>'; 
} elseif ($_GET['s'] == "paper") {
	$q = $pq . " ORDER BY `" . OCC_TABLE_PAPER . "`.`title`, `" . OCC_TABLE_CONFLICT . "`.`reviewerid`";
	$sortid = "paper";
	$pidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=pid">P-ID</a></span>';  
	$psort="Submission<br />" . $OC_sortImg;
	$rsort='<a href="'.$_SERVER['PHP_SELF'].'?s=reviewer">Reviewer</a>'; 
	$ridsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=rid">R-ID</a></span>'; 
} elseif ($_GET['s'] == "rid") {
	$q = $pq . " ORDER BY `" . OCC_TABLE_CONFLICT . "`.`reviewerid`, `" . OCC_TABLE_CONFLICT . "`.`paperid`";
	$sortid = "reviewer";
	$pidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=pid">P-ID</a></span>'; 
	$psort='<a href="'.$_SERVER['PHP_SELF'].'?s=paper">Submission</a>'; 
	$rsort='<a href="'.$_SERVER['PHP_SELF'].'?s=reviewer">Reviewer</a>'; 
	$ridsort='<span style="white-space: nowrap;">R-ID</span><br />' . $OC_sortImg; 
} elseif ($_GET['s'] == "reviewer") {
	$q = $pq . " ORDER BY `" . OCC_TABLE_REVIEWER . "`.`name_last`, `" . OCC_TABLE_REVIEWER . "`.`name_first`, `" . OCC_TABLE_CONFLICT . "`.`paperid`";
	$sortid = "reviewer";
	$pidsort='<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=pid">P-ID</a></span>'; 
	$psort='<a href="'.$_SERVER['PHP_SELF'].'?s=paper">Submission</a>'; 
	$rsort="Reviewer<br />" . $OC_sortImg;
	$ridsort= '<span style="white-space: nowrap;"><a href="'.$_SERVER['PHP_SELF'].'?s=rid">R-ID</a></span>'; 
} else {
	err("Unknown sort source");
}

print '
<p><strong>Manually Set Conflicts:</strong> [<a href="set_conflicts.php">set</a>]</p>
';

$r = ocsql_query($q) or err("Unable to get information");
if (ocsql_num_rows($r) == 0) {
        print '<p class="note"> &nbsp; &nbsp; &nbsp; No conflicts have been set.</p>';
} else {
	$s = substr($sortid, 0, 1);
	
	print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="src" value="' . $s . '">
<table border="0" cellspacing="1" cellpadding="4" COLS=3>
<tr><td align="right" colspan="5"><span style="border: 6px solid #ccf;"><input type="submit" name="ocaction" value="Unset Conflicts (UC)" /></span></td></tr>
<tr class="rowheader"><th valign="top" style="width: 4em;">' . $pidsort . '</th><th valign="top">' . $psort . '</th><th valign="top" style="width: 4em;">' . $ridsort . '</th><th valign="top">' . $rsort . '</th><th bgcolor="#ccccff">UC</th></tr>
	';
	$currid = -1;
	$row = 1;
	while ($l = ocsql_fetch_array($r)) {

		if ($sortid == "reviewer") {
			$ptags = '<td align="right">'.$l['paperid'].'</td><td><a href="show_paper.php?pid=' . $l['paperid'] . '" target="_blank" title="opens in new window/tab">' . safeHTMLstr($l['title']) . '</a></td>';
			$rtags = '<td align="right">' . $l['reviewerid'] . '</td><td><a href="show_reviewer.php?rid=' . $l['reviewerid'] . '" target="_blank" title="opens in new window/tab">' . safeHTMLstr($l['name']) . '</a></td>';
		} else {
			$ptags = '<td align="right">'.$l['paperid'].'</td><td><a href="show_paper.php?pid=' . $l['paperid'] . '" target="_blank" title="opens in new window/tab">' . safeHTMLstr($l['title']) . '</a></td>';
			$rtags = '<td align="right">'.$l['reviewerid'].'</td><td><a href="show_reviewer.php?rid=' . $l['reviewerid'] . '" target="_blank" title="opens in new window/tab">' . safeHTMLstr($l['name']) . '</a></td>';
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
		
		print '<tr class="row' . $row . '">' . $ptags . $rtags;
		print '<td align="center" bgcolor="#ccccff">';
		if (empty($l['reviewerid'])) { print '&nbsp;'; }
		else {
			print '<input type="checkbox" name="drop[]" value="' . safeHTMLstr($l['paperid'] . ',' . $l['reviewerid']) . '">';
		}
		print '</td>';

		print "</tr>\n";
	}
	print '
<tr><td align="right" colspan="5"><span style="border: 6px solid #ccf;"><input type="submit" name="ocaction" value="Unset Conflicts (UC)" /></span></td></tr>
</table></form>
';
}

// Show auto-detected conflicts
print '
<p><hr /></p>
<p><strong>Automatically Detected Conflicts:</strong></p>
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<p style="margin-left: 30px;"><label><input type="checkbox" name="allowemailconflict" value="no" ' . (($OC_configAR['OC_allowEmailConflict'] == 0) ? 'checked ' : '') . '/> Email Address</label> &nbsp; &nbsp; &nbsp; <label><input type="checkbox" name="alloworgconflict" value="no" ' . (($OC_configAR['OC_allowOrgConflict'] == 0) ? 'checked ' : '') . '/> Organization Name</label> &nbsp; &nbsp; <input type="submit" name="ocaction" value="update" /></p>
</form>
';

if (($OC_configAR['OC_allowEmailConflict'] == 1) && ($OC_configAR['OC_allowOrgConflict'] == 1)) {
	print '<p class="warn" style="margin-left: 30px;">Auto-conflict detection is disabled; change settings above.</p>';
} else {
	$q = "SELECT `" . OCC_TABLE_AUTHOR . "`.`paperid`, `reviewerid`, `title`, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name` FROM `" . OCC_TABLE_AUTHOR . "`, `" . OCC_TABLE_PAPER . "`, `" . OCC_TABLE_REVIEWER . "` WHERE " .
		"`" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` AND (";
	if ($GLOBALS['OC_configAR']['OC_allowEmailConflict'] == 0) {
		$q .= " (`" . OCC_TABLE_AUTHOR . "`.`email`=`" . OCC_TABLE_REVIEWER . "`.`email`)";
	}
	if ($GLOBALS['OC_configAR']['OC_allowOrgConflict'] == 0) {
		if ($GLOBALS['OC_configAR']['OC_allowEmailConflict'] == 0) {
			$q .= " OR";
		}
		$q .= " (`" . OCC_TABLE_AUTHOR . "`.`organization` <> '' AND `" . OCC_TABLE_AUTHOR . "`.`organization`=`" . OCC_TABLE_REVIEWER . "`.`organization`)";
	}
	$q .= ") GROUP BY `paperid`, `reviewerid`, `title`, `name` ORDER BY `paperid`, `reviewerid`";
	$r = ocsql_query($q) or err("Unable to get auto paper/reviewer conflicts");
	if (ocsql_num_rows($r) < 1) {
		print '<p class="note"> &nbsp; &nbsp; &nbsp; No conflicts detected</p>';
	} else {
		print '<table border="0" cellspacing="1" cellpadding="4"><tr class="rowheader"><th>Submission</th><th>Reviewer</th></tr>';
		$row = 1;
		while ($l=ocsql_fetch_array($r)) {
			print '<tr class="row' . $row . '"><td>' . $l['paperid'] . '. <a href="show_paper.php?pid=' . $l['paperid'] . '" target="_blank" title="opens in new window/tab">' . safeHTMLstr($l['title']) . '</a></td><td>' . $l['reviewerid'] .'. <a href="show_reviewer.php?rid=' . $l['reviewerid'] . '" target="_blank" title="opens in new window/tab">' . safeHTMLstr($l['name']) . '</a></td>';
				$row = $rowAR[$row];
		}
		print '</table><p class="note">For a match to occur, the email address or organization name must be exactly the same.</p>';
	}
}

// Check for addt'l (hook) conflict displays
if (isset($OC_hooksAR['list_conflicts-display']) && !empty($OC_hooksAR['list_conflicts-display'])) {
	foreach ($OC_hooksAR['list_conflicts-display'] as $hook) {
		print '<p><hr /></p>';
		require_once $hook;
	}
}

printFooter();

?>
