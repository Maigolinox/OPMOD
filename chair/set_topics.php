<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$hdr = '';
$hdrfn = 1;

$minTopics = 10; // min number of fields to display

$topicColAR = array(1, 2);

require_once "../include.php";

if (!OCC_INSTALL_COMPLETE && isset($_REQUEST['install']) && ($_REQUEST['install'] == 1)) {
	require_once "install-include.php";
	$token = '';
} else {
	beginChairSession();
	printHeader("Set Topics",1);
	$token = $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'];
}

if (isset($_POST['submit']) && ($_POST['submit'] == "Set Topics")) {
	// Check for valid submission
	if (OCC_INSTALL_COMPLETE && !validToken('chair')) {
		warn('Invalid submission');
	}
	
	// Check & update topic options
	if (isset($_POST['OC_multipleSubmissionTopics']) && preg_match("/^[01]$/", $_POST['OC_multipleSubmissionTopics']) && updateConfigSetting('OC_multipleSubmissionTopics', $_POST['OC_multipleSubmissionTopics'], 'OC')) {
		$OC_configAR['OC_multipleSubmissionTopics'] = $_POST['OC_multipleSubmissionTopics'];
	}
	if (isset($_POST['OC_multipleCommitteeTopics']) && preg_match("/^[01]$/", $_POST['OC_multipleCommitteeTopics']) && updateConfigSetting('OC_multipleCommitteeTopics', $_POST['OC_multipleCommitteeTopics'], 'OC')) {
		$OC_configAR['OC_multipleCommitteeTopics'] = $_POST['OC_multipleCommitteeTopics'];
	}
	if (isset($_POST['OC_topicDisplayAlpha']) && preg_match("/^[01]$/", $_POST['OC_topicDisplayAlpha']) && updateConfigSetting('OC_topicDisplayAlpha', $_POST['OC_topicDisplayAlpha'], 'OC')) {
		$OC_configAR['OC_topicDisplayAlpha'] = $_POST['OC_topicDisplayAlpha'];
	}

	// Delete current list of topics
	issueSQL("DELETE FROM `" . OCC_TABLE_TOPIC . "`");

	// Parse through submitted topics
	$j = 1;
	foreach ($_POST as $tid => $tval) {
		if (preg_match("/^name-(\d+)$/", $tid, $tmatch) && !empty($tval)) {
			$q2 = "INSERT INTO `" . OCC_TABLE_TOPIC . "` (`topicid`, `topicname`, `short`) VALUES ('" . safeSQLstr($j) . "','" . safeSQLstr(substr($tval,0,250)) . "','" . safeSQLstr(substr($_POST["short-".$tmatch[1]],0,20)) . "')";
			issueSQL($q2);
			$j++;
		}
	}
	
	// Success - if install, redirect, else let user know
	if (isset($_REQUEST['install']) && ($_REQUEST['install'] == 1)) {
		header("Location: set_status.php?install=1");
		exit;
	} else {
		print '<p style="text-align: center; font-weight: bold;" class="note">Options successfully updated</p>';
	}
}

