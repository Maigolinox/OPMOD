<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// NOTE: This file needs to use mysqli_query vs. ocsql_query

require_once "install-include.php";

$e = '';
if (isset($_POST['submit']) && ($_POST['submit'] == "Setup Database")) {
	// Check for basic info
	if (
		(!isset($_POST['dbport']) || !preg_match("/^\d+$/", $_POST['dbport']))
		||
		(!isset($_POST['dbhost']) || empty($_POST['dbhost']))
		||
		(!isset($_POST['dbuser']) || empty($_POST['dbuser']))
	) {
		$e = 'Information missing below';
	} else {
		// Connect to DB server
		$dbtest = mysqli_init();
		$mysql_flags = null;
		if (isset($_POST['dbssl']) && ($_POST['dbssl'] == 1)) {
			if (isset($_POST['dbssl_noverify']) && ($_POST['dbssl_noverify'] == 1)) {
				$mysql_flags = MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
			} else {
				$mysql_flags = MYSQLI_CLIENT_SSL;
	
			}
			mysqli_ssl_set(
				$dbtest, 
				((isset($_POST['dbssl_key']) && !empty($_POST['dbssl_key'])) ? $_POST['dbssl_key'] : null), 
				((isset($_POST['dbssl_cert']) && !empty($_POST['dbssl_cert'])) ? $_POST['dbssl_cert'] : null), 
				((isset($_POST['dbssl_ca']) && !empty($_POST['dbssl_ca'])) ? $_POST['dbssl_ca'] : null), 
				((isset($_POST['dbssl_capath']) && !empty($_POST['dbssl_capath'])) ? $_POST['dbssl_capath'] : null), 
				((isset($_POST['dbssl_cipher']) && !empty($_POST['dbssl_cipher'])) ? $_POST['dbssl_cipher'] : null)
			);
		}
		if ( ! mysqli_real_connect($dbtest, $_POST['dbhost'], $_POST['dbuser'], varValue('dbpw', $_POST), '', (int)$_POST['dbport'], null, $mysql_flags)) {
			$e = 'Unable to connect with database using information below:<br />' . safeHTMLstr(mysqli_connect_error());
		} else {
			// Specify UTF-8 use for connection
			if (mysqli_query($dbtest, "SET NAMES " . OCC_DB_ENCODING . " COLLATE " . OCC_DB_COLLATION)) {
				// Create DB?
				if (isset($_POST['dbcreate']) && ($_POST['dbcreate'] == 1)) {
					if (preg_match("/^[\w-]+$/", $_POST['dbname'])) {
						$q = "CREATE DATABASE `" . $_POST['dbname'] . "` DEFAULT CHARACTER SET " . OCC_DB_ENCODING . " DEFAULT COLLATE " . OCC_DB_COLLATION;
						if (!mysqli_query($dbtest, $q) && (mysqli_errno($dbtest) != 1007)) {  // 1007=exists
							$e = 'Unable to create database ' . safeHTMLstr($_POST['dbname']) . ". DB Error:<br />" . safeHTMLstr(mysqli_error($dbtest) . " (" . mysqli_errno($dbtest) . ")");
						}
					} else {
						$e = 'Database name limited to letters, numbers, hyphen, and underscore';
					}
				}
			} else {
				$e = 'Unable to set database connection with encoding ' . OCC_DB_ENCODING . ' and collation ' . OCC_DB_COLLATION . '. Check the MySQL version.';
			}
			if (empty($e)) {
			// Check prefix
				if (!empty($_POST['dbprefix']) && (!preg_match("/^[\w-]{0,40}$/",$_POST['dbprefix']))) {
					$e = 'Invalid table prefix; use up to 40 letters, numbers, and the underscore.';
				}
				// Attempt to access DB
				elseif (!mysqli_select_db($dbtest, $_POST['dbname'])) {
					$e = 'Unable to access database ' . safeHTMLstr($_POST['dbname']);
				}
				else {	// Save db info
					if (! $fp = fopen(OCC_LIB_DIR . 'config-sample.php', 'r')) {
						err('Config template file (lib/config-sample.php) does not exist.  Check that you have a full OpenConf distribution and click the Restart Install link above.', $hdr, $hdrfn, false);
					}
					if (!$optionFile = fread($fp, filesize(OCC_LIB_DIR . 'config-sample.php'))) {
						fclose($fp);
						err("Unable to read from config file", $hdr, $hdrfn, false);
					}
					fclose($fp);
					replaceConstantValue('OCC_SESSION_VAR_NAME', 'OPENCONF' . substr(oc_idGen(),3,4), $optionFile); // assign a (hopefully) unique session name in case of multiple installations
					replaceConstantValue('OCC_ENC_KEY', oc_idGen(64), $optionFile); // assign a unique encryption key
					replaceConstantValue('OCC_DB_USER', $_POST['dbuser'], $optionFile);
					replaceConstantValue('OCC_DB_PASSWORD', $_POST['dbpw'], $optionFile);
					replaceConstantValue('OCC_DB_HOST', $_POST['dbhost'], $optionFile);
					replaceConstantValue('OCC_DB_PORT', $_POST['dbport'], $optionFile);
					replaceConstantValue('OCC_DB_NAME', $_POST['dbname'], $optionFile);
					replaceConstantValue('OCC_DB_PREFIX', $_POST['dbprefix'], $optionFile);
					replaceConstantValue('OCC_DB_USE_SSL', ((isset($_POST['dbssl']) && ($_POST['dbssl'] == 1)) ? 1 : 0), $optionFile);
					replaceConstantValue('OCC_DB_SSL_NOVERIFY', ((isset($_POST['dbssl_noverify']) && ($_POST['dbssl_noverify'] == 1)) ? 1 : 0), $optionFile);
					replaceConstantValue('OCC_DB_SSL_KEY', varValue('dbssl_key', $_POST), $optionFile);
					replaceConstantValue('OCC_DB_SSL_CERT', varValue('dbssl_cert', $_POST), $optionFile);
					replaceConstantValue('OCC_DB_SSL_CA', varValue('dbssl_ca', $_POST), $optionFile);
					replaceConstantValue('OCC_DB_SSL_CAPATH', varValue('dbssl_capth', $_POST), $optionFile);
					replaceConstantValue('OCC_DB_SSL_CIPHER', varValue('dbssl_cipher', $_POST), $optionFile);
					if (! $fp = fopen(OCC_CONFIG_FILE,'w')) {
						err('Config file (config.php) cannot be created or is not writeable.  Try creating a blank config.php file manually and ensure file permissions allow config.php to be written to by the server; then click the Restart Install link above.', $hdr, $hdrfn, false);
					}
					if (!fwrite($fp, $optionFile)) {
						fclose($fp);
						err("Unable to write to config file", $hdr, $hdrfn, false);
					}
					fclose($fp);
					
					// Load schema
					if (isset($_POST['dbschema']) && ($_POST['dbschema'] == 1)) {
						if ($dbfile = file_get_contents(OCC_LIB_DIR . "DB.sql")) {
							// create tables
							if (preg_match_all("/(CREATE [^;]+);/", $dbfile, $matches)) {
								foreach ($matches[1] as $m) {
									// add table prefix
									$m = preg_replace("/(CREATE TABLE `?)/", "$1" . slashQuote(stripslashes($_POST['dbprefix'])), $m);
									// add encoding and collation
									$m .= " DEFAULT CHARACTER SET " . OCC_DB_ENCODING . " COLLATE " . OCC_DB_COLLATION;
									if (!mysqli_query($dbtest, $m)) {
										$e = "Error on loading schema -- " . safeHTMLstr(mysqli_error($dbtest)) . ".<br />Database may need to be reset";
										break;
									}
								}
							} else {
								err("No schema found in DB.sql file", $hdr, $hdrfn, false);
							}
							// insert data
							if (empty($e) && preg_match_all("/(INSERT [^;]+);/", $dbfile, $matches)) {
								foreach ($matches[1] as $m) {
									// add table prefix
									$m = preg_replace("/(INSERT INTO `?)/", "$1" . slashQuote(stripslashes($_POST['dbprefix'])), $m);
									if (!mysqli_query($dbtest, $m)) {
										$e = "Error on loading schema -- " . safeHTMLstr(mysqli_error($dbtest)) . ".<br />Database may need to be reset";
										break;
									}
								}
							}
						} else {
							$e = 'Unable to load schema from DB.sql.  Try loading it manually (see INSTALL instructions).';
						}
					}
					if (empty($e)) {
						mysqli_close($dbtest);
						header("Location: " . OCC_BASE_URL . "/chair/install-account.php");
						exit;
					}
				}
			}
			mysqli_close($dbtest);
		}
	}
	$dbuser = varValue('dbuser', $_POST);
	$dbname = varValue('dbname', $_POST);
	$dbhost = varValue('dbhost', $_POST);
	$dbport = varValue('dbport', $_POST, 3306);
	$dbprefix = varValue('dbprefix', $_POST);
	$dbssl = varValue('dbssl', $_POST);
	$dbssl_noverify = varValue('dbssl_verify', $_POST);
	$dbssl_key = varValue('dbssl_key', $_POST);
	$dbssl_cert = varValue('dbssl_cert', $_POST);
	$dbssl_ca = varValue('dbssl_ca', $_POST);
	$dbssl_capath = varValue('dbssl_capath', $_POST);
	$dbssl_cipher = varValue('dbssl_cipher', $_POST);
} else {
	$dbuser = (defined('OCC_DB_USER') ? OCC_DB_USER : '');
	$dbname = (defined('OCC_DB_NAME') ? OCC_DB_NAME : '');
	$dbhost = (defined('OCC_DB_HOST') ? OCC_DB_HOST : '');
	$dbport = (defined('OCC_DB_PORT') ? OCC_DB_PORT : 3306);
	$dbprefix = (defined('OCC_DB_PREFIX') ? OCC_DB_PREFIX : '');
	$dbssl = (defined('OCC_DB_USE_SSL') ? OCC_DB_USE_SSL : '');
	$dbssl_noverify = (defined('OCC_DB_SSL_NOVERIFY') ? OCC_DB_SSL_NOVERIFY : 1);
	$dbssl_key = (defined('OCC_DB_SSL_KEY') ? OCC_DB_SSL_KEY : '');
	$dbssl_cert = (defined('OCC_DB_SSL_CERT') ? OCC_DB_SSL_CERT : '');
	$dbssl_ca = (defined('OCC_DB_SSL_CA') ? OCC_DB_SSL_CA : '');
	$dbssl_capath = (defined('OCC_DB_SSL_CAPATH') ? OCC_DB_SSL_CAPATH : '');
	$dbssl_cipher = (defined('OCC_DB_SSL_CIPHER') ? OCC_DB_SSL_CIPHER : '');
}

