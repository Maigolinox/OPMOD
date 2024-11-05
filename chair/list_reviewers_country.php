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

if (isset($_REQUEST['cmt']) && ($_REQUEST['cmt'] == "rev")) {
	$cmtSQL = " WHERE `onprogramcommittee`='F'";
	$cmt = 'rev';
} elseif (isset($_REQUEST['cmt']) && ($_REQUEST['cmt'] == "pc")) {
	$cmtSQL = " WHERE `onprogramcommittee`='T'";
	$cmt = 'pc';
} else {
	$cmtSQL = '';
	$cmt = '';
}

$cmtURL = (isset($_REQUEST['cmt']) ? ('&cmt=' . urlencode($_REQUEST['cmt'])) : '');


printHeader('Committee Member Countries', 1);

if ($OC_configAR['OC_paperAdvocates']) {
	$options = '<option value="">All Committee Members</option><option value="rev">Review Committee</option><option value="pc">Program Committee (Advocates)</option>';
	print '
<form method="post" action="list_reviewers_country.php">
<input type="hidden" name="s" value="' . (isset($_REQUEST['s']) ? safeHTMLstr($_REQUEST['s']) : '') . '" />
<p style="text-align: center;">
<select name="cmt">' . preg_replace('/(value="' . $cmt . '")/', "$1 selected", $options) . '</select>
<input type="submit" value="Filter" />
</p>
</form>
';
}

$q = "SELECT `country`, COUNT(`reviewerid`) AS `num` FROM `" . OCC_TABLE_REVIEWER . "` $cmtSQL GROUP BY `country` ORDER BY `num` DESC";
$r = ocsql_query($q) or err("Unable to get information");
if (ocsql_num_rows($r) == 0) {
        print '<span class="warn">No committee members have signed up yet</span><p>';
} else {
	print '
<table border="0" cellpadding="5" cellspacing="1" style="margin: 0 auto">
';
	if (!isset($_REQUEST['s']) || ($_REQUEST['s'] == "country")) {
		print '<tr class="rowheader"><th valign="top">Country<br />' . $OC_sortImg . '</th><th valign="top"><a href="' . $_SERVER['PHP_SELF'] . '?s=num&' . $cmtURL . '">Count</a></th></tr>';
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
	} else {
		print '<tr class="rowheader"><th valign="top"><a href="' . $_SERVER['PHP_SELF'] . '?s=country&' . $cmtURL . '">Country</a></th><th valign="top">Count<br />' . $OC_sortImg . '</th></tr>';
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
	}
	
	print "</table>\n";
}

printFooter();

?>
