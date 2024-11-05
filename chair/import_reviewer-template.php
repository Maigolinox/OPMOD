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

require_once 'export.inc';

require_once OCC_FORM_INC_FILE;
require_once OCC_COMMITTEE_INC_FILE;

$fieldAR = array(
	'id' => 'id',
	'onprogramcommittee' => 'onprogramcommittee',
	'password' => 'password'
);

foreach ($OC_reviewerFieldAR as $f => $far) { 
	if (preg_match("/^password/", $f)) { continue; }
	$fieldAR[$f] = str_replace("\"", "\"\"", $far['short']);
}

oc_export_headers('committee-import-template.csv', 'csv');
print '"' . strtoupper(implode('","', $fieldAR)) . '"' . "\r\n";
exit;

?>