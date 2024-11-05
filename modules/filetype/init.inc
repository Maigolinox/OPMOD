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

oc_addHook('author-file-validate', '../modules/filetype/author-file-validate.inc');
oc_addHook('author-upload-validate', '../modules/filetype/author-file-validate.inc');

// Register chair settings hook
oc_addHook('chair-menu-settings-modules', array($OC_modulesAR[$moduleId]['name'], '<a href="../modules/request.php?module=filetype&action=settings.inc">File Format Check</a>'));

// Settings Export/Import
oc_addHook('settings-export-options', '../modules/filetype/settings-export-options.inc');
oc_addHook('settings-export-prep', '../modules/filetype/settings-export-prep.inc');