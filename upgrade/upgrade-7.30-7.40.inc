<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

ocsql_query("ALTER TABLE `" . OCC_TABLE_PAPER . "` ADD `decision_date` DATE NULL AFTER `lastupdate`");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_authorsEmailUnique', '0', 'Authors Email Unique', 'Require author email addresses to be unique (1=Yes, 0=No [default])', 0)");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='7.40' WHERE `setting`='OC_version'");
        
ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='7.40' WHERE `setting`='OC_versionLatest'");

