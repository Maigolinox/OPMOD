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

printHeader("Unassign Reviews", 1);

// Check for valid submission
if (!validToken('chair')) {
	warn('Invalid submission');
}

if (isset($_POST['submit']) && ($_POST['submit'] == "Unassign Reviews") && isset($_POST['drop']) && is_array($_POST['drop']) && (count($_POST['drop']) > 0)) {
	foreach ($_POST['drop'] as $val) {
		if (preg_match("/^\d+,\d+$/",$val)) {
			list($pid,$rid) = explode(",", $val);
			oc_deleteAssignments($pid, $rid);
		} else {
			print "Unable to process " . safeHTMLstr($val) . ".<p>\n";
		}
	}
	print "<p>Reviews have been unassigned.</p>\n";
	if (($_POST['src']=="p") || ($_POST['src']=="r")) {
		if (($miv = ini_get('max_input_vars')) && (count($_POST['drop']) > ($miv - 10))) {
			print '<p class="warn">The number of reviews selected for unassignment may have been greater than supported by this server. Click the link below as additional passes may be required.</p>';
		}
		print '<a href="list_reviews.php">Return to Review Listings</a><p>';
	}
}
else {
	print '<p class="warn">Please <a href="list_reviews.php">return</a> and select the reviews to unassign.</p>';
}

printFooter();

?>
