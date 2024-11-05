<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// SMTP Auth -- server account credentials, define below to avoid storing in database
# define('MOD_MAIL_SMTP_USERNAME', '');
# define('MOD_MAIL_SMTP_PASSWORD', '');

use PHPMailer\PHPMailer\PHPMailer;	// comment out or remove if PHPMailer <= 5.2
use PHPMailer\PHPMailer\Exception;	// comment out or remove if PHPMailer <= 5.2

// Module ID - needs to match directory name
$moduleId = 'mail';

// Module info
$OC_modulesAR[$moduleId] = array(
	'name'			=> 'Mail',
	'description'	=> 'Provides support for using an external SMTP server',
	'version'		=> '1.0.1',
	'dependencies'	=> array(),
	'developer'		=> 'OpenConf'
);

// PHPMailer plugin directory
if (!defined('MOD_MAIL_PHPMAILER_DIR')) {
	define('MOD_MAIL_PHPMAILER_DIR', OCC_PLUGINS_DIR . 'PHPMailer/');
}

// PHPMailer loader
$GLOBALS['mod_mail_phpmailer'] = false;
$GLOBALS['mod_mail_debug'] = 0;
if ($OC_configAR['MOD_MAIL_mailer'] == 'smtp') {
	if (is_file(MOD_MAIL_PHPMAILER_DIR . 'src/PHPMailer.php')) {			// >= 6
		require_once MOD_MAIL_PHPMAILER_DIR . 'src/PHPMailer.php';
		require_once MOD_MAIL_PHPMAILER_DIR . 'src/Exception.php';
		require_once MOD_MAIL_PHPMAILER_DIR . 'src/SMTP.php';
	} elseif (is_file(MOD_MAIL_PHPMAILER_DIR . 'PHPMailer.php')) {			// >= 6 (no src/)
		require_once MOD_MAIL_PHPMAILER_DIR . 'PHPMailer.php';
		require_once MOD_MAIL_PHPMAILER_DIR . 'Exception.php';
		require_once MOD_MAIL_PHPMAILER_DIR . 'SMTP.php';
	} elseif (is_file(MOD_MAIL_PHPMAILER_DIR . 'PHPMailerAutoload.php')) {	// <= 5.2	| comment out use statements above
		require_once MOD_MAIL_PHPMAILER_DIR . 'PHPMailerAutoload.php';
	} else {
		err('Mail SMTP set, however plugin not found; see Mail module info page for requirements');
	}
	$GLOBALS['mod_mail_phpmailer'] = new PHPMailer;
}
