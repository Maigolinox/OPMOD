<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$upgradeModulesAR = array('oc_payment');

ocsql_query("ALTER TABLE `" . OCC_TABLE_AUTHOR . "` ADD `photo` varchar(255) DEFAULT NULL");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`=CONCAT(`value`, ',fs_authors:photo') WHERE `module`='OC' AND `setting`='OC_hideSubFields' LIMIT 1");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerSeeAdvocate', '0', 'Allow Rev. See Advocate', 'Allow reviewers to see submission advocate', 0)");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorSeePendingSubReviews', '0', 'Display Reviews to Author if Sub. Decision Pending', 'Allow authors to see reviews when checking status if submission decision is pending', 0)");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_advocateSeeDecision', '0', 'Allow Adv. See Decision', 'Allow advocates to see acceptance decision', 0)");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerSeeDecision', '0', 'Allow Rev. See Decision', 'Allow reviewers to see acceptance decision', 0)");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.80' WHERE `setting`='OC_version'");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.80' WHERE `setting`='OC_versionLatest'");
