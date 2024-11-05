<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

print '
<fieldset>
<legend>' . oc_('CAPTCHA') . '</legend>

<br /><span class="err">';

require_once('captcha-include.inc');

mod_captcha_displayCAPTCHA();

print '</span>
</fieldset>';