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

printHeader("Auto Assign Advocates",1);

$advocatePerPaper = 1;

// Init algorithm array
$OC_algorithmAR = array(
	array(
		'algorithm'		=> 'Weighted Topic Match',
		'description'	=> 'Assigns based on number of topic matches (high to low) between submissions and reviewers, giving assignment precedence to submissions with the least number of overall matching reviewer/topic pairings',
		'include'		=> 'assign_auto_advocates_weighted_topic_match.inc'
	),
	array(
		'algorithm'		=> 'Topic Match',
		'description'	=> 'Assigns based on number of topic matches (high to low) between submissions and reviewers',
		'include'		=> 'assign_auto_advocates_topic_match.inc'
	),
);

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

// Check for addt'l (hook) algorithms
if (oc_hookSet('assign_auto_advocates-algorithm')) {
	foreach ($OC_hooksAR['assign_auto_advocates-algorithm'] as $k => $v) {
		$OC_algorithmAR[] = $v;
	}
}

// Submit - Commit assignments to database?
if (isset($_POST['submit']) && ($_POST['submit'] == "Make Assignments")) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	if (!isset($_SESSION['OPENCONFCHAIRVARS']['aAssignments'])) {
		err("No advocates set");
	}

	// Keep or delete current assignments
	$currAR = array();
	if ($_POST['keep'] == 'Yes') {
        $aq = "SELECT `paperid`, `advocateid` FROM `" . OCC_TABLE_PAPERADVOCATE . "`";
		$ar = ocsql_query($aq) or err("Unable to access database");
		while ($al = ocsql_fetch_array($ar)) {
			$currAR[] = $al['paperid'] . '-' . $al['advocateid'];
		}
	} else {
		oc_deleteAssignments(null, null, 'advocate');
	}
	
	// Add advocates
	foreach ($_SESSION['OPENCONFCHAIRVARS']['aAssignments'] as $pid => $aid) {
		if (!$aid || in_array($pid . '-' . $aid, $currAR)) { continue; }
		$q = "INSERT INTO `" . OCC_TABLE_PAPERADVOCATE . "` (`paperid`,`advocateid`) VALUES ('" . safeSQLstr($pid) . "','" . safeSQLstr($aid) . "')";
		issueSQL($q);
	}
	
	// Okey Dokey
	print '<p><strong>Assignments have been made</strong></p>
<p><a href="list_advocates.php">List Advocates</a></p>
	';
	unset($_SESSION['OPENCONFCHAIRVARS']['aAssignments']);

	printFooter();
	exit;
}

// Check whether any reviewers or advocates assigned yet
$aq = "SELECT `paperid`, `advocateid` FROM `" . OCC_TABLE_PAPERADVOCATE . "`";
$ar = ocsql_query($aq) or err("Unable to access database");
$patot = ocsql_num_rows($ar);
$q = "SELECT count(*) as `rtot` FROM `" . OCC_TABLE_PAPERREVIEWER . "`";
$r = ocsql_query($q) or err("Unable to access database");
$rl = ocsql_fetch_array($r);
if (($patot > 0) || ($rl['rtot'] > 0)) {
	print '<p class="err">Advocates or Reviewers appear to have already been assigned.  Existing advocate assignments will be deleted unless you choose <em>Yes</em> to <em>Keep Existing Assignments</em> below.</p>';
	$confirmOverride = true;
} else {
	$confirmOverride = false;
}

// Get number of papers and initialize paper count array
$pAR = array();	 		// paper advocate array
$pInfoAR = array();	// paper info array
$q = "SELECT `paperid`, `title`, `accepted`, `type` FROM `" . OCC_TABLE_PAPER . "` ORDER BY `paperid`";
$r = ocsql_query($q) or err("Unable to get papers");
if (($ptot = ocsql_num_rows($r)) == 0) {
	warn('No submissions have been made yet.');
}
while ($l=ocsql_fetch_array($r)) {
	$pAR[$l['paperid']] = "";
	$pInfoAR[$l['paperid']] = array(
		'title' => $l['title'],
		'accepted' => $l['accepted'],
		'type' => $l['type']
	);
}

// Get number of advocates and initialize advocate count array
$aNameAR = array();		// advocate name array
$aAR = array();   		// advocate papers array
$q = "SELECT `reviewerid`, CONCAT_WS(' ',`" . OCC_TABLE_REVIEWER . "`.`name_first`,`" . OCC_TABLE_REVIEWER . "`.`name_last`) AS `name` FROM `" . OCC_TABLE_REVIEWER . "` WHERE `onprogramcommittee`='T' ORDER BY `reviewerid`";
$r = ocsql_query($q) or err("Unable to get advocates");
if (($atot = ocsql_num_rows($r)) == 0) {
	warn('No program committee members have signed up yet.');
}
while ($l=ocsql_fetch_array($r)) {
	$aAR[$l['reviewerid']] = array();
	$aNameAR[$l['reviewerid']] = $l['name'];
}

