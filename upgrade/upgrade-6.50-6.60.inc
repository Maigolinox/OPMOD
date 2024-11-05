<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` VALUES ('author-upload', 'notification', 'OC', 'Author - File Upload', 'Submission ID [:sid:] file uploaded', 'Submission ID [:sid:] has been uploaded.\n\n[:error:]', NULL, '{\"sid\":\"Submission ID\",\"error\":\"Upload Error\"}')");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_allowEmailConflict', '0', 'Allow Email Conflict', 'Allow a submission author and reviewer to have the same email address', 0)");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorOneContact', '0', 'Set Author 1 as Contact', 'Auto set Author 1 as contact author and hide Contact ID field (1=Yes, 0=No (default)', 0)");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorsRequiredData', '0', 'Authors Required Data', 'Author(s) for whom required sub. form fields must be filled in (0=All, 1=First, 2=Contact)', 0)");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_emailAuthorRecipients', 0, 'Email Author Recipients', 'Author(s) to include in Chair emails (0: contact only, 1: all)', 0)");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.60' WHERE `setting`='OC_version'");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.60' WHERE `setting`='OC_versionLatest'");
