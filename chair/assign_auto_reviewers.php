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

function oc_skipSubAssignment($sid) {
	$skip = false;
	
	// skip if accepted
	if (isset($_POST['skipaccepted']) && ($_POST['skipaccepted'] == 'Yes') && isset($GLOBALS['pInfoAR'][$sid]['accepted']) && !empty($GLOBALS['pInfoAR'][$sid]['accepted'])) {
		$skip = true;
	}
	//skip if not of the selected type
	if (isset($_POST['limittype']) && !empty($_POST['limittype']) && isset($GLOBALS['pInfoAR'][$sid]['type']) && ($_POST['limittype'] != $GLOBALS['pInfoAR'][$sid]['type'])) {
		$skip = true;
	}

	return($skip);
}

function oc_assignTimeoutShutdown() {
	$_SESSION['OPENCONFCHAIRVARS']['timeoutAR'] = array(
		'pAR'					=> $GLOBALS['pAR'],
		'nAR'					=> $GLOBALS['nAR'],
		'rAR'					=> $GLOBALS['rAR'],
		'pInfoAR'				=> $GLOBALS['pInfoAR'],
		'ptot'					=> $GLOBALS['ptot'],
		'rNameAR'				=> $GLOBALS['rNameAR'],
		'pcAR'					=> $GLOBALS['pcAR'],
		'rtot'					=> $GLOBALS['rtot'],
		'reviewersPerPaper'		=> $GLOBALS['reviewersPerPaper'],
		'papersPerReviewer'		=> $GLOBALS['papersPerReviewer'],
		'pprThreshold'			=> $GLOBALS['pprThreshold'],
		'algo'					=> $GLOBALS['algo'],
		'continueFrom'			=> (isset($GLOBALS['continueVal']) ? $GLOBALS['continueVal'] : 0),
		'POST'					=> array(
				'remedy'	=> (isset($_POST['remedy']) ? $_POST['remedy'] : array()),
				'keep'		=> (isset($_POST['keep']) ? $_POST['keep'] : ''),
				'skipaccepted'	=> (isset($_POST['skipaccepted']) ? $_POST['skipaccepted'] : ''),
				'limittype'	=> (isset($_POST['limittype']) ? $_POST['limittype'] : ''),
				'pcrev'		=> (isset($_POST['pcrev']) ? $_POST['pcrev'] : ''),
				'advrev'	=> (isset($_POST['advrev']) ? $_POST['advrev'] : ''),
				'rpp'		=> (isset($_POST['rpp']) ? $_POST['rpp'] : ''),
				'ppr'		=> (isset($_POST['ppr']) ? $_POST['ppr'] : ''),
				'pprt'		=> (isset($_POST['pprt']) ? $_POST['pprt'] : ''),
				'ppa'		=> (isset($_POST['ppa']) ? $_POST['ppa'] : ''),
				'algo'		=> (isset($_POST['algo']) ? $_POST['algo'] : '')
		)		
	);
	if (isset($GLOBALS['continueAR'])) {
		foreach ($GLOBALS['continueAR'] as $k) {
			$_SESSION['OPENCONFCHAIRVARS']['timeoutAR'][$k] = $GLOBALS[$k];
		}
	}
	session_write_close();
	header("Location: " . $_SERVER['PHP_SELF'] . "?timeout=1");
	ob_clean();
	exit;
}

ob_start();

printHeader("Auto Assign Reviewers", 1);

// Init algorithm array
$OC_algorithmAR = array(
	array(
		'algorithm'		=> 'Weighted Topic Match',
		'description'	=> 'Assigns based on number of topic matches (high to low) between submissions and reviewers, giving assignment precedence to submissions with the least number of overall matching reviewer/topic pairings',
		'include'		=> 'assign_auto_reviewers_weighted_topic_match.inc'
	),
	array(
		'algorithm'		=> 'Topic Match',
		'description'	=> 'Assigns based on number of topic matches (high to low) between submissions and reviewers',
		'include'		=> 'assign_auto_reviewers_topic_match.inc'
	),
);

// Check for addt'l (hook) algorithms
if (oc_hookSet('assign_auto_reviewers-algorithm')) {
	foreach ($OC_hooksAR['assign_auto_reviewers-algorithm'] as $k => $v) {
		$OC_algorithmAR[] = $v;
	}
}

