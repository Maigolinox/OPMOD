<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$OC_translate = false; // do not translate

require_once '../include.php';
require_once OCC_REVIEW_INC_FILE;

beginChairSession();

printHeader('Review Fields Guide', 1);

foreach ($OC_reviewQuestionsAR as $k => $v) {
	print '<p><strong>' . safeHTMLstr($v['short']) . '</strong></p>';
	if (($k != 'sessions') && isset($v['values']) && is_array($v['values'])) {
		print '<ul>';

		foreach ($v['values'] as $kk => $vv) {
			if ($v['usekey']) {
				print '<li>' . $kk . ': ' . $vv . '</li>';
			} else {
				print '<li>' . $vv . '</li>';
			}
		}
	
		print '</ul>';
	}
}

print '
<p><strong>Review Completed</strong></p>
<ul>
<li>T: True/Yes</li>
<li>F: False/No</li>
</ul>
';


printFooter();

?>
