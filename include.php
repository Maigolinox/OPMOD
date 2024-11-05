<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Report errors only
error_reporting(E_ERROR);

// general config
ini_set('user_agent', 'OpenConf');
if (! ini_get('date.timezone')) {
	ini_set('date.timezone', 'UTC');
}
if (ini_get('session.gc_maxlifetime') < 3600) {
	ini_set('session.gc_maxlifetime', 3600); // may need to be adjusted
}

// Init vars
$OC_confNameFull = 'OpenConf';
$OC_pcemail = '';
$OC_timeStamp = time();
$OC_maxRunTime = ini_get('max_execution_time');
$OC_locale = 'en';
$OC_maxFileSize = toMB(ini_get('upload_max_filesize')); 
$OC_sortImg = '<img src="../images/sort.gif" width="17" height="10" alt="current sort selection" title="current sort selection" />';
$OC_sortImgAsc = '<img src="../images/sortasc.gif" width="17" height="10" alt="current sort selection" title="current sort selection" />';
$OC_translate = true;
$OC_translationExtrasAR = array();
$OC_db = NULL;
$OC_db_error = NULL;
$OC_db_errno = NULL;

// Init arrays
$OC_hooksAR = array();
$OC_cssAR = array();
$OC_jsAR = array();
$OC_extraHeaderAR = array();
$OC_onloadAR = array();
$OC_configAR = array();
$OC_statusAR = array();
$OC_modulesAR = array();
$OC_activeModulesAR = array();
$OC_languageAR = array();
$OC_httpHeaders = array('Content-type: text/html; charset=utf-8');

// Sanitize PHP_SELF
$_SERVER['PHP_SELF'] = htmlspecialchars($_SERVER['PHP_SELF']);

// Baseline version - set for install, updated from db below once installed
$GLOBALS['OC_configAR']['OC_version'] = '7.x'; ###

// Check whether it's home page or a subdir we're in
if (preg_match("/(?:privacy_policy|openconf)\.php/", basename($_SERVER['PHP_SELF']))) {
	$pfx = "";
	$OC_basepath = dirname($_SERVER['PHP_SELF']);
} else {
	$pfx = "../";
	$OC_basepath = dirname(dirname($_SERVER['PHP_SELF']));
}

// Define constants
define('OCC_DB_ENCODING', 'utf8mb4'); // Database encoding
define('OCC_DB_COLLATION', 'utf8mb4_unicode_520_ci'); // Database collation

define('OCC_BASE_URL', 'http' . ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) ? 's' : '') . '://' . safeHTMLstr($_SERVER['SERVER_NAME']) . (ctype_digit($_SERVER['SERVER_PORT']) && (($_SERVER['SERVER_PORT'] != '80')) ? (':' . $_SERVER['SERVER_PORT']) : '') . $OC_basepath . '/');

define('OCC_LIB_DIR', $pfx . 'lib/'); // lib dir

define('OCC_PLUGINS_DIR', $pfx . 'plugins/'); // plugins dir

define('OCC_CONFIG_FILE', $pfx . 'config.php'); // config file location

define('OCC_FORM_INC_FILE', $pfx . 'include-forms.inc'); // forms include file location

define('OCC_SUBMISSION_INC_FILE', $pfx . 'author/submission.inc'); // submission include file

define('OCC_ADVOCATE_INC_FILE', $pfx . 'review/advocate.inc'); // advocate include file

define('OCC_REVIEW_INC_FILE', $pfx . 'review/review.inc'); // review include file

define('OCC_COMMITTEE_INC_FILE', $pfx . 'review/committee.inc'); // review include file

define('OCC_MIME_FILE', OCC_LIB_DIR . 'mime.php'); // mime types file location

define('OCC_ZONE_FILE_EN', OCC_LIB_DIR . 'locale/en/zones.php');

define('OCC_UTF8CASECONV_FILE', OCC_LIB_DIR . 'UTF8CaseConv.php'); // UTF8CaseConv file location

/* DO NOT MODIFY THIS LINE OR OTHERWISE FALSELY DEFINE OR MAKE UP OCC_LICENSE */ (file_exists($pfx . 'license.php') ? require_once($pfx . 'license.php') : define('OCC_LICENSE', 'Public')); // License type /* DO NOT MODIFY THIS LINE */

define('OCC_JQUERY_VERSION', '3.6.0'); // jQuery version
define('OCC_JQUERYUI_VERSION', '1.13.1'); // jQueryUI version - also update oc_customforms.js


// Set OC_formatAR with mime types - moved to OCC_MIME_FILE in 4.00
require_once OCC_MIME_FILE;

// Row Array - used for toggling row style
$rowAR = array();
$rowAR[1] = 2;
$rowAR[2] = 1;

// Yes/No Array
$yesNoAR = array(
	1 => 'Yes',
	0 => 'No'
);

// Status Array
$OC_statusValueAR = array(
	1 => 'Open',
	0 => 'Closed',
);

// Context 
$OC_context = stream_context_create(array('http'=>array('timeout'=>20))); 

// i18n routines
// params: s|ource, d|omain, override-translate even if Chair (i.e.,OC_translate=false)
function oc_($s, $d='', $override=false) {
	if ($s == '') {
		return('');
	}
	if (($GLOBALS['OC_locale'] == 'en') || (!$GLOBALS['OC_translate'] && !$override)) {
		if (isset($GLOBALS['OC_translationExtrasAR'][$GLOBALS['OC_locale']][$s])) {
			return ($GLOBALS['OC_translationExtrasAR'][$GLOBALS['OC_locale']][$s]);
		} else {
			if ( defined('OCC_WORD_AUTHOR') && (OCC_WORD_AUTHOR != 'Author')) {
				$from = array('/Author/', '/author/');
				$to = array(OCC_WORD_AUTHOR, oc_strtolower(OCC_WORD_AUTHOR));
			} else {
				$from = array();
				$to = array();
			}
			if ( defined('OCC_WORD_CHAIR') && (OCC_WORD_CHAIR != 'Chair') ) {
				$from[] = '/(?<![Ss]ession )Chair/';
				$to[] = OCC_WORD_CHAIR;
			}
			if (count($from) > 0) {
				return(preg_replace($from, $to, $s));
			} else {
				return($s);
			}
		}
	} elseif (isset($GLOBALS['OC_translationExtrasAR'][$GLOBALS['OC_locale']][$s])) {
		return ($GLOBALS['OC_translationExtrasAR'][$GLOBALS['OC_locale']][$s]);
	} elseif ( ( $shash = hash("md5", $s) ) && isset($GLOBALS['OC_translationExtrasAR'][$GLOBALS['OC_locale']][$shash])) {
		return ($GLOBALS['OC_translationExtrasAR'][$GLOBALS['OC_locale']][$shash]);
	} elseif (function_exists('gettext')) {
		if (!empty($d)) {
			return(dgettext($d, $s));
		} else {
			return(_($s));
		}
	}
	else { return($s); }
	/*
	elseif (empty($t)) {
		return(T_($s));
	} else {
		return(T_dgettext($domain, $s));
	}
	*/
}
function oc_n($s, $p, $c, $d='') {	// s|ource p|lural c|ount d|omain
	if ($s == '') {
		return('');
	}
	if (function_exists('ngettext')) {
		if (!empty($d)) {
			return(dngettext($d, $s, $p, $c));
		} else {
			return(ngettext($s, $p, $c));
		}
	}
	elseif ($c > 1) { return $p; }
	else { return $s; }
	/*
	elseif (empty($d)) {
		return(T_ngettext($s, $p, $c));
	} else {
		return(T_dngettext($d, $s, $p, $c));
	}
	*/
}

// Returns a string with double-quotes (only) slashes
function slashQuote($s) {
    return(preg_replace('/"/','\\"',$s));
}

// Checks whether the script is close to timing out
function oc_checkTimeout() {
	if (($GLOBALS['OC_timeStamp'] > 0) 
		&& ((time() - $GLOBALS['OC_timeStamp']) > ($GLOBALS['OC_maxRunTime'] - 5))	// timeout if within 5 seconds
	) {
		return TRUE;
	}
	return FALSE;
}

// Returns a string containing define statements with an updated constant value
function replaceConstantValue($constName, $newValue, &$string) {
	$newValue = str_replace("'", "\\'", $newValue);
	$string = preg_replace("/(define\('" . $constName . "',\s?'?).*?('?\);)/", '${1}' . $newValue . '${2}', $string);
}

// Returns true/false on whether a named hook is set
function oc_hookSet($hook) {
	if (isset($GLOBALS['OC_hooksAR'][$hook]) && !empty($GLOBALS['OC_hooksAR'][$hook])) {
		return true;
	} else {
		return false;
	}
}

// Adds a hook for additional functionality; typically used with modules
function oc_addHook($name, $value) {
	if (!isset($GLOBALS['OC_hooksAR'][$name])) {	// init if first hook for name
		$GLOBALS['OC_hooksAR'][$name] = array($value);
	} elseif (!in_array($value, $GLOBALS['OC_hooksAR'][$name])) { // add only if not duplicate
		$GLOBALS['OC_hooksAR'][$name][] = $value;
	}
}

// Add CSS file to be read in by header
function oc_addCSS($file,$moduleId='') {
	if (!empty($moduleId)) {
		$GLOBALS['OC_cssAR'][] = 'modules/' . $moduleId . '/' . $file;	
	} else {
		$GLOBALS['OC_cssAR'][] = $file;
	}
}

// Add JS file to be read in by header
function oc_addJS($file,$moduleId='') {
	if (!empty($moduleId)) {
		$GLOBALS['OC_jsAR'][] = 'modules/' . $moduleId . '/' . $file;	
	} else {
		$GLOBALS['OC_jsAR'][] = $file;
	}
}

// Add body onLoad to be included in header
function oc_addOnLoad($js) {
	$GLOBALS['OC_onloadAR'][] = $js;
}

// Add extra headers
function oc_addHeader($hdr) {
	$GLOBALS['OC_extraHeaderAR'][] = $hdr;
}

// Returns an array of database tables
function getTables() {
	$constAR = get_defined_constants();
	preg_match_all("/(OCC_TABLE_\w+)/",implode('\0',array_keys($constAR)),$tAR);
	foreach ($tAR[0] as $t) { $tableAR[] = constant($t); }
	return($tableAR);
}

// oc_encrypt - encrypts string
function oc_encrypt($s, $key='') {
	$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
	if (empty($key)) {
		$key = ((defined('OCC_ENC_KEY') && (OCC_ENC_KEY != '')) ? OCC_ENC_KEY : pack('H*', md5(OCC_LICENSE))); // fallback for upgrade error
	}
	return(base64_encode($iv) . '|' . base64_encode(openssl_encrypt($s, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv)));
}

// oc_decrypt - decrypts encrypted string
function oc_decrypt($e, $key='') {
	if (empty($key)) {
		$key = ((defined('OCC_ENC_KEY') && (OCC_ENC_KEY != '')) ? OCC_ENC_KEY : pack('H*', md5(OCC_LICENSE))); // fallback for upgrade error
	}
	list($e1, $e2) = explode('|', $e);
	return(trim(openssl_decrypt(base64_decode($e2), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, base64_decode($e1))));
}

