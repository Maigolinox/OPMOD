<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once('captcha-include.inc');

if ($mod_captcha_err = mod_captcha_verifyCAPTCHA()) {
  $err .= '<li>' . $mod_captcha_err . '</li>';
}
