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

printHeader('Auto-Notification Templates', 1);

// Retrieve templates
$templateAR = array();
$q = "SELECT `templateid`, `name`, `module`, `variables` FROM `" . OCC_TABLE_TEMPLATE . "` WHERE `type`='notification' ORDER BY `name` ASC";
$r = ocsql_query($q) or err('Unable to retrieve templates');
while ($l = ocsql_fetch_assoc($r)) {
	// Skip templates for modules not active
	if (isset($l['module']) && !empty($l['module']) && ($l['module'] != 'OC') && !in_array($l['module'], $OC_activeModulesAR)) {
		continue;
	}
	// Skip PC templates if advocates not used
	if ($OC_configAR['OC_paperAdvocates'] || !preg_match("/advocate/", $l['templateid'])) {
		$templateAR[$l['templateid']] = array('name' => $l['name'], 'variables' => $l['variables']);
	}
}

$defaultVariables = array(
	'OC_pcemail' => OCC_WORD_CHAIR . ' Email Address',
	'OC_confirmmail' => 'Notification Email Address', 
	'OC_confName' => 'Event/Journal Short Name', 
	'OC_confNameFull' => 'Event/Journal Full Name', 
	'OC_confURL' => 'Event/Journal Web Address',
	'OC_openconfURL' => 'OpenConf Web Address',
);

clearstatcache();

function oc_templateForm($tid, $subject, $body) {
	print '
<p style="text-align: center;"><a href="' . $_SERVER['PHP_SELF'] . '">all templates</a></p>
<br />
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="templateid" value="' . safeHTMLstr($tid) . '" />
<table cellpadding="0" cellspacing="10">
<tr><td valign="top"><b>Template:</b></td><td>' . safeHTMLstr($GLOBALS['templateAR'][$tid]['name']) . '</td><td>&nbsp;</td></tr>
<tr><td><b><label for="subject">Subject:</label></b></td><td><input name="subject" id="subject" size="70" maxlength="70" value="' . safeHTMLstr($subject) . '" /></td><td>&nbsp;</td></tr>
<tr><td colspan="3"><b><label for="body">Message:</label></b></td><td>&nbsp;</td></tr>
<tr>
<td colspan="2"><textarea name="body" id="body" rows="20" cols="70">' . safeHTMLstr($body) . '</textarea></td>
<td valign="top">
';

	print '
<b>[:variables:]</b>
<br /><br />
<table border="0" cellspacing="5" callpadding="0">
';
	foreach ($GLOBALS['defaultVariables'] as $k => $v) {
		print '<tr><td>[:' . safeHTMLstr($k) . ':]</td><td><i>'. safeHTMLstr($v) . '</i></td></tr>';
	}
	if (!empty($GLOBALS['templateAR'][$tid]['variables'])) {
		$vars = json_decode($GLOBALS['templateAR'][$tid]['variables']);
		foreach ($vars as $k => $v) {
			print '<tr><td>[:' . safeHTMLstr($k) . ':]</td><td><i>'. safeHTMLstr($v) . '</i></td></tr>';
		}
	}

	print '
</table>
</td>
</tr>
<tr><td colspan="2"><input type="submit" name="ocaction" class="submit" value="Save Template" /></td></tr>
</table>
</form>
<p class="note">The variables appearing next to the message field may be used in your email by enclosing each instance in [:<em>variable</em>:] .  These will be substituted for their value prior to the email being sent.  For example, to include the conference (short) name in your message use [:OC_confName:] .  Some variables are only available for certain types of notification.</p>
';

	printFooter();
	exit;
}

if (isset($_GET['ocaction']) && ($_GET['ocaction'] == 'edit') && isset($_GET['tid']) && isset($templateAR[$_GET['tid']])) {
	$r = ocsql_query("SELECT `name`, `subject`, `body`, `variables` FROM `" . OCC_TABLE_TEMPLATE . "` WHERE `type`='notification' AND `templateid`='" . safeSQLstr($_GET['tid']) . "'") or err('Unable to retrieve template');
	if (ocsql_num_rows($r) == 1) {
		$l = ocsql_fetch_assoc($r);
		oc_templateForm($_GET['tid'], $l['subject'], $l['body']);
	} else {
		print '<p class="warn" style="text-align: center;">Template not found</p>';
	}
} elseif (isset($_POST['ocaction']) && ($_POST['ocaction'] == 'Save Template')) {
	$templateid = (isset($_POST['templateid']) ? trim($_POST['templateid']) : '');
	$subject = (isset($_POST['subject']) ? trim($_POST['subject']) : '');
	$body = (isset($_POST['body']) ? trim($_POST['body']) : '');
	
	$err = '';
	if ( ! preg_match("/^[\w-]+$/", $templateid) || ! isset($templateAR[$templateid]) ) {
		warn('Template ID invalid');
	} elseif (preg_match("/[\r\n]/", $subject)) {
		$err = 'Subject invalid';
	} else {
		$q = "UPDATE `" . OCC_TABLE_TEMPLATE . "` SET `subject`='" . safeSQLstr($subject) . "', `body`='" . safeSQLstr($body) . "', `updated`='" . safeSQLstr(date("Y-m-d")) . "' WHERE `templateid`='" . safeSQLstr($templateid) . "' AND `type`='notification' LIMIT 1";
		if ( ! ocsql_query($q) ) {
			$err = 'Unable to add/update database';
		}
	}
	
	if (empty($err)) {
		print '<p class="note2" style="text-align: center;">Template saved</p>';
	} else {
		print '<p class="warn" style="text-align: center;">' . $err . '</p>';
	}

	oc_templateForm($templateid, $subject, $body);

	exit;
} 

print '
<p class="note" style="text-align: center;">click name to edit</p>
<table border="0" cellspacing="1" cellpadding="5" style="margin: 0 auto;">
';

$row = 2;
foreach ($templateAR as $templateID => $templateInfo) {
	print '<tr class="row' . $row . '"><td><label for="' . safeHTMLstr($templateID) . '"><a href="' . $_SERVER['PHP_SELF'] . '?ocaction=edit&tid=' . safeHTMLstr($templateID) . '">' . safeHTMLstr($templateInfo['name']) . '</a></label></td></tr>';
	$row = $rowAR[$row];
}

print '
</table>

<p style="text-align:center; margin-top:2em;" class="note">If modifying a template and non-English language(s)<br />in use, include translation(s) in the template.</p>
';

printFooter();

?>