// oc_password_hash - hashes password
function oc_password_hash($pw) {
	return(password_hash($pw, PASSWORD_DEFAULT));
}

// oc_password_needs_rehash - checks whether password hash is outdated & new format available
function oc_password_needs_rehash($hash) {
	return(password_needs_rehash($hash, PASSWORD_DEFAULT));
}

// oc_password_verify - verifies password and updates hash if needed
function oc_password_verify($pw, $hash, $type=null, $id=null) {
	$verified = false;
	if (oc_hookSet('password-verify')) {
	   foreach ($GLOBALS['OC_hooksAR']['password-verify'] as $hook) {
			   require_once $hook;
	   }
	} else {
		// verify hash
		if (preg_match("/^\\$/", $hash)) { // new school
			if (password_verify($pw, $hash)) {
				$verified = true;
			}
		} else { // old school
			$salt = substr($hash, 0, 10);
			if ($hash == ($salt . sha1($salt . $pw))) {
				$verified = true;
			}
		}
		// update hash?
		if ($verified && preg_match("/^\w+$/", $type) && oc_password_needs_rehash($hash)) {
			if (($type == 'submission') && preg_match("/^\d+$/", $id)) {
				ocsql_query("UPDATE `" . OCC_TABLE_PAPER . "` SET `password`='" . safeSQLstr(oc_password_hash($pw)) . "' WHERE `paperid`='" . safeSQLstr($id) . "'");
			} elseif (($type == 'committee') && preg_match("/^\d+$/", $id)) {
				ocsql_query("UPDATE `" . OCC_TABLE_REVIEWER . "` SET `password`='" . safeSQLstr(oc_password_hash($pw)) . "' WHERE `reviewerid`='" . safeSQLstr($id) . "'");
			} elseif ($type == 'chair') {
				updateConfigSetting('OC_chair_pwd', oc_password_hash($pw));
			}
		}
		// check for chair pwd if not verified
		if (!$verified && ($type != 'chair') && ($type != 'trump') && OCC_CHAIR_PWD_TRUMPS && oc_password_verify($pw, $GLOBALS['OC_configAR']['OC_chair_pwd'], 'trump')) {
			$verified=true;
		}
	}
	
	return($verified);
}

// oc_password_generate - creates and returns a new random password
function oc_password_generate() {
	$validChars = 'bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ2346789'; // available chars for pwd
	$cmax = strlen($validChars);
	$ctot = rand(10, 14); // pwd length
	$p = ''; // pwd
	$c = 0; // number of chars in pwd
	while ($c < $ctot) {
		$p .= substr($validChars, mt_rand(0, ($cmax - 1)), 1);
		$c++;
	}
	return($p);
}

// Format number
// 	$n = number of bytes
function oc_formatNumber($n) {
	if ($n > 1048576)  { // > 1 MB
		return(number_format(($n/1048576),1) . "MB");
	} else {
		return(number_format(($n/1024),0) . "KB");
	}
}

// Convert units
function toMB($n) {
	if (preg_match("/^(\d+)(\w?)[bB]?$/",$n,$matches)) {
		switch (strtoupper($matches[2])) {
			case '': 
			case 'B': return((($matches[1] >= 105000) ? (number_format(($matches[1]/1048576),1) . "MB") : (number_format(($matches[1]/1024),1) . "KB"))); break;
			case 'K': return((($matches[1] >= 103) ? (number_format(($matches[1]/1024),1) . "MB") : (number_format($matches[1],1) . "KB"))); break;
			case 'M': return($matches[1] . "MB"); break;
		}
	}
	return($n);
}

// Returns the value of a var if it exists in the specified array, or a default value
//		if safe=true and array value exists, it's returned safeHTMLstr()
function varValue($varName, &$ar, $default='', $safe=false) {
	if (isset($ar[$varName])) {
		if ($safe) {
			return(safeHTMLstr($ar[$varName]));
		} else {
			return($ar[$varName]);
		}
	}
	return($default);
}

