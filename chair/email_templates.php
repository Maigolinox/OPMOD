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

printHeader('Email Templates', 1);

require_once 'email.inc';

clearstatcache();

function oc_templateForm($tid, $name, $subject, $body) {
	print '
<p style="text-align: center;"><a href="' . $_SERVER['PHP_SELF'] . '">all templates</a></p>
<br />
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="templateid" value="' . safeHTMLstr($tid) . '" />
<table cellpadding="0" cellspacing="10">
<tr><td valign="top"><b>Template<br />Name:</b></td><td><input name="templatename" size="50" maxlength="50" value="' . safeHTMLstr($name) . '" /><br /><span class="note">letters, numbers, hyphen, underscore, and space</span><br />&nbsp;</td></tr>
<tr><td><b>Subject:</b></td><td><input name="subject" size="70" maxlength="70" value="' . safeHTMLstr($subject) . '" /></td></tr>
<tr><td><b>Message:</b></td><td>&nbsp;</td></tr>
<tr><td colspan="3">
<textarea name="body" rows="20" cols="70">' . safeHTMLstr($body) . '</textarea><br />
<p class="note">Variables available for use in a message are based on the recipient group selected when sending<br />
the message.  <a href="email_variables.php?l=all" target="emailvars" title="opens in new window" onclick="' . "window.open('email_variables.php','emailvars','top=200,width=500,height=400,scrollbars=yes')" . '; return false;">View a list of groups and variables</a>.</p>
</td></tr>
<tr><td colspan="3"><input type="submit" name="ocaction" value="Save Template" class="submit" /></td></tr>
</table>
</form>
';
	printFooter();
	exit;
}

$name = ''; // new template name

if (isset($_GET['ocaction']) && ($_GET['ocaction'] == 'edit') && isset($_GET['tid']) && isset($templateAR[$_GET['tid']])) {
	$r = ocsql_query("SELECT `name`, `subject`, `body` FROM `" . OCC_TABLE_TEMPLATE . "` WHERE `type`='email' AND `templateid`='" . safeSQLstr($_GET['tid']) . "'") or err('Unable to retrieve template');
	if (ocsql_num_rows($r) == 1) {
		$l = ocsql_fetch_assoc($r);
		oc_templateForm($_GET['tid'], $l['name'], $l['subject'], $l['body']);
	} else {
		print '<p class="warn" style="text-align: center;">Template not found</p>';
	}
} elseif (isset($_POST['ocaction'])) {
	switch($_POST['ocaction']) {
		case 'Add Template':
			$name = (isset($_POST['name']) ? trim($_POST['name']) : '');
			$templateid = 'custom' . time();
			if (!preg_match("/^[\w -]+$/", $name)) {
				print '<p class="warn" style="text-align: center;">Template name must not be blank, and only contain<br />letters, numbers, hyphen, underscore, and space</p>';
			} elseif ( in_array($name, $templateAR) ) {
				print '<p class="warn" style="text-align: center;">A template with that name already exists</p>';
			} elseif ( ! ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` SET `templateid`='" . safeSQLstr($templateid) . "', `type`='email', `module`='OC', `name`='" . safeSQLstr($name) . "', `subject`='', `body`='', `updated`='" . safeSQLstr(date('Y-m-d')) . "'") ) {
				print '<p class="warn" style="text-align: center;">Unable to add template; perhaps you double-clicked?  Check below.</p>';
			} else {
				print '<p class="note2" style="text-align: center;">Template added</p>';
				$templateAR[$templateid] = $name;
				asort($templateAR);
				$name = '';
			}
			break;
			
		case 'Delete Templates':
			if (isset($_POST['templates']) && is_array($_POST['templates'])) {
				$count = 0;
				foreach ($_POST['templates'] as $tid) {
					if (isset($templateAR[$tid])) {
						if (ocsql_query("DELETE FROM `" . OCC_TABLE_TEMPLATE . "` WHERE `type`='email' AND `templateid`='" . safeSQLstr($tid) . "' LIMIT 1")) {
							unset($templateAR[$tid]);
							$count++;
						}
					}
				}
				print '<p class="note2" style="text-align: center;">Deleted ' . $count . ' template' . (($count!=1) ? 's' : '') . '</p>';
			}
			break;
			
		case 'Save Template':
			$templatename = (isset($_POST['templatename']) ? trim($_POST['templatename']) : '');
			$templateid = (isset($_POST['templateid']) ? trim($_POST['templateid']) : '');
			$subject = (isset($_POST['subject']) ? trim($_POST['subject']) : '');
			$body = (isset($_POST['body']) ? trim($_POST['body']) : '');
			
			$err = '';
			if ( ! preg_match("/^[\w-]+$/", $templateid) || ! isset($templateAR[$templateid]) ) {
				warn('Template ID invalid');
			} elseif (!preg_match("/^[\w -]+$/", $templatename)) {
				$err = 'Template name invalid';
			} elseif (preg_match("/[\r\n]/", $subject)) {
				$err = 'Subject invalid';
			} else {
				$q = "UPDATE `" . OCC_TABLE_TEMPLATE . "` SET `name`='" . safeSQLstr($templatename) . "', `subject`='" . safeSQLstr($subject) . "', `body`='" . safeSQLstr($body) . "', `updated`='" . safeSQLstr(date("Y-m-d")) . "' WHERE `templateid`='" . safeSQLstr($templateid) . "' LIMIT 1";
				if ( ! ocsql_query($q) ) {
					$err = 'Unable to add/update database';
				}
			}
			
			if (empty($err)) {
				print '<p class="note2" style="text-align: center;">Template saved</p>';
			} else {
				print '<p class="warn" style="text-align: center;">' . $err . '</p>';
			}

			oc_templateForm($templateid, $templatename, $subject, $body);

			break;
		
		default:
			warn('Request unknown');
			exit;
	}
}

print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<p style="margin: 1.5em 0; text-align: center;"><input name="name" size="20" value="' . safeHTMLstr($name) . '" placeholder="template name" title="Permitted: letters, numbers, hyphen, underscore, space" /> <input type="submit" name="ocaction" value="Add Template" /></p>
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<table border="0" cellspacing="1" cellpadding="4" style="margin: 0 auto;">
<tr class="rowheader"><th class="del">&nbsp;</th><th>Template<span style="font-weight: normal;"> - click name to edit</span></th></tr>
';

$row = 2;
$custom = 0;
foreach ($templateAR as $templateID => $templateName) {
	print '<tr class="row' . $row . '"><td class="del">';
	if (preg_match("/^custom/", $templateID)) {
		print '<input type="checkbox" id="' . safeHTMLstr($templateID) . '" name="templates[]" value="' . safeHTMLstr($templateID) . '" />';
		$custom++;
	} else {
		print '&nbsp;';
	}
	print '</td><td><label for="' . safeHTMLstr($templateID) . '"><a href="' . $_SERVER['PHP_SELF'] . '?ocaction=edit&tid=' . safeHTMLstr($templateID) . '">' . safeHTMLstr($templateName) . '</a></label></td></tr>';
	$row = $rowAR[$row];
}

if ($custom > 0) {
	print '<tr><td colspan="2" style="padding: 0;"><table border=0 cellpadding=5 cellspacing=0 bgcolor="#ccccff"><tr><td><input type="submit" name="ocaction" value="Delete Templates" onclick="return confirm(\'Confirm template deletion\');" /></td></tr></table></td></tr>';
}

print '
</table>
</form>
';

printFooter();

?>
