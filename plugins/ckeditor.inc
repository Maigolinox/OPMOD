<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// replace CKEditor fields
function oc_replaceCKEditor($fieldAR, $cssFix=true, $width=600, $height=0) {
	if (isset($GLOBALS['OC_ckConfig']) && !empty($GLOBALS['OC_ckConfig'])) {
		print '
<script type="text/javascript" language="javascript">
<!--
window.addEventListener("load", function(event) {
';
		foreach ($fieldAR as $fld) {
			print 'CKEDITOR.replace("' . $fld . '", { width: "' . $width . '", ' . (($height > 0) ? ('height: "' . $height . '", ') : '') . 'customConfig: "' . safeHTMLstr($GLOBALS['OC_ckConfig']) . '" } );' . "\n";
		}
		print '
}, false);
// -->
</script>
';
		if ($cssFix) {
			print '
<style>
<!-- /* Fix for CKEditor display within fieldset */
.cke_skin_kama { /* CKE 3 */
border: 0;
padding: 0 !important;
float: left;
margin-bottom: 1em;
}
.cke_chrome { /* CKE 4 */
float: left;
margin-bottom: 1em;
}
-->
</style>
';
		}
	}
}


// Setup CK
if (is_file(OCC_PLUGINS_DIR . 'ckeditor/ckeditor.js')) {
	oc_addHeader('<script type="text/javascript" src="' . OCC_PLUGINS_DIR . 'ckeditor/ckeditor.js"></script>');
	$OC_ckConfig = '../ckeditor_local_openconf.js';
} else {
	oc_addHeader('<script type="text/javascript" src="//cdn.ckeditor.com/4.19.0/full/ckeditor.js"></script>');
	$OC_ckConfig = OCC_BASE_URL . 'plugins/ckeditor_cdn_openconf.js';
}
