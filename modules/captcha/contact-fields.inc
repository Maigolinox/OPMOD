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
<tr>
<td class="item" valign="top"><label for="recaptcha_response_field">' . oc_('CAPTCHA Check') . ':</label></td>
<td colspan=2>

<span class="err">
';

require_once('captcha-include.inc');

mod_captcha_displayCAPTCHA();

print '</span></td></tr>';