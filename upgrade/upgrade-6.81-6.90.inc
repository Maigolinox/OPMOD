<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$upgradeModulesAR = array('oc_proceedings', 'oc_program');

ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` VALUES ('committee-withdrawnsub', 'notification', 'OC', 'Committee - Withdrawn Submission', 'Submission [:sid:] Withdrawn', 'The submission below has been withdrawn and is no longer assigned to you.\n\n[:sid:]. [:title:]', NULL, '{\"sid\":\"Submission ID\",\"title\":\"Submission Title\",\"memberid\":\"Member ID\",\"username\":\"Member Username\",\"name\":\"Member Name\"}')");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.90' WHERE `setting`='OC_version'");
	
ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.90' WHERE `setting`='OC_versionLatest'");
