<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once '../include.php';

// Is module valid & installed
if (!isset($_REQUEST['module']) || !oc_moduleValid($_REQUEST['module']) || !oc_module_installed($_REQUEST['module'])) {
	err('Module is not installed', 'Error', 3);
}
// Is action valid
elseif (!isset($_REQUEST['action']) || !preg_match("/^[\w\-]+\.\w+$/",$_REQUEST['action']) || !is_file($_REQUEST['module'] . '/' . $_REQUEST['action'])) {
	err('Module action is invalid','Error');}elseif (
	preg_match("/^oc_/",$_REQUEST['module'])&&(constant('OC'.'C_L'.'ICENSE')=='Pub'.'lic')){
	err(base64_decode('SW52YWxpZCBPcGVuQ29uZiBMaWNlbnNl'), 'Error');
}
// Is this a request for config settings 
// include it here to allow config even if module is inactive
elseif ($_REQUEST['action'] == 'settings.inc') {
	define('OCC_MODULE_ID', $_REQUEST['module']);
	define('OCC_SELF', $_SERVER['PHP_SELF'] . '?module=' . OCC_MODULE_ID . '&action=settings.inc');
	// read in module config even if not active
	if (!oc_moduleActive(OCC_MODULE_ID) && is_file(OCC_MODULE_ID . '/config.inc')) {
		require_once OCC_MODULE_ID . '/config.inc';
	}
	// setup chair session
	beginChairSession();
	// display settings page
	require_once OCC_PLUGINS_DIR . 'ckeditor.inc';
	$OC_displayTop = '<a href="modules.php">Modules</a> &#187; ';
	printHeader($OC_modulesAR[OCC_MODULE_ID]['name'] . " Settings",1);
	require_once OCC_MODULE_ID . '/settings.inc';
	printFooter();
	exit;
}
// Is action extension valid
elseif (preg_match("/\.(?:inc|sql)$/",$_REQUEST['action'])) {
	err('Request for module action is not permitted','Error');
}
// Is module active
elseif (!in_array($_REQUEST['module'],$OC_activeModulesAR)) {
	err('Module is not active','Error');
}
// OK already
else {
	define('OCC_MODULE_ID', $_REQUEST['module']);
	// Friendly URL? If so, decipher params
	if ((OCC_LICENSE=='P'.'ublic') && preg_match("/^oc_/",$_REQUEST['module'])){exit;}
	if (isset($_GET['ocparams']) && !empty($_GET['ocparams'])) {
		$params = '';
		if (preg_match_all("/(\w+)--(\w+)_-/", $_GET['ocparams'], $matches)) {
			foreach ($matches[1] as $idx => $m) {
				if (($m != 'module') && ($m != 'action') && preg_match("/^[\w-]+$/", $m)) {
					$params .= '&' . $m . '=' . urlencode($matches[2][$idx]);
					$_GET[$m] = $matches[2][$idx];
				}
			}
		}
		unset($_GET['ocparams']);
		define('OCC_SELF', $_SERVER['PHP_SELF'] . '?module=' . $_REQUEST['module'] . '&action=' . $_GET['action'] . $params);
	} elseif (isset($_SERVER['REQUEST_URI']) && strstr($_SERVER['REQUEST_URI'], '?')) {
		define('OCC_SELF', htmlspecialchars($_SERVER['REQUEST_URI']));
	} elseif (isset($_SERVER['QUERY_STRING']) && strstr($_SERVER['QUERY_STRING'], '&')) {
		define('OCC_SELF', $_SERVER['PHP_SELF'] . '?' . htmlspecialchars($_SERVER['QUERY_STRING']));
	} else {
		err('This server does not support REQUEST_URI or QUERY_STRING','Error');
	}
	define('OCC_MODULE_REQUEST_BASE', $_SERVER['PHP_SELF'] . '?module=' . $_REQUEST['module']);
	require_once OCC_MODULE_ID . '/' . $_REQUEST['action'];
	exit;
}

exit;

?>
