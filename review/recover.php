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

beginSession();

// Make sure we have a submit
if (!isset($_SESSION[OCC_SESSION_VAR_NAME]['POST']['submit'])) {
    header('Location: reviewer.php?' . strip_tags(SID));
	exit;
}

// Types of submit we accept and where to redirect them
$whatLocAR = array(
	'Review' => 'review.php',
	'Recommendation' => 'advocate.php'
);

// Submit type
$what = substr($_SESSION[OCC_SESSION_VAR_NAME]['POST']['submit'], (strrpos($_SESSION[OCC_SESSION_VAR_NAME]['POST']['submit'], " ") + 1));

// Valid submit type?
if (!in_array($what, array_keys($whatLocAR))) {
    header('Location: reviewer.php?' . strip_tags(SID));
	exit;
}

ob_start();
printHeader("Recover Submission", 2);

// Display form with current POST values
print '<p style="font-weight: bold">' . oc_('Your session timed out prior to your submission being completed.  Would you like to submit it now?') . '</p>
<dl><dd>

<form method="post" action="' . safeHTMLstr($whatLocAR[$what]) . '">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['actoken'] . '" />
<input type="hidden" name="ocaction" value="Submit ' . safeHTMLstr($what) . '" />
';

// Undefine submit so it doesn't get included
unset($_SESSION[OCC_SESSION_VAR_NAME]['POST']['submit']);

foreach ($_SESSION[OCC_SESSION_VAR_NAME]['POST'] as $k => $v) {
	if ($k == 'token') { // skip old token
		continue;
	}
	if (is_array($v)) {
	    foreach ($v as $vv) {
			print '<input type="hidden" name="' . safeHTMLstr($k) . '[]" value="' . safeHTMLstr($vv) . '" />' . "\n";
		}
	} else {
		print '<input type="hidden" name="' . safeHTMLstr($k) . '" value="' . safeHTMLstr($v) . '" />' . "\n";
	}
}

// Undefine session POST var so user not asked again later
unset($_SESSION[OCC_SESSION_VAR_NAME]['POST']);

ob_end_flush();

print '
<input type="submit" name="submit" class="submit" value="' . oc_('Submit') . '" />
</form>
<br /><br />
<form method="get" action="reviewer.php">
<input type="submit" value="No, Thanks" />
</form>

</dd></dl>
<br /><br />
';

printFooter();

?>
