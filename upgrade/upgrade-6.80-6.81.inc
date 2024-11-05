<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

if (!defined('OCC_ENC_KEY')) {
	// Setup encryption key -- do it here so if it fails we can notify admin to have them manually do it
	$enckeystr = '
// Encryption key
define("OCC_ENC_KEY", "' . oc_idGen(64) . '");
';
	$enckeyok = false;
	if ($fp = fopen(OCC_CONFIG_FILE, 'a')) {
		if (fwrite($fp, $enckeystr) !== false) {
			fclose($fp);
			$enckeyok = true;
		}
	}
	if (!$enckeyok) {
		print '
<div style="margin: 2em 20px; padding: 5px 30px 10px 30px; border: 1px solid #ccc; background-color: #eee;"><p style="color: #f00;">We were unable to edit the config.php file. Please edit the file using a plain text or code editor and add the following lines to the end, then click the <i>Continue with Upgrade</i> button.</p><textarea style="font-family: serif; font-size: 10pt; width: 100%;" rows="3" >' . safeHTMLstr($enckeystr) . '</textarea><p class="note">Be sure to use standard quotes and that they are not converted to smart or curly quotes.</p></div>

<form method="post" action="upgrade.php">
<input type="hidden" name="a" value="u" />
<input type="hidden" name="token" value="' . safeHTMLstr($_SESSION[OCC_SESSION_VAR_NAME]['chairtoken']) . '" />
<p><input type="submit" name="submit" value="Continue with Upgrade" /></p>
</form>
';
		printFooter();
		exit;
	}
} else {
	$enckeyok = true;
}

if ($enckeyok) {
	$upgradeModulesAR = array('captcha', 'mail', 'oc_ieee_copyright', 'oc_ithenticate', 'oc_payment', 'oc_program', 'oc_revupload', 'oc_subpayment');
	
	ocsql_query("ALTER TABLE `" . OCC_TABLE_PAPER . "` CHANGE `submissiondate` `submissiondate` DATE NOT NULL"); 
	
	ocsql_query("ALTER TABLE `" . OCC_TABLE_PAPER . "` ADD `consent` text AFTER `lastupdate`");
	
	ocsql_query("ALTER TABLE `" . OCC_TABLE_REVIEWER . "` ADD `signupdate` date DEFAULT NULL AFTER `comments`, ADD `consent` text AFTER `lastsignin`");
	
	ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorViewSubIfEditClosed', '0', 'View Sub. if Edit Closed', 'Allow author to view submission if editing is closed (1=Yes, 0=No (default)', 0)");
	
	ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_privacy_display', '0', 'Privacy Policy Link Display', 'Whether and where to display a Privacy Policy Link (0: none, 1: menu, 2: page footer).', 0)");
	
	ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_privacy_link', '', 'Privacy Policy Web Address', 'Full web address for privacy policy on remote site. Leave blank to use built-in privacy template.', 0)");
	
	ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_chairMFA', '', 'Chair Multi-Factor Authentication', 'Enter email address to receive authentication code or leave blank to disable', 0)");
	
	ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_chairMFAcode', '', 'Chair MFA Code', 'for internal use', 0)");
	
	ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_privacy_banner_options', '{\"display\":0,\"message\":\"This website uses cookies to offer you a better user experience. By using this website, you agree to the placement of these cookies.\",\"dismiss\":\"Accept\",\"link\":\"\",\"href\":\"\"}', 'Cookie Banner Options', 'JSON-encoded list of attributes (display, message, dismiss, link, href)', 0)");
	
	
	ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` VALUES ('privacy_policy', 'other', 'OC', 'Privacy Policy', '', '<p><strong>What information is being collected?</strong></p>\n\n<p>For anyone browsing our web site, our server records the page visited, IP address where the request came from, and if included the browser type and referring web page.</p>\n\n<p>If you choose to make a submission, become a reviewer or advocate, or serve as a discussant or session chair, we will request your name, email address, and affiliation, along with possibly other information as denoted when filling out the respective form for each role.</p>\n\n<p><strong>Who is collecting it?</strong></p>\n\n<p>Information is collected by the entity and/or activity named atop this page. You may contact us by using the Email menu link above.</p>\n\n<p><strong>How is it collected?</strong></p>\n\n<p>Information used in this system is collected through the forms provided (e.g., submission, account sign up), or automatically through our server logs (e.g., page visited, IP address).</p>\n\n<p><strong>Why is it being collected?</strong></p>\n\n<p>Information is being collected in relation to the activity named atop this page and your optional participation in this activity.</p>\n\n<p><strong>How will it be used?</strong></p>\n\n<p>The information automatically recorded by our server is used to troubleshoot issues and improve our service. Other information collected is used for evaluation, communication, and publishing related to the activity specified above.</p>\n\n<p><strong>Who will it be shared with?</strong></p>\n\n<p>Information collected is shared with the organizers of the above activity. A subset of information may also be shared with other participants in the activity (e.g., reviewers). Some of the information (e.g., name, affiliation) of accepted submitters and other participants may also be made publicly available to meet the goals of the activity.</p>\n\n<p>By virtue of the information being hosted on a server, the information is accessible to our hosting provider and subject to their privacy policy. Additional third-party providers may include: payment processor, plagiarism detection service, copyright processing service, reviewer credit service, publishing service.</p>\n\n<p><strong>How do I correct information or request its erasure?</strong></p>\n\n<p>In order to correct information, submitters and committee members may use the Edit Submission and Update Profile options respectively. If these do not apply to you, are no longer accessible, or you wish to request erasure of your data, use the Email menu link above to contact us. We ask that you contact us as erasure of the data may impact others on the system and the activity specified above, thus requiring manual intervention and notification on our part.</p>\n\n<p><strong>Are cookies or other trackers used?</strong></p>\n\n<p>Cookies are used to maintain language selection and allow you to remain logged into your account without having to keep re-entering credentials. Cookies are removed upon signing out of your account or exiting your browser.</p>', NULL, NULL)");
	
	ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`=CONCAT(`value`, ',fs_consent:consent') WHERE `module`='OC' AND `setting`='OC_hideSubFields' LIMIT 1");
	
	ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`=CONCAT(`value`, ',fs_consent:consent') WHERE `module`='OC' AND `setting`='OC_hideCmtFields' LIMIT 1");
	
	ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.81' WHERE `setting`='OC_version'");
	
	ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.81' WHERE `setting`='OC_versionLatest'");
}
