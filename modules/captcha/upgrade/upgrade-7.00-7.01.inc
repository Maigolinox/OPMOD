<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('captcha', 'MOD_CAPTCHA_hcaptcha_private_key', '', 'hCaptcha Private (Secret) Key', 'obtain from hcaptcha.com', 0)");

ocsql_query("INSERT INTO `" . OCC_TABLE_CONFIG . "` (`module`, `setting`, `value`, `name`, `description`, `parse`) VALUES ('captcha', 'MOD_CAPTCHA_hcaptcha_public_key', '', 'hCaptcha Public (Site) Key', 'obtain from hcaptcha.com', 0)");