$displayWarning = false;
if (isset($_REQUEST['install']) && ($_REQUEST['install'] == 1)) {
	printHeader($hdr,$hdrfn);
	print '<p style="text-align: center; font-weight: bold;">Step 4 of 5: Set Topics</p>';
} else {
	$countsr = ocsql_query("SELECT COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPER . "`") or err('Unable to check for existing submissions');
	$countsl = ocsql_fetch_assoc($countsr);
	$countcr = ocsql_query("SELECT COUNT(*) AS `count` FROM `" . OCC_TABLE_REVIEWER . "`") or err('Unable to check for existing committee member');
	$countcl = ocsql_fetch_assoc($countcr);

	if (($countsl['count'] > 0) || ($countcl['count'] > 0)) {
		$displayWarning = true;
	}
}

print '
<form method="post" action="'.$_SERVER['PHP_SELF'].'">
<input type="hidden" name="token" value="' . $token . '" />

<p>Topics are used when making automated assignments.  By default, both ' . oc_strtolower(OCC_WORD_AUTHOR) . 's and committee members are asked to select topics.  Enter a sequential list of topics below.  When you click on <em>Set Topics</em>, topics will be added sequentially regardless of the Topic ID listed, with blank topics skipped; thus topics should only be deleted until a submission has been made or committee member signed up.  The <em>Short Name</em> field is optional; if present, it is used where a long topic name may be cumbersome.</p>
';

if ($displayWarning) {
	print '<p class="warn">NOTE: As submissions have been made or committee members signed up already, deleting, changing, or re-ordering topics may result in data corruption. Instead, add new topics at the end, and rename topics no longer in use to "N/A" (without quotes) to have it skipped on applicable forms.</p>';
}

if (isset($_REQUEST['install']) && ($_REQUEST['install'] == 1)) {
    print '<input type="hidden" name="install" value="1" />';
}

print '
<table border="0" cellspacing="1" cellpadding="8" id="topicTable">
<tr class="rowheader"><th>Topic ID</th><th>Topic Name</th><th title="20 character limit">Short Name*</th></tr>
';

// Display existing topics
$q = "SELECT * FROM `" . OCC_TABLE_TOPIC . "`";
$r = ocsql_query($q) or err("Unable to query topics");
$topicNum = ocsql_num_rows($r);
$tAR = array();
$row=1;
while ($l=ocsql_fetch_array($r)) {
	print '<tr class="row' . $row . '"><td style="text-align: center">' . $l['topicid'] . '</td><td><input name="name-' . $l['topicid'] . '" value="' . (isset($l['topicname']) ? safeHTMLstr($l['topicname']) : '') . '" size="100" maxlength="250" /></td><td><input name="short-' . $l['topicid'] . '" value="' . (isset($l['short']) ? safeHTMLstr($l['short']) : '') . '" size="20" maxlength="20" /></td></tr>
';
	if ($row==1) { $row=2; } else { $row=1; }
}

// Display additional rows
$addRows = ((($topicNum + 4) < $minTopics) ? ($minTopics - $topicNum) : 4);

for ($i=1; $i <= $addRows; $i++) {
	$topicNum++;
	print '<tr class="row' . $row . '"><td style="text-align: center">' . $topicNum . '</td><td><input name="name-' . $topicNum . '" value="' . (isset($tAR[$i]['name']) ? safeHTMLstr($tAR[$i]['name']) : '') . '" size="100" maxlength="250" /></td><td><input name="short-' . $topicNum . '" value="' . (isset($tAR[$i]['short']) ? safeHTMLstr($tAR[$i]['short']) : '') . '" size="20" maxlength="20" /></td></tr>
';
	if ($row==1) { $row=2; } else { $row=1; }
}

print '
</table>

<style type="text/css">
<!--
.topic_link {
	color: #00f; cursor: pointer;
}
-->
</style><script language="javascript">
<!--
var topicNum = ' . ($topicNum+1) . ';
var row = ' . $row . ';
var j;
function addTopicRow() {
	if (document.getElementById) {
		var topicTable = document.getElementById("topicTable");
		if (topicTable) {
			for (j=1; j<=5; j++) {
				var topicRow = topicTable.insertRow(-1);
				topicRow.className = "row" + row;
				var idCell = topicRow.insertCell(-1);
				idCell.align = "center";
				idCell.innerHTML = topicNum;
				var nameCell = topicRow.insertCell(-1);
				nameCell.innerHTML = "<input name=\"name-" + topicNum + "\" value=\"\" size=\"100\" maxlength=\"250\" />";
				var shortCell = topicRow.insertCell(-1);
				shortCell.innerHTML = "<input name=\"short-" + topicNum + "\" value=\"\" size=\"20\" maxlength=\"20\" />";
				topicNum += 1;
				if (row == 1) { row = 2; } else { row = 1; }
			}
		}
	}
}
document.write(\'<span onclick="addTopicRow()" class="topic_link" style="text-decoration: underline">Add More Rows</span>\');
// -->
</script>
<noscript><span class="note">All topics filled in?  Click <em>Set Topics</em> to save topics and add more rows</span></noscript>
<br />
';

if (!oc_moduleActive('oc_customforms')) {
	print '
<p><strong>Allow ' . oc_strtolower(OCC_WORD_AUTHOR) . 's to select multiple submission topics?</strong> ' . generateRadioOptions('OC_multipleSubmissionTopics', $yesNoAR, $OC_configAR['OC_multipleSubmissionTopics']) . '<br />
<span class="note">Select Yes to allow multiple topics, or No to limit ' . oc_strtolower(OCC_WORD_AUTHOR) . ' to one topic.</span></p>

<p><strong>Allow committee members to select multiple submission topics?</strong> ' . generateRadioOptions('OC_multipleCommitteeTopics', $yesNoAR, $OC_configAR['OC_multipleCommitteeTopics']) . '<br />
<span class="note">Select Yes to allow multiple topics, or No to limit committee member to one topic.</span></p>
';
}

print '
<p><strong>Display topics alphabetically?</strong> ' . generateRadioOptions('OC_topicDisplayAlpha', $yesNoAR, $OC_configAR['OC_topicDisplayAlpha']) . '<br />
<span class="note">Select Yes to display topics alphabetically on submission and committee sign up forms, or No to use order above.</span></p>

<input type="submit" name="submit" class="submit" value="Set Topics" />

</form>
';

printFooter();

?>
