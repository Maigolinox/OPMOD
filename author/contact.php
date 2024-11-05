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

printHeader(oc_('Email Chair'), 3);

if (isset($_POST['ocaction']) && ($_POST['ocaction'] == "Send Email")) {
	$err = '';
	$message = '';
	$hdr = $OC_configAR['OC_mailHeaders']; // keep before validate hook
	if (isset($_POST['email']) && !empty($_POST['email'])) {
		$err .= '<li>' . oc_('Fields not correctly filled out') . '</li>';
	}
	if (!isset($_POST['name']) || !preg_match("/\p{L}/u", $_POST['name']) || preg_match("/[\r\n]/", $_POST['name'])) {
		$err .= '<li>' . sprintf(oc_('%s field empty or invalid'), oc_('Name')) . '</li>';
	}
	if (!isset($_POST['liame']) || !validEmail(($_POST['liame'] = trim($_POST['liame']))) || preg_match("/[\r\n]/", $_POST['liame'])) {
		$err .= '<li>' . sprintf(oc_('%s field empty or invalid'), oc_('Email')) . '</li>';
	}
	if (($OC_configAR['OC_privacy_display'] > 0) && (!isset($_POST['consent']) || ($_POST['consent'] != '1'))) {
		$err .= '<li>' . sprintf(oc_('%s field is required'), oc_('Consent')) . '</li>';
	}
	if (!isset($_POST['subject']) || !preg_match("/\p{L}/u", $_POST['subject']) || preg_match("/[\r\n]/", $_POST['subject'])) {
		$err .= '<li>' . sprintf(oc_('%s field empty or invalid'), oc_('Subject')) . '</li>';
	}
	if (!isset($_POST['message']) || !preg_match("/\p{L}/u", $_POST['message'])) {
		$err .= '<li>' . sprintf(oc_('%s field empty or invalid'), oc_('Message')) . '</li>';
	} else {
		$message = $_POST['message'];
	}

	if (oc_hookSet('author-contact-validate')) {
		foreach ($GLOBALS['OC_hooksAR']['author-contact-validate'] as $hook) {
			require_once $hook;
		}
	}
	
	if (empty($err)) {
		// add who it's from to body of message in case of an issue with reply headers
		$message .= "\n\nFrom: " . $_POST['name'] . ' <' . $_POST['liame'] . ">\n";

		// setup Reply-To
		if (empty($hdr)) {
			$hdr = 	'From: "' . preg_replace('/"/', '', $_POST['name']) . '" <' . $GLOBALS['OC_configAR']['OC_pcemail'] . '>' . "\r\n" . // pcemail used to avoid email validation issues
					'Reply-To: ' . $_POST['liame'] . "\r\n";
		} else {
			if (preg_match("/^(.*Reply-To:\s?)\S+(.*)$/s", $hdr, $matches)) {
				$hdr = $matches[1] . $_POST['liame'] . $matches[2];
			} elseif (preg_match("/[\r\n]$/s", $hdr)) {
				$hdr .= 'Reply-To: ' . $_POST['liame'] . "\r\n";
			} else {
				$hdr .= "\r\n" . 'Reply-To: ' . $_POST['liame'] . "\r\n";
			}
		}
		if (! oc_mail($OC_configAR['OC_pcemail'], $_POST['subject'], $message, $hdr)) {
			err(oc_('An error occurred sending out the email.'));
		} else {
			print '<div class="note2">' . oc_('Your message has been sent.') . '</div>';
			printFooter();
			exit;
		}
	}
}

print '
<style type="text/css">
<!--
#recaptcha_response_field { background-color: #eee; }
-->
</style>
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="ocaction" value="Send Email" />
<input type="hidden" name="email" value="" />
<table border="0" style="width: 500px; margin: 0 auto" cellspacing="4">
';

if (!empty($err)) {
	print '<tr><td>&nbsp;</td><td class="warn">' . oc_('Please correct the following:') . '<ul>' . $err . '</ul></br>';
}

print '
<tr><td><strong><label for="name">' . oc_('Name') . ':</label></strong></td><td><input size="60" name="name" id="name" class="ocinput" style="width: 400px" value="' . safeHTMLstr(varValue('name', $_POST)) . '"></td></tr>
<tr><td><strong><label for="liame">' . 
//T: Email Address
oc_('Email') . ':</label></strong></td><td><input size="60" name="liame" id="liame" class="ocinput" style="width: 400px" value="' . safeHTMLstr(varValue('liame', $_POST)) . '"></td></tr>
';

if ($OC_configAR['OC_privacy_display'] > 0) {
	print '<tr><td>&nbsp;</td><td><label><input name="consent" id="consent" type="checkbox" value="1" ' . ((isset($_POST['consent']) && ($_POST['consent'] == '1')) ? 'checked ' : '') . '/> ' . oc_('I consent to the collection of my personal information and to receive emails about the message below.') . ' (<a href="../privacy_policy.php" target="_blank">' . oc_('Privacy Policy') . '</a>)</label></td></tr>';
}

print '
<tr><td><strong><label for="subject">' . oc_('Subject') . ':</label></strong></td><td><input size="60" name="subject" id="subject" class="ocinput" style="width: 400px" value="' . 
//T: Email Subject Line
safeHTMLstr(varValue('subject', $_POST)) . '"></td></tr>
<tr><td valign="top"><strong><label for="message">' . oc_('Message') . ':</label></strong></td><td><textarea rows="5" cols="60" class="ocinput" style="width: 400px" name="message" id="message">' . 
//T: Email Message
safeHTMLstr(varValue('message', $_POST)) . '</textarea></td></tr>
';

if (oc_hookSet('author-contact-fields')) {
	foreach ($GLOBALS['OC_hooksAR']['author-contact-fields'] as $hook) {
		require_once $hook;
	}
}

print '
<tr><th align="center" colspan=2><br><input type="submit" name="submit" class="submit" value="' . oc_('Send Email') . '"></th></tr>
</table>
</form>
<p>
';

printFooter();

?>
