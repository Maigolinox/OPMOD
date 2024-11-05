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

printHeader("Log Search", 1);

// Retrieve entry types
$logTypeAR = array();
$r = ocsql_query("SELECT DISTINCT `type` FROM `" . OCC_TABLE_LOG . "` WHERE `type` NOT LIKE '%fail' ORDER BY `type`") or err('Failed to retrieve log types');
while ($l = ocsql_fetch_assoc($r)) {
	$logTypeAR[] = $l['type'];
}

if (!isset($_POST['type']) && isset($_GET['type']) && in_array($_GET['type'], $logTypeAR)) {
	$_POST['type'] = $_GET['type'];
}

// display search form
print '
<br />
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="membersform">
<div style="text-align: center;">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<select id="selfld" name="type" onchange="updateQueueBox();"><option value="">Type (all)</option>' . generateSelectOptions($logTypeAR, varValue('type', $_POST), false) . '</select>
<input name="q" size="20" placeholder="query" value="' . safeHTMLstr(varValue('q', $_POST)) . '" />
<input type="submit" name="subaction" class="submit" value="Search" />
&nbsp;
<label><input id="checkqueue" type="checkbox" name="checkqueue" value="1" ' . ((isset($_POST['checkqueue']) && ($_POST['checkqueue']==1)) ? 'checked ' : '') .' />include email messages</label>
</div>
<script>
var queueObj=document.getElementById("checkqueue"),
    selObj=document.getElementById("selfld");
function updateQueueBox() {
	var opt=selObj.options[selObj.selectedIndex].text;
	if ((opt == "Type (all)") || (opt == "email")) {
		queueObj.disabled=false;
		queueObj.parentNode.style.color="#000";
	} else {
		queueObj.checked=false;
		queueObj.disabled=true;
		queueObj.parentNode.style.color="#999";
	}
}
updateQueueBox();
</script>
';

