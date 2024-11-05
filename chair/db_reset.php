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

printHeader("Database Reset",1);

// Module pre hook
if (oc_hookSet('db-reset-pre')) {
	foreach ($OC_hooksAR['db-reset-pre'] as $f) {
		require_once $f;
	}
}

$tableAR=getTables();

// Tables that should not be emptied
$dontEmptyTablesAR = array(OCC_TABLE_ACCEPTANCE, OCC_TABLE_CONFIG, OCC_TABLE_STATUS, OCC_TABLE_TEMPLATE, OCC_TABLE_TOPIC);
if (oc_hookSet('db-reset-dontempty')) {
	foreach ($OC_hooksAR['db-reset-dontempty'] as $t) {
		$dontEmptyTablesAR[] = $t;
	}
}

if (isset($_POST['submit'])) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	if ($_POST['submit'] == "Confirm Request") {
		foreach ($tableAR as $table) {
			if (isset($_POST['table_'.$table]) && ($_POST['table_'.$table] == 1)) {
				issueSQL("TRUNCATE $table");
			}
		}
		print '<p class="note" style="text-align: center">Selected tables have been emptied</p>';
	} elseif ($_POST['submit'] == "Empty Tables") {
		print '<p><strong>Please confirm that you want to empty (truncate) the tables:</strong></p><form method="post" action="' . $_SERVER['PHP_SELF'] . '"><input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" /><ul>';
		foreach ($tableAR as $table) {
			if (isset($_POST['table_'.$table]) && ($_POST['table_'.$table] == 1)) {

				print '<li>' . $table . '<input type="hidden" name="table_' . $table . '" value="1" /></li>';
			}
		}
		print '</ul><br /><input type="submit" name="submit" class="submit" value="Confirm Request" /></form><br />';
	} else {
		err("Unknown submit option");
	}
} else {
	print '<p class="note">NOTE: Once emptied, the data in the tables cannot be recovered.  Make a <a href="db_backup.php">backup</a>, and use caution when deciding which <a href="https://www.openconf.com/documentation/tables.php" target="_blank" title="description of tables will open in new window">tables</a> to empty.</p><p><strong>Select tables to empty:</strong></p><form method="post" action="' . $_SERVER['PHP_SELF'] . '"><input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />';
	foreach ($tableAR as $table) {
		print '<label><input type="checkbox" name="table_' . $table . '" value="1" ';
		if (!in_array($table,$dontEmptyTablesAR)) { print ' checked'; }
		print ' /> ' . $table . '</label><br />';
	}
	print '<br /><input type="submit" name="submit" value="Empty Tables" class="submit" /></form><br />';
}

printFooter();

?>
