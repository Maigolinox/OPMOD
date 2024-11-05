<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Display banner
if ($oc_bannerAR = json_decode($GLOBALS['OC_configAR']['OC_privacy_banner_options'], true)) {

	$oc_banner_popupBG = '#252e39';		// Banner background color
	$oc_banner_buttonBG = '#99ccff';		// Button background color
	
	$oc_cookieConsentJS = OCC_PLUGINS_DIR . 'cookieconsent/build/cookieconsent.min.js';		// CookieConsent .js - if not present, CDN used instead
	$oc_cookieConsentCSS = OCC_PLUGINS_DIR . 'cookieconsent/build/cookieconsent.min.css';	// CookieConsent .css
	$oc_cookieConsentBaseURL = '//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.1/';	// Base URL for CDN
	
	if ($GLOBALS['OC_configAR']['OC_privacy_display'] > 0) {
		if (!isset($oc_bannerAR['link']) || empty($oc_bannerAR['link'])) {
			$oc_bannerAR['link'] = oc_('Privacy Policy');
		}
		if (!isset($oc_bannerAR['href']) || empty($oc_bannerAR['href'])) {
			$oc_bannerAR['href'] = OCC_BASE_URL . 'privacy_policy.php';
		}
	}
	$oc_useBannerAR = array(
		'palette' => array(
			'popup' => array(
				'background' => $oc_banner_popupBG,
				'text' => '#ffffff'
			),
			'button' => array(
				'background' => $oc_banner_buttonBG,
				'text' => '#000000'
			),
			'highlight' => array(
				'background' => '#cccccc'
			)
		),
		'content' => array(
			'message' => safeHTMLstr(oc_($oc_bannerAR['message'])),
			'dismiss' => safeHTMLstr(oc_($oc_bannerAR['dismiss']))
		),
		'cookie' => array(
			'name' => OCC_SESSION_VAR_NAME . '_cookieconsent',
			'path' => $GLOBALS['OC_basepath'],
			//'domain' => '',
			'expiryDays' => 365
		)
	);
	if (!empty($oc_bannerAR['link'])) {
		$oc_useBannerAR['content']['link'] = oc_($oc_bannerAR['link']);
	} else {
		$oc_useBannerAR['showLink'] = false;
	}
	if (!empty($oc_bannerAR['href'])) {
		$oc_useBannerAR['content']['href'] = $oc_bannerAR['href'];
	}

	// Select where to serve banner code from -- local plugins dir or CDN
	if (is_file($oc_cookieConsentJS)) {
		print '
<script src="' . $oc_cookieConsentJS . '"></script>
<link rel="stylesheet" type="text/css" href="' . $oc_cookieConsentCSS . '" />
';
	} else { // use CDN
		print '
<link rel="stylesheet" type="text/css" href="' . $oc_cookieConsentBaseURL . 'cookieconsent.min.css" />
<script src="' . $oc_cookieConsentBaseURL . 'cookieconsent.min.js"></script>
';
	}
	
	// Banner options
	print '
<style>#powered { margin-bottom: 90px; }</style>
<script>
window.addEventListener("load", function(){
window.cookieconsent.initialise(' . json_encode($oc_useBannerAR) . ')});
</script>
';

	// Ignored, for translation purposes only
	if (false) {
		//T: Leave as X - it indicates closing the privacy banner box
		$ocIgnore = oc_('X');
		$ocIgnore = oc_('This website uses cookies to provide essential services only.');
	}

}
