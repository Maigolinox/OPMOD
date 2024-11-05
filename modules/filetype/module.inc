<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Module ID - needs to match directory name
$moduleId = 'filetype';

// Module info
$OC_modulesAR[$moduleId] = array(
	'name'			=> 'File Format Check',
	'description'	=> 'Verifies file uploaded matches selected format',
	'version'		=> '3.1.1',
	'dependencies'	=> array(),
	'developer'		=> 'OpenConf'
);


// Checks $data for $sig from $offset
function mod_filetype_checksig($data, $sig, $offset=0, $case=1) {
	if (($case && (strpos($data, $sig) === $offset))
		|| (! $case && stripos($data, $sig) === $offset)
	) {
		return(true);
	}
	return(false);
}


