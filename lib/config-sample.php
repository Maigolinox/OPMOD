<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Session Var Name - should be unique to each instance of OpenConf
define('OCC_SESSION_VAR_NAME', 'OPENCONF');

// Installation complete? (0=No, 1=Yes)
define('OCC_INSTALL_COMPLETE', 0);

// Chair password may be used in place of reviewer/author pwd, and Chair may edit profile/submission
define('OCC_CHAIR_PWD_TRUMPS', 1);

// Enable advanced configuration
define('OCC_ADVANCED_CONFIG', 1);

// Encryption key
define('OCC_ENC_KEY', '');

//// DATABASE

// Database user
define('OCC_DB_USER', '');

// Database password
define('OCC_DB_PASSWORD', '');

// Database hostname
define('OCC_DB_HOST', '');

// Database port
define('OCC_DB_PORT', 3306);

// Database name
define('OCC_DB_NAME', '');

// Table prefix
define('OCC_DB_PREFIX', '');

// Use SSL
define("OCC_DB_USE_SSL", 0);

// Database SSL settings
if (OCC_DB_USE_SSL == 1) {
	// Do not verify SSL certificate
	define("OCC_DB_SSL_NOVERIFY", 1);
	
	// SSL Key File
	define("OCC_DB_SSL_KEY", '');
	
	// SSL Certificate File
	define("OCC_DB_SSL_CERT", '');
	
	// SSL CA File
	define("OCC_DB_SSL_CA", '');
	
	// SSL CA Directory Path
	define("OCC_DB_SSL_CAPATH", '');
	
	// SSL Cipher List
	define("OCC_DB_SSL_CIPHER", '');
}
