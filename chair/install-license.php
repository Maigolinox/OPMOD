<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once "install-include.php";

if (isset($_POST['submit'])) {
	/* DO NOT MODIFY OR CIRCUMVENT THE CODE ON THIS PAGE */if ($_POST['submit'] == "I Agree to the OpenConf License Terms"){if((OCC_LICENSE != 'Public') && ini_get('allow_url_fopen') && ($u = ocGetFile('http://www.openconf.com/licc.php?l='.urlencode(OCC_LICENSE).'&s='.urlencode(OCC_BASE_URL)))){$u = trim($u);if ($u==2){warn('The OpenConf License only permits a single installation of the OpenConf software.  This license appears to have been previously installed elsewhere.  Please purchase a new license prior to installation.  If you are moving the software or believe this message was received in error, please <a href="https://www.OpenConf.com/contact/">contact</a> OpenConf support.', $hdr, $hdrfn);}elseif ($u==3){warn('The OpenConf License installed is not valid.  Please <a href="https://www.OpenConf.com/contact/">contact</a> OpenConf support for assistance.', $hdr, $hdrfn);}}header("Location: " . OCC_BASE_URL . "chair/install-db.php");
	} else {
		printHeader($hdr,$hdrfn);
		print '<p style="text-align:center" class="err">You must agree to the OpenConf License in order to install or use this software.</p>';
	}
} else {
	header("Location: install.php");
}

printFooter();
?>
