<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once 'include.php';

if (!empty($OC_configAR['OC_privacy_link'])) { // link redirect even if feature disabled
	header("Location: " . $OC_configAR['OC_privacy_link']);
	exit;
} elseif (preg_match("/^[12]$/", $OC_configAR['OC_privacy_display'])) { // display policy if feature enabled
	$hdrfn = 3; // default to author or other
	if (isset($_GET['f'])) {
		if ($_GET['f'] == 2) { // committee member
			beginSession();
			$hdrfn = 2;
		} elseif ($_GET['f'] == 1) { // chair
			beginChairSession();
			$hdrfn = 1;
		}			
	}
	printHeader(oc_('Privacy Policy'), $hdrfn);
	list($none, $OC_privacy_policy) = oc_getTemplate('privacy_policy');
	print $OC_privacy_policy;
	printFooter();
} else { // display disabled
	printHeader('Error', 0);
	print '<p>' . oc_('This feature is currently disabled') . '</p>';
	printFooter();
}

?>