// Submit - Commit assignments to database?
if (isset($_POST['submit']) && ($_POST['submit'] == "Make Assignments")) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	if (!isset($_SESSION['OPENCONFCHAIRVARS']['pAssignments'])) {
		err("No reviewers set");
	}

	// Keep or delete current assignments?
	$currAR = array();
	if ($_POST['keep'] == 'Yes') {
		$prq = "SELECT `paperid`, `reviewerid` FROM `" . OCC_TABLE_PAPERREVIEWER . "`";
		$prr = ocsql_query($prq) or err("Unable to access database");
		while ($prl = ocsql_fetch_array($prr)) {
			$currAR[] = $prl['paperid'] . '-' . $prl['reviewerid'];
		}
	} else {
		oc_deleteAssignments(null, null);
	}
	
	// Add reviewers
	foreach ($_SESSION['OPENCONFCHAIRVARS']['pAssignments'] as $pid => $rids) {
		foreach ($rids as $rid) {
			if (!$rid || in_array($pid . '-' . $rid, $currAR)) { continue; }
			$q = "INSERT INTO `" . OCC_TABLE_PAPERREVIEWER . "` (`paperid`,`reviewerid`,`assigned`) VALUES ('" . safeSQLstr($pid) . "','" . safeSQLstr($rid) . "','" . safeSQLstr(date('Y-m-d')) . "')";
			issueSQL($q);
		}
	}
	
	// Okey Dokey
	print '<p><strong>Assignments have been made</strong></p>
<p><a href="list_reviews.php">List Reviews</a></p>
	';
	unset($_SESSION['OPENCONFCHAIRVARS']['pAssignments']);

	printFooter();
	exit;
}

// Check whether any reviews assigned yet
$prq = "SELECT `paperid`, `reviewerid` FROM `" . OCC_TABLE_PAPERREVIEWER . "`";
$prr = ocsql_query($prq) or err("Unable to access database");
$prtot = ocsql_num_rows($prr);
if ($prtot > 0) {
	print '<p class="err">Reviews appear to have already been assigned. Existing reviews will be deleted unless you choose <em>Yes</em> to <em>Keep Existing Assignments</em> below.</p>';
	$confirmOverride = true;
} else {
	$confirmOverride = false;
}


