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

// accepted or all papers?
if (isset($_REQUEST['acc']) && preg_match("/\d+/", $_REQUEST['acc']) && isset($OC_acceptanceValuesAR[$_REQUEST['acc']])) {
	$accSQL = " AND `" . OCC_TABLE_PAPER . "`.`accepted`='" . safeSQLstr($OC_acceptanceValuesAR[$_REQUEST['acc']]['value']) . "'";
	$accURL = 'acc=' . $_REQUEST['acc'];
	$accReq = $_REQUEST['acc'];
}
else {
	$accSQL = '';
	$accURL = '';
	$accReq = '';
}

// sort order
if (isset($_REQUEST['s']) && ($_REQUEST['s'] == "id")) {
	$sortby = "`paperid`";
	$idsort = 'Submission ID. Title<br />' . $OC_sortImg;
	$nsort = '<a href="' . $_SERVER['PHP_SELF'] . '?s=name&' . $accURL . '">' . OCC_WORD_AUTHOR . '</a>';
} else {
	$sortby = "`name_last`, `name_first`";
	$idsort = '<a href="' . $_SERVER['PHP_SELF'] . '?s=id&' . $accURL . '">Submission ID. Title</a>';
	$nsort = '<span title="Grouped by matching email address">' . OCC_WORD_AUTHOR. '</span><br />' . $OC_sortImg;
}

printHeader('All ' . OCC_WORD_AUTHOR . 's', 1);

$accOptions = '';
foreach ($OC_acceptanceValuesAR as $idx => $acc) {
	$accOptions .= '<option value="' . safeHTMLstr($idx) . '">' . safeHTMLstr($acc['value']) . '</option>';
}

print '
<form method="post" action="list_authors.php">
<input type="hidden" name="s" value="' . (isset($_REQUEST['s']) ? safeHTMLstr($_REQUEST['s']) : '') . '" />
<p style="text-align: center;">
<select name="acc">
<option value="">All Submissions</option>
' . preg_replace('/(value="' . preg_quote($accReq) . '")/', "$1 selected", $accOptions) . '
</select>
<input type="submit" value="Filter" />
</p>
</form>
';

$q = "SELECT `" . OCC_TABLE_AUTHOR . "`.`name_last`, `" . OCC_TABLE_AUTHOR . "`.`name_first`, `" . OCC_TABLE_AUTHOR . "`.`email`, `" . OCC_TABLE_AUTHOR . "`.`paperid`, `" . OCC_TABLE_PAPER . "`.`title` FROM `" . OCC_TABLE_AUTHOR . "`, `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_PAPER . "`.`paperid`=`" . OCC_TABLE_AUTHOR . "`.`paperid` " . $accSQL . " ORDER BY $sortby";
$r = ocsql_query($q) or err("Unable to get " . oc_strtolower(OCC_WORD_AUTHOR) . "s");
if (ocsql_num_rows($r) == 0) { print '<span class="warn">No submissions have been made yet.</span><p>'; }
else {
	$currid = null;
	$row = 2; // Seed at 2 to handle same IDs
	print '<table border=0 cellspacing="1" cellpadding="4" style="margin: 0 auto;"><tr class="rowheader"><th valign="top">' . $nsort . '</th><th valign="top">' . $idsort . '</th></tr>';
	if (isset($_REQUEST['s']) && ($_REQUEST['s'] == "id")) {
		while ($l = ocsql_fetch_array($r)) {
			if ($l['paperid'] == $currid) {
				print '<tr class="row' . $row . '"><td>' . safeHTMLstr($l['name_first']) . ' ' . safeHTMLstr($l['name_last']) . '</td><td>&nbsp;</td></tr>';
			} else {
				$row = $rowAR[$row];
				print '<tr class="row' . $row . '"><td>' . safeHTMLstr($l['name_first']) . ' ' . safeHTMLstr($l['name_last']) . '</td><td><a href="show_paper.php?pid=' . $l['paperid'] . '">' . $l['paperid'] . '. ' . safeHTMLstr($l['title']) . '</a></td></tr>';
				$currid = $l['paperid'];
			}
		}
	} else {
		while ($l = ocsql_fetch_array($r)) {
			if ($l['email'] == $currid) {
				print '<tr class="row' . $row . '"><td>&nbsp;</td><td><a href="show_paper.php?pid=' . $l['paperid'] . '">' . $l['paperid'] . '. ' . safeHTMLstr($l['title']) . '</a></td></tr>';
			} else {
				$row = $rowAR[$row];
				print '<tr class="row' . $row . '"><td>' . safeHTMLstr($l['name_first']) . ' ' . safeHTMLstr($l['name_last']) . '</td><td><a href="show_paper.php?pid=' . $l['paperid'] . '">' . $l['paperid'] . '. ' . safeHTMLstr($l['title']) . '</a></td></tr>';
			}
			$currid = $l['email'];
		}
	}
	print "</table><br />\n";
}

printFooter();

?>
