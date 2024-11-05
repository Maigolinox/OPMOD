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
require_once OCC_COUNTRY_FILE;

beginChairSession();

// accepted or all submissions?
if (isset($_REQUEST['acc']) && preg_match("/\d+/", $_REQUEST['acc']) && isset($OC_acceptanceValuesAR[$_REQUEST['acc']])) {
	$accSQL = "AND `" . OCC_TABLE_PAPER . "`.`accepted`='" . safeSQLstr($OC_acceptanceValuesAR[$_REQUEST['acc']]['value']) . "'";
	$accURL = 'acc=' . urlencode($_REQUEST['acc']);
	$accReq = $_REQUEST['acc'];
}
else {
	$accSQL = '';
	$accURL = '';
	$accReq = '';
}

printHeader('Submission Countries', 1);

$accOptions = '';
foreach ($OC_acceptanceValuesAR as $idx => $acc) {
	$accOptions .= '<option value="' . safeHTMLstr($idx) . '">' . safeHTMLstr($acc['value']) . '</option>';
}

print '
<form method="post" action="list_authors_country.php">
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

$q = "SELECT `country`, COUNT(*) AS `num` FROM `" . OCC_TABLE_AUTHOR . "`, `" . OCC_TABLE_PAPER . "` WHERE `" . OCC_TABLE_AUTHOR . "`.`paperid`=`" . OCC_TABLE_PAPER . "`.`paperid` AND `" . OCC_TABLE_AUTHOR . "`.`position`=`" . OCC_TABLE_PAPER . "`.`contactid` $accSQL GROUP BY `country` ORDER BY `num` DESC";
$r = ocsql_query($q) or err("Unable to get information");
if (ocsql_num_rows($r) == 0) {
        print '<span class="warn">No submissions available</span><p>';
} else {
	print '
<p style="text-align: center" class="note">Note: Only the country of the contact ' . oc_strtolower(OCC_WORD_AUTHOR) . ' is used for reporting</p>
<table border="0" cellpadding="5" cellspacing="1" style="margin: 0 auto">
';
	if (isset($_REQUEST['s']) && ($_REQUEST['s'] == "num")) {
		print '<tr class="rowheader"><th valign="top"><a href="' . $_SERVER['PHP_SELF'] . '?s=country&' . $accURL . '">Country</a></th><th valign="top">Count<br />' . $OC_sortImg . '</th></tr>';
		$resAR = array();
		$nocountry = 0;
		while ($l = ocsql_fetch_array($r)) {
			if (empty($l['country']) || !isset($OC_countryAR[$l['country']])) {
				$nocountry += $l['num'];
			} else {
				if (!isset($resAR[$l['num']])) {
					$resAR[$l['num']] = array();
				}
				$resAR[$l['num']][] = $OC_countryAR[$l['country']];
			}
		}
		$row = 1;
		foreach ($resAR as $num => $countries) {
			sort($countries, SORT_LOCALE_STRING);
			print '<tr class="row'  .$row . '"><td>' . implode('<br />', $countries) . '</td><td align="right">' . $num . "</td></tr>\n";
			if ($row==1) { $row=2; } else { $row=1; }
		}
		if ($nocountry > 0) {
			print '<tr class="row' . $row . '"><td style="font-style: italic;">unknown</td><td align="right">' . $nocountry . "</td></tr>\n";
		}
	} else {
		print '<tr class="rowheader"><th valign="top">Country<br />' . $OC_sortImg . '</th><th valign="top"><a href="' . $_SERVER['PHP_SELF'] . '?s=num&' . $accURL . '">Count</a></th></tr>';
		$resAR = array();
		$nocountry = 0;
		while ($l = ocsql_fetch_array($r)) {
			if (!empty($l['country']) && isset($OC_countryAR[$l['country']])) {
				$resAR[$OC_countryAR[$l['country']]] = $l['num'];
			} else {
				$nocountry = $l['num'];
			}
		}
		ksort($resAR, SORT_LOCALE_STRING);
		$row = 1;
		foreach ($resAR as $country => $num) {
			print '<tr class="row' . $row . '"><td>' . $country . '</td><td align="right">' . $num . "</td></tr>\n";
			if ($row==1) { $row=2; } else { $row=1; }
		}
		if ($nocountry > 0) {
			print '<tr class="row' . $row . '"><td style="font-style: italic;">unknown</td><td align="right">' . $nocountry . "</td></tr>\n";
		}
	}
	
	print "</table>\n";
}

printFooter();

?>
