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

if (!validToken('chair') || !isset($_POST['maxauthors']) || !preg_match("/^[0-9]+$/", $_POST['maxauthors'])) {
	warn('Invalid request', 'Submission Import', 0);
}


require_once 'export.inc';

require_once OCC_FORM_INC_FILE;
require_once OCC_SUBMISSION_INC_FILE;

$fieldAR = array(
	'paperid'    		=> 'Submission ID',
	'password'			=> 'Password',
	'submissiondate'	=> 'Submission Date',
    'accepted'          => 'Acceptance',
	'pcnotes'			=> 'Chair Notes'
);

$authorFieldAR = array();

foreach ($OC_submissionFieldSetAR as $fsk=>$fsv)  {
	foreach ($fsv['fields'] as $f) { 
		if (preg_match("/^password/", $f) || empty($OC_submissionFieldAR[$f]['name']) || ($OC_submissionFieldAR[$f]['type'] == 'file')) { continue; }
		if ($fsk == 'fs_authors') {
			$authorFieldAR[] = $f;
		} else {
			$fieldAR[$f] = str_replace("\"", "\"\"", $OC_submissionFieldAR[$f]['short']); 
		}
	}
}

for ($author=1; $author<=$_POST['maxauthors']; $author++) {
	foreach ($authorFieldAR as $f) {
		$fieldAR[$f . $author] = OCC_WORD_AUTHOR . ' ' . $author . ' ' . str_replace("\"", "\"\"", $OC_submissionFieldAR[$f]['short']);
	}
}

oc_export_headers('submissions-import-template.csv', 'csv');
print '"' . strtoupper(implode('","', $fieldAR)) . '"' . "\r\n";
exit;

?>