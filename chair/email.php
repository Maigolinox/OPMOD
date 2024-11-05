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

if (isset($_POST['submit'])) {
	$OC_displayTop = '<a href="' . $_SERVER['PHP_SELF'] . '">New Email</a> &#187; ';
}

printHeader("Email", 1);

require_once 'email.inc';

clearstatcache();

$commentSeparator = "\n***************************************************************\n"; // author comments separator

$specialIndexAR = array(); // tracks which DB col to use for special var handling

function showAddresses($recipient, $sql) {
	$r = ocsql_query($sql) or err("Unable to retrieve emails");
    if (ocsql_num_rows($r) == 0) {
        print '<p class="warn">No email addresses available for ' . safeHTMLstr($recipient) . '</p>';
    } else {
	    print '<p><strong>Email addresses for ' . safeHTMLstr($recipient) . ':</strong></p>';
        while ($l = ocsql_fetch_array($r)){
            print safeHTMLstr($l['email']) . "<br />\n";
		}
    }
}

function specialValue($a1, $a2) {
	global $specialIndexAR, $l;
	$varName = $a1 . $a2;
	if (!empty($varName) && isset($specialIndexAR[$varName]) && isset($l[$specialIndexAR[$varName]]) && isset($GLOBALS[$varName][$l[$specialIndexAR[$varName]]])) {
		return($GLOBALS[$varName][$l[$specialIndexAR[$varName]]]);
	} else {
		return('');
	}
}

function queueMessage(&$queueAR, $date) {
	$q = "INSERT INTO `" . OCC_TABLE_EMAIL_QUEUE . "` (`queued`, `to`, `subject`, `body`, `reference_id`) VALUES " . implode(', ', $queueAR);
	if ( ! ocsql_query($q)) {
		$err = 'Unable to queue messages (' . ocsql_errno() . ').  You may want to try again or have the administrator check the error logs.  ';
		if (ocsql_query("DELETE FROM `" . OCC_TABLE_EMAIL_QUEUE . "` WHERE `datetime`='" . $date . "'")) {
			$err .= 'Messages just queued for delivered have been deleted';
		} else {
			$err .= 'We were unable to remove messages queued for delivery; for reference, their time stamp is ' . $date . '.';
		}
		err($err);
	}
}

