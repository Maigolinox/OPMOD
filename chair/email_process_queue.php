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

$OC_displayTop = '<a href="email.php">New Email</a> &#187; ';

ob_start();

printHeader("Email Queue", 1);

ob_flush();
flush();

$r = ocsql_query("SELECT *  FROM `" . OCC_TABLE_EMAIL_QUEUE . "` WHERE `sent` IS NULL AND `tries`<1") or err('Unable to retrieve queued messages');
$count = ocsql_num_rows($r);
if (($count == 0) && (!isset($_GET['pass']) || !ctype_digit($_GET['pass']))) {
	print '<p>The queue is empty.  <a href="log.php?type=email">View email log</a>.</p>';
} else {
	if (isset($_GET['f']) && ctype_digit($_GET['f'])) {
		$failed = $_GET['f'];
	} else {
		$failed = 0;
	}
	$date = safeSQLstr(gmdate('Y-m-d H:i:s'));
	print '<p>' . ((isset($_GET['pass']) && ($_GET['pass'] == 1)) ? 'Remaining m' : 'M') . 'essages in queue: ' . $count . "</p>\n";
	while ($l = ocsql_fetch_assoc($r)) {
		if ($l['tries'] >= 1) { continue; }  // skip messages that have been tried already
		print 'Message ID ' . $l['id'] . ' (' . safeHTMLstr($l['to']) . ') ... ';
		ob_flush();
		flush();
		$set = "`tries`=" . ((int) $l['tries'] + 1);
		if (oc_mail($l['to'], $l['subject'], $l['body'])) {
			print 'sent';
			$set .= ", `sent`='" . $date . "'";
		} else {
			print '<span class="err">FAILED!!!';
			if ($l['tries'] == 2) {  // this will have been the third try
				print '  Too many tries, will not try again.';
			}
			print '</span>';
			$failed++;
		}
		print "<br />\n";
		$q = "UPDATE `" . OCC_TABLE_EMAIL_QUEUE . "` SET " . $set . " WHERE `id`=" . (int) $l['id'] . " LIMIT 1";
		if ( ! ocsql_query($q)) {   // throw error so same message doesn't keep being sent
			ob_flush_end();
			err('Unable to update status for message ID ' . $l['id'] . '.  Troubleshoot before trying again.');
		}
		
		// reload script if close to timeout
		if (oc_checkTimeout()) {
			print '
<script language="javascript" type="text/javascript">
<!--
window.location.replace("http' . ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) ? 's' : '') . '://' . safeHTMLstr($_SERVER['SERVER_NAME']) . (ctype_digit($_SERVER['SERVER_PORT']) && (($_SERVER['SERVER_PORT'] != '80')) ? (':' . $_SERVER['SERVER_PORT']) : '') . $_SERVER['PHP_SELF'] . '?pass=1&f=' . $failed . '");
// -->
</script>
<noscript>
<p style="font-weight: bold"><a href="' . $_SERVER['PHP_SELF'] . '">Click here</a> to continue processing messages.</p>
</noscript>
';
		}
	}
	if ($failed == 0) {
		print '<p>All messages have been sent.</p>';
	} else {
		print '<p style="warn">' . $failed . ' messages failed to be sent.</p>';
	}
	print '<p><a href="log.php?type=email">View email log</a> (most recent batch of messages will appear at the top)</p>';
}

ob_end_flush();

printFooter();

?>