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

require_once 'email.inc';

print '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Email Variables</title>
</head>
<body>
<style type="text/css">
body { font-family: arial, verdana, sans-serif; font-size: 9pt; }
table { margin-left: 20px; }
td { font-family: arial, verdana, sans-serif; font-size: 9pt; }
td:first-child { padding-right: 20px; white-space: nowrap; vertical-align: top; }
</style>
<script language="Javascript" type="application/javascript">
var varsAR = Array();
';

$varsAR = array();
foreach ($recipients as $rName => $rID) {
	$table = '<table>';
	foreach ($recipientAR[$rName]['vars'] as $varID => $var) {
		$table .= '<tr><td>[:' . safeHTMLstr($varID) . ':]</td><td>' . safeHTMLstr($var) . '</td></tr>';
	}
	$table .= '</table>';
	$varsAR[$rName] = array('text' => $recipientAR[$rName]['text'], 'table' => $table);
	print 'varsAR["' . safeHTMLstr($rName) . '"] = "<p><b>' . safeHTMLstr($recipientAR[$rName]['text']) . '</b></p>' . $table . '";' . "\n";
}

print '
function updateVars(group) {
	document.getElementById("varDiv").innerHTML = varsAR[group];
}
</script>
';

if (isset($_GET['l']) && ($_GET['l'] == 'all')) {
	foreach ($varsAR as $rAR) {
		print '<p><b>' . safeHTMLstr($rAR['text']) . '</b></p>' . $rAR['table'];
	}
} else {
	print '<form><select id="group" onchange="updateVars(this.value)">';
	$first = current($varsAR);
	foreach ($varsAR as $varID => $varAR) {
		print '<option value="' . safeHTMLstr($varID) . '">' . safeHTMLstr($varAR['text']) . '</option>';
	}
	print '</select></form><div id="varDiv" aria-live="polite"><p><b>' . safeHTMLstr($first['text']) . '</b></p>' . $first['table'] . '</div>';
}

print '<p><b>General</b></p><table>';

foreach ($OC_emailVarAR['general'] as $varID => $var) {
	print '<tr><td>[:' . safeHTMLstr($varID) . ':]</td><td>' . safeHTMLstr($var) . '</td></tr>';
}

print '
</table>
</body>
</html>
';

?>