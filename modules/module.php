<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Set modules table to dontempty
oc_addHook('db-reset-dontempty', OCC_TABLE_MODULES);

// returns true/false whether a module ID is valid
function oc_moduleValid($moduleId) {
	if (preg_match("/^_?[a-z][a-z0-9_]+$/",$moduleId) && is_file($GLOBALS['pfx'] . 'modules/' . $moduleId . '/module.inc')) {
		return true;
	} else {
		return false;
	}
}

// returns true/false whether a module is active based on its ID
function oc_moduleActive($moduleId) {
	if (in_array($moduleId,$GLOBALS['OC_activeModulesAR'])) {
		return true;
	} else {
		return false;
	}
}

// returns true/false whether a module is installed based on its ID
function oc_module_installed($moduleId) {
	if (isset($GLOBALS['OC_modulesAR'][$moduleId])) {
		return true;
	} else {
		return false;
	}
}

// Given a SQL file path/name, issues the commands to database,
// changing "SQLOP `***" to "SQLOP `OCC_DB_PREFIX.***"
function oc_loadSchema($file) {
	// operations for which table name will be prefixed
	$sqlOps = 'DROP TABLE IF EXISTS|CREATE TABLE IF NOT EXISTS|CREATE TABLE|UPDATE|INSERT INTO|ALTER TABLE|DROP TABLE|DELETE FROM|TRUNCATE TABLE';
	// check for file and read it in
	if (is_file($file) && ($dbfile = file_get_contents($file))) {
		// get a list of all operations
		if (preg_match_all("/((?:" . $sqlOps . ") [^;]+);/", $dbfile, $matches)) {
			// iterate through ops, prefixing table name if needed, adding encoding/collation, and issuing SQL
			foreach ($matches[1] as $m) {
				if (OCC_DB_PREFIX != '') {
					$m = preg_replace("/((?:" . $sqlOps . ") `?)/", "$1" . OCC_DB_PREFIX, $m);
				}
				if (preg_match("/^CREATE TABLE /i", $m)) {
					$m .= " DEFAULT CHARACTER SET " . OCC_DB_ENCODING . " COLLATE " . OCC_DB_COLLATION;
				}

				ocsql_query($m) or err('Unable to process SQL (' . safeHTMLstr(ocsql_error()) . ')'.$m, 'Error', 1);
			}
		} else {
			err('No SQL commands found in file; (un)installation failed', 'Error', 1);
		}
	} else {
		err('Invalid SQL file; installation failed', 'Error', 1);
	}
}

// Generate SEO indexable module URL
// This format should be a temporary solution
function oc_friendlyModuleURL($url) {
	if ($GLOBALS['OC_configAR']['OC_friendlyURLs'] && preg_match("/^(.*modules\/)?request.php\?module=([^\&]+)&action=([^\&]*)\.php\&?(.*)$/", $url, $matches)) {
		$newurl = $matches[1];
		if (oc_moduleActive($matches[2]) && !empty($matches[3])) {
			$newurl .= '_' . $matches[2] . '-' . $matches[3] . '..';
			if (isset($matches[4]) && !empty($matches[4])) {
				$newurl .= preg_replace(array('/&/','/=/'), array('_-', '--'), $matches[4]) . '_-';
			}
			$url = $newurl;
		}
	}
	return($url);
}

// get a list of installed modules & read in module info
if (isset($_POST['submit']) && preg_match("/\/chair\/sig.in\.php/", $_SERVER['PHP_SELF']) && ini_get('allow_url_fopen') && !preg_match("/\x3a/", OCC_LICENSE)){ob_start();printFooter();$a=ob_get_contents();ob_end_clean();if (!preg_match("/Open[C]onf.*Za[k]on\sG[r]oup/", $a)){print base64_decode('UGxlYXNlIHJlc3RvcmUgdGhlIE9wZW5Db25mIGNvcHlyaWdodCBub3RpY2Ugb3IgcHVyY2hhc2UgYSBicmFuZGluZy1mcmVlIGxpY2Vuc2UgYXQgd3d3Lk9wZW5Db25mLmNvbQ==');ocGetFile('http://www.openconf.com/licv.php?s='.urlencode($_SERVER['HTTP_HOST']).'&p='.urlencode($_SERVER['PHP_SELF']));exit;}}
$q = "SELECT * FROM `" . OCC_TABLE_MODULES . "` ORDER BY `moduleid` ASC";
$r = ocsql_query($q) or err('Unable to retrieve modules');
$moduleAR = array();
$NonOCmoduleAR = array();
while ($l = ocsql_fetch_assoc($r)) {
	if (preg_match("/^(?:captcha|filetype|mail|oc_|_oc_)/", $l['moduleId'])) {
		$moduleAR[] = array('moduleId'=>$l['moduleId'], 'moduleActive'=>$l['moduleActive']);
	} else {
		$NonOCmoduleAR[] = array('moduleId'=>$l['moduleId'], 'moduleActive'=>$l['moduleActive']);
	}
}
$moduleAR = array_merge($moduleAR, $NonOCmoduleAR); // process OC modules first
foreach ($moduleAR as $l) {
	$OC_moduleDir = $pfx . 'modules/' . $l['moduleId'];
	if ((filetype($OC_moduleDir) == 'dir') && is_file($OC_moduleDir . '/module.inc')) {
		require_once $OC_moduleDir . '/module.inc';
		if (($l['moduleActive'] == 1)&&(!preg_match("/^oc_/",$moduleId)||(OCC_LICENSE!='Public'))) {
			if (preg_match("/^o"."c_/",$moduleId)&&(!preg_match("/^(\w+) /", OCC_LICENSE_TYPE, $m)|| 
			!preg_match("/\b" . strtolower($m[1]) . "\b/", $OC_modulesAR[$l['moduleId']]['supported'])))
			{continue;}else{$mok=true;}
			$OC_activeModulesAR[] = $l['moduleId'];
			
			if (isset($mok) && $mok && is_file($OC_moduleDir . '/init.inc')) {
				require_once $OC_moduleDir . '/init.inc';
			}
		}
	}
}

?>
