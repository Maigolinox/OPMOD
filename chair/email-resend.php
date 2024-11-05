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

printHeader("Re-Send Message", 1);

if (isset($_GET['qid']) && ctype_digit($_GET['qid'])) {
	$q = "SELECT * FROM `" . OCC_TABLE_EMAIL_QUEUE . "` WHERE `id`='" . safeSQLstr($_GET['qid']) . "'";
	$r = ocsql_query($q) or err('Unable to retrieve message');
	if (ocsql_num_rows($r) == 1) {
		$l = ocsql_fetch_assoc($r);
		if (oc_mail($l['to'], $l['subject'], $l['body'])) {
			print '
<p>Message ID ' . safeHTMLstr($_GET['qid']) . ' successfully sent.</p>
<p class="note">Note: Re-sent messages will not be displayed in the log/queue.</p>
';
		} else {
			warn('Unable to send message ID ' . safeHTMLstr($_GET['qid']));
		}
	}
} else {
	warn('Invalid ID');
}

printFooter();

?>