printHeader($hdr,$hdrfn);

print '<p style="text-align: center; font-weight: bold">Step 1 of 5: Enter Database Settings</p>';

if (!empty($e)) {
	print '<p style="text-align: center" class="warn">' . $e . '</p>';
}

print '
<script>
function ocinstall_showSSL() {
	var useSSLObj = document.getElementById("dbssl"),
		sslFSObj = document.getElementById("fs_ocinstall-ssl");
	if (dbssl.checked) {
		sslFSObj.style.display="block";
	} else {
		sslFSObj.style.display="none";
	}
}
</script>

<form method="post" action="' . $_SERVER['PHP_SELF'] . '" class="ocform">
';

print '
<fieldset>
<legend>Database General Settings</legend>
<div class="fieldsetnote note">The database user must have the following database privileges: ALTER, CREATE, DELETE, DROP, INSERT, SELECT, TRUNCATE, UPDATE</div>
<div class="field">
<label for="dbuser">Database User:</label>
<input name="dbuser" id="dbuser" size="30" maxlength="30" value="' . safeHTMLstr($dbuser) . '">
</div>
<div class="field">
<label for="dbpw">Database Password:</label>
<input type="password" name="dbpw" id="dbpw" size="30" maxlength="100" value="">
</div>
<div class="field">
<label for="dbhost">Database Hostname:</label>
<input name="dbhost" id="dbhost" size="30" maxlength="100" value="' . safeHTMLstr($dbhost) . '">
</div>
<div class="field">
<label for="dbport">Database Port:</label>
<input name="dbport" id="dbport" size="30" maxlength="100" value="' . safeHTMLstr($dbport) . '">
</div>
<div class="field">
<label for="dbname">Database Name:</label>
<input name="dbname" id="dbname" size="30" maxlength="64" value="' . safeHTMLstr($dbname) . '">
<span class="note">valid characters: &nbsp;a-z &nbsp; 0-9 &nbsp; _ &nbsp; -</span>
</div>
<div class="field">
<label for="dbprefix">Table Prefix:</label>
<input name="dbprefix" id="dbprefix" size="30" maxlength="64" value="' . safeHTMLstr($dbprefix) . '">
<span class="note">optional</span>
</div>
<div class="field">
<label for="dbcreate">Create Database:</label>
<input name="dbcreate" id="dbcreate" type="checkbox" value="1" ' . ((isset($_POST['dbcreate']) && ($_POST['dbcreate'] != 1)) ? '' : 'checked ') . '>
</div>
<div class="field">
<label for="dbschema">Load Schema:</label>
<input name="dbschema" id="dbschema" type="checkbox" value="1" ' . ((isset($_POST['dbschema']) && ($_POST['dbschema'] != 1)) ? '' : 'checked ') . '>
</div>
<div class="field">
<label for="dbssl">Use SSL:</label>
<input name="dbssl" id="dbssl" type="checkbox" value="1" ' . (($dbssl == 1) ? 'checked ' : '') . ' onclick="ocinstall_showSSL()">
</div>
</fieldset>

