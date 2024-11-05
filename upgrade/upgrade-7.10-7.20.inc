<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='7.20' WHERE `setting`='OC_version'");
	
ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='7.20' WHERE `setting`='OC_versionLatest'");

if (isset($OC_configAR['OC_mailParams']) && !empty($OC_configAR['OC_mailParams'])) {
	print '<p style="color: #f00; font-weight: bold;">Add the following line to the config.php file on your server:</p><p style="margin-left: 30px;">define(\'OCC_MAILPARAMS\', \'' . safeHTMLstr($OC_configAR['OC_mailParams']) . '\');</p><p>Continuing with upgrade...</p>';
}
ocsql_query("DELETE FROM `" . OCC_TABLE_CONFIG . "` WHERE `module`='OC' AND `setting`='OC_mailParams' LIMIT 1");
