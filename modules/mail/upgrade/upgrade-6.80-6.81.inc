<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// encrypt pw
if (!empty($OC_configAR['MOD_MAIL_smtp_password'])) {
	ocsql_query("UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='" . safeSQLstr(oc_encrypt($OC_configAR['MOD_MAIL_smtp_password'])) . "' WHERE `module`='mail' AND `setting`='MOD_MAIL_smtp_password' LIMIT 1");
}