// Get conflicts
$nAR = getConflicts();

// Calculate max # of papers each advocate should advocate (pronunciation test)
if (isset($_POST['ppa']) && preg_match("/^\d+$/",$_POST['ppa'])) {
	$papersPerAdvocate = $_POST['ppa'];
} else {
	$papersPerAdvocate = ceil($ptot/$atot);  # +count($nAR) to $ptot ?
}

// Set min # of papers each advocate should be assigned
if (isset($_POST['ppat']) && preg_match("/^\d+$/",$_POST['ppat'])) {
	$ppaThreshold = $_POST['ppat'];
} else {
	$ppaThreshold = floor($papersPerAdvocate/2);
}

// Keep assignments already made?
if (!isset($_POST['keep']) || ($_POST['keep'] == 'Yes')) {
	$confirmOverride = false;
	if ($patot > 0) {
		while ($al = ocsql_fetch_array($ar)) {
			$pAR[$al['paperid']] = $al['advocateid'];
			array_push($aAR[$al['advocateid']],$al['paperid']);
		}
	}
}

// Get list of sub. types
$OC_activeSubTypeAR = array();
$typer = ocsql_query("SELECT DISTINCT `type` FROM `" . OCC_TABLE_PAPER . "` WHERE `type` IS NOT NULL AND `type`!='' ORDER BY `type`") or err('Unable to retrieve submission types');
while ($typel = ocsql_fetch_assoc($typer)) {
	$OC_activeSubTypeAR[$typel['type']] = substr($typel['type'], 0, 50);
}

// Algorithm to use
if (isset($_POST['algo']) && ctype_digit($_POST['algo'])) {
	$algo = $_POST['algo'];
} else {
	$algo = 0;  // default to first defined algorithm above
}
// Run algo
if (isset($OC_algorithmAR[$algo]['include']) && is_file($OC_algorithmAR[$algo]['include'])) {
	require_once $OC_algorithmAR[$algo]['include'];
} else {
	err("Algorithm choice unknown");
	exit;
}

// Remedy missing advocates by assigning advocates w/lowest #s (if set)
if (isset($_POST['remedy']) && in_array("random",$_POST['remedy'])) {
	foreach (array_keys($pAR) as $k) {
		if (oc_skipSubAssignment($k)) {
			continue;
		}
		if (empty($pAR[$k])) {
			// Create an ordered array of advocates w/least # of reviews
			$acountAR = array();
			foreach (array_keys($aAR) as $k2) {
				$acountAR[$k2] = count($aAR[$k2]);
			}
			asort($acountAR);
			reset($acountAR);
			// Assign advocate
			while (key($acountAR) && empty($pAR[$k])) {
				// check for no conflict
				if (!in_array($k.'-'.key($acountAR),$nAR)) {
					$pAR[$k] = key($acountAR);
					array_push($aAR[key($acountAR)],$k);
				}
				next($acountAR);
			}
		}
	}
}



// Remember assignments
$_SESSION['OPENCONFCHAIRVARS']['aAssignments'] = $pAR;

// Display form
print '
<p>Below you will find OpenConf\'s suggested advocate assignments. You may fine tune these automated assignments by changing the following options and clicking <em>Re-Evaluate Assignments</em>. Once you are satisfied, click the <em>Make Assignments</em> button to commit them to the database. You may manually add/delete advocates afterwards through the <em>Assign Advocates Manually</em> and <em>List/Unassign Advocates</em> menus.';

if (oc_moduleValid('oc_auto_assign')) {
	print ' If instead of using this feature you would like assignments to be made automatically when a new submission is made, use the <a href="../modules/modules.php">Auto Assign</a> module.';
}

print ' Note that changes to the options below are not kept upon leaving this page.</p>

<form method="post" action="'.$_SERVER['PHP_SELF'].'">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<table border=0 cellspacing=5 cellpadding=0 bgcolor="#eeeeee">
<tr><td>Total Submissions:</td><td>' . $ptot . '</td></tr>
<tr><td>Total Advocates:</td><td>' . $atot . '</td></tr>
<tr><td>Advocate/Submission Pairings in Conflict:</td><td>' . count($nAR) . ' &nbsp;(<a href="list_conflicts.php" target="_blank" style="font-style: italic;" title="opens in a new window/tab">manage</a>)</td></tr>
<tr><td>Maximum Submissions per Advocate:</td><td><input name="ppa" size="4" maxlength="4" value="' . $papersPerAdvocate . '"></td></tr>
<tr><td>Highlight if Submissions per Advocate &lt; =</td><td><input name="ppat" size="4" maxlength="4" value="' . $ppaThreshold . '" style="background-color: #ffc;"></td></tr>

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

