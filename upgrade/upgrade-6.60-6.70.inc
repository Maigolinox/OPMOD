<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$upgradeModulesAR = array('oc_adv_assign', 'oc_proceedings', 'oc_program');

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('OC', 'OC_reviewerUnassignReviews', '0', 'Allow Rev. to Unassign Own Reviews', 'Allow reviewer to unassign review and delete review data', 0)");

ocsql_query("INSERT INTO `" . OCC_TABLE_TEMPLATE . "` VALUES ('committee-reviewunassign', 'notification', 'OC', 'Reviewer - Unassign Review', 'Review Unassigned [:sid:]-[:reviewerid:]', 'Reviewer [:name:] ([:reviewerid:]) has been unassigned from submission:\n\n[:sid:]. [:title:]', NULL, '{\"sid\":\"Submission ID\",\"title\":\"Submission Title\",\"reviewerid\":\"Reviewer ID\",\"username\":\"Reviewer Username\",\"name\":\"Reviewer Name\"}')");


ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.70' WHERE `setting`='OC_version'");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.70' WHERE `setting`='OC_versionLatest'");
