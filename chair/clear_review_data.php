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

printHeader("Clear Review Data",1);

// get acceptance types
$accTypesAR = array('All Submissions');
$r = ocsql_query("SELECT `accepted`, COUNT(`accepted`) AS `count`, SUM(ISNULL(`accepted`)) AS `pending` FROM `" . OCC_TABLE_PAPER . "` GROUP BY `accepted` ORDER BY `accepted`") or err('Unable to query acceptance types');
while ($l = ocsql_fetch_assoc($r)) {
	if ($l['accepted'] == '') {
		$accTypesAR[] = 'Pending';
	} else {
		$accTypesAR[] = $l['accepted'];
	}
}


// submit
if (isset($_POST['submit']) && ($_POST['submit'] == 'Clear Review Data')) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	//// Clear out reviews

	// field list to be cleared
	$fields = "`completed`='F', `updated`=NULL, `score`=NULL, `recommendation`=NULL, `category`=NULL, `value`=NULL, `familiar`=NULL, `bpcandidate`=NULL, `length`=NULL, `difference`=NULL";
	if (!isset($_POST['authorcomments']) || ($_POST['authorcomments'] != 1)) {
		$fields .= ", `authorcomments`=NULL";
	}
	if (!isset($_POST['committeecomments']) || ($_POST['committeecomments'] != 1)) {
		$fields .= ", `pccomments`=NULL";
	}
	// include custom fields
	$r = ocsql_query("SHOW COLUMNS FROM `" . OCC_TABLE_PAPERREVIEWER . "` WHERE LEFT(`field`, 3) = 'cf_'") or err('Unable to delete custom fields (1)');
	if (ocsql_num_rows($r) >= 1) {
		while ($l = ocsql_fetch_assoc($r)) {
			$fields .= ", `" . $l['Field'] . "`=NULL";
		}
	}

	// clear out
	if ($_POST['type'] == 'All Submissions') {
		issueSQL("TRUNCATE `" . OCC_TABLE_PAPERSESSION . "`");
		issueSQL("UPDATE `" . OCC_TABLE_PAPERREVIEWER . "` SET " . $fields);
	} else { 
		if ($_POST['type'] == 'Pending') {
			$acceptedValue = ' IS NULL';
		} elseif (in_array($_POST['type'], $accTypesAR)) {
			$acceptedValue = "='" . safeSQLstr($_POST['type']) . "'";
		} else {
			warn('Invalid acceptance type');
		}
		issueSQL("DELETE FROM `" . OCC_TABLE_PAPERSESSION . "` WHERE `" . OCC_TABLE_PAPERSESSION . "`.`paperid` IN (SELECT `" . OCC_TABLE_PAPER . "`.`paperid` FROM `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_PAPER . "`.`accepted`" . $acceptedValue . ")");
		issueSQL("UPDATE `" . OCC_TABLE_PAPERREVIEWER . "` `pr` INNER JOIN `" . OCC_TABLE_PAPER . "` `p` ON  `pr`.`paperid`=`p`.`paperid` SET `pr`." . preg_replace("/, `/", ", `pr`.`", $fields) . " WHERE `p`.`accepted`" . $acceptedValue);
	}
	$count = ocsql_affected_rows();

	// Hook
	if (oc_hookSet('chair-clear-review')) {
		foreach ($OC_hooksAR['chair-clear-review'] as $f) {
			require_once $f;
		}
	}
	
	// confirm
	print '<p style="text-align: center;" class="note">';
	if ($count > 0) {
		print safeHTMLstr($count) . ' review records have been cleared';
	} else {
		print 'No reviews found to be cleared';
	}
	print '</p>';

}

print '
<form method="post" action="clear_review_data.php">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<p>Clicking the button below will clear out data for all built-in and custom review form fields while maintaining review assignments.  Only click the button if you intend on having reviewers start the review process anew. Backing up the database and exporting reviews is recommended prior to clearing review data.</p>
<p style="text-align: center"><select name="type">' . generateSelectOptions($accTypesAR, '', false) . '</select></p>
<p style="text-align: center"><input type="submit" name="submit" class="submit" value="Clear Review Data" onclick="return confirm(\'Confirm deletion of review data for submissions with selected acceptance type. Once cleared, data cannot be recovered.\')" /></p>
<p style="text-align: Center"><i>keep comments to</i> <label title="check box to keep reviewer comments to author"><input type="checkbox" name="authorcomments" value="1" /> author</label> <label title="check box to keep reviewer comments to committee"><input type="checkbox" name="committeecomments" value="1" /> committee</label></p>
</form>
';


printFooter();

?>