// Displays page header
function printHeader($what, $function="0") {
	global $pfx;

	// CHANGES TO MENU ITEMS MAY REQUIRE UPDATE TO PLACEMENT OF PRIVACY POLICY BELOW
	$menusAR = array(
		0 => array(	// none of the below
			safeHTMLstr(sprintf(oc_('%s Home'), 'OpenConf')),
			'<a href="' . $pfx . 'author/contact.php" id="oc-menu-email">' . safeHTMLstr(oc_('Email Chair')) . '</a>',
			),
		1 => array(	// chair
			'<a href="' . $pfx . 'chair/" id="oc-menu-chair-home">' . safeHTMLstr(sprintf(oc_('%s Home'), (defined('OCC_WORD_CHAIR') ? OCC_WORD_CHAIR : 'Chair'))) . '</a>',
			'<a href="https://www.openconf.com/support/" id="oc-menu-chair-support" target="_blank" title="OpenConf Support Web Site (new window)">Help</a>',
			'<a href="' . $pfx . 'chair/signout.php" id="oc-menu-chair-signout">Sign Out</a>',
			),
		2 => array(	// committee
			//T: Member Home = Committee Member Home Page (e.g., Reviewer Home Page)
			'<a href="' . $pfx . 'review/reviewer.php" id="oc-menu-committee-home">' . oc_('Member Home') . '</a>',
			'<a href="' . $pfx . 'review/update.php" id="oc-menu-committee-profile">' . safeHTMLstr(oc_('Update Profile')) . '</a>',
			'<a href="mailto:' . safeHTMLstr($GLOBALS['OC_configAR']['OC_pcemail']) . '" id="oc-menu-committee-email">' . oc_('Email Chair') . '</a>',
			'<a href="' . $pfx . 'review/signout.php" id="oc-menu-committee-signout">' . oc_('Sign Out') . '</a>',
			),	
		3 => array(	// author, sign in/up, other
			//T: %s = OpenConf
			'<a href="' . (empty($pfx) ? './' : $pfx) . '" id="oc-menu-home">' . safeHTMLstr(sprintf(oc_('%s Home'), 'OpenConf')) . '</a>',
			'<a href="' . $pfx . 'author/contact.php" id="oc-menu-email">' . safeHTMLstr(oc_('Email Chair')) . '</a>',
			),
		4 => array( // install
			'<a href="install.php">Restart Install</a>',
			),
	);
	$menusFillerAR = array(
		1 => 'Signed in as: ' . safeHTMLstr($GLOBALS['OC_configAR']['OC_chair_uname']),
		2 => (defined('OCC_SESSION_VAR_NAME') ? (safeHTMLstr(sprintf(oc_('Signed in as: %1$s (%2$d)'), safeHTMLstr($_SESSION[OCC_SESSION_VAR_NAME]['acusername']), $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']))) : '')
	);
	
	// privacy policy?
	if ($GLOBALS['OC_configAR']['OC_privacy_display'] == 1) {
		$privacyMenu = '<a href="' . $pfx . 'privacy_policy.php' . (preg_match("/^[12]$/", $function) ? ('?f=' . $function) : '') . '" id="oc-menu-privacy">' . safeHTMLstr(oc_('Privacy Policy')) . '</a>';
		array_splice($menusAR[0], 1, 0, $privacyMenu);
		array_splice($menusAR[1], 1, 0, $privacyMenu);
		array_splice($menusAR[2], 2, 0, $privacyMenu);
		array_splice($menusAR[3], 1, 0, $privacyMenu);
	}

	if (oc_hookSet('header-top-pre')) {
		   foreach ($GLOBALS['OC_hooksAR']['header-top-pre'] as $hook) {
				   require_once $hook;
		   }
	}
	
	require_once $GLOBALS['pfx'] . (isset($GLOBALS['OC_configAR']['OC_headerFile']) ? $GLOBALS['OC_configAR']['OC_headerFile'] : 'header.php');
	
	print '<div class="menuoc">&nbsp;';
	//T: %s = OpenConf
	print sprintf(oc_('%s Peer Review and Submission Management System'), 'OpenConf');
	if ($function == 1) { print ' v' . safeHTMLstr($GLOBALS['OC_configAR']['OC_version']); }
	print '</div>
<nav class="menu">
';
	foreach ($menusAR[$function] as $menuItem) {
		print '	<div class="menuitem">' . $menuItem . '</div>' . "\n";
	}
	if (isset($menusFillerAR[$function])) {
		print '	<div class="menufiller"> ' . $menusFillerAR[$function] . '&nbsp;</div>';
	} else {
		print '	<div class="menufiller"> &nbsp; </div>';
	}

	print '
</nav>
<div class="mainbody" id="mainbody">
<br />
';

	if (oc_hookSet('header-top')) {
		foreach ($GLOBALS['OC_hooksAR']['header-top'] as $hook) {
			require_once $hook;
		}
	}

	if (isset($GLOBALS['OC_displayTop']) && !empty($GLOBALS['OC_displayTop'])) {
		print $GLOBALS['OC_displayTop'];
	}
	
	if (!empty($what)) {
		print '
<h1 class="header">' . safeHTMLstr($what) . '</h1>
';
	}

	unset($menusAR);
	unset($menusFillerAR);
}

// Displays page footer
function printFooter() {
	global $pfx;
	
	if (oc_hookSet('footer-top')) {
		foreach ($GLOBALS['OC_hooksAR']['footer-top'] as $hook) {
			require_once $hook;
		}
	}

	print '
</div><!-- mainbody -->
<p>&nbsp;</p>
<div class="footerBorder"></div>
';

	if ($GLOBALS['OC_configAR']['OC_privacy_display'] == 2) {
		print '<div class="ocprivacy"><a href="' . $pfx . 'privacy_policy.php" target="_blank">' . oc_('Privacy Policy') . '</a></div>';
	}

// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// IT IS A VIOLATION OF THE OPENCONF LICENSE TO 
// MODIFY OR HIDE THE PRINT STATEMENT BELOW OR ITS ASSOCIATED CSS STYLES
// WITHOUT HAVING FIRST PURCHASED AN OPENCONF BRANDING-FREE LICENSE FOR THIS INSTALLATION
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	print '<!-- DO NOT REMOVE, ALTER, OR HIDE THIS COPYRIGHT NOTICE --><br /><div id="powered">' . 
//T: %2s = OpenConf
/* DO NOT REMOVE, ALTER, OR HIDE THIS COPYRIGHT NOTICE */sprintf(oc_('Powered by <a href="%1$s" target="_blank">%2$s</a><sup>&reg;</sup>'), 'https://www.OpenConf.com', 'OpenConf') . '<br />' . 
//T: %1s-%2s = YYYY-YYYY, %4$s = Zakon Group LLC
/* DO NOT REMOVE, ALTER, OR HIDE THIS COPYRIGHT NOTICE */sprintf(oc_('Copyright &copy;%1$s-%2$s <a href="%3$s" target="_blank">%4$s</a>'), '2002', '2022', 'https://www.ZakonGroup.com/', 'Zakon Group LLC') . '</div><!-- DO NOT REMOVE, ALTER, OR HIDE THIS COPYRIGHT NOTICE -->';
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// IT IS A VIOLATION OF THE OPENCONF LICENSE TO 
// MODIFY OR HIDE THE PRINT STATEMENT ABOVE OR ITS ASSOCIATED CSS STYLES
// WITHOUT HAVING FIRST PURCHASED AN OPENCONF BRANDING-FREE LICENSE FOR THIS INSTALLATION
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

	// include Google Analytics tag if it starts with <script
	if (isset($GLOBALS['OC_configAR']['OC_googleAnalytics']) && preg_match("/<script/", $GLOBALS['OC_configAR']['OC_googleAnalytics'])) {
		print $GLOBALS['OC_configAR']['OC_googleAnalytics'];
	}

	if (oc_hookSet('footer-bottom')) {
		foreach ($GLOBALS['OC_hooksAR']['footer-bottom'] as $hook) {
			require_once $hook;
		}
	}

	require_once $pfx . 'footer.php';
}

// Displays warning and exits
function warn($warnmsg, $hdr='', $hdrfn=0) {
	if (!empty($hdr)) {
		printHeader($hdr, $hdrfn);
	}
	print '<p><span class="warn" role="alert">' . $warnmsg . '</span></p>';
	printFooter();
	exit;
}

// Displays error and exits
function err($errmsg, $hdr='', $hdrfn=0, $contact=true) {
	global $OC_configAR;
	if (!empty($hdr)) {
		printHeader($hdr, $hdrfn);
	}
	print '<div class="err" style="margin: 1em;">' . oc_('We have encountered a problem:') . ' <em>' . $errmsg . '</em><br /><br />';
	if ($contact) {
		if (! isset($_SESSION[OCC_SESSION_VAR_NAME]['chairlast'])) {
			print sprintf(oc_('Please contact the <a href="mailto:%1$s?subject=OpenConf problem&body=%2$s">Chair</a>.'), varValue('OC_pcemail', $OC_configAR, '', true), htmlspecialchars($errmsg));
		} else {
			print oc_('Please contact the system administrator.');
		}
	}
	print "</div><br /><br />\n";
	printFooter();
	exit;
}

// Displays database error
function dberr($errmsg) {
	if (oc_hookSet('error-database')) {
		foreach ($GLOBALS['OC_hooksAR']['error-database'] as $hook) {
			require_once $hook;
		}
	}
	err($errmsg, 'Error', 0, false);
}

// Makes database connection
function dbConnect($hdrfn=0) {
	global $OC_db;
	// Return if already connected
	if (isset($OC_db) && !empty($OC_db)) { return; }

	// Connect to DB server
	$OC_db = mysqli_init();
	$mysql_flags = null;
	if (defined('OCC_DB_USE_SSL') && (OCC_DB_USE_SSL == 1)) {
		if (defined('OCC_DB_SSL_NOVERIFY') && (OCC_DB_SSL_NOVERIFY == 1)) {
			$mysql_flags = MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
		} else {
			$mysql_flags = MYSQLI_CLIENT_SSL;

		}
		mysqli_ssl_set(
			$OC_db, 
			((defined('OCC_DB_SSL_KEY') && (OCC_DB_SSL_KEY != '')) ? OCC_DB_SSL_KEY : null), 
			((defined('OCC_DB_SSL_CERT') && (OCC_DB_SSL_CERT != '')) ? OCC_DB_SSL_CERT : null), 
			((defined('OCC_DB_SSL_CA') && (OCC_DB_SSL_CA != '')) ? OCC_DB_SSL_CA : null), 
			((defined('OCC_DB_SSL_CAPATH') && (OCC_DB_SSL_CAPATH != '')) ? OCC_DB_SSL_CAPATH : null), 
			((defined('OCC_DB_SSL_CIPHER') && (OCC_DB_SSL_CIPHER != '')) ? OCC_DB_SSL_CIPHER : null)
		);
	}
	mysqli_real_connect($OC_db, OCC_DB_HOST, OCC_DB_USER, OCC_DB_PASSWORD, '', (int)OCC_DB_PORT, null, $mysql_flags) or dberr('could not connect to database (' . mysqli_connect_errno() . ')');

	// Specify UTF-8 use for connection -- yup, sadly semi-redundant due to collation
	mysqli_set_charset($OC_db, OCC_DB_ENCODING);
	mysqli_query($OC_db, "SET NAMES " . OCC_DB_ENCODING . " COLLATE " . OCC_DB_COLLATION);

	// Select DB
	mysqli_select_db($OC_db, OCC_DB_NAME) or dberr('could not select database (' . mysqli_errno($OC_db) . ')');

	// Set reporting to off given PHP 8.1 changeover
	mysqli_report(MYSQLI_REPORT_OFF);
}

// Close DB connection
function ocsql_close() {
	return(mysqli_close($GLOBALS['OC_db']));
}

// Custom db query function to enable logging
function ocsql_query($q, $log=true) {
	$logid = false;
	// Log SQL
	if ($log
			&& isset($GLOBALS['OC_configAR']['OC_logSQL']) 
			&& $GLOBALS['OC_configAR']['OC_logSQL'] 
			&& preg_match("/^(?:INSERT|UPDATE|DELETE|ALTER|TRUNCATE|DELETE|CREATE|DROP)/", $q)
	) {
		// log DB updates
		$logq = "INSERT INTO `" . OCC_TABLE_LOG . "` SET `datetime`='" . safeSQLstr(gmdate('Y-m-d H:i:s')) . "', `entry`='" . safeSQLstr($q) . "', `type`='sql'";
		if (!mysqli_query($GLOBALS['OC_db'], $logq)) {
			return(false);
		} else {
			$logid = mysqli_insert_id($GLOBALS['OC_db']);
		}
	}
	// Run Query
	if ($ret = mysqli_query($GLOBALS['OC_db'], $q)) {
		$GLOBALS['OC_db_error'] = '';
		$GLOBALS['OC_db_errno'] = 0;
		return($ret);
	} else { // fail - logit
		$GLOBALS['OC_db_error'] = mysqli_error($GLOBALS['OC_db']);
		$GLOBALS['OC_db_errno'] = mysqli_errno($GLOBALS['OC_db']);
		if ($logid) {
			$logq = "UPDATE `" . OCC_TABLE_LOG . "` SET `type`='sqlfail', `extra`='" . safeSQLstr(mysqli_error($GLOBALS['OC_db'])) . "' WHERE `logid`=" . (int)$logid . " LIMIT 1";
		} else {
			$logq = "INSERT INTO `" . OCC_TABLE_LOG . "` SET `datetime`='" . safeSQLstr(gmdate('Y-m-d H:i:s')) . "', `entry`='" . safeSQLstr($q) . "', `type`='sqlfail', `extra`='" . safeSQLstr($GLOBALS['OC_db_error']) . "'";
		}
		mysqli_query($GLOBALS['OC_db'], $logq);
		return(false);
	}
}

// Fetch result row assoc
function ocsql_fetch_assoc(&$r) {
	return(mysqli_fetch_assoc($r));
}

// Fetch result row array
function ocsql_fetch_array(&$r) {
	return(mysqli_fetch_array($r));
}

// Fetch result row
function ocsql_fetch_row(&$r) {
	return(mysqli_fetch_row($r));
}

// Return insert id
function ocsql_insert_id() {
	return(mysqli_insert_id($GLOBALS['OC_db']));
}

// Fetch num rows
function ocsql_num_rows(&$r) {
	return(mysqli_num_rows($r));
}

// Fetch affected rows
function ocsql_affected_rows() {
	return(mysqli_affected_rows($GLOBALS['OC_db']));
}

// Frees result set
function ocsql_free_result(&$r) {
	mysqli_free_result($r);
}

// adjust result pointer
function ocsql_data_seek(&$r, $loc) {
	mysqli_data_seek($r, $loc);
}

// Return error
function ocsql_error() {
	return($GLOBALS['OC_db_error']);
}

// Return error number
function ocsql_errno() {
	return($GLOBALS['OC_db_errno']);
}

// Add entry to log table
function oc_logit($type, $entry, $extra='') {
	return(ocsql_query("INSERT INTO `" . OCC_TABLE_LOG . "` SET `datetime`='" . safeSQLstr(gmdate('Y-m-d H:i:s')) . "', `type`='" . safeSQLstr($type) . "', `entry`='" . safeSQLstr($entry) . "'" . (!empty($extra) ? (", `extra`='" . safeSQLstr($extra) . "'") : ''), false ));
}

// Retrieve a file's content
function ocGetFile($f) {
	if (preg_match("/^http/", $f)) {
		if ($s = file_get_contents($f, 0, $GLOBALS['OC_context'])) {
			return($s);
		} elseif (function_exists('curl_init')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $f);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);       
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);       
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);       
			curl_setopt($ch, CURLOPT_MAXREDIRS, 5);       
			curl_setopt($ch, CURLOPT_HEADER, false);       
			$s = curl_exec($ch);
			curl_close($ch);
			return($s);
		} else {
			return(false);
		}
	} elseif (oc_hookSet('get_file')) {
		return(call_user_func($GLOBALS['OC_hooksAR']['get_file'][0], $f));      // only one hook allowed here		
	} else {
		return(file_get_contents($f));
	}
}

// updates a setting in the config table
function updateConfigSetting($setting, $value, $module='OC') {
	$q = "UPDATE `" . OCC_TABLE_CONFIG . "` SET `value`='" . safeSQLstr(preg_replace("/\r/", "", trim($value))) . "' WHERE `module`='" . safeSQLstr($module) . "' AND `setting`='" . safeSQLstr($setting) . "'";
	return(ocsql_query($q));
}

// cycles through an array of config settings and updates them if needed
function updateAllConfigSettings(&$varAR, &$valAR, $module='OC') {
	global $OC_configAR;
	foreach ($varAR as $v) {
		if (isset($valAR[$v]) && isset($OC_configAR[$v]) && ($OC_configAR[$v] != $valAR[$v])) {
			updateConfigSetting($v, $valAR[$v], $module) or err('Unable to update setting ' . safeHTMLstr($v));
			$OC_configAR[$v] = $valAR[$v];
		}
	}
} oc_addHook('footer-bottom', $GLOBALS['pfx'].'chair/list_topics_b.inc');


// updates a setting in the status table
function updateStatusSetting($setting, $value) {
	$q = "UPDATE `" . OCC_TABLE_STATUS . "` SET `status`='" . safeSQLstr($value) . "' WHERE `setting`='" . safeSQLstr($setting) . "'";
	if (ocsql_query($q)) {
		$q = "INSERT INTO `" . OCC_TABLE_LOG . "` (`datetime`, `entry`, `type`) SELECT CONCAT('" . safeSQLstr(gmdate('Y-m-d H:i:s')) . "'), CONCAT_WS(' ', `name`, '" . (($value == 1) ? 'opened' : 'closed') . " – ', `module`) AS `entry`, 'status' FROM `" . OCC_TABLE_STATUS . "` WHERE `setting`='" . safeSQLstr($setting) . "'";
		ocsql_query($q);
		return(true);
	}
	return(false);
}

