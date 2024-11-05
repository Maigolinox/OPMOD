<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once "../include.php";

beginChairSession();

// Module pre hook
if (oc_hookSet('db-backup-pre')) {
	foreach ($OC_hooksAR['db-backup-pre'] as $f) {
		require_once $f;
	}
}

function backuperr($e) {
	err($e,'Database Backup',1);
}

function quoteit($s) {
	return("`$s`");
}

$tableAR=getTables();

$sqldump = '# OpenConf SQL Backup
# version ' . $GLOBALS['OC_configAR']['OC_version'] . '
# https://www.OpenConf.com
#
# Host: ' . OCC_DB_HOST . '
# Generated: ' . date('Y-m-d H:i:s') . '
# Server version: ' . mysqli_get_server_info($GLOBALS['OC_db']) . '
# PHP version: ' . phpversion() . '
#
# Database: ' . OCC_DB_NAME . '
#
';

foreach ($tableAR as $table) {
	$sqldump .= '


#
# Delete table ' . quoteit($table) . '
#

DROP TABLE IF EXISTS ' . quoteit($table) . ';


#
# Table structure for ' . quoteit($table) . '
#

';

	$q = 'SHOW CREATE TABLE ' . quoteit($table);
	$r = ocsql_query($q) or backuperr("Unable to query table structure for $table");
	if (mysqli_num_rows($r) > 0) {
		$l = mysqli_fetch_row($r);
		$sqldump .= $l[1];
		if (preg_match("/auto_increment/s",$l[1])) {
            mysqli_free_result($r);
			$q = 'SHOW TABLE STATUS LIKE "' . $table . '"';
			$r = ocsql_query($q) or backuperr("Unable to query auto increment value for $table $q");
			$l = mysqli_fetch_array($r);
			$sqldump .= " AUTO_INCREMENT=" . $l['Auto_increment'];
		}
		$sqldump .= ";\n\n\n";
	}
	mysqli_free_result($r);

	$q = 'SELECT * FROM ' . quoteit($table);
	if ($table == 'log') {
		$q .= " WHERE `type` NOT LIKE '%fail'";
	}
	$r = ocsql_query($q) or backuperr("Unable to query table contents for $table");
	$fieldNum = mysqli_num_fields($r);
	$recNum = mysqli_num_rows($r);
	$sqldump .= '
#
# Records in table ' . quoteit($table) . ' (' . $recNum . ')
#

';

	$fieldAR = array();
	for ($f=0; $f < $fieldNum; $f++) {
		$finfo = mysqli_fetch_field_direct($r, $f);
		$fieldAR[$f] = quoteit($finfo->name);
		if (preg_match("/(?:int|timestamp)$/",$finfo->type)) {
			$fieldNumAR[$f] = TRUE;
		} else { $fieldNumAR[$f] = FALSE; }
	}

	$fldVal = array();
	while ($row = mysqli_fetch_row($r)) {
		$sqldump .= 'INSERT INTO ' . quoteit($table) . ' VALUES (';
		for ($f=0; $f < $fieldNum; $f++) {
			if (!isset($row[$f])) {
				$fldVal[] = 'NULL';
			} elseif (($row[$f] == '0') || ($row[$f] != '')) {
				if ($fieldNumAR[$f]) {
					$fldVal[] = $row[$f];
				} else {
					$fldVal[] = "'" . safeSQLstr($row[$f]) . "'";
				}
			} else {
				$fldVal[] = "''";
			}
		}
		$sqldump .= implode(', ', $fldVal) . ");\n";
		unset($fldVal);
	}
	mysqli_free_result($r);

} // for tables

$fileName = 'openconf';
if (preg_match("/^\w+$/", $OC_configAR['OC_confName'])) {
	$fileName .= '-' . $OC_configAR['OC_confName'];
}
$fileName .= '-' . date('YmdHi') . '.sql';

oc_sendNoCacheHeaders();
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $fileName . '"');

print $sqldump;

?>
