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

printHeader("Log", 1);

if (isset($_GET['type']) && preg_match("/^[\w-]+$/", $_GET['type'])) {
	$type = $_GET['type'];
} else {
	$type = '';
}

// Single log entry
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
	print '<p style="text-align: center"><a href="' . $_SERVER['PHP_SELF'] . '?' . ((isset($_GET['limit']) && ($_GET['limit'] == 0)) ? 'limit=0&' : '') . (!empty($type) ? ('type=' . safeHTMLstr($type)) : '') . '">show ' . (!empty($type) ? safeHTMLstr($type) : '') . ' entries</a> | <a href="log-search.php?type=' . urlencode($type) . '">search</a></p>';
	$q = "SELECT * FROM `" . OCC_TABLE_LOG . "` WHERE `logid`='" . safeSQLstr($_GET['id']) . "'";
	$r = ocsql_query($q) or err('Unable to retrieve log entry');
	if (ocsql_num_rows($r) == 0) {
		warn('Log entry not found');
	} else {
		$l = ocsql_fetch_assoc($r);
		print '<strong>Date/Time:</strong> ' . safeHTMLstr($l['datetime']);
		if ($l['type'] == 'email') {
			print ' &nbsp; (<a href="email-queue-log.php?lid=' . $l['logid'] . '">view individual messages/status</a>)';
		}
		print '<br />
<strong>Type:</strong> ' . safeHTMLstr($l['type']) . '<br />
<strong>Entry:</strong> ' . safeHTMLstr($l['entry']) . '<br />
<br /><hr /><br />
' . nl2br(safeHTMLstr($l['extra'])) . '<br />
';
	}
} else { // all entries
	if (isset($_GET['limit']) && ($_GET['limit'] == 0)) { // limit entries?
		$limit = '';
	} else {
		$limit = ' LIMIT 30';
		print '<p style="text-align: center"><a href="' . $_SERVER['PHP_SELF'] . '?limit=0' . (!empty($type) ? ('&type=' . safeHTMLstr($type)) : '') . '">show all ' . safeHTMLstr($type) . ' entries</a> | <a href="log-search.php?type=' . urlencode($type) . '">search</a></p>';
	}
	
	// Get email entries with failed messages
	$failedMessageAR = array();
	$q = "SELECT `queued` FROM `" . OCC_TABLE_EMAIL_QUEUE . "` WHERE `sent` IS NULL GROUP BY `queued`";
	$r = ocsql_query($q) or err('Unable to retrieve email log entries');
	while ($l = ocsql_fetch_assoc($r)) {
		$failedMessageAR[] = $l['queued'];
	}
	
	// retrieve entries
	$q = "SELECT * FROM `" . OCC_TABLE_LOG . "` WHERE `type`" . (empty($type) ? "!='sql'" : ("='" . safeSQLstr($type) . "'")) . " ORDER BY `datetime` DESC" . $limit;
	$r = ocsql_query($q) or err('Unable to retrieve log entries');
	if (ocsql_num_rows($r) == 0) {
		warn('No log entries were found');
	}
	
	// display entries
	if ($GLOBALS['OC_configAR']['OC_timeZone'] != 'UTC') {
		print '<p class="note">The Date/Time column is shown in Coordinated Universal Time (UTC).<br />UTC ' . date('P') . ' = ' . safeHTMLstr($GLOBALS['OC_configAR']['OC_timeZone'] ) . '</p>';
	}
	print '<table border="0" cellspacing="1" cellpadding="5"><tr class="rowheader"><th>Date / Time (UTC)</th><th>Type</th><th>Entry</th></tr>';
	$row = 1;
	while ($l = ocsql_fetch_assoc($r)) {
		print '<tr class="row' . $row . '"><td style="white-space: nowrap;">';
		if (!empty($l['extra'])) {	// extra stuff? if so link it in
			print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $l['logid'] . (empty($limit) ? '&limit=0' : '') . (!empty($type) ? ('&type=' . safeHTMLstr($type)) : '') . '">' . safeHTMLstr($l['datetime']) . '</a>';
		} else {
			print safeHTMLstr($l['datetime']);
		}
		 print '</td><td>' . safeHTMLstr($l['type']);
		 if (($l['type'] == 'email') && in_array($l['datetime'], $failedMessageAR)) {
			 print ' &ndash; <a href="email-queue-log.php?lid=' . $l['logid'] . '" style="color: #f00; text-decoration: underline;" title="click to view individual messages/status">failed</a>';
		 }
		 print '</td><td>' . safeHTMLstr($l['entry']) . "</td></tr>\n";
		 if ($row == 1) {
			$row = 2;
		} else {
			$row = 1;
		}
	}
	print '</table>';
}

printFooter();

?>
