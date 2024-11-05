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

printHeader('Import Committee Members', 1);

require_once OCC_FORM_INC_FILE;
require_once OCC_COMMITTEE_INC_FILE;

$encodingAR = array('UTF-8','ISO-8859-1', 'ISO-8850-2', 'ISO-8859-9', 'ISO-8859-15', 'Big5', 'GB2312', 'EUC-KR', 'EUC-JP', 'SJis', 'Windows-1251', 'Windows-1252');

if (isset($_POST['submit']) && ($_POST['submit'] == "Import Committee Members")) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}
	
	// handle Mac line ending
	if (isset($_POST['lineend']) && ($_POST['lineend'] == 1)) {
		ini_set('auto_detect_line_endings', true);
	}

	if ( // file uploaded ok
		isset($_FILES['csvfile']['error'])									// good upload
		&& ($_FILES['csvfile']['error'] == UPLOAD_ERR_OK)					// no error
		&& is_uploaded_file($_FILES['csvfile']['tmp_name'])					// legitimate upload
		&& ($_FILES['csvfile']['size'] > 0)									// not empty (redundant w/ERR)
		&& (($fp = fopen($_FILES['csvfile']['tmp_name'], "r")) !== false)	// open ok
	) { 
		// retrieve existing reviewer IDs, usernames, email addresses
		$reviewerIdAR = array();
		$usernameAR = array();
		$emailAR = array();
		$r = ocsql_query("SELECT `reviewerid`, `username`, `email` FROM `" . OCC_TABLE_REVIEWER . "`");
		while ($l = ocsql_fetch_assoc($r)) {
			$reviewerIdAR[] = $l['reviewerid'];
			$usernameAR[] = $l['username'];
			$emailAR[] = $l['email'];
		}
		// set list of field names to ID mapping
		$fieldIdAR = array(
			'id' => 'reviewerid',
			'onprogramcommittee' => 'onprogramcommittee',
			'password' => 'password'
		);
		$fieldNameAR = array(
			'id' => 'reviewerid',
			'onprogramcommittee' => 'onprogramcommittee',
			'password' => 'password'
		);
		foreach ($OC_reviewerFieldAR as $fid => $far) {
			$lowshort = strtolower($far['short']);
			$fieldIdAR[$fid] = $fid;
			$fieldNameAR[$lowshort] = $fid;
		}
		// retieve and check header row
		$fieldMapAR = array();
		$errAR = array();
		$row = fgetcsv($fp, 0, $_POST['delimiter'], $_POST['enclosure'], $_POST['escape']);
		foreach ($row as $rowid => $rowval) {
			$col = strtolower($rowval);
			if (isset($fieldIdAR[$col])) {
				$fieldMapAR[$rowid] = $col;
			} elseif (isset($fieldNameAR[$col])) {
				$fieldMapAR[$rowid] = $fieldNameAR[$col];
			} elseif (!isset($_POST['skipunknown']) || ($_POST['skipunknown'] != 1)) {
				$errAR[] = 'Unknown column ' . safeHTMLstr($rowval);
			}
		}

		// get short topics
		$shortTopicAR = array();
		$shorttopr = ocsql_query("SELECT `topicid`, `short` FROM `" . OCC_TABLE_TOPIC . "`") or err('unable to retrieve short topics');
		while ($shorttopl = ocsql_fetch_assoc($shorttopr)) {
			if (empty($shorttopl['short'])) { continue; }
			$shortTopicAR[$shorttopl['topicid']] = $shorttopl['short'];
		}

		// check for missing required field columns
		if (!in_array('name_last', $fieldMapAR) || !in_array('email', $fieldMapAR)) {
			$errAR[] = 'Email and Last Name field columns not found';
		}
		if (count($errAR) > 0) {
			print '<ul class="warn"><li>' . implode('</li><li>', $errAR) . '</ul>';
		} else {
			// retrieve and check data
			$importAR = array();
			$rownum = 2; // skip header -- value for human consumption only
			$reverseFieldMapAR = array_flip($fieldMapAR);
			$reverseTopicAR = array_flip($topicAR);
			$reverseShortTopicAR = array_flip($shortTopicAR);
			while (($row = fgetcsv($fp, 0, $_POST['delimiter'], $_POST['enclosure'], $_POST['escape'])) !== false) {
				if (count($row) < 2) { continue; }
				if (!isset($row[$reverseFieldMapAR['name_last']]) || empty(trim($row[$reverseFieldMapAR['name_last']]))
					|| !isset($row[$reverseFieldMapAR['email']]) || empty(trim($row[$reverseFieldMapAR['email']]))
				) {
					$errAR[] = 'Row ' . $rownum++ . ' missing Last Name or Email';
					continue;
				}
				$recordAR = array();
				foreach ($fieldMapAR as $colid => $fid) {
					if (empty(trim($row[$colid]))) {
						continue;
					}
					// check/convert encoding
					if (isset($_POST['encoding']) && ($_POST['encoding'] != 'UTF-8')) {
						$row[$colid] = mb_convert_encoding($row[$colid], 'UTF-8', $_POST['encoding']);
					}
					if (
						(function_exists('mb_detect_encoding') && (mb_detect_encoding($row[$colid], 'UTF-8', true) != 'UTF-8'))
						||
						!preg_match("//u", $row[$colid])
					) {
						$errAR[] = 'Row ' . $rownum . ' Invalid UTF-8 encoding for field id ' . $fid;
					}
					if ($fid == 'password') {
						if (preg_match("/^[a-f0-9]{50}$/", $row[$colid]) || preg_match("/^\$\w\w\$\w\w\$/", $row[$colid])) { // OC encrypted password
							$recordAR['password'] = $row[$colid];
						} elseif (!preg_match("/^$/", $row[$colid])) {
							$recordAR['password'] = oc_password_hash($row[$colid]);
						}
					} elseif ($fid == 'topics') {
						if (($topics = preg_split("/\n/", $row[$colid])) && (count($topics) > 0)) {
							$recordAR['topics'] = array();
							foreach ($topics as $topic) {
								if (empty(trim($topic))) { continue; }
								if (isset($reverseShortTopicAR[$topic])) {								// short topic name
									$recordAR['topics'][] = $reverseShortTopicAR[$topic];
								} elseif (preg_match("/^\d+$/", $topic) && isset($topicAR[$topic])) {	// topic ID
									$recordAR['topics'][] = $topic;
								} elseif (isset($reverseTopicAR[$topic])) {								// full topic name
									$recordAR['topics'][] = $reverseTopicAR[$topic];
								} else {
									$errAR[] = 'Row ' . $rownum . ' Topic invalid: ' . $topic;
								}
							}
						} else {
							$errAR[] = 'Row ' . $rownum . ' Topics invalid';
						}
					} elseif ($fid == 'id') {
						if (preg_match("/^\d+$/", $row[$colid]) && !in_array($row[$colid], $reviewerIdAR)) {
							$recordAR['reviewerid'] = $row[$colid];
							$reviewerIdAR[] = $row[$colid];
						} else {
							$errAR[] = 'Row ' . $rownum . ' ID already exists or invalid';
						}
					} elseif ($fid == 'email') {
						if (validEmail($row[$colid]) && !in_array($row[$colid], $emailAR)) {
							$recordAR['email'] = $row[$colid];
							$emailAR[] = $row[$colid];
						} else {
							$errAR[] = 'Row ' . $rownum . ' Email already exists or invalid';
						}
					} elseif ($fid == 'username') {
						if (preg_match("/^[\p{L}\p{Nd}_\.\-\@]{5,50}$/u", $row[$colid]) && !in_array($row[$colid], $usernameAR)) {
							$recordAR['username'] = $row[$colid];
							$usernameAR[] = $row[$colid];
						} else {
							$errAR[] = 'Row ' . $rownum . ' Username already exists or invalid';
						}
					} elseif ($fid == 'onprogramcommittee') {
						if (preg_match("/^[TF]$/i", $row[$colid])) {
							$recordAR['onprogramcommittee'] = strtoupper($row[$colid]);
						} else {
							$errAR[] = 'Row ' . $rownum . ' OnProgramCommittee invalid';
						}
					} else {
						if (preg_match("/^(?:checkbox|picklist)$/", $OC_reviewerFieldAR[$fid]['type'])) { // multi-select field
							if (($values = preg_split("/\n/", $row[$colid])) && (count($values) > 0)) {
								$recordAR[$fid] = array();
								$reverseValuesAR = array_flip($OC_reviewerFieldAR[$fid]['values']);
								foreach ($values as $value) {
									if (empty(trim($value))) { continue; }
									if (!isset($OC_reviewerFieldAR[$fid]['usekey']) || $OC_reviewerFieldAR[$fid]['usekey']) {
										if (isset($OC_reviewerFieldAR[$fid]['values'][$value])) {
											$recordAR[$fid][] = $value;
										} elseif (isset($reverseValuesAR[$value])) {
											$recordAR[$fid][] = $reverseValuesAR[$value];
										}
									} elseif (in_array($value, $OC_reviewerFieldAR[$fid]['values'])) {
										$recordAR[$fid][] = $value;
									} elseif ($fid == 'consent') { // override in case translated value saved
										$recordAR[$fid][] = $value;
									} else {
										$errAR[] = 'Row ' . $rownum . ' ' . $OC_reviewerFieldAR[$fid]['short'] . ' invalid value: ' . $value;
									}
								}
							} else {
								$errAR[] = 'Row ' . $rownum . ' ' . $OC_reviewerFieldAR[$fid]['short'] . ' invalid';
							}
						} else { // not multi-select field (e.g., text, textarea, dropdown, radio)
							if (isset($OC_reviewerFieldAR[$fid]['values']) && !empty($OC_reviewerFieldAR[$fid]['values'])) { 
								if (!isset($OC_reviewerFieldAR[$fid]['usekey']) || $OC_reviewerFieldAR[$fid]['usekey']) {
									$reverseValuesAR = array_flip($OC_reviewerFieldAR[$fid]['values']);
									if (isset($OC_reviewerFieldAR[$fid]['values'][$row[$colid]])) {
										$recordAR[$fid] = $row[$colid];
									} elseif (isset($reverseValuesAR[$row[$colid]])) {
										$recordAR[$fid] = $reverseValuesAR[$row[$colid]];
									} else {
										$errAR[] = 'Row ' . $rownum . ' ' . $OC_reviewerFieldAR[$fid]['short'] . ' invalid value: ' . $row[$colid];
									}
								} elseif (in_array($row[$colid], $OC_reviewerFieldAR[$fid]['values'])) {
									$recordAR[$fid] = $row[$colid];
								} else {
									$errAR[] = 'Row ' . $rownum . ' ' . $OC_reviewerFieldAR[$fid]['short'] . ' invalid value: ' . $row[$colid];
								}
							} else {
								$recordAR[$fid] = $row[$colid];
							}
						}
					}
				}
				// fill in missing fields
				if (!isset($recordAR['password'])) {
					$recordAR['password'] = oc_password_hash(oc_password_generate()); // filler password
				}
				if (!isset($recordAR['username']) && isset($recordAR['email'])) { // if not present, default username to email
					if (!in_array($recordAR['email'], $usernameAR)) {
						$recordAR['username'] = $recordAR['email'];
						$usernameAR[] = $recordAR['username'];
					} else {
						$errAR[] = 'Row ' . $rownum . ' cannot use email for username';
					}
				}
				if (!isset($recordAR['onprogramcommittee'])) { // default to not being on program committee
					$recordAR['onprogramcommittee'] = 'F';
				}
				if (count($recordAR) > 0) {
					$importAR[] = $recordAR;
				}
				$rownum++;
			}
			if (count($errAR) == 0) {
				if (count($importAR) > 0) {
					// import'em Danno
					foreach($importAR as $recordID => $record) {
						$reviewerid = 0;
						$q = "INSERT INTO `" . OCC_TABLE_REVIEWER . "` SET ";
						foreach ($record as $fid => $fval) {
							if ($fid == 'topics') {
								continue;
							} elseif ($fid == 'reviewerid') {
								$reviewerid = $fval;
							}
							$q .= "`" . $fid . "`='" . safeSQLstr((is_array($fval) ? implode(',', $fval) : $fval)) . "', ";
						}
						$q .= "`lastupdate`='" . safeSQLstr(date('Y-m-d')) . "'";
						$r = ocsql_query($q) or warn('Import DB failure: (' . $recordID . ') ' . safeHTMLstr(ocsql_error()));
						if ($reviewerid === 0) {
							$reviewerid = ocsql_insert_id();
						}
						if (
							preg_match("/^[1-9][0-9]*$/", $reviewerid)
							&& isset($record['topics'])
							&& is_array($record['topics'])
							&& (count($record['topics']) > 0)
						) {
							$tq = "";
							foreach ($record['topics'] as $topic) {
								if (preg_match("/^\d+$/", $topic)) {
									$tq .= "(" . $reviewerid . "," . $topic . "),";
								}
							}
							if (!empty($tq)) {
								$tq = "INSERT INTO `" . OCC_TABLE_REVIEWERTOPIC . "` (`reviewerid`, `topicid`) VALUES " . rtrim($tq, ',');
								ocsql_query($tq) or warn('Topic import DB failure: (' . $recordID . ') ' . safeHTMLstr(ocsql_error()));
							}
						}
					}
					print '<p class="note2">' . safeHTMLstr(count($importAR)) . ' records imported</p>';
				} else {
					print '<p class="warn">No valid reviewer records found</p>';
				}
			} else {
				print '<ul class="warn"><li>' . implode('</li><li>', $errAR) . '</ul>';
			}
		}
	} else {
		print '<p class="warn">File did not upload properly or size too large</p>';
	}
	
	// clear out file
	if (isset($_FILES['csvfile']['tmp_name']) && is_file($_FILES['csvfile']['tmp_name'])) {
		unlink($_FILES['csvfile']['tmp_name']);
	}
	
	print '<hr />';
}

