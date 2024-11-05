<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$tableAR = getTables();
foreach ($tableAR as $t) {
	ocsql_query("ALTER TABLE `" . $t . "` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci");	
}

ocsql_query("ALTER TABLE `" . OCC_TABLE_PAPER . "` CHANGE `title` `title` VARCHAR(1000) NOT NULL DEFAULT ''; ");

ocsql_query("ALTER TABLE `" . OCC_TABLE_AUTHOR . "` ADD `role` TEXT DEFAULT NULL");

ocsql_query("ALTER TABLE `" . OCC_TABLE_EMAIL_QUEUE . "` ADD `reference_id` VARCHAR(50) DEFAULT NULL");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`=CONCAT(`value`, ',fs_authors:role') WHERE `module`='OC' AND `setting`='OC_hideSubFields' LIMIT 1");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='7.00' WHERE `setting`='OC_version'");
	
ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='7.00' WHERE `setting`='OC_versionLatest'");
