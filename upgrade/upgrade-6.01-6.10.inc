<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$upgradeModulesAR = array('oc_customforms');

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.10' WHERE `setting`='OC_version'");

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='6.10' WHERE `setting`='OC_versionLatest'");
