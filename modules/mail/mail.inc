<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Use $hdr if set?
$usehdr = 1; // 0=no, 1=set Reply-To to $hdr:From, 2=set Reply-To and From to $hdr:From

$mod_mail = $GLOBALS['mod_mail_phpmailer'];

// Setup PHPMailer if not already configured
if ($mod_mail->Mailer != 'smtp') {
	// From name/address to use
	if (empty($OC_configAR['MOD_MAIL_from_name'])) {
		$from_name = $OC_configAR['OC_confName'];
	} else {
		$from_name = $OC_configAR['MOD_MAIL_from_name'];
	}

	if (empty($OC_configAR['MOD_MAIL_from_email'])) {
		$from_email = $OC_configAR['OC_pcemail'];
	} else {
		$from_email = $OC_configAR['MOD_MAIL_from_email'];
	}

	// $hdr set?
	if ( isset($hdr) && ($usehdr > 0) && preg_match("/From: \"?([^\"<]+)\"?\s?<([^>]+)>/", $hdr, $matches) && !empty($matches[1]) && validEmail($matches[2])) {
		$reply_name = $matches[1];
		$reply_email = $matches[2];
		if ($usehdr == 2) {
			$from_name = $reply_name;
			$from_email = $reply_email;
		}
	} else {
		$reply_name = $from_name;
		$reply_email = $from_email;
	}
	
	// Setup
	$mod_mail->isSMTP();
	$mod_mail->Host = $OC_configAR['MOD_MAIL_smtp_host'];
	$mod_mail->Port = $OC_configAR['MOD_MAIL_smtp_port'];
	if (!empty($OC_configAR['MOD_MAIL_smtp_username']) && !empty($OC_configAR['MOD_MAIL_smtp_password'])) {
		if (!isset($GLOBALS['mod_mail_smtp_password'])) {
			$GLOBALS['mod_mail_smtp_password'] = oc_decrypt($OC_configAR['MOD_MAIL_smtp_password']);
		}
		$mod_mail->SMTPAuth = true;
		$mod_mail->Username = $OC_configAR['MOD_MAIL_smtp_username'];
		$mod_mail->Password = $GLOBALS['mod_mail_smtp_password'];
	} elseif (defined('MOD_MAIL_SMTP_USERNAME') && defined('MOD_MAIL_SMTP_PASSWORD')) {
		$mod_mail->SMTPAuth = true;
		$mod_mail->Username = MOD_MAIL_SMTP_USERNAME;
		$mod_mail->Password = MOD_MAIL_SMTP_PASSWORD;
	} else {
		$mod_mail->SMTPAuth = false;
	}
	if ($OC_configAR['MOD_MAIL_smtp_encryption'] != 'none') {
		$mod_mail->SMTPSecure = $OC_configAR['MOD_MAIL_smtp_encryption'];
	}
	$mod_mail->setFrom($from_email, $from_name, ($OC_configAR['MOD_MAIL_return_path'] ? true : false));
	$mod_mail->addReplyTo($reply_email, $reply_name);
	$mod_mail->CharSet = 'UTF-8';
	$mod_mail->WordWrap = $OC_configAR['OC_emailWrap'];
	if ($GLOBALS['mod_mail_debug']) {
		$mod_mail->SMTPDebug = $GLOBALS['mod_mail_debug'];
		$mod_mail->Debugoutput = 'html';
	}
	$GLOBALS['mod_mail_phpmailer'] = $mod_mail;
}

// Addresse(es)
if ($to_emails = preg_split("/,/", $to)) {
	foreach($to_emails as $address) {
		$mod_mail->addAddress($address);
	}
} else {
	$mod_mail->addAddress($to);
}

// Subject & Body
$mod_mail->Subject = $subject;
$mod_mail->Body = $body;

// Send it
if ($mod_mail->send()) {
	$mod_mail->clearAddresses();
    return(true);
} else {
	$mod_mail->clearAddresses();
	oc_logit('email-smtp', 'SMTP Failure to ' . $to . ' -- ' . $mod_mail->ErrorInfo);
    return(false);
}
