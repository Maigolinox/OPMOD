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

printHeader("Email Queue Log", 1);

if (isset($_POST['submit']) && ($_POST['submit'] == "Resend Failed Messages")) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}
	// Check for valid qid
	if (!isset($_POST['lid']) || !ctype_digit($_POST['lid'])) {
		warn('email log entry selection invalid');
	}
	// Retrieve failed submissions, retry mailing them, and update their sent date
	$baseq = "UPDATE `" . OCC_TABLE_EMAIL_QUEUE . "` SET `sent`='" . safeSQLstr(gmdate('Y-m-d H:i:s')) . "' WHERE `id`=";
	$q = "SELECT `" . OCC_TABLE_EMAIL_QUEUE . "`.* FROM `" . OCC_TABLE_EMAIL_QUEUE . "`, `" . OCC_TABLE_LOG . "` WHERE `" . OCC_TABLE_LOG . "`.`logid`='" . safeSQLstr($_POST['lid']) . "' AND `" . OCC_TABLE_LOG . "`.`datetime`=`" . OCC_TABLE_EMAIL_QUEUE . "`.`queued` AND `" . OCC_TABLE_EMAIL_QUEUE . "`.`sent` IS NULL ORDER BY `id`";
	$r = ocsql_query($q) or err('Unable to retrieve failed messages');
	while ($l = ocsql_fetch_assoc($r)) {
		if (oc_mail($l['to'], $l['subject'], $l['body'])) {
			issueSQL($baseq . $l['id'] . " LIMIT 1");
		}
	}
	$_GET['lid'] = $_POST['lid'];	
}

print '<p style="text-align: center"><a href="log.php?type=email">show email log entries</a></p>';

if (isset($_GET['qid']) && ctype_digit($_GET['qid'])) {
	$q = "SELECT * FROM `" . OCC_TABLE_EMAIL_QUEUE . "` WHERE `id`='" . safeSQLstr($_GET['qid']) . "'";
	$r = ocsql_query($q) or err('Unable to retrieve message');
	if (ocsql_num_rows($r) == 1) {
		$l = ocsql_fetch_assoc($r);
		print '
	<strong>Message ID:</strong> ' . safeHTMLstr($l['id']) . '<br />
';
		if (!empty($l['reference_id'])) {
			print '<strong>Reference ID:</strong> ' . safeHTMLstr($l['reference_id']) . '<br />';
		}
		print '
	<strong>Queued:</strong> ' . safeHTMLstr($l['queued']) . (!empty($_GET['lid']) ? ' &nbsp; (<a href="' . $_SERVER['PHP_SELF'] . '?lid=' . safeHTMLstr($_GET['lid']) . '">view messages queued at same time</a>)' : '') . '<br />
	<strong>Sent:</strong> ';
		if (!empty($l['sent'])) { 
			print safeHTMLstr($l['sent']);
		} else {
			print '<span class="warn" style="font-style: italic">Failed to send</span>';
		}
		print ' &nbsp; (<a href="email-resend.php?qid=' . urlencode($l['id']) . '">re-send message</a>)<br /><br />
<strong>To:</strong> ' . safeHTMLstr($l['to']) . '<br />
<strong>Subject:</strong> ' . safeHTMLstr($l['subject']) . '<br />
<pre>' . safeHTMLstr($l['body']) . '</pre>
';
	} else {
		warn('Unable to find message');
	}
} elseif (isset($_GET['lid']) && ctype_digit($_GET['lid'])) {
	$q = "SELECT `" . OCC_TABLE_EMAIL_QUEUE . "`.`id`,  `" . OCC_TABLE_EMAIL_QUEUE . "`.`reference_id`, `" . OCC_TABLE_EMAIL_QUEUE . "`.`sent`, `" . OCC_TABLE_EMAIL_QUEUE . "`.`tries`, `" . OCC_TABLE_EMAIL_QUEUE . "`.`to`, `" . OCC_TABLE_EMAIL_QUEUE . "`.`subject` FROM `" . OCC_TABLE_EMAIL_QUEUE . "`, `" . OCC_TABLE_LOG . "` WHERE `" . OCC_TABLE_LOG . "`.`logid`='" . safeSQLstr($_GET['lid']) . "' AND `" . OCC_TABLE_LOG . "`.`datetime`=`" . OCC_TABLE_EMAIL_QUEUE . "`.`queued` ORDER BY `id`";
	$r = ocsql_query($q) or err('Unable to retrieve log entries');
	if (ocsql_num_rows($r) > 0) {
		$displayed = true;
		print '<table border="0" cellspacing="1" cellpadding="5"><tr class="rowheader"><th>ID</th><th>Sent</th><th><span title="Reference ID (e.g., submission, committee member)">Ref ID</span></th><th>To</th><th>Subject</th></tr>';
		$row = 1;
		$failed = 0;
		while ($l = ocsql_fetch_assoc($r)) {
			print '<tr class="row' . $row . '"><td><a href="' . $_SERVER['PHP_SELF'] . '?lid=' . safeHTMLstr($_GET['lid']) . '&qid=' . $l['id'] . '">' . safeHTMLstr($l['id']) . '</a></td><td style="white-space: nowrap;">';
			if (!empty($l['sent'])) { 
				print safeHTMLstr($l['sent']);
			} else {
				print '<span class="warn" style="font-style: italic">Failed to send</span>';
				$failed++;
			}
			 print '</td><td>' . safeHTMLstr($l['reference_id']) . '</td><td>' . safeHTMLstr($l['to']) . '</td><td>' . safeHTMLstr($l['subject']) . "</td></tr>\n";
			 if ($row == 1) {
				$row = 2;
			} else {
				$row = 1;
			}
		}
		print '</table><p class="note">Log entries are shown in Coordinated Universal Time (UTC)</span></p>';
		if ($failed > 0) {
			print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="lid" value="' . safeHTMLstr($_GET['lid']) . '" />
<input type="submit" name="submit" value="Resend Failed Messages" />
</form>';
		}
	} else {
		warn('No messages found');
	}
} else {
	warn('Invalid ID');
}

printFooter();

?>
