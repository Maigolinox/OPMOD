<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

//// Add hooks

// Mail
if ($OC_configAR['MOD_MAIL_mailer'] == 'smtp') {
	oc_addHook('mail', '../modules/mail/mail.inc');
}

// Chair settings
oc_addHook('chair-menu-settings-modules', array($OC_modulesAR[$moduleId]['name'], '<a href="../modules/request.php?module=mail&action=settings.inc">Mail</a>'));

// Settings Export/Import
oc_addHook('settings-export-options', '../modules/mail/settings-export-options.inc');
oc_addHook('settings-export-prep', '../modules/mail/settings-export-prep.inc');