// submission?
if (isset($_POST['subaction'])) {
	// delete?
	if (($_POST['subaction'] == 'Delete Log Entries') && isset($_POST['logids']) && is_array($_POST['logids']) && !empty($_POST['logids'])) {
		foreach ($_POST['logids'] as $id) {
			if ( ! ocsql_query("DELETE FROM `" . OCC_TABLE_LOG . "` WHERE `logid`='" . safeSQLstr($id) . "' LIMIT 1") ) {
				print '<p class="warn" style="text-align: center;">Failed to delete Log ID ' . safeHTMLstr($id) . '</p>';
			}
		}
	} elseif (($_POST['subaction'] == 'Delete Messages') && isset($_POST['queueids']) && is_array($_POST['queueids']) && !empty($_POST['queueids'])) {
		foreach ($_POST['queueids'] as $id) {
			if ( ! ocsql_query("DELETE FROM `" . OCC_TABLE_EMAIL_QUEUE . "` WHERE `id`='" . safeSQLstr($id) . "' LIMIT 1") ) {
				print '<p class="warn" style="text-align: center;">Failed to delete Message ID ' . safeHTMLstr($id) . '</p>';
			}
		}
	}
	
	// search
	if (isset($_POST['q']) && !empty($_POST['q'])) {
		// Check for valid submission
		if (!validToken('chair')) {
			warn('Invalid submission');
		}

		print '<p><hr /></p><div style="font-weight: bold; font-size: 1.1em;">Log Entries:</div>';
		
		// log
		$r = ocsql_query("SELECT * FROM `" . OCC_TABLE_LOG . "` WHERE " . ((isset($_POST['type']) && !empty($_POST['type'])) ? ("`type`='" . safeSQLstr($_POST['type']) . "' AND ") : "") . "(`entry` LIKE '%" . safeSQLstr($_POST['q']) . "%' OR `extra` LIKE '%" . safeSQLstr($_POST['q']) . "%') ORDER BY `datetime` DESC, `logid` DESC") or err('Unable to search log');
		if (ocsql_num_rows($r) > 0) {
			print '
	<table border="0" cellspacing="1" cellpadding="5">
	<tr><td colspan="5" align="right"><table border="0" cellspacing="0" cellpadding="3"><tr><td class="del"><input type="submit" name="subaction" value="Delete Log Entries" onclick="return confirm(\'Entries will be permanently deleted. Proceed?\');" /></td></tr></table></td></tr>
	<tr class="rowheader"><th>Log&nbsp;ID</th><th>Date / Time (UTC)</th><th>Type</th><th>Entry</th><th class="del"><input type="checkbox" title="check/uncheck all boxes" onclick="oc_toggleCheckboxes(this.checked, \'logids[]\');" /></th></tr>
	';
			$row = 1;
			while ($l = ocsql_fetch_assoc($r)) {
				print '<tr class="row' . $row . '"><td>' . safeHTMLstr($l['logid']) . '</td><td style="white-space: nowrap;"><a href="log.php?id=' . $l['logid'] . (empty($limit) ? '&limit=0' : '') . ((isset($_POST['type']) && !empty($_POST['type'])) ? ('&type=' . safeHTMLstr($_POST['type'])) : '') . '" target="_blank" title="open entry in new window">' . safeHTMLstr($l['datetime']) . '</a></td><td>' . safeHTMLstr($l['type']) . '</td><td>' . safeHTMLstr(substr($l['entry'], 0, 80)) . ((strlen($l['entry']) > 80) ? '&#8230;' : '') . '</td><td class="del"><input type="checkbox" name="logids[]" value="' . safeHTMLstr($l['logid']) . '" /></td></tr>';
				 if ($row == 1) {
					$row = 2;
				} else {
					$row = 1;
				}
			}
			print '
	<tr><td colspan="5" align="right"><table border="0" cellspacing="0" cellpadding="3"><tr><td class="del"><input type="submit" name="subaction" value="Delete Log Entries" onclick="return confirm(\'Entries will be permanently deleted. Proceed?\');" /></td></tr></table></td></tr>
	</table>
	';
		} else {
			print '<p class="warn">No log entries found</p>';
		}
		
		// queue
		if (isset($_POST['checkqueue']) && ($_POST['checkqueue'] == 1)) {
			print '<p><hr /></p><div style="font-weight: bold; font-size: 1.1em;">Messages:</div>';
			$r = ocsql_query("SELECT * FROM `" . OCC_TABLE_EMAIL_QUEUE . "` WHERE (`to` LIKE '%" . safeSQLstr($_POST['q']) . "%' OR `subject` LIKE '%" . safeSQLstr($_POST['q']) . "%' OR `body` LIKE '%" . safeSQLstr($_POST['q']) . "%') ORDER BY `queued` DESC") or err('Unable to search messages');
			if (ocsql_num_rows($r) > 0) {
				print '
	<table border="0" cellspacing="1" cellpadding="5">
	<tr><td colspan="5" align="right"><table border="0" cellspacing="0" cellpadding="3"><tr><td class="del"><input type="submit" name="subaction" value="Delete Messages" onclick="return confirm(\'Messages will be permanently deleted. Proceed?\');" /></td></tr></table></td></tr>
	<tr class="rowheader"><th>Msg&nbsp;ID</th><th>Queued (UTC)</th><th>To</th><th>Subject</th><th class="del"><input type="checkbox" title="check/uncheck all boxes" onclick="oc_toggleCheckboxes(this.checked, \'queueids[]\');" /></th></tr>
	';
				$row = 1;
				while ($l = ocsql_fetch_assoc($r)) {
					print '<tr class="row' . $row . '"><td>' . safeHTMLstr($l['id']) . '</td><td style="white-space: nowrap;"><a href="email-queue-log.php?lid=&qid=' . $l['id'] . '" target="_blank" title="open entry in new window">' . safeHTMLstr($l['queued']) . '</a></td><td>' . safeHTMLstr($l['to']) . '</td><td>' . safeHTMLstr(substr($l['subject'], 0, 80)) . ((strlen($l['subject']) > 80) ? '&#8230;' : '') . '</td><td class="del"><input type="checkbox" name="queueids[]" value="' . safeHTMLstr($l['id']) . '" /></td></tr>';
					 if ($row == 1) {
						$row = 2;
					} else {
						$row = 1;
					}
				}
				print '
	<tr><td colspan="5" align="right"><table border="0" cellspacing="0" cellpadding="3"><tr><td class="del"><input type="submit" name="subaction" value="Delete Messages" onclick="return confirm(\'Messages will be permanently deleted. Proceed?\');" /></td></tr></table></td></tr>
	</table>
	';
			} else {
				print '<p class="warn">No messages found</p>';
			}
		}
	}
}

print '</form>';

printFooter();

?>