// cycles through an array of status settings and updates them if needed
function updateAllStatusSettings(&$varAR, &$valAR) {
	foreach ($varAR as $v) {
		if (isset($valAR[$v]) && isset($GLOBALS['OC_statusAR'][$v]) && preg_match("/^[01]$/", $valAR[$v]) && ($GLOBALS['OC_statusAR'][$v] != $valAR[$v])) {
			updateStatusSetting($v, $valAR[$v]) or err('Unable to update setting ' . safeHTMLstr($v));
			$GLOBALS['OC_statusAR'][$v] = $valAR[$v];
		}
	}
}

// Issues a SQL call - synonymous with ocsql_query() but does not return result
function issueSQL($s) {
	ocsql_query($s) or err('database call error');
}

// safeSQLstr - return a string safe for db insertion
function safeSQLstr ($s) {
	return mysqli_real_escape_string($GLOBALS['OC_db'], $s);
}

// safeHTMLstr - return a string safe for html display
function safeHTMLstr ($s) {
	return htmlspecialchars($s, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
}

// generateSelectOptions - Creates series of <option> tags based on input array values
function generateSelectOptions(&$optionAR, $selected='', $usekey=TRUE, $multiple=FALSE, $length=0) {
	$options = '';
	foreach ($optionAR as $key => $val) {
		// if key is numeric, then just use value
		if ($usekey) {
			$options .= '<option value="' . safeHTMLstr($key) . '"';
			$comp = $key;
		} else {
			$options .= '<option value="' . safeHTMLstr($val) . '"';
			$comp = $val;
		}
		if ($multiple) {
			if (!empty($selected) && in_array((string)$comp, $selected)) {
				$options .= ' selected'; 
			}
		} elseif ((string)$comp == (string)$selected) {
			$options .= ' selected';
		}
		$options .= '>' . safeHTMLstr( (($length > 0) ? substr($val, 0, $length) : $val) ) . '</option>';
	}
	return($options);
}

// generateBoxNRadioOptions - Creates a series of radio or checkboxes based on input array values
function generateBoxNRadioOptions($name, $type, &$optionAR, $selected='', $usekey=1, $extra='', $break='<br />', $multiple=FALSE) {
	$boxes = '';
	$i = 1;
	foreach ($optionAR as $key => $val) {
		// if key is numeric, then just use value
		if ($usekey) {
			$comp = $key;
		} else {
			$comp = $val;
		}
		$boxes .= '<label><input type="' . $type . '" name="' . $name;
		if ($type == 'checkbox') {
			$boxes .= '[]';
		}
		$boxes .= '" id="' . $name . $i++ . '" value="' . safeHTMLstr($comp) . '" ';
		if ((($type == 'checkbox') && is_array($selected) && in_array($comp, $selected))
			|| (!is_array($selected) && ((string)$comp == (string)$selected))) {
			$boxes .= 'checked ';
		}
		$boxes .=  $extra . ' />' . $val . '</label>' . $break;
	}
	return($boxes);
}

function generateRadioOptions($name, &$optionAR, $selected='', $usekey=1, $extra='', $break=' &nbsp; ', $multiple=FALSE) {
	return(generateBoxNRadioOptions($name, 'radio', $optionAR, $selected, $usekey, $extra, $break, $multiple));
}

function generateCheckboxOptions($name, &$optionAR, $selected='', $usekey=1, $extra='', $break=' &nbsp; ', $multiple=FALSE) {
	return(generateBoxNRadioOptions($name, 'checkbox', $optionAR, $selected, $usekey, $extra, $break, $multiple));
}

// Shorten a string w/o splitting a word
// Implemented for use in <select>'s
function shortenStr($s,$l) {
        if (($l==0) || (($slen=oc_strlen($s)) < $l)) {
            return($s);
        } else {
                $news = substr($s,0,$l);
                // Don't break string mid-word
                if (!preg_match("/[^\w\']$/",$news)) {
                        $pos = $l;
                        while (($pos < $slen) && preg_match("/[\w\']/",($c=substr($s,$pos,1)))) {
                                $news .= $c;
                                $pos++;
                        }
                }
                if (oc_strlen($news) < $slen) { $news .= "..."; }
                return($news);
        }
}

// Checks whether a string is multi-byte
function oc_isMultibyte($s) {
	if (function_exists('mb_check_encoding')) {
		return ! mb_check_encoding($s, 'ASCII');
	} else {
		return (bool)preg_match('/[^\x00-\x7f]/', $s);
	}
}

// str case conversion to properly handle UTF-8
function oc_caseConvert($s, $f) {
	if (function_exists('mb_strtoupper')) {
		switch ($f) {
			case 'strtoupper':
				return mb_strtoupper($s, 'UTF-8');
				break;
			case 'strtolower':
				return mb_strtolower($s, 'UTF-8');
				break;
			default:
				err('string function unknown');
				break;
		}
	} elseif (! oc_isMultibyte($s)) {
		switch ($f) {
			case 'strtoupper':
				return strtoupper($s);
				break;
			case 'strtolower':
				return strtolower($s);
				break;
			default:
				err('string function unknown');
				break;
		}
	} else {
		require_once OCC_UTF8CASECONV_FILE;
		switch ($f) {
			case 'strtoupper':
				return preg_replace_callback( 
					"/([a-z]|[\\xc0-\\xff][\\x80-\\xbf]*)/",
					function ($matches) {
						return strtr($matches[1], $GLOBALS['ocUpperChars']);
					},
					$s 
				); 	
				break;
			case 'strtolower':
				return preg_replace_callback( 
					"/([A-Z]|[\\xc0-\\xff][\\x80-\\xbf]*)/",
					function ($matches) {
						return strtr($matches[1], $GLOBALS['ocLowerChars']);
					},
					$s 
				);
				break;
			default:
				err('string function unknown');
				break;
		}
	}
}

// Safe UTF8 strtoupper
function oc_strtoupper($s) {
	return oc_caseConvert($s, 'strtoupper');
}

// Safe UTF8 strtolower
function oc_strtolower($s) {
	return oc_caseConvert($s, 'strtolower');
}

// Safe UTF8 strlen
function oc_strlen($s) {
	return strlen(utf8_decode((string) $s));
}

// Safe UTF8 ucfirst
function oc_ucfirst($s) {
	if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
		return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8')) . mb_substr($s, 1, null, 'UTF-8');
	} elseif (! oc_isMultibyte($s)) {
		return ucfirst($s);
	} else {
		require_once OCC_UTF8CASECONV_FILE;
		if (preg_match("/^(\p{Ll})(.*)/u", $s, $matches)) {
			return preg_replace("/([a-z]|[\\xc0-\\xff][\\x80-\\xbf]*)/", $GLOBALS['ocUpperChars'], $matches[1]) . $matches[2];
		} else {
			return $s;
		}
	}
}

// Inserts &nbsp; to format number
function padNumber($num, $size) {
	$padsize = $size - oc_strlen((string) $num);
	for ($i=$padsize;$i>0;$i--) {
		$num = "&nbsp;&nbsp;" . $num;
	}
	return($num);
}

// Converts IDN to ASCII
function oc_idn_to_ascii($domain) {
	if (function_exists('idn_to_ascii') && defined('IDNA_NONTRANSITIONAL_TO_ASCII') && defined('INTL_IDNA_VARIANT_UTS46')) {
		return(idn_to_ascii($domain, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46));
	} else {
		require_once OCC_LIB_DIR . 'idna_convert.class.php';
		$IDN = new idna_convert(array('idn_version' => 2008));
		return($IDN->encode($domain));
	}
}

// Convert IDN email to ASCII email
function oc_idn_email_to_ascii($email) {
	if (preg_match("/@/", $email)) {
		list($local, $domain) = explode('@', $email);
		return(oc_idn_to_ascii($local) . '@' . oc_idn_to_ascii($domain));
	} else {
		return(oc_idn_to_ascii($email));
	}
}

// Generate time zone select entries -- requires OCC_ZONE_FILE be loaded already
function oc_generateSelectTimeZoneOptions($selected='') {
	$options = '';
	$t = '';
	foreach ($GLOBALS['OC_zoneTerritoryAR'] as $territory => $territoryName) {
		if ($t != $territory) {
			$t = $territory;
			if (!empty($options)) {
				$options .= "</optgroup>";
			}
			$options .= '<optgroup label="' . safeHTMLstr($territoryName) . '">';
		}
		foreach ($GLOBALS['OC_zoneAR'][$territory] as $zone => $zoneName) {
			$options .= '<option value="' . $zone . '">' . safeHTMLstr($zoneName) . '</option>';
		}
	}
	$options .= "</optgroup>";
	if (!empty($selected)) {
		$options = preg_replace('/(value="' . preg_replace("/\//", "\\/", $selected) . '")/', "\\1 selected", $options);
	}
	return($options);
}

// Validates time zone -- requires OCC_ZONE_FILE be loaded already
function oc_validateTimeZone($tz) {
	if (empty($tz) || !preg_match("/^([^\/]+)\/?/", $tz, $match) || !isset($GLOBALS['OC_zoneAR'][$match[1]][$tz])) {
		return false;
	} else {
		return true;
	}
}

// Format intl date
function oc_strftime($format, $time='') {
	if (empty($time)) {
		$time = time();
	}
	$conv = '';
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') { // Workaround Windows lack of %e and utf-8 support for strftime
		$cp = explode('.',setlocale(LC_CTYPE, 0));
		$cp = 'cp' . $cp[1];
		$conv = iconv($cp, 'utf-8', strftime(preg_replace("/\%e/", '%#d', $format), $time));
		if (!empty($conv)) {
			return($conv);
		}
	}
	return(strftime($format, $time));
}

// Return the current or specified month name
function oc_monthName($m='') {
	if (!empty($m)) {
		return(oc_strftime('%B', mktime(12, 0, 0, $m, 3))); // day=3 to avoid any TZ issues on the 1st
	} else {
		return(oc_strftime('%B'));
	}
}

// Return an array of months
function oc_getMonths($cal=0) {
	$calinfo = cal_info($cal);
	return($calinfo['months']);
}

// Validate email address
function validEmail($email,$len=100) {
	if (($len>0) && ( (oc_strlen($email) > $len) || preg_match('/"/', $email) ) ) {
		return(false);
	}
	return (filter_var(oc_idn_email_to_ascii($email), FILTER_VALIDATE_EMAIL) !== FALSE);
	// FILTER_FLAG_EMAIL_UNICODE not used as it only validates the local part of address
}

