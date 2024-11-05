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

printHeader('Import Submissions', 1);

require_once OCC_FORM_INC_FILE;
require_once OCC_SUBMISSION_INC_FILE;

$encodingAR = array('UTF-8','ISO-8859-1', 'ISO-8850-2', 'ISO-8859-9', 'ISO-8859-15', 'Big5', 'GB2312', 'EUC-KR', 'EUC-JP', 'SJis', 'Windows-1251', 'Windows-1252');

if (isset($_POST['submit']) && ($_POST['submit'] == "Import Submissions")) {
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
		// retrieve existing submission IDs
		$submissionIdAR = array();
		$r = ocsql_query("SELECT `paperid` FROM `" . OCC_TABLE_PAPER . "`");
		while ($l = ocsql_fetch_assoc($r)) {
			$submissionIdAR[] = $l['paperid'];
		}
		// set list of field names to ID mapping
		$fieldIdAR = array(
			'paperid' => 'paperid',
			'password' => 'password',
			'submissiondate' => 'submissiondate',
			'pcnotes' => 'pcnotes'
		);
		$fieldNameAR = array(
			'submission id' => 'paperid',
			'password' => 'password',
			'submission date' => 'submissiondate',
			'chair notes' => 'pcnotes'

		);
		$authorFieldIdAR = array();
		$authorFieldNameAR = array();
		foreach ($OC_submissionFieldSetAR as $fsk=>$fsv)  {
			foreach ($fsv['fields'] as $fid) { 
				if (preg_match("/^password/", $fid) || preg_match("/[\'\"]/", $OC_submissionFieldAR[$fid]['short'])) { continue; }
				$lowshort = strtolower($OC_submissionFieldAR[$fid]['short']);
				if ($fsk == 'fs_authors') {
					$authorFieldIdAR[$fid] = $fid;
					$authorFieldNameAR[$lowshort] = $fid;
				} else {
					$fieldIdAR[$fid] = $fid;
					$fieldNameAR[$lowshort] = $fid;
				}
			}
		}
		
		// retieve and check header row
		$fieldMapAR = array();
		$authorFieldMapAR = array();
		$errAR = array();
		$row = fgetcsv($fp, 0, $_POST['delimiter'], $_POST['enclosure'], $_POST['escape']);
		foreach ($row as $rowid => $rowval) {
			if (
				preg_match("/^" . OCC_WORD_AUTHOR . " (\d+) (.*)$/i", $rowval, $matches1)
				||
				preg_match("/^(\w+)-(\d+)$/", $rowval, $matches2)
			) {
				if (isset($matches1[1])) {
					$authornum = $matches1[1];
					$col = strtolower($matches1[2]);
				} else {
					$authornum = $matches2[2];
					$col = strtolower($matches2[1]);
				}

				if (isset($authorFieldIdAR[$col])) {
					$fieldMapAR[$rowid] = $col . '-' . $authornum;
				} elseif (isset($authorFieldNameAR[$col])) {
					$fieldMapAR[$rowid] = $authorFieldNameAR[$col] . '-' . $authornum;
				} elseif (!isset($_POST['skipunknown']) || ($_POST['skipunknown'] != 1)) {
					$errAR[] = 'Unknown column ' . safeHTMLstr($rowval);
				}
			} else {
				$col = strtolower($rowval);
				if (isset($fieldIdAR[$col])) {
					$fieldMapAR[$rowid] = $col;
				} elseif (isset($fieldNameAR[$col])) {
					$fieldMapAR[$rowid] = $fieldNameAR[$col];
				} elseif (!isset($_POST['skipunknown']) || ($_POST['skipunknown'] != 1)) {
					$errAR[] = 'Unknown column ' . safeHTMLstr($rowval);
				}
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
		if (
			!in_array('title', $fieldMapAR) 
			|| !in_array('name_last-1', $fieldMapAR)
			|| !in_array('email-1', $fieldMapAR)
		) {
			$errAR[] = 'Title (title), ' . safeHTMLstr(OCC_WORD_AUTHOR) . ' 1 Last Name (name_last-1), or Author 1 Email (email-1) field columns not found';
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
			$today = date('Y-m-d');
			while (($row = fgetcsv($fp, 0, $_POST['delimiter'], $_POST['enclosure'], $_POST['escape'])) !== false) {
				$recordAR = array();
				if (count($row) < 3) {
					$errAR[] = 'Row ' . $rownum++ . ' skipped';
					continue;
				}
				// check for title, author 1 last name & email
				if (
					!isset($row[$reverseFieldMapAR['title']]) || empty(trim($row[$reverseFieldMapAR['title']]))
					|| !isset($row[$reverseFieldMapAR['name_last-1']]) || empty(trim($row[$reverseFieldMapAR['name_last-1']]))
					|| !isset($row[$reverseFieldMapAR['email-1']]) || !validEmail(trim($row[$reverseFieldMapAR['email-1']]))
				) {
					$errAR[] = 'Row ' . $rownum++ . ' missing or invalid Title, ' . safeHTMLstr(OCC_WORD_AUTHOR) . ' 1 Last Name, or ' . safeHTMLstr(OCC_WORD_AUTHOR) . ' 1 Email';
					continue;
				}
				// check for contact (if > 1) last name & email
				if (
					isset($reverseFieldMapAR['contactid'])
					&& preg_match("/^(?:Author |" . OCC_WORD_AUTHOR . " |)(\d+)$/", $row[$reverseFieldMapAR['contactid']], $cidmatch)
				) {
					$recordAR['contactid'] = $cidmatch[1];
					if (
						($row[$cidmatch[1]] > 1)
						&& (
							!isset($reverseFieldMapAR['name_last-'.$cidmatch[1]])
							|| empty($row[$reverseFieldMapAR['name_last-'.$cidmatch[1]]])
							|| !isset($reverseFieldMapAR['email-'.$cidmatch[1]])
							|| !validEmail($row[$reverseFieldMapAR['email-'.$cidmatch[1]]])
						)
					) {
						$errAR[] = 'Row ' . $rownum++ . ' missing or invalid ' . safeHTMLstr(OCC_WORD_AUTHOR) . ' ' . $cidmatch[1] . ' Last Name or ' . safeHTMLstr(OCC_WORD_AUTHOR) . ' ' . $cidmatch[1] . ' Email';
						continue;
					}
				} else {
					$recordAR['contactid'] = 1;
				}
				// check non-empty fields
				foreach ($fieldMapAR as $colid => $fid) {
					if (empty(trim($row[$colid]))) {
						continue;
					}
					// check/convert encoding
					if (isset($_POST['encoding']) && ($_POST['encoding'] != 'UTF-8') && in_array($_POST['encoding'], $encodingAR)) {
						$row[$colid] = mb_convert_encoding($row[$colid], 'UTF-8', $_POST['encoding']);
					}
					if (
						(function_exists('mb_detect_encoding') && (mb_detect_encoding($row[$colid], 'UTF-8', true) != 'UTF-8'))
						||
						!preg_match("//u", $row[$colid])
					) {
						$errAR[] = 'Row ' . $rownum . (isset($recordAR['paperid']) ? (' (ID ' . safeHTMLstr($recordAR['paperid']) . ') ') : '') .  'Invalid UTF-8 encoding for field id ' . $fid;
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
									$errAR[] = 'Row ' . $rownum . (isset($recordAR['paperid']) ? (' (ID ' . safeHTMLstr($recordAR['paperid']) . ') ') : '') .  'Topic invalid: ' . $topic;
								}
							}
						} elseif (!preg_match("/^$/", $row[$colid])) {
							$errAR[] = 'Row ' . $rownum . (isset($recordAR['paperid']) ? (' (ID ' . safeHTMLstr($recordAR['paperid']) . ') ') : '') . ' Topics invalid';
						}
					} elseif ($fid == 'paperid') {
						if (preg_match("/^\d+$/", $row[$colid]) && !in_array($row[$colid], $submissionIdAR)) {
							$recordAR['paperid'] = $row[$colid];
							$submissionIdAR[] = $row[$colid];
						} elseif (!preg_match("/^$/", $row[$colid])) {
							$errAR[] = 'Row ' . $rownum . ' (ID ' . safeHTMLstr($row[$colid]) . ') Submission ID already exists or invalid';
						}
					} elseif ($fid == 'accepted') {
						if (in_array($row[$colid], $OC_acceptedValuesAR)) {
							$recordAR['accepted'] = $row[$colid];
						} elseif (!preg_match("/^$/", $row[$colid])) {
							$errAR[] = 'Row ' . $rownum . (isset($recordAR['paperid']) ? (' (ID ' . safeHTMLstr($recordAR['paperid']) . ') ') : '') . ' Accepted invalid';
						}
					} elseif ($fid == 'submissiondate') {
						if (preg_match("/^\d{4}-\d\d-\d\d$/", $row[$colid])) {
							$recordAR['submissiondate'] = $row[$colid];
						} elseif (!preg_match("/^$/", $row[$colid])) {
							$errAR[] = 'Row ' . $rownum . (isset($recordAR['paperid']) ? (' (ID ' . safeHTMLstr($recordAR['paperid']) . ') ') : '') . ' Submission Date invalid';
						}
					} else {
						if (preg_match("/^(.*)-(\d+)$/", $fid, $fmatches)) {
							$usefid = $fmatches[1];
						} else {
							$usefid = $fid;
						}
						if (preg_match("/^(?:checkbox|picklist)$/", $OC_submissionFieldAR[$usefid]['type'])) { // multi-select field
							if (($values = preg_split("/\n/", $row[$colid])) && (count($values) > 0)) {
								$recordAR[$fid] = array();
								$reverseValuesAR = array_flip($OC_submissionFieldAR[$usefid]['values']);
								foreach ($values as $value) {
									if (empty(trim($value))) { continue; }
									if (!isset($OC_submissionFieldAR[$usefid]['usekey']) || $OC_submissionFieldAR[$usefid]['usekey']) {
										if (isset($OC_submissionFieldAR[$usefid]['values'][$value])) {
											$recordAR[$fid][] = $value;
										} elseif (isset($reverseValuesAR[$value])) {
											$recordAR[$fid][] = $reverseValuesAR[$value];
										}
									} elseif (in_array($value, $OC_submissionFieldAR[$usefid]['values'])) {
										$recordAR[$fid][] = $value;
									} elseif ($fid == 'consent') { // override in case translated value saved
										$recordAR[$fid][] = $value;
									} else {
										$errAR[] = 'Row ' . $rownum . ' ' . (isset($recordAR['paperid']) ? ('(ID ' . safeHTMLstr($recordAR['paperid']) . ') ') : '') .  $OC_submissionFieldAR[$usefid]['short'] . ' invalid value: ' . $value;
									}
								}
							} else {
								$errAR[] = 'Row ' . $rownum . ' ' . $OC_submissionFieldAR[$usefid]['short'] . ' invalid';
							}
						} else { // not multi-select field (e.g., text, textarea, dropdown, radio)
							if (isset($OC_submissionFieldAR[$usefid]['values']) && !empty($OC_submissionFieldAR[$usefid]['values'])) { 
								if (!isset($OC_submissionFieldAR[$usefid]['usekey']) || $OC_submissionFieldAR[$usefid]['usekey']) {
									$reverseValuesAR = array_flip($OC_submissionFieldAR[$usefid]['values']);
									if (isset($OC_submissionFieldAR[$usefid]['values'][$row[$colid]])) {
										$recordAR[$fid] = $row[$colid];
									} elseif (isset($reverseValuesAR[$row[$colid]])) {
										$recordAR[$fid] = $reverseValuesAR[$row[$colid]];
									} else {
										$errAR[] = 'Row ' . $rownum . ' ' . (isset($recordAR['paperid']) ? ('(ID ' . safeHTMLstr($recordAR['paperid']) . ') ') : '') .  $OC_submissionFieldAR[$usefid]['short'] . ' invalid value: ' . $row[$colid];
									}
								} elseif (in_array($row[$colid], $OC_submissionFieldAR[$usefid]['values'])) {
									$recordAR[$fid] = $row[$colid];
								} else {
									$errAR[] = 'Row ' . $rownum . ' ' . (isset($recordAR['paperid']) ? ('(ID ' . safeHTMLstr($recordAR['paperid']) . ') ') : '') .  $OC_submissionFieldAR[$usefid]['short'] . ' invalid value: ' . $row[$colid];
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
				if (!isset($recordAR['submissiondate'])) {
					$recordAR['submissiondate'] = $today;
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
						$paperid = 0;
						$authorAR = array();
						$q = "INSERT INTO `" . OCC_TABLE_PAPER . "` SET ";
						foreach ($record as $fid => $fval) {
							if ($fid == 'topics') {
								continue;
							} elseif ($fid == 'paperid') {
								$paperid = $fval;
							}
							if (preg_match("/^(.*)-(\d+)$/", $fid, $amatches)) {
								$authorAR[$amatches[2]][$amatches[1]] = $fval;
							} else {
								$q .= "`" . $fid . "`='" . safeSQLstr((is_array($fval) ? implode(',', $fval) : $fval)) . "', ";
							}
						}
						$q .= "`lastupdate`='" . safeSQLstr($today) . "'";
						$r = ocsql_query($q) or warn(safeHTMLstr('Import DB failure: (' . ($recordID+2) . ') ' . ocsql_error()));
						if ($paperid === 0) {
							$paperid = ocsql_insert_id() or warn('Invalid Submission ID (0) returned by database');
						}
						// import topics
						if (
							preg_match("/^[1-9][0-9]*$/", $paperid)
							&& isset($record['topics'])
							&& is_array($record['topics'])
							&& (count($record['topics']) > 0)
						) {
							$tq = "";
							foreach ($record['topics'] as $topic) {
								if (preg_match("/^\d+$/", $topic)) {
									$tq .= "(" . $paperid . "," . $topic . "),";
								}
							}
							if (!empty($tq)) {
								$tq = "INSERT INTO `" . OCC_TABLE_PAPERTOPIC . "` (`paperid`, `topicid`) VALUES " . rtrim($tq, ',');
								ocsql_query($tq) or warn(safeHTMLstr('Topic import DB failure: (' . ($recordID+2) . ') ' . ocsql_error()));
							}
						}
						// import authors
						foreach ($authorAR as $authorpos => $authorinfo) {
							$aq = "INSERT INTO `" . OCC_TABLE_AUTHOR . "` SET `paperid`='" . safeSQLstr($paperid) . "', `position`='" . safeSQLstr($authorpos) . "', ";
							foreach ($authorinfo as $fid => $fval) {
								$aq .= "`" . $fid . "`='" . safeSQLstr((is_array($fval) ? implode(',', $fval) : $fval)) . "', ";
							}
							ocsql_query(rtrim($aq, ', ')) or warn(safeHTMLstr(OCC_WORD_AUTHOR . ' ' . $authorpos . ' import DB failure: (' . ($recordID+2) . ') ' . ocsql_error()));
						}
					}
					print '<p class="note2">' . safeHTMLstr(count($importAR)) . ' records imported. <a href="list_papers.php">List submissions</a></p>';
				} else {
					print '<p class="warn">No valid submission records found</p>';
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
<p>Select the CSV file, then click the <i>Import Submissions</i> button. The file must be in standard CSV format and have a header row that matches the submission form field (short) names or IDs. For a CSV file template, see below.</p>

<form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<p><b>CSV File:</b> <input type="file" name="csvfile" id="csvfile" /></p>
<p><input type="submit" name="submit" class="submit" value="Import Submissions" /></p>
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
<form method="post" action="import_submissions-template.php">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<p style="margin-left: 30px">Maximum number of ' . safeHTMLstr(OCC_WORD_AUTHOR) . 's: <input name="maxauthors" value="1" size="3" maxlength="2" /> <input type="submit" name="submit" value="Download template" /></p>
</form>

<p style="font-weight: bold; color: #444;">Import rules:</p>
<ul>
<li>The CSV file is assumed to be plain text with one submission record per row</li>
<li>Row 1 must be a title row with a unique field (short) name or field ID per column, UTF-8 encoded</li>
<li>The ' . safeHTMLstr($OC_submissionFieldAR['title']['short']) . ' (title), ' . safeHTMLstr(OCC_WORD_AUTHOR) . ' 1 ' . safeHTMLstr($OC_submissionFieldAR['name_last']['short']) . ' (name_last-1) and ' . safeHTMLstr(OCC_WORD_AUTHOR) . ' 1 ' . safeHTMLstr($OC_submissionFieldAR['email']['short']) . ' (email-1) must be included</li>
<li>If ' . safeHTMLstr($OC_submissionFieldAR['contactid']['short']) . ' (contactid) is not included, ' . safeHTMLstr(OCC_WORD_AUTHOR) . ' 1 will be automatically set as the contact</li>
<li>If ' . safeHTMLstr($OC_submissionFieldAR['contactid']['short']) . ' (contactid) is &gt; 1, the corresponding Last Name and Email must be included</li>
<li>If included, the Submission ID (paperid) must be unique; if not included, one will be automatically assigned</li>
<li>If included, the Submission Date format needs to be YYYY-mm-dd; otherwise today\'s date is used</li>
<li>If Password is not included, the submitter will need to use the "forgot password" feature to have a new one issued</li>
<li>For multi-value fields (e.g., Topics, checkboxes, picklist), separate values with a newline (\n)</li>
<li>Extraneous spaces around data may be considered intentional</li>
</ul>

';

printFooter();

?>
