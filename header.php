<?php 

foreach ($GLOBALS['OC_httpHeaders'] as $httpHeader) {
	header($httpHeader);
}

?><!DOCTYPE html>
<html lang="<?php echo $GLOBALS['OC_locale']; ?>"<?php echo (OCC_LANGUAGE_LTR ? '' : ' dir="rtl"'); ?>>
<head>
<meta charset="utf-8">
<title><?php echo (isset($GLOBALS['OC_configAR']['OC_confName']) ? (safeHTMLstr($GLOBALS['OC_configAR']['OC_confName']) . ' - ') : '') . safeHTMLstr(sprintf(oc_('%s Abstract Submission, Peer Review, and Event Management System'), 'OpenConf')); ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['pfx']; ?>openconf.css?v=11" />
<script type="text/javascript" src="<?php echo $GLOBALS['pfx']?>openconf.js?v=9"></script>
<?php 
if ( defined('OCC_LANGUAGE_LTR') && ( ! OCC_LANGUAGE_LTR ) ) {
	echo '<link rel="stylesheet" type="text/css" href="' . $GLOBALS['pfx'] . 'openconf-rtl.css?v=8" />'."\n";
}
foreach ($GLOBALS['OC_cssAR'] as $file) {
	echo '<link rel="stylesheet" type="text/css" href="' . $GLOBALS['pfx'] . $file . '" />'."\n";
}
foreach ($GLOBALS['OC_jsAR'] as $file) {
	echo '<script type="text/javascript" src="' . $GLOBALS['pfx'] . $file . '" /></script>'."\n";
}
if (isset($GLOBALS['OC_configAR']['OC_privacy_banner_options']) && preg_match('/"display":"?1/', $GLOBALS['OC_configAR']['OC_privacy_banner_options'])) {
	require_once OCC_PLUGINS_DIR . 'privacy_banner.inc';
}
echo implode("\n", $GLOBALS['OC_extraHeaderAR']);
?>
</head>
<body onload="<?php echo implode(';', $GLOBALS['OC_onloadAR']); ?>">
<div class="ocskip"><a href="#mainbody">Skip to main content</a></div>
<div class="conf" role="heading"><?php
if (isset($GLOBALS['OC_configAR']['OC_headerImage']) && !empty($GLOBALS['OC_configAR']['OC_headerImage'])) {
	$confHeader = '<img src="' . $GLOBALS['OC_configAR']['OC_headerImage'] . '" alt="' . safeHTMLstr((isset($GLOBALS['OC_configAR']['OC_confNameFull']) ? $GLOBALS['OC_configAR']['OC_confNameFull'] : $GLOBALS['OC_confNameFull'])) . '" border="0" />';
} else {
	$confHeader = safeHTMLstr((isset($GLOBALS['OC_configAR']['OC_confNameFull']) ? $GLOBALS['OC_configAR']['OC_confNameFull'] : $GLOBALS['OC_confNameFull']));
}
if (isset($GLOBALS['OC_configAR']['OC_confURL']) && !empty($GLOBALS['OC_configAR']['OC_confURL'])) {
	echo '<a href="' . safeHTMLstr($GLOBALS['OC_configAR']['OC_confURL']) . '" class="confName">' . $confHeader . '</a>';
} else {
	echo $confHeader;
}
?></div>