// Encode text into quoted-printable format
function oc_qpencode($what, $linelenmax = 75) {
	$eol = "\n"; // using \n instead of \r\n to avoid double-spaced messages in Outlook
	$encoded = '';
	$lines = preg_split("/(?:\r\n|\r|\n)/", $what);
	foreach ($lines as $line) {
		$linelen = strlen($line);
		$encline = '';
		for ($i=0; $i<$linelen; $i++) {
			$c = substr($line, $i, 1);
			$cdec = ord($c);
			if (($cdec == 32) && ($i == ($linelen - 1))) {
				$c = '=20';
			} elseif (($cdec == 61) || ($cdec < 32) || ($cdec > 126)) {
				$c = '=' . strtoupper(sprintf('%02s', dechex($cdec)));
			}
			if ((strlen($encline) + strlen($c)) >= $linelenmax) {
				$encoded .= $encline . '=' . $eol;
				$encline = '';
			}
			$encline .= $c;
		}
		$encoded .= $encline . $eol;
	}
	$encoded = substr($encoded, 0, -1 * strlen($eol));
	return $encoded; }
	if (OCC_LICENSE=='Public'){define('OCC_LICENSE_TYPE','Community');
}

// Sends out optionally utf-8 enabled email
// Note: headers are not converted except for subject
// We are defaulting to QP so bounced messages can be more easily read (for non-CJK)
function oc_mail($to, $subject, $body, $hdr='', $enc='quoted-printable') {
	global $OC_configAR;
	
	if (!preg_match("/\p{L}/u", $subject) || !preg_match("/\p{L}/u", $body)) { // skip messages with empty body or subject, and return successfully
		return(true);
	}
	
	$newsubject = $subject;
	$return = null;
	if (oc_hookSet('mail')) {
		foreach ($GLOBALS['OC_hooksAR']['mail'] as $hook) {	// really intended for a single hook (i.e., Mail module)
			$return = require $hook;
		}
	}
	if ($return !== null) {
		return($return);
	} else { // use PHP mail()
		$headers = (empty($hdr) ? $OC_configAR['OC_mailHeaders'] : $hdr);
		// Encode if UTF-8 email enabled
		if ($OC_configAR['OC_mailUTF8']) {
			$headers = "MIME-Version: 1.0\r\n" . $headers . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: " . $enc;
			if (!preg_match("/X-OC: PHP\/OpenConf\//", $headers)) {
				$headers = preg_replace("/\r/", "", $headers);
			}
			switch ($enc) {
				case 'base64':
					$body = chunk_split(base64_encode($body));
					$newsubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
					if (preg_match("/From: \"(.*?)\" </", $headers, $fmatches) && oc_isMultibyte($fmatches[1])) {
						$headers = preg_replace("/From: \".*?\"/", 'From: "=?UTF-8?B?' . base64_encode($fmatches[1]) . '?="', $headers);
					}
					if (preg_match("/Reply-To: \"(.*?)\" </", $headers, $fmatches) && oc_isMultibyte($fmatches[1])) {
						$headers = preg_replace("/Reply-To: \".*?\"/", 'Reply-To: "=?UTF-8?B?' . base64_encode($fmatches[1]) . '?="', $headers);
					}
					break;
				default:
					$body = oc_qpencode($body);
					$newsubject = "=?UTF-8?Q?" . rtrim(preg_replace(array("/[\r\n].*$/", "/\s+/", "/\?/"), array("", "_", "=3f"), oc_qpencode($subject)), '= ') . "?="; // only use first line for subject and replace spaces/?s
					if (preg_match("/From: \"(.*?)\" </", $headers, $fmatches) && oc_isMultibyte($fmatches[1])) {
						$headers = preg_replace("/From: \".*?\"/", 'From: "=?UTF-8?Q?' . rtrim(preg_replace(array("/[\r\n].*$/", "/\s+/", "/\?/"), array("", "_", "=3f"), oc_qpencode($fmatches[1])), '= ') . '?="', $headers);
					}
					if (preg_match("/Reply-To: \"(.*?)\" </", $headers, $fmatches) && oc_isMultibyte($fmatches[1])) {
						$headers = preg_replace("/Reply-To: \".*?\"/", 'Reply-To: "=?UTF-8?Q?' . rtrim(preg_replace(array("/[\r\n].*$/", "/\s+/", "/\?/"), array("", "_", "=3f"), oc_qpencode($fmatches[1])), '= ') . '?="', $headers);
					}
					break;
			}
		} else {
			$body = wordwrap($body, $OC_configAR['OC_emailWrap']);
		}
		// Handle IDN addresses
		$newto = '';
		if (preg_match("/[,;]/", $to)) {
			$toAR = preg_split("/[,;]/", $to);
			foreach ($toAR as $email) {
				$newto .= oc_idn_email_to_ascii(trim($email)) . ',';
			}
			$newto = rtrim($newto, ",");
		} else {
			$newto = oc_idn_email_to_ascii($to);
		}
		// Send out message
        if (oc_hookSet('mailsend')) { // only first hook called
			return call_user_func($GLOBALS['OC_hooksAR']['mailsend'][0], $newto, $newsubject, $body, $headers, $to, $subject); 
		} else {
			return(mail($newto, $newsubject, $body, $headers, (defined('OCC_MAILPARAMS') ? OCC_MAILPARAMS : '')));
		}
	}
}

// Sends an email message, adding OC_confName to beginning of subject line, and cc'ing OC_confirmmail and including IP address if requested
function sendEmail($to, $subject, $body, $ccConfirm=0) {
	global $OC_configAR;
	// include confirm address?
	if ($ccConfirm) {
		$to .= (empty($to) ? '' : ',') . $OC_configAR['OC_confirmmail'];
	}
	// Bail out successfully(?) if no one to email
	if (empty($to)) { return(true); }
	// Add conf name to beginning of subject
	$subject = "[" . $OC_configAR['OC_confName'] . "] " . $subject;
	// Include IP in message body?
	if ($OC_configAR['OC_notifyIncludeIP']) {
		$body .= "\n\nIP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";
	}
	// Send message & return whether successful
	return(oc_mail($to, $subject, $body));
}

// Generates a unique ID
//   $len is only used if openssl_random_pseudo_bytes() available, and in which case $len will be halved to account for bin2hex()
function oc_idGen($len=32) {
	if (function_exists('openssl_random_pseudo_bytes')) {
		return(bin2hex(openssl_random_pseudo_bytes(ceil($len/2))));
	} else {
		return(substr(md5(uniqid(rand(), true)), 0, $len));
	}
}

// Returns short topic if !empty, else full topic name
function useTopic($short, $full, $cut=0) {
	if (!empty($short)) { return($short); }
	elseif ($cut) {
		if ($cut == 1) { $cut = 30; }   // default cut length
		return(substr($full,0,$cut));
	}
	return($full);
}

// Checks whether form token is valid
function validToken($type) {
	if (isset($_SESSION[OCC_SESSION_VAR_NAME][$type . 'token']) && isset($_REQUEST['token']) && ($_REQUEST['token'] == $_SESSION[OCC_SESSION_VAR_NAME][$type . 'token'])) {
		return(TRUE);
	} else {
		return(FALSE);
	}
}

