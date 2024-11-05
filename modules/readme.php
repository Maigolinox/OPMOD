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

beginChairSession();

if (isset($_GET['m']) && oc_moduleValid($_GET['m']) && is_file($_GET['m'] . '/README.html') && ($readme = file_get_contents($_GET['m'] . '/README.html'))) {
    if (preg_match("/^(.*)?\s*<hr/s", $readme, $matches)) {
        print $matches[1] . '
</body>
</html>';
    } else {
        print $readme;
    }
} else {
	print 'Unable to access module information';
}

?>