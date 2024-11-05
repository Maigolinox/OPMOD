<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$upgradeModulesAR = array('oc_adv_assign', 'oc_program', 'oc_ithenticate', 'oc_ieee_copyright');

ocsql_query("ALTER TABLE `" . OCC_TABLE_TEMPLATE . "` ADD `type` VARCHAR(30) NOT NULL DEFAULT 'email' AFTER `templateid`");

ocsql_query("ALTER TABLE `" . OCC_TABLE_TEMPLATE . "` ADD `variables` TEXT NULL AFTER `updated`"); 

ocsql_query("ALTER TABLE `" . OCC_TABLE_REVIEWER . "` ADD `orcid` varchar(255) default NULL AFTER `email`");

ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` (`templateid`, `type`, `module`, `name`, `subject`, `body`, `updated`, `variables`) VALUES ('author-edit', 'notification', 'OC', 'Author - Edit Submission', 'Submission Update ID [:sid:]', '[:fields:]', NULL, '{\"fields\":\"Fields\",\"sid\":\"Submission ID\"}')");

ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` (`templateid`, `type`, `module`, `name`, `subject`, `body`, `updated`, `variables`) VALUES ('author-submit', 'notification', 'OC', 'Author - New Submission', 'Submission ID [:sid:]', 'Thank you for your submission to [:OC_confName:].  Below is a copy of the information submitted for your records.\n\n[:fields:]', NULL, '{\"fields\":\"Fields\",\"sid\":\"Submission ID\"}')");

ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` (`templateid`, `type`, `module`, `name`, `subject`, `body`, `updated`, `variables`) VALUES ('author-withdraw', 'notification', 'OC', 'Author - Withdraw Submission', 'Submission Withdraw - ID [:sid:]', 'The submission below has been withdrawn at the author''s request.  If you did not intend to withdraw the submission, please reply back.\n\n[:submission:]', NULL, '{\"sid\":\"Submission ID\",\"submission\":\"Submission ID/Title\"}')");

ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` (`templateid`, `type`, `module`, `name`, `subject`, `body`, `updated`, `variables`) VALUES ('chair-assign_reviews', 'notification', 'OC', 'Chair - Manual Review Assignment(s)', 'New Reviewer Assignment(s)', 'New assignments have been made for you to review in the [:OC_confName:] OpenConf system:\n\n\n[:assignments:]\n\nThank you.', NULL, '{\"assignments\":\"New Assignments\",\"name_first\":\"First Name\",\"name_last\":\"Last Name\",\"name\":\"Full Name\",\"reviewerid\":\"Reviewer ID\",\"username\":\"Username\"}')");

ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` (`templateid`, `type`, `module`, `name`, `subject`, `body`, `updated`, `variables`) VALUES ('chair-assign_advocates', 'notification', 'OC', 'Chair - Manual Advocate Assignment(s)', 'New Advocate Assignment(s)', 'New assignments have been made for you to advocate in the [:OC_confName:] OpenConf system:\n\n\n[:assignments:]\n\nThank you.', NULL, '{\"assignments\":\"New Assignments\",\"name_first\":\"First Name\",\"name_last\":\"Last Name\",\"name\":\"Full Name\",\"advocateid\":\"Advocate ID\",\"username\":\"Username\"}')");

ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` (`templateid`, `type`, `module`, `name`, `subject`, `body`, `updated`, `variables`) VALUES ('committee-review', 'notification', 'OC', 'Reviewer - Submit Review', 'Review of submission [:sid:]', 'Following is a copy of your review for submission number [:id:] submitted to [:OC_confName:].  Note that you will receive this email even if an error occurred during submission.\n\n[:fields:]', NULL, '{\"fields\":\"Fields\",\"sid\":\"Submission ID\"}')");

ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` (`templateid`, `type`, `module`, `name`, `subject`, `body`, `updated`, `variables`) VALUES ('committee-signup', 'notification', 'OC', 'Committee - New Signup', 'Committee Signup', 'Thank you for signing up for the [:OC_confName:] [:committee:].  Below is the information you provided.  If you have any questions, please contact [:OC_pcemail:] or reply to this email.\n\n[:fields:]', NULL, '{\"committee\":\"Committee Type\",\"fields\":\"Fields\"}')");

ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` (`templateid`, `type`, `module`, `name`, `subject`, `body`, `updated`, `variables`) VALUES ('committee-update', 'notification', 'OC', 'Committee - Profile Update', 'Committee Member Profile Updated', 'Your profile has been updated.  The submitted information follows below:\n\n[:fields:]', NULL, '{\"fields\":\"Fields\"}')");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_fileLimit', '', 'File Limit', 'File upload size limit in MB', 0)");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_hideCmtFields', 'fs_personal:orcid', 'Hide Cmt. Profile Form Fields', 'Comma-delimited list of committee profile form fields to not display (e.g., fieldset:field,fieldset:field,fieldset:field).  Overridden by Custom Forms module if used.', 0)");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.40' WHERE `setting`='OC_version'");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.40' WHERE `setting`='OC_versionLatest'");
