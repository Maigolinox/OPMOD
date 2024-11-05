<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$hdr = "OpenConf Install";
$hdrfn = 4;

require_once "../include.php";

oc_sendNoCacheHeaders();

if (defined('OCC_INSTALL_COMPLETE') && OCC_INSTALL_COMPLETE) {
        printHeader($hdr,$hdrfn);
        print '<span class="warn">Install has already been completed.  To go through the installation again, first reset OCC_INSTALL_COMPLETE in config.php, then visit/reload this page again.  Otherwise, proceed to the <a href="../">OpenConf Home Page</a></span>';
        printFooter();
        exit;
}

?>