if (isset($_GET['timeout']) && ($_GET['timeout'] == 1) && isset($_SESSION['OPENCONFCHAIRVARS']['timeoutAR']) && is_array($_SESSION['OPENCONFCHAIRVARS']['timeoutAR'])) {
	foreach($_SESSION['OPENCONFCHAIRVARS']['timeoutAR'] as $k => $v) {
		if ($k == 'POST') {
			foreach ($v as $pk => $pv) {
				$_POST[$pk] = $pv;
			}
		} else {
			$$k = $v;
		}
	}
	unset($_SESSION['OPENCONFCHAIRVARS']['timeoutAR']);
} else {
	// Assign PC members as reviewers?
	$ppa = '';
	if (isset($_POST['pcrev']) && ($_POST['pcrev'] == 'Yes')) {
		$onpc = "";
		// Set a diff max # of subs per advocate reviewer
		if (isset($_POST['ppa']) && preg_match("/^\d+$/", $_POST['ppa'])) {
			$ppa = $_POST['ppa'];
		}
	} else {
		$onpc = " AND `" . OCC_TABLE_REVIEWER . "`.`onprogramcommittee`='F'";
	}
	
	// Get number of papers and initialize paper count array
	$pAR = array();			// paper reviewers array
	$pInfoAR = array();	// paper info array
	$q = "SELECT `paperid`, `title`, `accepted`, `type` FROM `" . OCC_TABLE_PAPER . "` ORDER BY `paperid`";
	$r = ocsql_query($q) or err("Unable to get submissions");
	if (($ptot = ocsql_num_rows($r)) == 0) {
		warn('No submissions have been made yet.');
	}
	while ($l=ocsql_fetch_array($r)) {
		$pAR[$l['paperid']] = array();
		$pInfoAR[$l['paperid']] = array(
			'title' => $l['title'],
			'accepted' => $l['accepted'],
			'type' => $l['type']
		);
	}
	
	// Get number of reviewers and initialize reviewer count arrays
	$rAR = array();			// reviewer papers array
	$rNameAR = array();		// reviewer name array
	$pcAR = array();        // program committee array
	$q = "SELECT `reviewerid`, CONCAT_WS(' ', `name_first`, `name_last`) AS `name`, `onprogramcommittee` FROM `" . OCC_TABLE_REVIEWER . "` ORDER BY `reviewerid`";
	$r = ocsql_query($q) or err("Unable to get reviewers");
	if (($rtot = ocsql_num_rows($r)) == 0) {
		warn('No reviewers have signed up yet.');
	}
	while ($l=ocsql_fetch_array($r)) {
		$rAR[$l['reviewerid']] = array();
		$rNameAR[$l['reviewerid']] = $l['name'];
		if ($l['onprogramcommittee'] == 'T') { $pcAR[] = $l['reviewerid']; }
	}
	
	// Revise rtot based on whether pc members assigned as reviewers
	if (!isset($_POST['pcrev']) || ($_POST['pcrev'] == 'No')) {
		$rtot -= count($pcAR);
	}
	
	// Get conflicts
	$nAR = getConflicts();
	
	// Calculate # of reviewers per paper
	if (isset($_POST['rpp']) && preg_match("/^\d+$/",$_POST['rpp'])) {
		$reviewersPerPaper = $_POST['rpp'];
	} else {
		if (($rppavg=round($rtot/$ptot)) > $OC_configAR['OC_minReviewersPerPaper']) {
			$reviewersPerPaper = $rppavg;
		} else {
			$reviewersPerPaper = $OC_configAR['OC_minReviewersPerPaper'];
		}
	}
	
	// Calculate max # of papers each reviewer should get assigned
	if (isset($_POST['ppr']) && preg_match("/^\d+$/",$_POST['ppr'])) {
		$papersPerReviewer = $_POST['ppr'];
		#set below: $pprThreshold = $_POST['pprt'];
	} else {
		$totrevs = $rtot;
		$papersPerReviewer = ceil((($ptot*$reviewersPerPaper)+count($nAR))/$totrevs);  # possibly remove +count($nAR)
	}
	
	// Set min # of paper each reviewer should be assigned
	if (isset($_POST['pprt']) && preg_match("/^\d+$/",$_POST['pprt'])) {
		$pprThreshold = $_POST['pprt'];
	} else {
		$pprThreshold = floor($papersPerReviewer/2);
	}
	
	// Keep assignments already made?
	if (!isset($_POST['keep']) || ($_POST['keep'] == 'Yes')) {
		$confirmOverride = false;
		if ($prtot > 0) {
			while ($prl = ocsql_fetch_array($prr)) {
				array_push($pAR[$prl['paperid']], $prl['reviewerid']);
				array_push($rAR[$prl['reviewerid']], $prl['paperid']);
			}
		}
	}
	
	// Add advocates as reviewers?
	if ($OC_configAR['OC_paperAdvocates'] 
		&& (!isset($_POST['advrev']) || empty($_POST['advrev']) || ($_POST['advrev'] == "Yes"))
	) {
		$q = "SELECT `paperid`, `advocateid` FROM `" . OCC_TABLE_PAPERADVOCATE . "`";
		$r = ocsql_query($q) or err("Unable to get submissions' advocate");
		while ($l=ocsql_fetch_array($r)) {
			if (oc_skipSubAssignment($l['paperid'])) {
				continue;
			}
			if (!in_array($l['advocateid'], $pAR[$l['paperid']])) {
				array_push($pAR[$l['paperid']], $l['advocateid']);
				array_push($rAR[$l['advocateid']], $l['paperid']);
			}
		}
	}
	
	// Set max # of papers for advocate reviewers
	if (empty($ppa)) {
		$papersPerAdvocate = $papersPerReviewer;
	} else {
		$papersPerAdvocate = $ppa;
	}
	
	// Algorithm to use
	if (isset($_POST['algo']) && ctype_digit($_POST['algo'])) {
		$algo = $_POST['algo'];
	} else {
		$algo = 0;  // default to first defined algorithm above
	}
}

// Get list of sub. types
$OC_activeSubTypeAR = array();
$typer = ocsql_query("SELECT DISTINCT `type` FROM `" . OCC_TABLE_PAPER . "` WHERE `type` IS NOT NULL AND `type`!='' ORDER BY `type`") or err('Unable to retrieve submission types');
while ($typel = ocsql_fetch_assoc($typer)) {
	$OC_activeSubTypeAR[$typel['type']] = substr($typel['type'], 0, 50);
}