$remStr = '<tr><td valign="top">Remedy Missing Assignments by:</td><td><!--<input type="checkbox" name="remedy[]" value="bump">Bumping # Submissions / Advocate<br />--><label><input type="checkbox" name="remedy[]" value="random"> Randomly Assigning Advocate</label></td></tr>';
if (isset($_POST['remedy'])) { 
	foreach ($_POST['remedy'] as $rs) {
		if (preg_match("/^\w+$/", $rs)) {
			$remStr = preg_replace("/(value=\"" . preg_quote($rs, '/') . "\")/", "$1 checked", $remStr);
		}
	}
}

print $remStr . '
<tr><td colspan=2><br /><input type="submit" name="submit" class="submit" value="Re-Evaluate Assignments"><p><input type="submit" name="submit" class="submit" value="Make Assignments"' . ($confirmOverride ? ' onclick="return confirm(\'Confirm overwrite of existing assignments\')"' : '')  . '> <span class="note">(commits assingments to database)</span></td></tr>
</table>
</form>
<p><hr><p>
<style type="text/css">
.phighlight { background: #ffcccc; font-weight: bold;}
.rhighlight { background: #ffffcc; font-weight: bold;}
</style>
';

function fmtNumSpacing ($n,$dir="l") {
	$sp = "";
	if ($n < 10) { $sp = "  "; }
	elseif ($n < 100) { $sp = " "; }
	if ($dir=="l") { return($sp.$n); }
	else { return($n.$sp); }
}

function fmtStrSpacing ($str,$len) {
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
<span class="phighlight"> * </span> Unable to assign advocate

<span class="note">Click title for submission info (new window)</span>


<strong>Submission ID (Title) (Advocate)</strong>
';
foreach ($pAR as $k => $v) {
	$tmpStr = fmtNumSpacing($k).' (<a href="show_paper.php?pid=' . $k . '" title="information for submission ID ' . $k . ' (new window)" target="p">' . safeHTMLstr(fmtStrSpacing($pInfoAR[$k]['title'], 20)) . "</a>) (";
	if (!empty($v)) { 
		$tmpStr .= safeHTMLstr(fmtStrSpacing($pAR[$k] . "-" . $aNameAR[$pAR[$k]],15)); 
		print $tmpStr.")\n";
	}
	else { 
		$tmpStr .= fmtStrSpacing("",15); 
		print '<span class="phighlight">' . $tmpStr . ") *</span>\n";
	}
}


print '
</pre></td><td width="50" style="white-space: nowrap;"> &nbsp; &nbsp; &nbsp; &nbsp; </td><td valign="top"><pre>
<span class="rhighlight"> * </span> below threshold

<span class="note">Click name for reviewer info (new window)</span>
<span class="note">Place cursor on No. Submissions for assignments</span>

<strong>No. Submissions per Advocate ID (Name)</strong>
';

foreach ($aAR as $k => $v) {
	$titleStr = "Submissions for Advocate $k - " . $aNameAR[$k] . ":\n\n";
	foreach ($v as $vv) { $titleStr .= "$vv - ". safeHTMLstr(substr($pInfoAR[$vv]['title'],0,40))."\n"; }
	$tmpStr = '<span class="popup"><a href="javascript:popup(\'p' . $k . 'Popup\')">' . fmtNumSpacing(count($v)) . " - " . fmtNumSpacing($k) . '<span id="p' . $k. 'Popup">' . safeHTMLstr($titleStr) . '</span></a></span> (<a href="show_reviewer.php?rid='.$k.'" title="information for advocate ID ' . $k . ' (new window)" target="a">' . safeHTMLstr(fmtStrSpacing($aNameAR[$k],25))."</a>)";
	if (count($v) <= $ppaThreshold) { print '<span class="rhighlight">'.$tmpStr." *</span>\n"; }
	else { print $tmpStr . "\n"; }
}

print '
</pre></td></tr>
</table>

<script>
var ocaaakeepno = document.getElementById("keep2");
if (ocaaakeepno.addEventListener) {
	ocaaakeepno.addEventListener("click", function(){alert("Selecting No will delete all current advocate assignments and data regardless of other options selected")}, false);
} else if (ocaaakeepno.attachEvent) {
	ocaaakeepno.attachEvent("onclick", function(){alert("Selecting No will delete all current advocate assignments and data regardless of other options selected")});
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

?>
