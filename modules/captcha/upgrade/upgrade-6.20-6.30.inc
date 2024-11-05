<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('captcha', 'MOD_CAPTCHA_version', '1.0', 'Version Number', 'reCAPTCHA API version number', 0)");