// Run algo
if (isset($OC_algorithmAR[$algo]['include']) && is_file($OC_algorithmAR[$algo]['include'])) {
	require_once $OC_algorithmAR[$algo]['include'];
} else {
	err("Algorithm choice unknown");
	exit;
}

// Remedy missing reviews by assigning reviewers w/lowest #s (if set)
if (isset($_POST['remedy']) && in_array("random", $_POST['remedy'])) {
	foreach (array_keys($pAR) as $k) {
		if (oc_skipSubAssignment($k)) {
			continue;
		}
		if (count($pAR[$k]) < $reviewersPerPaper) {
			// Create an ordered array of reviewers w/least # of reviews
			$rcountAR = array();
			foreach (array_keys($rAR) as $k2) {
				if ((!isset($_POST['pcrev']) || ($_POST['pcrev'] == 'No')) && in_array($k2, $pcAR)) { // skip PC members?
					continue;
				}
				$rcountAR[$k2] = count($rAR[$k2]);
			}
			asort($rcountAR);
			reset($rcountAR);
			// Assign reviewers
			if (!in_array(key($rcountAR),$pAR[$k]) && !in_array($k.'-'.key($rcountAR),$nAR)) {
				array_push($pAR[$k], key($rcountAR));
				array_push($rAR[key($rcountAR)], $k);
			}
			while ( (count($pAR[$k]) < $reviewersPerPaper) && (next($rcountAR) !== false) ) {
				if (!in_array(key($rcountAR),$pAR[$k]) && !in_array($k.'-'.key($rcountAR),$nAR)) {
					array_push($pAR[$k], key($rcountAR));
					array_push($rAR[key($rcountAR)], $k);
				}
			}
		}
	}
}

// Remember assignments
$_SESSION['OPENCONFCHAIRVARS']['pAssignments'] = $pAR;

// Display form
print '
<script language="javascript">
<!--
function suggestPPR() {
	if (document.getElementById && Math.ceil) {
		var rpp = document.getElementById("rpp").value;
		var pprSug = Math.ceil(((' . $ptot . ' * rpp) + ' . count($nAR) . ') / ' . $rtot . ');
		alert("Suggested value: " + pprSug);
	}
}
function updatePPA(fieldChecked) {
	if (fieldChecked == "Yes") {
		document.getElementById("maxppa").style.display = "";
	} else {
		document.getElementById("maxppa").style.display = "none";
	}
}
// -->
</script>

<p>Below you will find OpenConf\'s suggested review assignments. You may fine tune these automated assignments by changing the following options and clicking <em>Re-Evaluate Assignments</em>. Once you are satisfied, click the <em>Make Assignments</em> button to commit them to the database. You may manually add/delete reviews afterwards through the <em>Assign Reviews Manually</em> and <em>List/Unassign Reviews</em> menus.';

if (oc_moduleValid('oc_auto_assign')) {
	print ' If instead of using this feature you would like assignments to be made automatically when a new submission is made, use the <a href="../modules/modules.php">Auto Assign</a> module.';
}

print ' Note that changes to the options below are not kept upon leaving this page.</p>