// Sends no cache headers
function oc_sendNoCacheHeaders() {
	header("Expires: Mon, 18 Sep 2003 13:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
}

// Display (retrieve) file
function oc_displayFile($path, $format) {
	if (oc_hookSet('display_file')) {
		foreach ($GLOBALS['OC_hooksAR']['display_file'] as $hook) {
			require_once $hook;
		}
	}
	if (file_exists($path)) {
		header("Content-type: " . $GLOBALS['OC_mimeTypeAR'][$format]);
		header("Content-Disposition: ; filename=" . basename($path));
		header("Content-Length: " . filesize($path));
		header("Cache-control: private");
		header("Pragma: public"); // IE issue work around
		readfile($path);
		exit;
	} else {
		return false;
	}
	return true;
}

// Save uploaded file
function oc_saveFile($src, $dest, $type) {
	if (oc_hookSet('save_file')) {
		return call_user_func($GLOBALS['OC_hooksAR']['save_file'][0], $src, $dest, $type);	// only one hook allowed here
	} elseif (move_uploaded_file($src, $dest)) {
		chmod($dest, 0666);
		return true;
	} else {
		return false;
	}
}

// Create directory
function oc_createDir($dir, $htaccess=false) {
	if (oc_hookSet('create_directory')) {
		return call_user_func($GLOBALS['OC_hooksAR']['create_directory'][0], $dir);	// only one hook allowed here
	} elseif (! is_dir($dir)) {
		$umask = umask(0);
		if (! mkdir($dir, 0755, true)) {
			return(false);
		}
		// Create .htaccess
		if ($htaccess) {
			if (! ($fp = fopen($dir . '/.htaccess', 'w')) || ! fwrite($fp, "deny from all\n") || ! fclose($fp)) {
				return(false);
			} else {
				chmod($dir . '/.htaccess', 0644);
			}
		}
		umask($umask);
	}
	return(true);
}

// Delete directory and subdirectories
function oc_deleteDir($dir, $pass=0) {
	if (($pass == 0) && oc_hookSet('delete_directory')) {	// only call hook once
		return call_user_func($GLOBALS['OC_hooksAR']['delete_directory'][0], $dir);	// only one hook allowed here
	} elseif (is_dir($dir)) {
		$dir = rtrim($dir, '/');
		$dirPtr = dir($dir);
		while (($name = $dirPtr->read()) !== false) {
			if (!preg_match("/^\.\.?$/", $name)) {
				is_dir($dir . '/' . $name) ? oc_deleteDir($dir . '/' . $name, ++$pass) : unlink($dir . '/' . $name);
			}
		}
		$dirPtr->close();
		return rmdir($dir);
	}
}

// Delete file
function oc_deleteFile($path) {
	if (oc_hookSet('delete_file')) {
		return call_user_func($GLOBALS['OC_hooksAR']['delete_file'][0], $path);	// only one hook allowed here
	} elseif (is_file($path)) {
		return unlink($path);
	}
}

// Rename file
function oc_renameFile($oldFileName, $newFileName) {
	if (oc_hookSet('rename_file')) {
		return call_user_func($GLOBALS['OC_hooksAR']['rename_file'][0], $oldFileName, $newFileName);	// only one hook allowed here
	} else {
		return rename($oldFileName, $newFileName);
	}
}

// Check if file exists
function oc_isFile($path) {
	if (oc_hookSet('is_file')) {
		return call_user_func($GLOBALS['OC_hooksAR']['is_file'][0], $path);	// only one hook allowed here
	} else {
		return is_file($path);
	}
}

// get file size
function oc_fileSize($path) {
	if (oc_hookSet('filesize')) {
		return call_user_func($GLOBALS['OC_hooksAR']['filesize'][0], $path);	// only one hook allowed here
	} else {
		return filesize($path);
	}
}

// get file size
function oc_fileMtime($path) {
	if (oc_hookSet('filemtime')) {
		return call_user_func($GLOBALS['OC_hooksAR']['filemtime'][0], $path);	// only one hook allowed here
	} else {
		return filemtime($path);
	}
}

// get count of data files in dir (with name number.ext)
function oc_fileCount($dir) {
	$count = 0;
	if (oc_hookSet('file_count')) {
		$count = call_user_func($GLOBALS['OC_hooksAR']['file_count'][0], $dir); // only one allowed
	} else {
		if ($pdh = opendir($dir)) {
			while(($f = readdir($pdh)) !== false) {
				if (preg_match("/^\d+\.\w+$/",$f)) {
					$count++;
				}
			}
			closedir($pdh);
		}
	}
	return($count);
}

// prints out a table cell with link to file if available
function oc_printFileCells(&$sub, $chair=false) {
	$str = '';
	if (oc_hookSet('print_file_cells')) {
		$str = call_user_func($GLOBALS['OC_hooksAR']['print_file_cells'][0], $sub, $chair);	// only one hook allowed here
	} else {
		$paper = $sub['paperid'] . '.' . $sub['format'];
		$file = $GLOBALS['OC_configAR']['OC_paperDir'] . $paper;
		if (!empty($sub['format']) && oc_isFile($file)) {
			$str = '<td align="center"><a href="../review/paper.php?p=' . $paper . ($chair ? '&c=1': '') . '" style="white-space: nowrap"><img src="../images/document-sm.gif" border="0" alt="' . oc_('View File') . '" title="' . oc_('View File') . '" width="13" height="16" /> <span style="font-size: 70%">(' . oc_formatNumber($fs = oc_fileSize($file)) . ', ' . oc_strftime('%e %b', oc_fileMtime($file)) . ')</span></a></td>';
			if (isset($GLOBALS['OC_downloadZipAR'])) { // set counter if needed
				if (isset($GLOBALS['OC_downloadZipAR'][1])) {
					$GLOBALS['OC_downloadZipAR'][1]['count']++;
					$GLOBALS['OC_downloadZipAR'][1]['size'] += $fs;
				} else {
					$GLOBALS['OC_downloadZipAR'][1]['count'] = 1;
					$GLOBALS['OC_downloadZipAR'][1]['size'] = $fs;
				}
			}
		} else {
			$str = '<td align="center"><img src="../images/documentless-sm.gif" border=0 alt="' . oc_('file not available') . '" title="' . oc_('file not available') . '" width="13" height="16" /></td>';
			if (isset($GLOBALS['missingFileAR'])) {
				$GLOBALS['missingFileAR']['ft1'][] = $sub['paperid'];
			}
		}
	}
	return($str);
} $GLOBALS['OC_h' . 'tt' . 'pHea' . 'ders'][] = 'X-P' . 'owered-' . 'By: Ope' . 'nCo' . 'nf';

// Delete assignment(s)
function oc_deleteAssignments($pid=null, $cid=null, $type='reviewer', $who='chair') {
	if ($cid === null) {
		$whereSQL = '';
	} else {
		$whereSQL = "`" . $type . "id`='" . safeSQLstr($cid) . "'";
	}
	if ($pid !== null) {
		$whereSQL .= (empty($whereSQL) ? '' : ' AND ') . "`paperid`='" . safeSQLstr($pid) . "'";
	}
	if (empty($whereSQL)) {
		$whereSQL = '1=1'; // delete all
	}
	switch ($type) {
		case 'advocate':
			// log
			if ($del_r = ocsql_query("SELECT * FROM `" . OCC_TABLE_PAPERADVOCATE . "` WHERE " . $whereSQL)) {
				while ($del_l = ocsql_fetch_assoc($del_r)) {
					$entry = 'Advocate assignment ' . $del_l['paperid'] . '-' . $del_l['advocateid'] . ' deleted by ' . $who;
					oc_logit('advocate', $entry, json_encode($del_l));
				}
			}
			// delete
			issueSQL("DELETE FROM `" . OCC_TABLE_PAPERADVOCATE . "` WHERE " . $whereSQL);
			break;
		default: // review
			// log - note that papersession is not maintained
			if ($del_r = ocsql_query("SELECT * FROM `" . OCC_TABLE_PAPERREVIEWER . "` WHERE " . $whereSQL)) {
				while ($del_l = ocsql_fetch_assoc($del_r)) {
					$entry = 'Review assignment ' . $del_l['paperid'] . '-' . $del_l['reviewerid'] . ' deleted by ' . $who;
					oc_logit('review', $entry, json_encode($del_l));
				}
			}
			//delete
			issueSQL("DELETE FROM `" . OCC_TABLE_PAPERREVIEWER . "`WHERE " . $whereSQL);
			issueSQL("DELETE FROM `" . OCC_TABLE_PAPERSESSION . "` WHERE " . $whereSQL);
			break;
	}
	// Hook
	if (oc_hookSet('chair-unassign-' . $type)) {
		foreach ($GLOBALS['OC_hooksAR']['chair-unassign-' . $type] as $f) {
			include $f;
		}
	}
}

// Get (email) template
function oc_getTemplate($templateid) {
	$r = ocsql_query("SELECT `subject`, `body`, `type` from `" . OCC_TABLE_TEMPLATE . "` WHERE `templateid`='" . safeSQLstr($templateid) . "'") or err('Unable to retrieve template ' . safeHTMLstr($templateid));
	if (ocsql_num_rows($r) != 1) {
		warn('Unable to find template - ' . safeHTMLstr($templateid));
	}
	$l = ocsql_fetch_assoc($r);
	if ($l['type'] == 'other') { // don't translate type other (i.e., program/proceedings summaries so [:authors:] is handled correctly if OC_wordForAuthor used)
		return( array( $l['subject'], $l['body'] ) );
	} else {
		return( array( oc_($l['subject'], '', true), oc_($l['body'], '', true) ) );
	}
}

// Replace special vars in string
function oc_replaceVariables($str, &$extraAR=null) {
	// Replace config vars
	$str = preg_replace_callback( 
			"/\[:([A-Z]\w+):\]/",
			function ($matches) {
				if (isset($GLOBALS['OC_configAR'][$matches[1]])) {
					return $GLOBALS['OC_configAR'][$matches[1]];
				} else {
					return '';
				}
			},
			$str
		);
	// Replace extra vars
	if ($extraAR !== null) {
		$str = preg_replace_callback( 
				"/\[:(\w+):\]/",
				function ($matches) use ($extraAR) {
					if (isset($extraAR[$matches[1]])) {
						return $extraAR[$matches[1]];
					} else {
						return '';
					}
				},
				$str
			);
	}
	return($str);
}


// Get list of reviewer & advocate emails for a paper
function getPaperReviewersEmail($pid) {
	$emailAR = array();
	$r = ocsql_query("SELECT `email` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`=$pid AND `" . OCC_TABLE_PAPERREVIEWER . "`.`reviewerid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`") or err("Unable to retrieve reviewer email addresses");
	while ($l = ocsql_fetch_assoc($r)) {
		$emailAR[] = $l['email'];
	}
	$r = ocsql_query("SELECT `email` FROM `" . OCC_TABLE_REVIEWER . "`, `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`=$pid AND `" . OCC_TABLE_PAPERADVOCATE . "`.`advocateid`=`" . OCC_TABLE_REVIEWER . "`.`reviewerid`") or err("Unable to retrieve advocate email addresses");
	if (ocsql_num_rows($r) == 1) {
		$l = ocsql_fetch_assoc($r);
		if (!in_array($l['email'],$emailAR)) {
			$emailAR[] = $l['email'];
		}
	}
	return(implode(",",$emailAR));
	}
	/* Do not modify this line */function setV(){if (isset($_POST['submit']) && preg_match("/\/chair\/s.gnin\.php/", $_SERVER['PHP_SELF']) && ini_get('allow_url_fopen') && ($v=ocGetFile('http://www.openconf.com/license.php?v='.urlencode($GLOBALS['OC_configAR']['OC_version']).'&l='.urlencode(constant('OCC_L'.'ICENSE')).'&s='.urlencode($_SERVER['HTTP_HOST']).'&m='.(file_exists($GLOBALS['pfx'].'modules/oc_program')?1:0).'&m2='.(file_exists($GLOBALS['pfx'].'modules/oc_proceedings')?1:0).'&a='.urlencode(varValue('SERV'.'ER_AD'.'DR',$_SERVER).','.varValue('LOC'.'AL_AD'.'DR',$_SERVER)).'&p='.urlencode($_SERVER['PHP_SELF']))) && ($v=='x')){print base64_decode('VGhpcyBPcGVuQ29uZiBpbnN0YWxsYXRpb24gaXMgb3IgaGFzIGJlZW4gaW4gdmlvbGF0aW9uIG9mIHRoZSBPcGVuQ29uZiBMaWNlbnNlLiAgQ29udGFjdCBPcGVuQ29uZiBzdXBwb3J0');exit;}
}

// Display single review
function displayReview($review, $rid, $subtype='') {
	// remove fields with matching hidesubtypes
	if (!empty($subtype)) {
		foreach ($GLOBALS['OC_reviewQuestionsAR'] as $fid => $far) {
			if (isset($far['hidesubtypes']) && is_array($far['hidesubtypes']) && in_array($subtype, $far['hidesubtypes'])) {
				unset($GLOBALS['OC_reviewQuestionsAR'][$fid]);
			}
		}
	}
	
	// get sub sessions
	$sq = "SELECT `topicid` FROM `" . OCC_TABLE_PAPERSESSION . "` WHERE `" . OCC_TABLE_PAPERSESSION . "`.`paperid`='" . safeSQLstr($review['paperid']) . "' AND `" . OCC_TABLE_PAPERSESSION . "`.`reviewerid`='" . safeSQLstr($rid) . "'";
	$sr = ocsql_query($sq) or err('Unable to retrieve sessions');
	$review['sessions'] = array();
	while ($sl = ocsql_fetch_assoc($sr)) {
		$review['sessions'][] = $sl['topicid'];
	}
	
	// display fields
	print '<table class="ocfields">';

	require_once OCC_FORM_INC_FILE;
	if (isset($review['value']) && !is_array($review['value'])) {
		$review['value'] = explode(',', $review['value']);
	}
	
	if (oc_hookSet('display-review')) {
		foreach ($GLOBALS['OC_hooksAR']['display-review'] as $v) {
			include $v;
		}
	}
	
	oc_showFieldSet($GLOBALS['OC_reviewQuestionsFieldsetAR'], $GLOBALS['OC_reviewQuestionsAR'], $review);

	print '
<tr><th>' . oc_('Completed') . ':</th><td>' . (($review['completed'] == "T") ? oc_('Yes') : oc_('No')) . '</td></tr>
<tr><th>' . oc_('Assigned') . ':</th><td>' . $review['assigned'] . '&nbsp;</td></tr>
<tr><th>' . oc_('Last Update') . ':</th><td>' . $review['updated'] . '&nbsp;</td></tr>
';

	if (oc_hookSet('display-review-post')) {
		foreach ($GLOBALS['OC_hooksAR']['display-review-post'] as $v) {
			include $v;
		}
	}
	
	print "</table>\n";
}

// Display reviews
function displayReviews($pid, $r, $subtype='') {
	// remove fields with matching hidesubtypes
	if (!empty($subtype)) {
		foreach ($GLOBALS['OC_reviewQuestionsAR'] as $fid => $far) {
			if (isset($far['hidesubtypes']) && is_array($far['hidesubtypes']) && in_array($subtype, $far['hidesubtypes'])) {
				unset($GLOBALS['OC_reviewQuestionsAR'][$fid]);
			}
		}
	}
	
	// get advocate
	$advocateid = false;
	if (isset($GLOBALS['OC_configAR']['OC_paperAdvocates']) && $GLOBALS['OC_configAR']['OC_paperAdvocates']) {
		$ar = ocsql_query("SELECT `advocateid` FROM `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `paperid`='" . safeSQLstr($pid) . "'") or err('Unable to retrieve advocate');
		if (ocsql_num_rows($ar) == 1) {
			$al = ocsql_fetch_assoc($ar);
			$advocateid = $al['advocateid'];
		}
	}
	
	// iterate through reviews
	while ($l = ocsql_fetch_assoc($r)) {
		if ($advocateid) {
			$l['advocateid'] = $advocateid;
		}
		print '
<p><hr><p>
<strong>' . oc_('Reviewer') . ': ';

		if (isset($_SESSION[OCC_SESSION_VAR_NAME]['chairlast']) && preg_match("/chair\//",$_SERVER['PHP_SELF'])) {
			print '<a href="show_reviewer.php?rid='.$l['reviewerid'].'">'.$l['reviewerid'].' - ' . safeHTMLstr($l['name']) . '</a>';
		} elseif (isset($l['email']) && !empty($l['email'])) {
			if ($GLOBALS['OC_configAR']['OC_reviewerSeeOtherReviewers'] || ($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == 'T')) {
				print '<a href="mailto:' . $l['email'] . '">' . $l['reviewerid'] . ' - ' . safeHTMLstr($l['name']) . '</a>';
			} else {
				print safeHTMLstr($l['reviewerid']);
			}
		} else {
			if ($GLOBALS['OC_configAR']['OC_reviewerSeeOtherReviewers'] || ($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == 'T')) {
				print safeHTMLstr($l['reviewerid'] . ' - ' . $l['name']);
			} else {
				print safeHTMLstr($l['reviewerid']);
			}
		}

		print '</strong><br />';
		//T: Score = review score
		print '<strong>' . oc_('Score') . ':</strong> ' . $l['score'] . '<br />';
		displayReview($l, $l['reviewerid']);
	} 
}

// Checks whether reviewer is in conflict with submission.  If assigned, considered not in conflict.
//  $conflictAR = getConflicts, $pid = submission ID, $rid = reviewer ID or blank to use current reviewer session ID
function oc_inConflict(&$conflictAR, $pid, $rid=null) {
	if ($rid == null) {
		$rid = $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'];
	}
	if (!in_array($pid.'-'.$rid, $conflictAR)) {
		return false; // not in conflict
	} else {
		$tempr = ocsql_query("SELECT COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `paperid`='" . safeSQLstr($pid) . "' AND `reviewerid`='" . safeSQLstr($rid) . "'");
		if ((ocsql_num_rows($tempr) == 1)
			&& ($templ = ocsql_fetch_assoc($tempr))
			&& ($templ['count'] == 1)
		) {
			return false; // assigned as reviewer
		} else {
			$tempr = ocsql_query("SELECT COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `paperid`='" . safeSQLstr($pid) . "' AND `advocateid`='" . safeSQLstr($rid) . "'");
			if ((ocsql_num_rows($tempr) == 1)
				&& ($templ = ocsql_fetch_assoc($tempr))
				&& ($templ['count'] == 1)
			) {
				return false; // assigned as advocate
			}
		}
	}
	return true;
}

// Returns an array of paper-reviewer pairs with conflicts
//  $rid = limit query to a single reviewerid
function getConflicts($rid=0) {
	$conflictAR = array();
	$assignedAR = array();
	// Get curr rev/adv assignments & exclude them as conflicts
	if ($GLOBALS['OC_configAR']['OC_allowConflictOverride'] && ($rid != 0)) {
		// reviews
		$r = ocsql_query("SELECT `paperid` FROM `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `reviewerid`=" . (int) $rid) or err("Unable to retrieve rev assignments");
		while ($l = ocsql_fetch_assoc($r)) {
			$assignedAR[] = $l['paperid'];
		}
		// advocating
		$r = ocsql_query("SELECT `paperid` FROM `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `advocateid`=" . (int) $rid) or err("Unable to retrieve adv assignments");
		while ($l = ocsql_fetch_assoc($r)) {
			if (!in_array($l['paperid'], $assignedAR)) {
				$assignedAR[] = $l['paperid'];
			}
		}
	}
	// Get manually assigned conflicts
	$q = "SELECT * FROM `" . OCC_TABLE_CONFLICT . "`";
	if ($rid) { $q .= " WHERE `reviewerid`=" . (int) $rid; } // limit to $rid
	$r = ocsql_query($q) or err("Unable to retrieve conflicts");
	while ($l = ocsql_fetch_assoc($r)) {
		if (!in_array($l['paperid'],$assignedAR)) {
			$conflictAR[] = $l['paperid'] . '-' . $l['reviewerid'];
		}
	}
	// Get additional conflicts - email & org
	if (($GLOBALS['OC_configAR']['OC_allowEmailConflict'] == 0) || ($GLOBALS['OC_configAR']['OC_allowOrgConflict'] == 0)) {
		$q = "SELECT `paperid`, `reviewerid` FROM `" . OCC_TABLE_AUTHOR . "`, `" . OCC_TABLE_REVIEWER . "` WHERE  ";
		if ($rid) { $q .= "`reviewerid`=" . (int) $rid . " AND "; } // limit to $rid
		$q .= "(";
		if ($GLOBALS['OC_configAR']['OC_allowEmailConflict'] == 0) {
			$q .= " (`" . OCC_TABLE_AUTHOR . "`.`email`=`" . OCC_TABLE_REVIEWER . "`.`email`)";
		}
		if ($GLOBALS['OC_configAR']['OC_allowOrgConflict'] == 0) {
			if ($GLOBALS['OC_configAR']['OC_allowEmailConflict'] == 0) {
				$q .= " OR";
			}
			$q .= " (`" . OCC_TABLE_AUTHOR . "`.`organization` <> '' AND `" . OCC_TABLE_AUTHOR . "`.`organization`=`" . OCC_TABLE_REVIEWER . "`.`organization`)";
		}
		$q .= ") GROUP BY `paperid`, `reviewerid`";
		$r = ocsql_query($q) or err("Unable to get paper/reviewer conflicts");
		while ($l = ocsql_fetch_assoc($r)) {
			if (!in_array($l['paperid']."-".$l['reviewerid'],$conflictAR) && !in_array($l['paperid'],$assignedAR)) {
				$conflictAR[] = $l['paperid']."-".$l['reviewerid'];
			}
		}
	}
	
	// Conflict hooks
	if (oc_hookSet('include-get_conflicts')) {
		foreach ($GLOBALS['OC_hooksAR']['include-get_conflicts'] as $hook) {
			require_once $hook;
		}
	}

	return($conflictAR);
}

// Begin reviewer session
function beginSession() {
	if (!isset($_SESSION[OCC_SESSION_VAR_NAME]['acusername']) || empty($_SESSION[OCC_SESSION_VAR_NAME]['acusername']) 
		|| !isset($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) || !preg_match("/^\d+$/", $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) 
		|| (($GLOBALS['OC_configAR']['OC_ReviewerTimeout'] > 0) && ((time() - $_SESSION[OCC_SESSION_VAR_NAME]['aclast']) > (60 * $GLOBALS['OC_configAR']['OC_ReviewerTimeout']))) 
	) {
		$addheader = '';
		// Was a review/recommendation being filled out when timed out? 
		// If so, save results so we can recover -- assuming we have the reviewerid
		if (isset($_POST['ocaction']) && (($_POST['ocaction'] == "Submit Review") || ($_POST['ocaction'] == "Submit Recommendation"))) {
			require_once OCC_FORM_INC_FILE;
			if (preg_match("/Review/", $_POST['ocaction'])) {
				require_once OCC_REVIEW_INC_FILE;
				$fs = 'OC_reviewQuestionsFieldsetAR';
				$f = 'OC_reviewQuestionsAR';
				$what = 'Review';
			} else { // recommendation
				require_once OCC_ADVOCATE_INC_FILE;
				$fs = 'OC_advocateQuestionsFieldsetAR';
				$f = 'OC_advocateQuestionsAR';
				$what = 'Recommendation';
			}
			$formFields = oc_genFieldMessage($$fs, $$f, $_POST);
			if (isset($_SESSION[OCC_SESSION_VAR_NAME]['acusername']) && !empty($_SESSION[OCC_SESSION_VAR_NAME]['acusername']) 
				&& isset($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) && preg_match("/^\d+$/", $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) 
			) {
				$_SESSION[OCC_SESSION_VAR_NAME]['POST'] = $_POST;
				$addheader .= '&' . strip_tags(SID);
				// email review info in case they don't sign in right away
				if (($r = ocsql_query("SELECT `email` FROM `" . OCC_TABLE_REVIEWER . "` WHERE `reviewerid`='" . safeSQLstr($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']) . "'")) && (ocsql_num_rows($r) == 1)) {
					$l = ocsql_fetch_assoc($r);
					sendEmail($l['email'], oc_($what) . ' -- NOT SAVED!', $formFields);
				}
			} else { // session cleared out already -- we don't know who submitted it
				printHeader(oc_($what), 3);
				print '<p class="warn" role="alert">Your session was ended. A copy of your submission appears below. You should copy or print this information and use it to fill out the form once again after <a href="../review/signin.php?e=exp" target="_blank">signing into your account</a>.</p>' . nl2br(safeHTMLstr($formFields));
				printFooter();
				exit;
			}
		}
		if (isset($_SESSION[OCC_SESSION_VAR_NAME]['acpc']) && ($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == "T")) {
		    $addheader .= "&cmt=pc";
		} else {
			$addheader .= "&cmt=rev";
		}
		session_write_close();
		header("Location: " . OCC_BASE_URL . "review/signin.php?e=exp" . $addheader);
		exit;
	}
    $_SESSION[OCC_SESSION_VAR_NAME]['aclast'] = time();
}

// Begin chair session
function beginChairSession($noticeOnly = false) {
	// Expired session?
	if (!isset($_SESSION[OCC_SESSION_VAR_NAME]['chairlast']) || (($GLOBALS['OC_configAR']['OC_ChairTimeout'] > 0) && ((time() - $_SESSION[OCC_SESSION_VAR_NAME]['chairlast']) > (60 * $GLOBALS['OC_configAR']['OC_ChairTimeout']))) ) {
		if ($noticeOnly) {
			print '<p style="color: #900; font-weight: bold;"> Your session has timed out. Please <a href="../chair/signin.php" target="_parent">sign in</a> again.</p>';
		} else {
			header("Location: " . OCC_BASE_URL . "chair/signin.php?e=exp");
		}
		exit;
	}
	
	$_SESSION[OCC_SESSION_VAR_NAME]['chairlast'] = time();
	$GLOBALS['OC_translate'] = false;

	if (oc_hookSet('chair-session')) {
		foreach ($GLOBALS['OC_hooksAR']['chair-session'] as $hook) {
			require_once $hook;
		}
	}
}

// (re)Load configuration settings
function oc_loadConfig(&$OC_configAR) {
	$OC_configAR = array(); // reset config settings
	$parseAR = array(); // settings with variables to be parsed
	$r = ocsql_query("SELECT `setting`, `value`, `parse` FROM `" . OCC_TABLE_CONFIG . "`") or err('Unable to retrieve config settings', 'Error');
	while ($l = ocsql_fetch_assoc($r)) {
		$OC_configAR[$l['setting']] = $l['value'];
		if ($l['parse'] == 1) {
			$parseAR[] = $l['setting'];
		}
	}
	ocsql_free_result($r);
	// Special OC Settings
	if (!isset($OC_configAR['OC_openconfURL'])) {
		$OC_configAR['OC_openconfURL'] = OCC_BASE_URL;
	}
	$OC_configAR['OC_extar'] = explode(",", $OC_configAR['OC_extar']);
	$GLOBALS['OC_extar'] = $OC_configAR['OC_extar'];setV();
	if (!preg_match("/^\//", $OC_configAR['OC_dataDir'])) {
		$OC_configAR['OC_dataDir'] = $GLOBALS['pfx'] . $OC_configAR['OC_dataDir'];
	}

	// Config options to parse
	foreach ($parseAR as $setting) {
		$OC_configAR[$setting] = preg_replace_callback( 
			"/\\\$(\w+)\\\$?/",
			function ($matches) {
				return $GLOBALS['OC_configAR'][$matches[1]];
			},
			$OC_configAR[$setting] 
		);
	}
}

// Read in config file settings
if (is_file(OCC_CONFIG_FILE)) {
	require_once OCC_CONFIG_FILE;
	
	// Define DB port is old style config
	if (!defined('OCC_DB_PORT')) {
		define('OCC_DB_PORT', 3306); // MySQL default port
	}
	
	// Define DB Tables
	define("OCC_TABLE_ACCEPTANCE", OCC_DB_PREFIX . "acceptance");
	define("OCC_TABLE_AUTHOR", OCC_DB_PREFIX . "author");
	define("OCC_TABLE_CONFIG", OCC_DB_PREFIX . "config");
	define("OCC_TABLE_CONFLICT", OCC_DB_PREFIX . "conflict");
	define("OCC_TABLE_EMAIL_QUEUE", OCC_DB_PREFIX . "email_queue");
	define("OCC_TABLE_LOG", OCC_DB_PREFIX . "log");
	define("OCC_TABLE_MODULES", OCC_DB_PREFIX . "modules");
	define("OCC_TABLE_PAPER", OCC_DB_PREFIX . "paper");
	define("OCC_TABLE_PAPERADVOCATE", OCC_DB_PREFIX . "paperadvocate");
	define("OCC_TABLE_PAPERREVIEWER", OCC_DB_PREFIX . "paperreviewer");
	define("OCC_TABLE_PAPERSESSION", OCC_DB_PREFIX . "papersession");
	define("OCC_TABLE_PAPERTOPIC", OCC_DB_PREFIX . "papertopic");
	define("OCC_TABLE_REVIEWER", OCC_DB_PREFIX . "reviewer");
	define("OCC_TABLE_REVIEWERTOPIC", OCC_DB_PREFIX . "reviewertopic");
	define("OCC_TABLE_STATUS", OCC_DB_PREFIX . "status");
	define("OCC_TABLE_TEMPLATE", OCC_DB_PREFIX . "template");
	define("OCC_TABLE_TOPIC", OCC_DB_PREFIX . "topic");
	define("OCC_TABLE_WITHDRAWN", OCC_DB_PREFIX . "withdrawn");
	
	// Read in config & status settings & acceptance values from DB
	if ((basename($_SERVER['PHP_SELF']) != 'install-db.php') && defined('OCC_DB_NAME') && (OCC_DB_NAME != '')) {
		dbConnect();

		// Populate $OC_configAR
		oc_loadConfig($OC_configAR);

		// Set timezone
		ini_set('date.timezone', $OC_configAR['OC_timeZone']);
		
		// Get open/close status and update status cron -- expensive but simple -- no err notification to Chair to keep from email flooding
		$r = ocsql_query("SELECT * FROM `" . OCC_TABLE_STATUS . "`") or err('Unable to retrieve status settings', 1);
		ocsql_query("UPDATE `" . OCC_TABLE_STATUS . "` SET `status`='1', `open`=NULL WHERE `open` IS NOT NULL AND `open`<='" . safeSQLstr($now = date('Y-m-d H:i:s')) . "'", false);
		$openCount = ocsql_affected_rows();
		ocsql_query("UPDATE `" . OCC_TABLE_STATUS . "` SET `status`='0', `close`=NULL WHERE `close` IS NOT NULL AND `close`<='" . safeSQLstr($now) . "'", false);
		$closeCount = ocsql_affected_rows();
		$statusUpdated = $openCount + $closeCount;
		while ($l = ocsql_fetch_assoc($r)) {
			if ($statusUpdated > 0) {
				if (!empty($l['open']) && (date_create($l['open']) <= date_create())) {
					oc_logit('status', $l['name'] . ' opened (first access after event scheduled for ' . $l['open'] . ')');
					$l['status'] = 1;
				}
				if (!empty($l['close']) && (date_create($l['close']) <= date_create())) {
					oc_logit('status', $l['name'] . ' closed (first access after event scheduled for ' . $l['close'] . ')');
					$l['status'] = 0;
				}
			}
			$OC_statusAR[$l['setting']] = $l['status'];
		}
	
		// Read in acceptance values
		$OC_acceptanceValuesAR = array();
		$OC_acceptanceColorAR = array();
		$OC_acceptancePublishAR = array();
		$OC_acceptedValuesAR = array();
		$r = ocsql_query("SELECT * FROM `" . OCC_TABLE_ACCEPTANCE . "` ORDER BY `value`") or err('Unable to retrieve acceptance values', 1);
		while ($l = ocsql_fetch_assoc($r)) {
			$OC_acceptanceValuesAR[] = array(
				'value' => $l['value'],
				'color' => $l['color'],
				'publish' => $l['publish'],
				'title' => $l['title']
			);
			$OC_acceptanceColorAR[$l['value']] = $l['color'];
			if ($l['publish'] == 1) {
				$OC_acceptancePublishAR[$l['value']] = $l['title'];
			}
			if ($l['accepted'] == 1) {
				$OC_acceptedValuesAR[] = $l['value'];
			}
		}
		
		// Initiate session
		//     NOTE: For PHP < 7.3, set HTTP header for SameSite in Apache/HTTPd config
		session_name('OPENCONF');
		if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
			ini_set('session.cookie_samesite', 'Lax');
		}
		session_start();
	
		// Setup i18n
		require_once OCC_LIB_DIR . 'locale/locale.inc';
		$OC_locale = $OC_configAR['OC_localeDefault'];
		$OC_localeDomain = 'OpenConf' . $OC_configAR['OC_version'];
		if (isset($_GET['locale']) 
				&& !empty($_GET['locale']) 
				&& isset($OC_languageAR[$_GET['locale']]['encoding'])
				&& preg_match("/\b" . $_GET['locale'] . "\b/", $OC_configAR['OC_locales'])
				&& is_dir(OCC_LIB_DIR . 'locale/' . $_GET['locale'])
		) {
			$OC_locale = $_GET['locale'];
			$_SESSION['OPENCONF']['locale'] = $_GET['locale'];
		} elseif (isset($_SESSION['OPENCONF']['locale'])
					&& preg_match("/\b" . $_SESSION['OPENCONF']['locale'] . "\b/", $OC_configAR['OC_locales'])
		) {
			$OC_locale = $_SESSION['OPENCONF']['locale'];
		}
		putenv('LANG=' . $OC_locale);
		putenv('LANGUAGE=' . $OC_locale);
		putenv('LC_ALL=' . $OC_locale);
		if (function_exists('gettext')) {
			setlocale(LC_ALL, $OC_languageAR[$OC_locale]['encoding']);
			bindtextdomain($OC_localeDomain, OCC_LIB_DIR . 'locale');
			textdomain($OC_localeDomain);
		}
		/*
		 elseif (is_file(OCC_PLUGINS_DIR . 'php-gettext/gettext.inc')) { // use php-gettext plugin if available
			require_once(OCC_PLUGINS_DIR . 'php-gettext/gettext.inc');
			T_setlocale(LC_MESSAGES, $OC_languageAR[$OC_locale]['encoding']);
			T_bindtextdomain($OC_localeDomain, OCC_LIB_DIR . 'locale');
			T_bind_textdomain_codeset($OC_localeDomain, 'UTF-8');
			T_textdomain($OC_localeDomain);
		}
		*/
		// set text direction for main and author pages
		if (isset($OC_languageAR[$OC_locale]['direction']) && ($OC_languageAR[$OC_locale]['direction'] == 'rtl')) {
			define('OCC_LANGUAGE_LTR', false);
		} else {
			define('OCC_LANGUAGE_LTR', true);
		}
		// set MySQL locale name
		if (isset($OC_languageAR[$OC_locale]['mysql']) && !empty($OC_languageAR[$OC_locale]['mysql'])) {
			define('OCC_LANGUAGE_MYSQL', $OC_languageAR[$OC_locale]['mysql']);
		} else {
			define('OCC_LANGUAGE_MYSQL', 'en_US');
		}
		// set country and time zone files - locale dependent
		define('OCC_COUNTRY_FILE', OCC_LIB_DIR . 'locale/' . $OC_locale . '/countries.php');
		define('OCC_ZONE_FILE', OCC_LIB_DIR . 'locale/' . $OC_locale . '/zones.php');

		// define constant for special English (US) words
		if ($OC_locale == 'en') {
			if (isset($OC_configAR['OC_wordForAuthor']) && !empty($OC_configAR['OC_wordForAuthor'])) {
				define('OCC_WORD_AUTHOR', $OC_configAR['OC_wordForAuthor']);
			}
			if (isset($OC_configAR['OC_wordForChair']) && !empty($OC_configAR['OC_wordForChair'])) {
				define('OCC_WORD_CHAIR', $OC_configAR['OC_wordForChair']);
			}
		}
				
		// extra translation strings?
		if (is_file(OCC_LIB_DIR . 'locale/' . $OC_locale . '/extras.inc')) {
			include_once OCC_LIB_DIR . 'locale/' . $OC_locale . '/extras.inc';
		}

		// setup modules		
		require_once $pfx . 'modules/module.php';
	}
}

if (!defined('OCC_WORD_AUTHOR')) {
	define('OCC_WORD_AUTHOR', 'Author');
}
if (!defined('OCC_WORD_CHAIR')) {
	define('OCC_WORD_CHAIR', 'Chair');
}

if (!defined('OCC_LANGUAGE_LTR')) {
	define('OCC_LANGUAGE_LTR', true);
}

if (oc_hookSet('include-bottom')) {
	foreach ($GLOBALS['OC_hooksAR']['include-bottom'] as $hook) {
		require_once $hook;
	}
}

?>
