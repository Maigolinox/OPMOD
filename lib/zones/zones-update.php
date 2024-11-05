<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// This program generates the zone.php file used by OpenConf.
// You should only run it if there has been a zone update not yet reflected in zones.php

$oczoneFile = 'zones.php'; // output

$zoneAR = array();
$tzids = DateTimeZone::listIdentifiers();
foreach ($tzids as $tz) {
    $zoneAR[] = $tz;
}

if (count($zoneAR) < 50) {
	die('ERROR: Failed retrieving zones');
}

sort($zoneAR);

$newfile = '<?php

// OpenConf Zone List
//
// Update manually or use zones-update.php 

$OC_zoneAR = array(
	\'' . implode("',\n\t'", $zoneAR) . '\'
);

';

$fp = fopen($oczoneFile, 'w') or die('ERROR: Unable to open output file');
fwrite($fp, $newfile);
fclose($fp);

print "Zone file updated\n";

?>