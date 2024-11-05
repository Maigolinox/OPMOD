<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// display CAPTCHA
function mod_captcha_displayCAPTCHA() {
	if (OC_CAPTCHA_service == 'hCaptcha') {
		print '
<script src="https://www.hCaptcha.com/1/api.js?hl=' . urlencode($GLOBALS['OC_locale']) . '" async defer></script>
<div class="h-captcha" data-sitekey="' . safeHTMLstr(OC_CAPTCHA_public_key) . '"></div>
';
	} elseif (OC_CAPTCHA_service == 'reCAPTCHA') {
		print '
<script src="https://www.google.com/recaptcha/api.js?hl=' . $GLOBALS['OC_locale'] . '" async defer></script>
<div class="g-recaptcha" data-sitekey="' . safeHTMLstr(OC_CAPTCHA_public_key) . '"></div>
<noscript>
	<div style="width: 302px; height: 352px;">
	<div style="width: 302px; height: 352px; position: relative;">
		<div style="width: 302px; height: 352px; position: absolute;">
		<iframe src="https://www.google.com/recaptcha/api/fallback?k=' . safeHTMLstr(OC_CAPTCHA_public_key) . '" frameborder="0" scrolling="no" style="width: 302px; height:352px; border-style: none;" title="CAPTCHA">
		</iframe>
		</div>
		<div style="width: 250px; height: 80px; position: absolute; border-style: none; bottom: 21px; left: 25px; margin: 0px; padding: 0px; right: 25px;">
		<textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px; height: 80px; border: 1px solid #c1c1c1; margin: 0px; padding: 0px; resize: none;" value="">
		</textarea>
		</div>
	</div>
	</div>
</noscript>
';
	}
}

// verify CAPTCH - returns false if verifies OK, otherwise error string
function mod_captcha_verifyCAPTCHA() {
	if (OC_CAPTCHA_service == 'hCaptcha') {
		$mod_captcha_var = 'h-captcha-response';
		$mod_catpcha_url = 'https://hcaptcha.com/siteverify';
	} elseif (OC_CAPTCHA_service == 'reCAPTCHA') {
		$mod_captcha_var = 'g-recaptcha-response';
		$mod_catpcha_url = 'https://www.google.com/recaptcha/api/siteverify';
	} else {
		err('invalid CAPTCHA');
	}

	if (isset($_POST[$mod_captcha_var]) && !empty($_POST[$mod_captcha_var])) {
		$mod_catpcha_url .= '?secret=' . urlencode(OC_CAPTCHA_private_key) . '&response=' . urlencode($_POST[$mod_captcha_var]) . '&remoteip=' . urlencode($_SERVER['REMOTE_ADDR']);
		if ($response = file_get_contents($mod_catpcha_url)) {
			if ($responseAR = json_decode($response, true)) {
				if (trim($responseAR['success']) == true) {
					return(false);  // OK
				} elseif (isset($responseAR['error-codes'])) {
					$mod_captcha_err = (is_array($responseAR['error-codes']) ? implode(',', $responseAR['error-codes']) : $responseAR['error-codes']);
				} else {
					$mod_captcha_err = 3;
				}
			} else {
				$mod_captcha_err = 4;
			}
		} else {
			$mod_captcha_err = 5;
		}
	}

	return(
		oc_('CAPTCHA verification failed') . 
		(empty($mod_captcha_err) ? '' : (' (' . $mod_captcha_err . ')'))
	);
}
