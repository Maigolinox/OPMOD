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
require_once OCC_FORM_INC_FILE;
require_once OCC_REVIEW_INC_FILE;

$hdr = oc_('Check Status');
$hdrfn = 3;

require_once "../include-submissions.inc";

// Check status allowed?
if (! $OC_statusAR['OC_status_open']) {
	warn(oc_('Check Status is not available.'), $hdr, $hdrfn);
}

// Is this a post?
if (isset($_POST['ocaction']) && ($_POST['ocaction'] == 'Check Status')) {
	// Check token
	if (!isset($_POST['token']) || !isset($_SESSION['atoken']) || ($_POST['token'] != $_SESSION['atoken'])) {
		warn(oc_('Invalid submission'), $hdr, $hdrfn);	
	}
	unset($_SESSION['atoken']);
	session_write_close();

	// Check for paper ID & password
	if (! isset($_POST['pid']) || 
		! preg_match("/^\d+$/", $_POST['pid']) ||
		! isset($_POST['pwd']) || 
		empty($_POST['pwd'])
	) {
		warn(oc_('Submission ID or password entered is incorrect'), $hdr, $hdrfn);
	}

	// retrieve sub
	$q = "SELECT `title`, `password`, `accepted` FROM `" . OCC_TABLE_PAPER . "` WHERE `paperid`='" . safeSQLstr($_POST['pid']) . "'";
	$r = ocsql_query($q) or err(oc_('Submission ID or password entered is incorrect'), $hdr, $hdrfn);
	if (ocsql_num_rows($r) == 1) {
		$l = ocsql_fetch_assoc($r);
		// check pwd
		if (oc_password_verify($_POST['pwd'], $l['password'])) {
			// display info & status
			printHeader($hdr, $hdrfn);
			print '
<p><strong>' . oc_('Submission ID') . ':</strong> ' . safeHTMLstr($_POST['pid']) . '</p>
<p><strong>' . 
//T: Submission Title
oc_('Title') . ':</strong> ' . safeHTMLstr($l['title']) . '</p>
<p><strong>' . 
//T: Submission Status
oc_('Status') . ':</strong> ' . (empty($l['accepted']) ? oc_('Pending') : safeHTMLstr(oc_($l['accepted']))) . '</p>
';

			if (oc_hookSet('author-status')) {
				foreach ($GLOBALS['OC_hooksAR']['author-status'] as $v) {
					require_once $v;
				}
			}
			
			// display review comments to author
			if ($OC_configAR['OC_authorSeePendingSubReviews'] || !empty($l['accepted'])) {
				$q2 = "SELECT * FROM `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `paperid`='" . safeSQLstr($_POST['pid']) . "' ORDER BY `reviewerid`";
				$r2 = ocsql_query($q2) or err('Unable to retrieve review data');
				if (ocsql_num_rows($r2) > 0) {
					// Fields to display
					$displayFieldAR = array();
					foreach ($OC_reviewQuestionsAR as $fid => $far) {
						if (isset($far['showauthor']) && $far['showauthor']) {
							$displayFieldAR[] = $fid;
						}
					}
					if (count($displayFieldAR) > 0) {
						$rcount = 1;
						while ($l2 = ocsql_fetch_assoc($r2)) {
							$reviewInfo = '';
							foreach ($displayFieldAR as $fid) {
								if (!empty($l2[$fid])) {
									$reviewInfo .= '<p><span style="font-weight: bold; font-style: italic; color: #555;">' . safeHTMLstr(oc_($OC_reviewQuestionsAR[$fid]['short'])) . ':</span> ' . nl2br(safeHTMLstr(oc_getFieldValue($OC_reviewQuestionsAR, $l2, $fid))) . "</p>\n";
								}
							}
							if (!empty($reviewInfo)) {
								print '<p><strong>' . safeHTMLstr(sprintf(oc_('Reviewer %s'), $rcount++)) . ':</strong></p><div style="margin-left: 20px;">' . $reviewInfo . '</div>';
							}
						}
					}
				}
			}
			printFooter();
		} else {
			warn(oc_('Submission ID or password entered is incorrect'), $hdr, $hdrfn);
		}
	} else {
		warn(oc_('Submission ID or password entered is incorrect'), $hdr, $hdrfn); 
	}
}
else {  // not a submission -- display sub id/password form
	// set author token
	$_SESSION['atoken'] = oc_idGen();
	session_write_close();

	printHeader($hdr, $hdrfn);

	print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="statusform">
<input type="hidden" name="ocaction" value="Check Status" />
<input type="hidden" name="token" value="' . safeHTMLstr($_SESSION['atoken']) . '" />
<table border=0 cellspacing=0 cellpadding=5>
<tr><td><strong><label for="pid">' . oc_('Submission ID') . '</label>:</strong></td><td><input name="pid" id="pid" size="10" tabindex="1"> ( <a href="email_papers.php" tabindex="4">' . oc_('forgot ID?') . '</a> )</td></tr>
<tr><td><strong><label for="password">' . oc_('Password') . '</label>:</strong></td><td><input name="pwd" id="password" type="password" tabindex="2" size="20" maxlength="255"> ( <a href="reset.php" tabindex="5">' . oc_('forgot password?') . '</a> )</td></tr>
</table>
<p><input type="submit" name="submit" value="' . oc_('Check Status') . '" class="submit" tabindex="3" /></p>
</form>
<script language="javascript">
<!--
document.forms[0].elements[0].focus();
// -->
</script>
';
	printFooter();

	if (oc_hookSet('author-status-bottom')) {
		foreach ($GLOBALS['OC_hooksAR']['author-status-bottom'] as $hook) {
			require_once $hook;
		}
	}
	
}

exit;

?>