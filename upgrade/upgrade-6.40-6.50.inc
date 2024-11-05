<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$upgradeModulesAR = array('oc_adv_assign', 'oc_payment');

ocsql_query("ALTER TABLE `" . OCC_TABLE_PAPERREVIEWER . "` ADD `assigned` DATE NULL DEFAULT NULL AFTER `completed`");

ocsql_query("INSERT INTO `" . OCC_TABLE_STATUS . "` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_reviewing_open', NULL, 0, 'Reviewing', NULL, NULL, NULL, 12)");

ocsql_query("INSERT INTO `" . OCC_TABLE_STATUS . "` (`module`, `setting`, `description`, `status`, `name`, `open`, `close`, `dependency`, `order`) VALUES ('OC', 'OC_advocating_open', NULL, 0, 'Advocating', NULL, NULL, 'OC_paperAdvocates', 17)");

ocsql_query("UPDATE `" . OCC_TABLE_STATUS . "` SET `description`='If closed but Review Cmt. Sign In is open, Program Cmt. members may still sign in, but will not be able to advocate submissions even if Advocating is open' WHERE `module`='OC' AND `setting`='OC_pc_signin_open' LIMIT 1");


ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.50' WHERE `setting`='OC_version'");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.50' WHERE `setting`='OC_versionLatest'");