<div aria-live="polite">
<fieldset id="fs_ocinstall-ssl">
<legend>Database SSL Settings</legend>
<div class="fieldsetnote note">All fields are optional. Enter full path names.</div>
<div class="field">
<label for="dbssl_noverify">Do not Verify Cert:</label>
<input name="dbssl_noverify" id="dbssl_noverify" type="checkbox" value="1" ' . (($dbssl_noverify == 1) ? 'checked ' : '') . '>
</div>
<div class="field">
<label for="dbssl_key">Key File:</label>
<input name="dbssl_key" id="dbssl_key" size="60" value="' . safeHTMLstr($dbssl_key) . '">
</div>
<div class="field">
<label for="dbssl_cert">Certificate File:</label>
<input name="dbssl_cert" id="dbssl_cert" size="60" value="' . safeHTMLstr($dbssl_cert) . '">
</div>
<div class="field">
<label for="dbssl_ca">Cert. Authority File:</label>
<input name="dbssl_ca" id="dbssl_ca" size="60" value="' . safeHTMLstr($dbssl_ca) . '">
</div>
<div class="field">
<label for="dbssl_capath">CA Certificates Path:</label>
<input name="dbssl_capath" id="dbssl_capath" size="60" value="' . safeHTMLstr($dbssl_capath) . '">
</div>
<div class="field">
<label for="dbssl_cipher">Allowed Ciphers:</label>
<input name="dbssl_cipher" id="dbssl_cipher" size="60" value="' . safeHTMLstr($dbssl_cipher) . '">
</div>
</fieldset>
</div>

<p style="text-align: center;"><input type="submit" name="submit" class="submit" value="Setup Database" /></p>

</form>

<p style="text-align: center; margin-top: 2em;" class="note">The above information is stored in config.php';

if (defined('OCC_DB_NAME') && (OCC_DB_NAME != '')) {
	print '.<br />If you already configured config.php and loaded the schema, you may <a href="install-account.php">skip to the next step</a>.';
}

print '
</p>

<script>
ocinstall_showSSL();
</script>
';

printFooter();
?>
