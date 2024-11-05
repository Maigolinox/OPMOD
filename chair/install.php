<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once "install-include.php";

printHeader($hdr,$hdrfn);

print '
<div style="text-align: center; margin: 0 auto; width: 700px;">
<p><strong>Welcome to OpenConf!  This appears to be a new install, so we will take you through the set-up and configuration of your OpenConf system.  <em>Following are the steps to install OpenConf:</em></strong></p>

<p>
<span style="white-space: nowrap;">1. Enter Database Settings</span>
&nbsp; &#8211;&gt; &nbsp;
<span style="white-space: nowrap;">2. Create ' . OCC_WORD_CHAIR . ' Account</span>
&nbsp; &#8211;&gt; &nbsp;
<span style="white-space: nowrap;">3. Tailor Configuration Settings</span>
&nbsp; &#8211;&gt; &nbsp;
<span style="white-space: nowrap;">4. Set Topics</span>
&nbsp; &#8211;&gt; &nbsp;
<span style="white-space: nowrap;">5. Open Submissions & Sign-Up/In</span>
</p>

';

function stripDDS($f) {
	return(preg_replace("/\.\.\//","",$f));
}

$e = "";
clearstatcache();
if ((is_file(OCC_CONFIG_FILE) && !is_writable(OCC_CONFIG_FILE)) && (!defined('OCC_DB_NAME') || (OCC_DB_NAME == ''))) {
	print '
<p><span class="warn">Before proceeding, you must allow write privilege by the Web server (HTTP) process to the config.php files.</span></p>
';
} else {
	print '
<form method="post" action="install-license.php">
<p><strong>When you are ready to proceed, read the OpenConf License below and click the <em>I Agree</em> button to indicate your agreement to its terms:</strong></p>
<p style="text-align: center"><textarea name="license" style="width: 680px; height: 320px; background-color: #eee; padding: 5px;">';
	readfile('../docs/LICENSE');
	print '</textarea><br />
<p style="text-align: center"><input type="submit" name="submit" class="submit" value="I Agree to the OpenConf License Terms" /></p>
</form>
';
}

print '
<br />
<p><span class="note">If you have already installed OpenConf but are still seeing this page, change the value of OCC_INSTALL_COMPLETE in config.php</span></p>
</div>
';

printFooter();

?>
