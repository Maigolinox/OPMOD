<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once "../include.php";

printHeader("OpenConf Install Completed",3);

if (defined('OCC_INSTALL_COMPLETE') && OCC_INSTALL_COMPLETE) {
	warn('Installation was previously completed');
	exit;
}

// Set install status to complete
if (!$fp=fopen(OCC_CONFIG_FILE,'r')) {
	err("Unable to open config.php for reading");
}
if (!$optionFile = fread($fp,filesize(OCC_CONFIG_FILE))) {
	fclose($fp);
	err("Unable to read from config.php");
}
fclose($fp);
replaceConstantValue('OCC_INSTALL_COMPLETE', 1, $optionFile);
if (!$fp=fopen(OCC_CONFIG_FILE,'w')) {
	err("Unable to open config.php for writing");
}
if (!fwrite($fp,$optionFile)) {
	fclose($fp);
	err("Unable to write config.php");
}
fclose($fp);


print '
<p>Congratulations, you have completed the OpenConf installation!</p>
<p><a href="../">Proceed to your OpenConf Home Page</a></p>
<p style="text-align: center"><img src="//www.openconf.com/images/openconf-install.gif" alt="OpenConf logo" title="OpenConf" /></p>
';

clearstatcache();

if (!is_writable($OC_configAR['OC_paperDir'])) {
print '
<p class="note">NOTE: Before accepting file uploads, you will need to change permissions of the file upload directory (default: data/papers/) so that the Web (HTTP) server process has read-write privileges.</p>
';
}

printFooter();

?>
