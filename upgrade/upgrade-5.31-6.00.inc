<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$upgradeModulesAR = array('oc_adv_assign');

ocsql_query("ALTER TABLE `" . OCC_TABLE_AUTHOR . "` CHANGE `name_last` `name_last` VARCHAR(255) NOT NULL DEFAULT '', CHANGE `name_first` `name_first` VARCHAR(255) NOT NULL DEFAULT '', CHANGE `country` `country` VARCHAR(255) NULL DEFAULT NULL, CHANGE `email` `email` VARCHAR(255) NULL DEFAULT NULL, CHANGE `honorific` `honorific` VARCHAR(255) NULL DEFAULT NULL, CHANGE `suffix` `suffix` VARCHAR(255) NULL DEFAULT NULL, CHANGE `postcode` `postcode` VARCHAR(255) NULL DEFAULT NULL, CHANGE `phone` `phone` VARCHAR(255) NULL DEFAULT NULL, CHANGE `presenter` `presenter` VARCHAR(255) NULL DEFAULT NULL, CHANGE `orcid` `orcid` VARCHAR(255) NULL DEFAULT NULL");

if (!oc_moduleActive('oc_subtype')) {
	ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`=CONCAT(`value`, ',fs_general:type') WHERE `module`='OC' AND `setting`='OC_hideSubFields' LIMIT 1");
}

if (oc_module_installed('oc_subtype')) {
	ocsql_query("ALTER TABLE `" . OCC_TABLE_PAPER . "` CHANGE `module_oc_subtype_type` `type` VARCHAR(255) NULL");
	$subtypes = array();
	if (($upgrader = ocsql_query("SELECT * FROM `" . OCC_TABLE_MODULE_OC_SUBTYPE . "`")) && (ocsql_num_rows($upgrader) > 0)) {
		while ($upgradel = ocsql_fetch_assoc($upgrader)) {
			$subtypes[] = preg_replace("/,/", ";", $upgradel['sub_type']);
		}
	} else {
		$subtypes[] = 'Paper';
	}
	ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_subtypes', '" . safeSQLstr(implode(',', $subtypes)) . "', 'Sub. Types', 'Internal Use Only', 0)");
	ocsql_query("DELETE FROM `" . OCC_TABLE_MODULES . "`  WHERE `moduleId`='oc_subtype' LIMIT 1");
} else {
	ocsql_query("ALTER TABLE `" . OCC_TABLE_PAPER . "` ADD `type` VARCHAR(255) NULL");
	ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_subtypes', 'Paper', 'Sub. Types', 'Internal Use Only', 0)");
}

if ($OC_configAR['OC_includeSubmissionUploadField'] != 1) {
	ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`=CONCAT(`value`, ',fs_content:file') WHERE `module`='OC' AND `setting`='OC_hideSubFields' LIMIT 1");
}

ocsql_query("ALTER TABLE `" . OCC_TABLE_AUTHOR . "`  ADD `orcid` VARCHAR(30) NULL");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`=CONCAT(`value`, ',fs_authors:orcid') WHERE `module`='OC' AND `setting`='OC_hideSubFields' LIMIT 1");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_editAcceptedOnly', '0', 'Edit Accepted Only', 'Restrict Edit Submission to accepted submissions only (1=Accepted only, 0=All)', 0)");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_includeReferenceSearchLinks', '1', 'Include Reference Service Links', 'Include reference service links on committee abstract page (1=Yes, 0=No)', 0)");


ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_wordForAuthor', 'Author', 'Word for Author', 'English word to be used for Author (e.g., Author, Presenter, Applicant); must pluralize with s at the end', 0)");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_wordForChair', 'Chair', 'Word for Chair', 'English word to be used for Chair (e.g., Chair, Administrator, Editor)', 0)");

if ($OC_configAR['OC_includeSubmissionUploadLink'] == 1) {
	ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_subConfirmNotice', '<p><strong>Thank you for your submission. Your submission ID number is [:sid:]. Please write this number down and include it in any communications with us.</strong></p>\n\n<p><strong>Below is the information submitted. We have also emailed a copy to the submission contact. If you notice any problems or do <em>not</em> receive the email within 24 hours, please contact us.</strong></p>\n\n<p><span style=\"font-size:16px\"><strong>&gt;&gt;&gt; <a href=\"upload.php?id=[:sid:]\">Proceed to upload file</a></strong></span></p>\n\n<hr />\n<p>[:formfields:]</p>', 'Sub. Confirm Notice', 'Notice displayed on Make Submission confirmation page.  Variables: [:sid:] = submission ID, [:formfields:] = form fields', 0)");
} else {
	ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_subConfirmNotice', '<p><strong>Thank you for your submission.  Your submission ID number is [:sid:].  Please write this number down and include it in any communications with us.</strong></p>\n\n<p><strong>Below is the information submitted.  We have also emailed a copy to the submission contact.  If you notice any problems or do <em>not</em> receive the email within 24 hours, please contact us.</strong></p>\n\n<p>[:formfields:]</p>', 'Sub. Confirm Notice', 'Notice displayed on Make Submission confirmation page.  Variables: [:sid:] = submission ID, [:formfields:] = form fields', 0)");
}
ocsql_query("UPDATE `" . OCC_TABLE_MODULES . "` SET  `moduleId`='oc_formfields' WHERE `moduleId`='oc_customforms' LIMIT 1");

if ($OC_configAR['OC_trackStudent'] != 1) {
	ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`=CONCAT(`value`, ',fs_general:student') WHERE `setting`='OC_hideSubFields' LIMIT 1");
}

ocsql_query("ALTER TABLE `" . OCC_TABLE_PAPERREVIEWER . "` ADD `score` TINYINT UNSIGNED NULL AFTER `updated`");

ocsql_query("ALTER TABLE `" . OCC_TABLE_PAPERREVIEWER . "` CHANGE `recommendation` `recommendation` TINYINT UNSIGNED NULL DEFAULT NULL");

ocsql_query("UPDATE `" . OCC_TABLE_PAPERREVIEWER . "` SET `score`=`recommendation`");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.00' WHERE `setting`='OC_version'");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.00' WHERE `setting`='OC_versionLatest'");
