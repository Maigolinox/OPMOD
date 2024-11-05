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

printHeader("Email Username", 3);

if (! $OC_configAR['OC_chairUsernameForgot']) {
	print '<p class="err" style="text-align: center">Functionality disabled</p>';
}
elseif (isset($_POST['submit']) && ($_POST['submit'] == "Email Username")) {
	$msg = '
The ' . OCC_WORD_CHAIR . ' username for accessing the ' . $OC_configAR['OC_confName'] . ' OpenConf system is:

	' . $OC_configAR['OC_chair_uname'] . '
';
	if (sendEmail($OC_configAR['OC_pcemail'], OCC_WORD_CHAIR . " username", $msg)) {
		print '<p class="note2" style="text-align: center">The ' . OCC_WORD_CHAIR . ' username has been emailed to the ' . OCC_WORD_CHAIR . '\'s address</p><p style="text-align: center"><a href="signin.php">Proceed to sign in</a></p>';
	} else {
		err('Unable to send email');
	}
} else {
	print '
<p class="note2" style="text-align: center">Click the button below to email the username to the ' . OCC_WORD_CHAIR . '\'s address</p>
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<p style="text-align: center"><input type="submit" name="submit" class="submit"  value="Email Username"></p>
</form>
';
}

printFooter();

?>
