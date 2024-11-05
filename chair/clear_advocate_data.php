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

printHeader("Clear Advocate Data",1);

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


if (isset($_POST['submit']) && ($_POST['submit'] == 'Clear Advocate Recommendations')) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	// Clear out recommendations
	$fields = "`adv_recommendation`=NULL";
	if (!isset($_POST['comments']) || ($_POST['comments'] != 1)) {
		$fields .= ", `adv_comments`=NULL";
	}
	if ($_POST['type'] == 'All Submissions') {
		issueSQL("UPDATE `" . OCC_TABLE_PAPERADVOCATE . "` SET " . $fields);
	} else {
		if ($_POST['type'] == 'Pending') {
			$acceptedValue = ' IS NULL';
		} elseif (in_array($_POST['type'], $accTypesAR)) {
			$acceptedValue = "='" . safeSQLstr($_POST['type']) . "'";
		} else {
			warn('Invalid acceptance type');
		}
		issueSQL("UPDATE `" . OCC_TABLE_PAPERADVOCATE . "` `pa` INNER JOIN `" . OCC_TABLE_PAPER . "` `p` ON  `pa`.`paperid`=`p`.`paperid` SET `pa`." . preg_replace("/, `/", ", `pa`.`", $fields) . " WHERE `p`.`accepted`" . $acceptedValue);
	}
	$count = ocsql_affected_rows();

	// Hook
	if (oc_hookSet('chair-clear-advocate')) {
		foreach ($OC_hooksAR['chair-clear-advocate'] as $f) {
			require_once $f;
		}
	}
	
	print '<p style="text-align: center;" class="note">';
	if ($count > 0) {
		print safeHTMLstr($count) . ' advocate recommendation records have been cleared';
	} else {
		print 'No advocate recommendations found to be cleared';
	}
	print '</p>';

} 

print '
<form method="post" action="clear_advocate_data.php">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<p>Clicking the button below will clear out advocate recommendation data while maintaining advocate assignments.  Only click the button if you intend on having advocates start the recommendation process anew.</p>
<p style="text-align: center"><select name="type">' . generateSelectOptions($accTypesAR, '', false) . '</select></p>
<p style="text-align: Center"><input type="submit" name="submit" class="submit" value="Clear Advocate Recommendations" /></p>
<p style="text-align: Center"><label title="check box to keep advocate comments"><input type="checkbox" name="comments" value="1" /> keep comments</label></p>
</form>
';

printFooter();

?>
