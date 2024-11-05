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

printHeader("Set Conflicts",1);

if (isset($_POST['submit']) && ($_POST['submit'] == "Set Conflicts")) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}
	// Check that we have at least one paper and reviewer
	if (empty($_POST['papers']) || empty($_POST['reviewers'])) {
		print '<span class="err">Please go back and select at least one submission and one reviewer</span><p>';
	} else {
		foreach ($_POST['reviewers'] as $i) {
			if (!preg_match("/^\d+$/", $i)) {
				err('Reviewer ID invalid');
			}
			foreach ($_POST['papers'] as $j) {
				if (!preg_match("/^\d+$/", $j)) {
					err('Submission ID invalid');
				}
				$q = "INSERT INTO `" . OCC_TABLE_CONFLICT . "` (`paperid`, `reviewerid`) VALUES ($j,$i)";
				ocsql_query($q);
                if (($merr = ocsql_errno()) != 0) {
					if ($merr == 1062) {	// Duplicate entry
					    print "<p class=\"warn\">! Submission $j and reviewer $i already registered as a conflict.</p>\n";
					} else {
	                    print "<p class=\"err\">! Error registering submission $j and reviewer $i as a conflict</p>\n";
					}
				} else { print "<p>Submission $j and reviewer $i registered as a conflict.\n"; }
			}
		}
		print '
<p>&#187; <a href="' . $_SERVER['PHP_SELF'] . '">Set additional conflicts</a></p>
<p>&#187; <a href="list_conflicts.php">View conflicts</a></p>

';
	}
} else {
	$pq = "SELECT `" . OCC_TABLE_PAPER . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`title` FROM `" . OCC_TABLE_PAPER . "` ORDER BY `" . OCC_TABLE_PAPER . "`.`paperid`";
	$pr = ocsql_query($pq) or err("Unable to get submissions");
	// Get pad size for paper id's - yes, we really need the max id, but this should do:)
    $rows = ocsql_num_rows($pr);
	$psize = oc_strlen((string) $rows);
	if ($rows == 0) {
		print '<span class="warn">No submissions have been made yet</span><p>';
	}
	else {
		if (!isset($_GET['s']) || ($_GET['s'] == "id"))  {
			$idsortstr = 'ID';
		    $nsortstr = '<a href="' . $_SERVER['PHP_SELF'].'?s=name">Name</a>';
			$legend = "[ Reviewer ID - $nsortstr ]";
			$sortby = "`" . OCC_TABLE_REVIEWER . "`.`reviewerid`";
		} else {
		    $idsortstr = '<a href="' . $_SERVER['PHP_SELF'].'?s=id">ID</a>';
			$nsortstr = 'Name';
			$legend = "[ Reviewer Name - $idsortstr ]";
			$sortby = "`" . OCC_TABLE_REVIEWER . "`.`name_last`, `" . OCC_TABLE_REVIEWER . "`.`name_first`";
		}
		$rq = "SELECT `" . OCC_TABLE_REVIEWER . "`.`reviewerid`, `onprogramcommittee`, CONCAT_WS(' ', `" . OCC_TABLE_REVIEWER . "`.`name_first`, `" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name` FROM `" . OCC_TABLE_REVIEWER . "` ORDER BY $sortby";
		$rr = ocsql_query($rq) or err("Unable to get reviewers");
		// Get pad size for reviewer id's - yes, we really need the max id, but this should do:)
		$rsize = oc_strlen((string) ocsql_num_rows($rr));
		if (ocsql_num_rows($rr) == 0) {
			print '<span class="warn">No reviewers have signed up yet</span><p>';
		}
		else {
			print '
<form method="post" action="'.$_SERVER['PHP_SELF'].'">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />

<div style="float: left; margin-right: 50px;">
<p><strong>Select Submission(s):</strong></p>
<p>[ Submission ID - Title ]</p>
<select multiple size="20" name="papers[]">
			';
  
			while ($paper = ocsql_fetch_assoc($pr)) {
				print '<option value="' . $paper['paperid'].'">' . padNumber($paper['paperid'],$psize) . ' - ' . safeHTMLstr(shortenStr($paper['title'],80)) . "</option>\n";
			}
  
			print '
</select>
</div>
<div style="float: left;">
<p><strong>Select Reviewer(s):</strong></p>
<p>' . $legend . '</p>
<select multiple size="20" name="reviewers[]">
			';
  
			while ($reviewer = ocsql_fetch_assoc($rr)) {
				print '<option value="' . $reviewer['reviewerid'] . '">';
				if (!isset($_GET['s']) || ($_GET['s'] == "id")) {
					print padNumber($reviewer['reviewerid'],$rsize) . ' - ';
					if ($reviewer['onprogramcommittee'] == 'T') {
					    print "[PC] ";
					}
					print safeHTMLstr($reviewer['name']) . "</option>\n";
				} else {
					if ($reviewer['onprogramcommittee'] == 'T') {
					    print "[PC] ";
					}
					print safeHTMLstr($reviewer['name']) . " - " . $reviewer['reviewerid'] . "</option>\n";
				}
			}
  
			print '
</select>
<p class="note">Tip: Click the ID or Name links above<br />to re-sort this list (page will reload)</p>
</div>

<br style="clear: left;" />

<p><input type="submit" name="submit" value="Set Conflicts"></p>
</form>
			';
		}
	}
}

printFooter();

?>