<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<table border=0 cellspacing=5 cellpadding=0 bgcolor="#eeeeee">
<tr><td>Total Submissions:</td><td>' . $ptot . '</td></tr>
<tr><td>Total Reviewers:</td><td>' . $rtot . '</td></tr>
<tr><td>Reviewer/Submission Pairings in Conflict:</td><td>' . count($nAR) . ' &nbsp;(<a href="list_conflicts.php" target="_blank" style="font-style: italic;" title="opens in a new window/tab">manage</a>)</td></tr>
<tr><td>Desired Reviewers per Submission:</td><td><input name="rpp" id="rpp" size="4" maxlength="4" value="' . $reviewersPerPaper . '" style="background-color: #fcc;"></td></tr>
<tr><td>Maximum Submissions per Reviewer:</td><td><input name="ppr" id="ppr" size="4" maxlength="4" value="' . $papersPerReviewer . '"> 
<script language="javascript">
<!--
document.write(\'(<a href="javascript:void(0);" onclick="suggestPPR();" style="font-style: italic;">suggest</a>)\');
// -->
</script>
</td></tr>
<tr><td>Highlight if Submissions per Reviewer &lt; =</td><td><input name="pprt" size="4" maxlength="4" value="' . $pprThreshold . '" style="background-color: #ffc;"></td></tr>

<tr><td valign="top" title="Selecting No will result in all current assignments being deleted">Keep Existing Assignments?</td><td>' . generateRadioOptions('keep', $yesNoAR, varValue('keep', $_POST, 'Yes'), 0) . '</td></tr>

<tr><td valign="top" title="Selecting No will result in submissions already accepted or rejected in also being assigned">Skip Accepted/Rejected Submissions?</td><td>' . generateRadioOptions('skipaccepted', $yesNoAR, varValue('skipaccepted', $_POST, 'Yes'), 0) . '</td></tr>
';

if (count($OC_activeSubTypeAR) > 1) {
	print '
<tr><td valign="top" title="Only submissions of the selected type will be assigned">Assign submissions of type:</td><td><select name="limittype"><option value="">All</option>' . generateSelectOptions($OC_activeSubTypeAR, varValue('limittype', $_POST), 1) . '</select></td></tr>
';
}

$algoOptions = '';
foreach ($OC_algorithmAR as $k => $v) {
	$algoOptions .= '<label title="' . safeHTMLstr((isset($v['description']) ? $v['description'] : '')) . '"><input type="radio" name="algo" value="' . $k . '" /> ' . safeHTMLstr($v['algorithm']) . '</label><br />'; 
}
$algoOptions = preg_replace("/(value=\"" . $algo . "\")/","$1 checked", $algoOptions);

print '<tr><td valign="top">Algorithm:</td><td>' . $algoOptions . '</td></tr>';

$remStr = '<tr><td valign="top">Remedy Missing Assignments by:</td><td><!--<input type="checkbox" name="remedy[]" value="bump">Bumping # Submissions / Reviewer<br />--><label><input type="checkbox" name="remedy[]" value="random">Randomly Assigning Reviewers</label></td></tr>';
if (isset($_POST['remedy']) && !empty($_POST['remedy'])) { 
	foreach ($_POST['remedy'] as $rs) {
		if (preg_match("/^\w+$/", $rs)) {
			$remStr = preg_replace("/(value=\"" . preg_quote($rs, '/') . "\")/", "$1 checked", $remStr);
		}
	}
}

print $remStr;

if ($OC_configAR['OC_paperAdvocates']) {
	print '
<tr><td valign="top">Assign PC members as reviewers?</td><td>' . generateRadioOptions('pcrev', $yesNoAR, varValue('pcrev', $_POST, 'No'), 0, 'onclick="updatePPA(this.value);"') . '</td></tr>
<tr id="maxppa"><td valign="top">Maximum Submissions per Advocate:</td><td><input name="ppa" size="4" maxlength="4" value="' . safeHTMLstr(varValue('ppa', $_POST, '')) . '" /> <span class="note">leave blank to use reviewer value above</span></td></tr>
<tr><td valign="top">Assign submission\'s advocate as reviewer?</td><td>' . generateRadioOptions('advrev', $yesNoAR, varValue('advrev', $_POST, 'Yes'), 0) . '</td></tr>
';
} else {
	print '
<input type="hidden" name="pcrev" value="No" />
<input type="hidden" name="ppa" value="" />
<input type="hidden" name="advrev" value="No" />
';
}

print '
<tr><td colspan=2>&nbsp;</td></tr>
<tr><td colspan=2><input type="submit" name="submit" class="submit" value="Re-Evaluate Assignments"><p><input type="submit" name="submit" class="submit" value="Make Assignments"' . ($confirmOverride ? ' onclick="return confirm(\'Confirm overwrite of existing assignments\')"' : '')  . '> <span class="note">(commits assignments to database)</span></td></tr>
</table>
</form>
<p><hr><p>
<style type="text/css">
.phighlight { background: #ffcccc; font-weight: bold;}
.rhighlight { background: #ffffcc; font-weight: bold;}
</style>
';

function fmtNumSpacing ($n, $dir="l") {
	$sp = "";
	if ($n < 10) { $sp = "  "; }
	elseif ($n < 100) { $sp = " "; }
	if ($dir=="l") { return($sp.$n); }
	else { return($n.$sp); }
}

function fmtStrSpacing ($str, $len) {
	$a = substr($str,0,$len);
	for ($i=oc_strlen($a); $i<$len; $i++) {
		$a .= " ";
	}
	return $a;
}

print '
<table border=0 cellspacing=0 cellpadding=0>
<tr><th colspan=3>Suggested Assignments' . ((!isset($_POST['keep']) || ($_POST['keep'] == 'Yes')) ? '<p class="note" style="font-weight: normal;">including already assigned</p>' : '') . '</th></tr>
<tr><td valign="top"><pre>
<span class="phighlight"> * </span> below threshold

<span class="note">Place cursor on No. Reviewers for assignments</span>
<span class="note">Click title for submission info (new window)</span>

<strong>No. Reviewers per Submission ID (Title)</strong>
';
foreach ($pAR as $k => $v) {
	$titleStr = "Reviewers for Submission $k:\n\n";
	foreach ($v as $vv) { $titleStr .= "$vv - " . $rNameAR[$vv] . "\n"; }
	$tmpStr = '<span class="popup"><a href="javascript:popup(\'p' . $k . 'Popup\')">' . fmtNumSpacing(count($v)) . " - " . fmtNumSpacing($k) . '<span id="p' . $k . 'Popup">' . safeHTMLstr($titleStr) . '</span></a></span> (<a href="show_paper.php?pid=' . $k . '" title="information on Submission ID ' . $k . ' (new window)" target="p">' . safeHTMLstr(fmtStrSpacing($pInfoAR[$k]['title'], 28)) . "</a>)";
	if (count($v) < $reviewersPerPaper) { print '<span class="phighlight">' . $tmpStr . " *</span>\n"; }
	else { print $tmpStr."\n"; }
}


print '
</pre></td><td width="50" style="white-space: nowrap;"> &nbsp; &nbsp; &nbsp; &nbsp; </td><td valign="top"><pre>
<span class="rhighlight"> * </span> below threshold

<span class="note">Place cursor on No. Submissions for reviewers</span>
<span class="note">Click name for reviewer info (new window)</span>

<strong>No. Submissions per Reviewer ID (Name)</strong>
';

foreach ($rAR as $k => $v) {
	$titleStr = "Submissions for Reviewer $k:\n\n";
	foreach ($v as $vv) { $titleStr .= "$vv - " . substr($pInfoAR[$vv]['title'],0,40) . "\n"; }
	$tmpStr = '<span class="popup"><a href="javascript:popup(\'r' . $k . 'Popup\')">' . fmtNumSpacing(count($v)) . " - " . fmtNumSpacing($k) . '<span id="r' . $k. 'Popup">' . safeHTMLstr($titleStr) . '</span></a></span> (';
	if (in_array($k, $pcAR)) {
		$tmpStr .= 'PC-';
		$len = 21;
	} else {
		$len = 24;
	}
	$tmpStr .= '<a href="show_reviewer.php?rid='.$k.'" title="information for reviewer ID ' . $k . ' (new window)" target="r">' . safeHTMLstr(fmtStrSpacing($rNameAR[$k],$len))."</a>)";
	if (count($v) <= $pprThreshold) { print '<span class="rhighlight">' . $tmpStr . " *</span>\n"; }
	else { print $tmpStr . "\n"; }
}

print '</pre></td></tr>
</table>

<script>
updatePPA("' . safeHTMLstr(varValue('pcrev', $_POST, 'No')) . '");

var ocaaakeepno = document.getElementById("keep2");
if (ocaaakeepno.addEventListener) {
	ocaaakeepno.addEventListener("click", function(){alert("Selecting No will delete all current review assignments and data regardless of other options selected")}, false);
} else if (ocaaakeepno.attachEvent) {
	ocaaakeepno.attachEvent("onclick", function(){alert("Selecting No will delete all current review assignments and data regardless of other options selected")});
}
var ocaaaskipno = document.getElementById("skipaccepted2");
if (ocaaaskipno.addEventListener) {
	ocaaaskipno.addEventListener("click", function(){alert("Selecting No will result in submissions already accepted or rejected by the Chair also being assigned")}, false);
} else if (ocaaaskipno.attachEvent) {
	ocaaaskipno.attachEvent("onclick", function(){alert("Selecting No will result in submissions already accepted or rejected by the Chair also being assigned")});
}
</script>
';

printFooter();

ob_end_flush();

?>