// Display form

print '
<p>Select the CSV file, then click the <i>Import Committee Members</i> button. The file must be in standard CSV format and have a header row that matches the committee profile form field (short) names or IDs. For a CSV file template, see below.</p>

<form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<p><b>CSV File:</b> <input type="file" name="csvfile" id="csvfile" /></p>
<p><input type="submit" name="submit" class="submit" value="Import Committee Members" /></p>
<p><b>Options:</b></p>
<div style="margin-left: 30px;">
<p>
<label>Delimiter: <input name="delimiter" id="delimiter" value="' . varValue('delimiter', $_POST, ',', true) . '" size="2" /></label> &nbsp; &nbsp;
<label>Enclosure: <input name="enclosure" id="enclosure" value="' . varValue('enclosure', $_POST, '&quot;', true) . '" size="2" /></label> &nbsp; &nbsp;
<label>Escape: <input name="escape" id="escape" value="' . varValue('escape', $_POST, '\\', true) . '" size="2" /></label>
</p>
';

if (function_exists('mb_convert_encoding')) {
	print '
<p>
<label>Encoding: <select name="encoding">' . generateSelectOptions($encodingAR, varValue('encoding', $_POST), false) . '</select></label>
</p>
';
}

print '
<p>
<label><input type="checkbox" id="skipunknown" name="skipunknown" value="1" ' . ((isset($_POST['skipunknown']) && ($_POST['skipunknown'] == 1)) ? 'checked ' : '') . '/> skip unknown columns</label>
</p>
<p>
<label><input type="checkbox" id="lineend" name="lineend" value="1" ' . ((isset($_POST['lineend']) && ($_POST['lineend'] == 1)) ? 'checked ' : '') . '/> detect Mac line endings</label>
</p>
</div>
</form>

<hr />

<p style="font-weight: bold; color: #444;">Template:</p>
<form method="post" action="import_reviewer-template.php">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<p style="margin-left: 30px"><input type="submit" name="submit" value="Download template" /></p>
</form>

<p style="font-weight: bold; color: #444;">Import rules:</p>
<ul>
<li>The CSV file is assumed to be plain text with one committee member record per row</li>
<li>Row 1 is assumed to be the title row with a unique field (short) name or ID per column, UTF-8 encoded</li>
<li>The committee member\'s Last Name and Email Address must be included</li>
<li>If no Username value is present, the Email Address will be used</li>
<li>The Username and Email Address must be unique</li>
<li>If no ID value is present, one will be automatically assigned</li>
<li>If no Password value is present, the committee member will need to use the "forgot password" feature to have a new one issued</li>
<li>The OnProgramCommittee value must be T for true or F for false; if neither of these, F is assumed</li>
<li>For multi-value fields (e.g., Topics, checkboxes, picklist), separate values with a newline (\n)</li>
</ul>

';

printFooter();

?>