if (isset($_POST['submit'])) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}
	// Verify recipient
	if (!isset($_POST['recipient']) || !isset($recipients[$_POST['recipient']])) {
		warn("Recipient(s) must be selected");
	}

    // Verify template
    if (!empty($_POST['template']) && !in_array($_POST['template'], array_keys($templateAR))) {
        err("Invalid template");
    }

	// Which message should we use?
	if (isset($_POST['message'])) {
		$subject = stripslashes($_POST['subject']);
		$message = stripslashes($_POST['message']);

        // YMMV wrt below
        $message = preg_replace("/\r/","",$message);

		// Save template?
		if (!empty($_POST['template']) && isset($_POST['save']) && ($_POST['save'] == "yes")) {
		    saveTemplate($subject, $message, $_POST['template']);
		}
	} elseif (!empty($_POST['template'])) {
		// retrieve template
		list($subject, $message) = oc_getTemplate($_POST['template']);
	} else {
		$message = '';
		$subject = '';
	}
	
	// Which submit?
	// List Email addresses
	if ($_POST['submit'] == "List Email Addresses") {
		showAddresses($recipientAR[$_POST['recipient']]['text'], $recipientAR[$_POST['recipient']]['sql']);
	} 
	// Send Email
	elseif ($_POST['submit'] == "Send Message") {
	
		$q = $recipientAR[$_POST['recipient']]['sql'];
		
		// Individual recipients?
		$recipientList = array();
		if (isset($_POST['select_recipients']) && ($_POST['select_recipients'] == 1)) {
			if (! isset($_POST['selected_recipients']) || empty($_POST['selected_recipients'])) {
				print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="recipient" value="' . safeHTMLstr($_POST['recipient']) . '" />
<input type="hidden" name="template" value="' . safeHTMLstr($_POST['template']) . '" />
<input type="hidden" name="subject" value="' . safeHTMLstr($subject) . '" />
<input type="hidden" name="message" value="' . safeHTMLstr($message) . '" />
<input type="hidden" name="select_recipients" value="1" />
<input type="submit" name="submit" value="Edit Message" class="submit" />
</form>
';
				warn('No recipients selected');
			}
			$selectRecipients = true;
			$qrecipientsAR = array();
			foreach ($_POST['selected_recipients'] as $recip) {
				list($id, $email) = explode('/', $recip);
				if (!preg_match("/^\d+$/", $id) || !validEmail($email)) { continue; }
				$recipientList[] = $email;
				$qrecipientsAR[] = "'" . safeSQLstr($recip) . "'";
			}
			$q = preg_replace("/ WHERE /", " WHERE CONCAT_WS('/', " . $recipientAR[$_POST['recipient']]['id'] . ", " . (isset($recipientAR[$_POST['recipient']]['emailcol']) ? $recipientAR[$_POST['recipient']]['emailcol'] : "`email`") . ") IN (" . implode(',', $qrecipientsAR) .  ") AND ", $q);
		} else {
			$selectRecipients = false;
		}
		$r = ocsql_query($q) or err("Unable to retrieve information to email");
		$recipientTotal = ocsql_num_rows($r);
		if ($recipientTotal == 0) {
		    err("No email addresses found (on send)");
		} elseif ($selectRecipients && ( ! $OC_configAR['OC_emailAuthorRecipients'] ) && ($recipientTotal != count($_POST['selected_recipients']))) {
			// This may mean that the recipientAR SELECT statement does not contain a WHERE clause, resulting the preg_replace above failing
			warn("Recipient mismatch - contact your OpenConf administrator");
		}
		
		// Special var handling
		if (isset($recipientAR[$_POST['recipient']]['special']) && is_file($recipientAR[$_POST['recipient']]['special'])) {
			require_once $recipientAR[$_POST['recipient']]['special'];
		}
		
		// Log it
		$date = safeSQLstr(gmdate('Y-m-d H:i:s'));  // set fixed time for log & so messages in queue are grouped together
		$logq = "INSERT INTO `" . OCC_TABLE_LOG . "` SET `datetime`='" . $date . "', `type`='email', `entry`='Email sent to ";
		if ($selectRecipients) {
			$logq .= safeSQLstr($extra = implode(", ", $recipientList));
		} else {
			$logq .= safeSQLstr($extra = $recipientAR[$_POST['recipient']]['text']);
		}
		$extra .= "\nSubject: " . $subject . "\n\n" . $message;
		$logq .= "', `extra`='To: " . safeSQLstr($extra) . "'";
		ocsql_query($logq);
		
		// Send out emails
		$to = $tmpmessage = $tmpsubject = '';
		$queueAR = array();
		$queue_date = gmdate('Y-m-d H:i:s');
		if ($OC_configAR['OC_queueEmails']) {	// queue messages?
			$queueMessages = true;
			print '<p>Messages are being queued for delivery.  Once queued, this page will refresh and the message sent out.  If the page does not refresh, you must click the link that will appear at the bottom of the page, and is also available on the ' . OCC_WORD_CHAIR . ' home page.</p>';
		} else {
			$queueMessages = false;
		}
		ob_end_flush();
		flush();
		ob_start();
		$emailAR = array();
		while ($l = ocsql_fetch_array($r)) {
			if (
				empty($l['email'])
				||
				(isset($_POST['skipsame']) && ($_POST['skipsame'] == 'yes') && in_array($l['email'], $emailAR))	
			) {
				continue;
			}

			$emailAR[] = $l['email'];
			
			$tmpsubject = oc_replaceVariables($subject, $l);
			$tmpmessage = oc_replaceVariables($message, $l);

			// Replace special vars (\w-\w)
			if (isset($recipientAR[$_POST['recipient']]['special'])) {
				$tmpmessage = preg_replace_callback( 
					"/\[:(\w+)-(\w+):\]/",
					function ($matches) {
						return specialValue($matches[1], $matches[2]);
					},
					$tmpmessage
				);
			}

            // YMMV wrt below
            $tmpmessage = preg_replace("/\r/","",$tmpmessage);

			$to = $l['email']; // req'd for OC_mailCopyLast code block below
			print 'emailing ' . safeHTMLstr($l['email'] . ' (' . $l[0] . ') ... ');
			ob_flush();
			flush();
			if ($queueMessages) {
				// try to get id for log reference
				$reference_id = '';
				if (isset($l['paperid']) && preg_match("/^\d+$/", $l['paperid'])) {
					$reference_id = $l['paperid'];
				}
				if (isset($l['reviewerid']) && preg_match("/^\d+$/", $l['reviewerid'])) {
					if (!empty($reference_id)) {
						$reference_id .= '-';
					}
					$reference_id = $l['reviewerid'];
				}
				// add to queue
				$queueAR[] = "('" . $date . "', '" . safeSQLstr($l['email']) . "', '" . safeSQLstr($tmpsubject) . "', '" . safeSQLstr($tmpmessage) . "', '" . safeSQLstr($reference_id) . "')";
				print "queued<br />\n";
				if (count($queueAR) >= 10) {  // store messages as a group to save on DB calls
					queueMessage($queueAR, $date);
					$queueAR = array(); // reset queue array

				}
			} elseif (oc_mail($l['email'], $tmpsubject, $tmpmessage)) {  // deliver instantly
				print "sent<br />\n";
			} else {
				print "<span class=\"err\">FAILED!!!</span><br />\n";
			}
		}
		ob_end_flush();
		flush();

		// Any remaining messages for queue storage?
		if ($queueMessages && (count($queueAR) > 0)) {
			queueMessage($queueAR, $date);
		}
		
		// Email chair a copy of last email sent
		if ($OC_configAR['OC_mailCopyLast']) {
			$msg = "To: " . $to . "\nSubject: " . $tmpsubject . "\n\n" . $tmpmessage . "\n";
			if (sendEmail($OC_configAR['OC_confirmmail'], 'Copy of last email sent', $msg)) {
				print '<p>A copy of the last message ' . ($queueMessages ? 'queued' : 'sent') . ' has been forwarded to ' . $OC_configAR['OC_confirmmail'] . '.</p>';
			} else {
				print '<p class="err">The system was unable to forward a copy of the last message ' . ($queueMessages ? 'queued' : 'sent') . ' to ' . $OC_configAR['OC_confirmmail'] . '.</p>';
			}
		}
		
		// attempt javascript redirect
		if ($queueMessages) {
			print '
<script language="javascript">
<!--
function gotoEmailQueue() {
	window.location.replace("' . preg_replace('/email.php/', 'email_process_queue.php', $_SERVER['PHP_SELF']) . '");
}
setTimeout("gotoEmailQueue()", 5000);
// -->
</script>
<p style="font-weight: bold">Your messages have been queued.  If this page does not refresh automatically and your browser does not look like it is doing something, please follow <a href="email_process_queue.php" style="text-decoration: underline;">this link</a> to send them out now, or visit the ' . OCC_WORD_CHAIR . ' home page to send them out later.</p>
';
		}
	}
	// Preview Email
	elseif ($_POST['submit'] == "Preview Message") {
		$q = $recipientAR[$_POST['recipient']]['sql'] . " LIMIT 1";
		if (isset($_POST['selected_recipients']) && !empty($_POST['selected_recipients'])) {
			list($selid, $selemail) = preg_split("/\//", $_POST['selected_recipients'][0]);
			$q = preg_replace("/ WHERE /", " WHERE " . $recipientAR[$_POST['recipient']]['id'] . "='" . safeSQLstr($selid) .  "' AND " . (isset($recipientAR[$_POST['recipient']]['emailcol']) ? $recipientAR[$_POST['recipient']]['emailcol'] : "`email`") . "='" . safeSQLstr($selemail) . "' AND ", $q);
		}
		$r = ocsql_query($q) or err("Unable to retrieve information to preview");
		if (ocsql_num_rows($r) == 0) {
		    err("No email addresses found (preview)");
		}

		$l = ocsql_fetch_array($r);
		
		// Special var handling
		if (isset($recipientAR[$_POST['recipient']]['special']) && is_file($recipientAR[$_POST['recipient']]['special'])) {
			require_once $recipientAR[$_POST['recipient']]['special'];
		}
		
		print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="recipient" value="' . safeHTMLstr($_POST['recipient']) . '" />
<input type="hidden" name="template" value="' . safeHTMLstr($_POST['template']) . '" />
<input type="hidden" name="subject" value="' . safeHTMLstr($subject) . '" />
<input type="hidden" name="message" value="' . safeHTMLstr($message) . '" />
';
		if (isset($_POST['select_recipients']) && ($_POST['select_recipients'] == 1)) {
			print '<input type="hidden" name="select_recipients" value="1" />';
			if (isset($_POST['selected_recipients'])) {
				foreach ($_POST['selected_recipients'] as $selected_recipient) {
					print '<input type="hidden" name="selected_recipients[]" value="' . safeHTMLstr($selected_recipient) . '" />';
				}
			}
		}
		print '
<input type="submit" name="submit" value="Edit Message" class="submit" />
&nbsp; &nbsp; &nbsp;
<input type="submit" name="submit" value="Send Message" class="submit" />
</form>
<pre>

';		

		$tmpsubject = oc_replaceVariables($subject, $l);
		$tmpmessage = oc_replaceVariables($message, $l);

		// Replace special vars (\w-\w)
		if (isset($recipientAR[$_POST['recipient']]['special'])) {
			$tmpmessage = preg_replace_callback( 
				"/\[:(\w+)-(\w+):\]/",
				function ($matches) {
					return specialValue($matches[1], $matches[2]);
				},
				$tmpmessage
			);
		}
		
		// Show to/subject and message
		print '<strong>To:</strong> ';
		if (isset($_POST['select_recipients']) && ($_POST['select_recipients'] == 1)) {
			if (! isset($_POST['selected_recipients']) || empty($_POST['selected_recipients'])) {
				warn('No recipients selected');
			} elseif (count($_POST['selected_recipients']) == 1) {
				list($id, $email) = explode("/", $_POST['selected_recipients'][0]);
				print safeHTMLstr($email . ' (ID: ' . $id . ')');
			} else {
				print "<i>(The following is a list of selected recipients. The preview is shown for only one of them.)</i>";
				foreach ($_POST['selected_recipients'] as $recip) {
					list($id, $email) = explode("/", $recip);
					print "\n  " . safeHTMLstr($email . ' (ID: ' . $id . ')');
				}
			}
		} else {
			print safeHTMLstr($l['email']);
		}
		print "\n\n";
		print '<strong>Subject:</strong> ' . safeHTMLstr($tmpsubject) . "\n\n";
		print safeHTMLstr(wordwrap($tmpmessage, $OC_configAR['OC_emailWrap'])) . "\n</pre>\n";
	} 
	// Write Email
	elseif (($_POST['submit'] == "Write Email Online") || ($_POST['submit'] == "Edit Message")) {
		// Valid recipient?
		if (!isset($_POST['recipient']) || !isset($recipients[$_POST['recipient']])) {
			err("Unknown recipient group selected");
		}
        // Any addresses
	    $r = ocsql_query($recipientAR[$_POST['recipient']]['sql']) or err("Unable to retrieve email addresses");
        if (ocsql_num_rows($r) == 0) {
            print '<p class="warn">No email addresses available for ' . safeHTMLstr($recipientAR[$_POST['recipient']]['text']) . '</p>';
        } else {
	
		    print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="recipient" value="' . safeHTMLstr($_POST['recipient']) . '" />
<input type="hidden" name="template" value="' . safeHTMLstr($_POST['template']) . '" />
<table border="0" cellspacing="10" cellpadding="0">
<tr><th style="text-align: left; vertical-align: top;"><label for="selected_recipients" style="font-weight: bold;">To:</label></th><td>';

			if (isset($_POST['select_recipients']) && ($_POST['select_recipients'] == 1)) {
				print '<input type="hidden" name="select_recipients" value="1" /><select name="selected_recipients[]" id="selected_recipients" size="10" multiple>';
				if (! isset($_POST['selected_recipients'])) {
					$_POST['selected_recipients'] = array();
				}
				$idField = substr($recipientAR[$_POST['recipient']]['id'], (strrpos($recipientAR[$_POST['recipient']]['id'], '`', -2)+1), -1);
				while ($l = ocsql_fetch_assoc($r)) {
					if (empty($l['email'])) { continue; }
					if (isset($l['name'])) {
						$name = $l['name'];
					} elseif (isset($l['name_last'])) {
						$name = $l['name_first'] . ' ' . $l['name_last'];
					}
					$idValue = $l[$idField] . '/' . $l['email'];
					print '<option value="' . safeHTMLstr($idValue) . '"' . (in_array($idValue, $_POST['selected_recipients']) ? ' selected' : '') . '>[ID: ' . safeHTMLstr($l[$idField] . '] ' . $l['email'] . ' (' . $name) . ')</option>';
				}
				print '</select>';
			} else {
				print safeHTMLstr($recipientAR[$_POST['recipient']]['text']);
			}

			print '</td><td>&nbsp;</td></tr>
<tr><th style="text-align: left"><label for="subject">Subject:</label></th><td><input name="subject" id="subject" size="60" value="' . safeHTMLstr($subject) . '"></td><td>&nbsp;</td></tr>
<tr><th style="text-align: left" colspan="3"><label for="message">Message:</label></th></tr>
<tr>
<td colspan="2" valign="top"><textarea name="message" id="message" rows="25" cols="60">' . safeHTMLstr($message) . '</textarea></td>
<td valign="top" aria-describedby="variablesNote"><strong>[:<em>variables</em>:]</strong><br /><br /><table border="0" cellspacing="0" cellpadding="3">';

			foreach ($OC_emailVarAR['general'] as $vkey => $vval) {
				print '<tr><td valign="top" style="white-space: nowrap;">[:' . safeHTMLstr($vkey) . ':]</td><th style="font-style: italic; font-weight: normal; text-align: left;">' . safeHTMLstr($vval) . '</th></tr>';
			}
 			foreach ($recipientAR[$_POST['recipient']]['vars'] as $vkey => $vval) {
				print '<tr><td valign="top" style="white-space: nowrap;">[:' . safeHTMLstr($vkey) . ':]</td><th style="font-style: italic; font-weight: normal; text-align: left;">' . safeHTMLstr($vval) . '</th></tr>';
			}

 			print '</table></td></tr>';

		    // Save template?
		    if (!empty($_POST['template'])) {
			    print '<tr><td colspan=3><br /><label><input type="checkbox" name="save" value="yes"> Save changes to template <i>' . safeHTMLstr($templateAR[$_POST['template']]) . '</i></label></td></tr>';
		    }

		    print '
<tr><td colspan=3><label><input type="checkbox" name="skipsame" value="yes"> Send at most one message per email address<br /> &nbsp; &nbsp; &nbsp; <i>Only check this option when message content is known to repeat for a recipient</i> </label></td></tr><tr><td colspan=3>
<br /><br />
<input type="submit" name="submit" value="Preview Message" class="submit" />
&nbsp; &nbsp; &nbsp;
<input type="submit" name="submit" value="Send Message" class="submit" />
</td></tr>
</table>
</form>
<p class="note" id="variablesNote">The variables appearing next to the message field may be used in your email by enclosing each instance in [:<em>variable</em>:] .  These will be substituted for their value prior to the email being sent.  For example, to include the conference (short) name in your message use [:OC_confName:] .  Some variables are only available for certain recipient groups.  The [:review-fields:] variable, available when emailing author acceptance notices, includes the review form fields designated to "show author" in the Custom Forms module; the default is to only show the reviewer Comments to Author.</p>
    ';
        } // if Any emails
    } // end submit write
} // end submit
// Not a submit - select recipient/template
else {	
	print '
<script language="javascript">
<!--
function selectTemplate(tmpl) {
	if (document.getElementById) {
		if (document.getElementById(tmpl)) {
			document.getElementById(tmpl).selected = true;
		} else {
			document.getElementById("blank").selected = true;
		}
	}
}
// -->
</script>
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<dl>
<dt><p><label for="recipient" style="font-weight: bold;">Select the recipient group you would like to email:</label></dt></p>
<dd><p><select name="recipient" id="recipient" onChange="selectTemplate(this.options[this.selectedIndex].value)"><option></option>
';

	foreach ($recipients as $r => $rval) {
		print '<option value="' . $r . '"';
		if ($rval == 'blank') {
			print ' disabled="disabled" style="font-style: italic; fot-weight: bold;"';
		}
		print '>' . safeHTMLstr($rval) . "</option>\n";
	}

	print '
</select></p>
<p style="padding-left: 50px"><label><input type="checkbox" name="select_recipients" value="1" /> Select individual recipients</label></p>
</dd>
<dd class="note">For "' . OCC_WORD_AUTHOR . 's -", ' . (($OC_configAR['OC_emailAuthorRecipients'] == 0) ? ('only the contact ' . oc_strtolower(OCC_WORD_AUTHOR)) : ('all ' . oc_strtolower(OCC_WORD_AUTHOR) . 's')) . ' of a submission will be emailed</dd>

<dt><p><label for="template" style="font-weight: bold;">Select an email template to use:</label> <span style="font-size: 0.8em;">(<a href="email_templates.php" title="edit templates">edit</a>)</span></dt></p>
<dd><p><select name="template" id="template">
<option value="" id="blank">Blank Email</option>
';

	foreach ($templateAR as $k => $v) {
		print '<option value="' . $k . '" id="' . $k . '">' . safeHTMLstr($v) . "</option>\n";
	}

	print '
</select></p></dd>

<dt>
<br />
<input type="submit" name="submit" value="Write Email Online" class="submit" />
&nbsp; &nbsp; &nbsp;
<input type="submit" name="submit" value="List Email Addresses" class="submit" />
</dt>
</dl>
</form>
';

} // else not a submit

printFooter();

?>
