<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

function setupFieldsAR(&$fieldsAR, &$OC_fieldsetAR, &$OC_fieldAR, &$dateFieldsAR, &$skipFieldTypeAR, &$skipFieldIdAR) {
	foreach ($OC_fieldsetAR as $fsAR) {
		foreach ($fsAR['fields'] as $f) {
			if (!in_array($OC_fieldAR[$f]['type'], $skipFieldTypeAR) && !in_array($f, $skipFieldIdAR)) { 
				$fieldsAR[$f] = array(
					'short' => substr($OC_fieldAR[$f]['short'], 0, 30),
					'type' => $OC_fieldAR[$f]['type'],
					'fieldset' => $fsAR['fieldset']
				);
				if ($OC_fieldAR[$f]['type'] == 'date') {
					$dateFieldsAR[] = $f;
				}
				if (isset($OC_fieldAR[$f]['values']) && is_array($OC_fieldAR[$f]['values']) && (count($OC_fieldAR[$f]['values']) > 0)) {
					if (!isset($OC_fieldAR[$f]['usekey']) || $OC_fieldAR[$f]['usekey']) {
						$fieldsAR[$f]['values'] = $OC_fieldAR[$f]['values'];
					} else {
						$fieldsAR[$f]['values'] = array();
						foreach ($OC_fieldAR[$f]['values'] as $v) {
							$fieldsAR[$f]['values'][$v] = $v;
						}
					}
				}
			}
		}
	}
}

function queryFieldWrapper($table, $field, $fieldType, $fieldValue, $operator, $tableFieldOverride=null) {
	if ($tableFieldOverride !== null) {
		$tableField = $tableFieldOverride;
	} else {
		$tableField = "`" . $table . "`.`" . safeSQLstr($field) . "`";
	}
	$qf = '';
	if (($fieldType == 'checkbox') || ($fieldType == 'picklist')) {
		$qf = "FIND_IN_SET('" . safeSQLstr($fieldValue) . "', " . $tableField . ") ";
	} else {
		if ($operator == 'contains') {
			$qf = $tableField . " LIKE '%" . safeSQLstr(preg_replace(array('/_/', '/%/'), array('\\_', '\\%'), oc_strtolower($fieldValue))) .  "%'" ;
		} elseif ($operator == 'before') {
			$qf = "( (" . $tableField . "<'" . safeSQLstr(oc_strtolower($fieldValue)) . "') OR (" . $tableField . " IS NULL) )";
		} elseif ($operator == 'after') {
			$qf = $tableField . ">'" . safeSQLstr(oc_strtolower($fieldValue)) . "'";
		} elseif ($operator == 'onbefore') {
			$qf = "( (" . $tableField . "<='" . safeSQLstr(oc_strtolower($fieldValue)) . "') OR (" . $tableField . " IS NULL) )";
		} elseif ($operator == 'onafter') {
			$qf = $tableField . ">='" . safeSQLstr(oc_strtolower($fieldValue)) . "'";
		} else {
			$qf = $tableField . "='" . safeSQLstr(oc_strtolower($fieldValue)) . "'";
		}
	}
	return($qf);
}

function displayResultsHeader() {
	header('Content-type: text/html; charset=utf-8');
	print '<html lang="' . $GLOBALS['OC_locale'] . '"' . (OCC_LANGUAGE_LTR ? '' : ' dir="rtl"') . '
<head>
<meta charset="utf-8">
<title>Search</title>
<link rel="stylesheet" type="text/css" href="' . $GLOBALS['pfx'] . 'openconf.css?v=11" />
' .
( 
	( defined('OCC_LANGUAGE_LTR') && ( ! OCC_LANGUAGE_LTR ) )
	?
	'<link rel="stylesheet" type="text/css" href="' . $GLOBALS['pfx'] . 'openconf-rtl.css?v=8" />'
	:
	''
) . '
</head>
<body>
';
}

function displayResultsFooter() {
	print '
</body>
</html>
';
}

function validateSearchFields($i, &$fieldsAR, &$intFieldsAR, &$dateFieldsAR, $decFieldsAR=array()) {
	if (
		// field selected exists and is valid
		isset($_POST['searchfield'.$i]) && isset($fieldsAR[$_POST['searchfield'.$i]])
		&&
		// field operator exists and is valid
		isset($_POST['searchoperator'.$i]) && preg_match("/^(?:is|contains|before|after)$/", $_POST['searchoperator'.$i])
		&&
		// field value entered/selected
		isset($_POST['searchvalue'.$i]) && !empty($_POST['searchvalue'.$i])
		&&
		// field value exists if selection
		(
			!isset($fieldsAR[$_POST['searchvalue'.$i]]['values']) 
			|| 
			isset($fieldsAR[$_POST['searchvalue'.$i]]['values'][$_POST['searchvalue'.$i]])
		)
		&&
		// int field format valid
		(
			!in_array($_POST['searchfield'.$i], $intFieldsAR)
			||
			preg_match("/^\d+$/", $_POST['searchvalue'.$i])
		)
		&&
		// decimal field format valid
		(
			!in_array($_POST['searchfield'.$i], $decFieldsAR)
			||
			preg_match("/^\d+(\.\d{1,2})?$/", $_POST['searchvalue'.$i])
		)
		&&
		// date field format valid
		(
			!in_array($_POST['searchfield'.$i], $dateFieldsAR)
			||
			preg_match("/^\d{4}-\d\d-\d\d$/", $_POST['searchvalue'.$i])
		)
	) {
		return(true);
	} else {
		return(false);
	}
}

function displayEmailForm(&$emailAddresses, $recipient, $submitText) {
	print '	
<form method="post" action="email.php" target="_blank">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
<input type="hidden" name="submit" value="Edit Message" />
<input type="hidden" name="recipient" value="' . $recipient . '" />
<input type="hidden" name="template" value="" />
<input type="hidden" name="subject" value="" />
<input type="hidden" name="message" value="" />
<input type="hidden" name="select_recipients" value="1" />
';
	foreach ($emailAddresses as $eAddress) {
		print '<input type="hidden" name="selected_recipients[]" value="' . safeHTMLstr($eAddress) . '" />';
	}
	print '
<p><input type="submit" value="' . $submitText . '" title="opens in new tab/window" class="submit" /></p>
</form>
';
}

function displaySearchForm(&$fieldsAR, &$dateFieldsAR, &$intFieldsAR, &$decFieldsAR, &$searchFieldNum) {
	print '
<style>
#searchForm input[type=text] { width: 30em; }
#searchForm input[type=date] { font-family: verdana, arial, helvetica, sans-serif; }
#searchForm div { margin: 1em 0; }
#searchResults { border: 0; width: 100%; margin-top: 2em; } 
</style>
<script>
var year = new Date().getFullYear(),
	dateFieldsAR = [' . ((count($dateFieldsAR) > 0) ? ("'" . implode("','", $dateFieldsAR). "'") : '') . '],
	intFieldsAR = [' . ((count($intFieldsAR) > 0) ? ("'" . implode("','", $intFieldsAR). "'") : '') . '],
	decFieldsAR = [' . ((count($decFieldsAR) > 0) ? ("'" . implode("','", $decFieldsAR). "'") : '') . '],
	fieldOptionsAR = {};
';

	$fieldOptions = '';
	$fs = '';
	foreach ($fieldsAR as $f => $fAR) {
		if ($fAR['fieldset'] != $fs) {
			$fieldOptions .= '<option value="" disabled>&gt;&gt;' . safeHTMLstr($fAR['fieldset']) . '</option>';
			$fs = $fAR['fieldset'];
		}
		$fieldOptions .= '<option value="' . safeHTMLstr($f) . '">' . safeHTMLstr($fAR['short']) . '</option>';
		if (isset($fAR['values'])) {
			$jsFieldOptions = '';
			foreach ($fAR['values'] as $k => $v) {
				$jsFieldOptions .= '<option value="' . preg_replace("/'/", "\\'", safeHTMLstr($k)) . '">' . preg_replace("/'/", "\\'", safeHTMLstr(substr($v, 0, 50))) . '</option>';
			}
			print "fieldOptionsAR['" . safeHTMLstr($f) . "'] = '" . $jsFieldOptions . "';\n";
		}
	}

	print '
function updateField(searchField, fieldID) {
	if (dateFieldsAR.includes(fieldID)) {
		document.getElementById("searchvalue" + searchField + "span").innerHTML = \'<input type="date" name="searchvalue\' + searchField + \'" value="" placeholder="\' + year + \'-05-30" pattern="\d{4}-\d{2}-\d{2}">\';
		document.getElementById("searchoperator" + searchField + "_is").selected = true;
		document.getElementById("searchoperator" + searchField + "_is").innerHTML = "= is";
		document.getElementById("searchoperator" + searchField + "_contains").disabled = true;
		document.getElementById("searchoperator" + searchField + "_contains").innerHTML = "⊂";
		document.getElementById("searchoperator" + searchField + "_before").disabled = false;
		document.getElementById("searchoperator" + searchField + "_before").innerHTML = "&lt; before";
		document.getElementById("searchoperator" + searchField + "_after").disabled = false;
		document.getElementById("searchoperator" + searchField + "_after").innerHTML = "&gt; after";
		document.getElementById("searchoperator" + searchField + "_onbefore").disabled = false;
		document.getElementById("searchoperator" + searchField + "_onbefore").innerHTML = "&lt;= on or before";
		document.getElementById("searchoperator" + searchField + "_onafter").disabled = false;
		document.getElementById("searchoperator" + searchField + "_onafter").innerHTML = "&gt;= on or after";
	} else if (fieldOptionsAR.hasOwnProperty(fieldID)) {
		document.getElementById("searchvalue" + searchField + "span").innerHTML = \'<select name="searchvalue\' + searchField + \'"><option value="" disabled selected hidden>select value</option>\' + fieldOptionsAR[fieldID] + \'</select>\';
		document.getElementById("searchoperator" + searchField + "_is").selected = true;
		document.getElementById("searchoperator" + searchField + "_is").innerHTML = "= is";
		document.getElementById("searchoperator" + searchField + "_contains").disabled = true;
		document.getElementById("searchoperator" + searchField + "_contains").innerHTML = "⊂";
		document.getElementById("searchoperator" + searchField + "_before").disabled = true;
		document.getElementById("searchoperator" + searchField + "_before").innerHTML = "&lt;";
		document.getElementById("searchoperator" + searchField + "_after").disabled = true;
		document.getElementById("searchoperator" + searchField + "_after").innerHTML = "&gt;";
		document.getElementById("searchoperator" + searchField + "_onbefore").disabled = true;
		document.getElementById("searchoperator" + searchField + "_onbefore").innerHTML = "&lt;=";
		document.getElementById("searchoperator" + searchField + "_onafter").disabled = true;
		document.getElementById("searchoperator" + searchField + "_onafter").innerHTML = "&gt;=";
	} else {
		document.getElementById("searchvalue" + searchField + "span").innerHTML = \'<input type="text" name="searchvalue\' + searchField + \'" value="">\';
		document.getElementById("searchoperator" + searchField + "_is").selected = true;
		if (intFieldsAR.includes(fieldID) || decFieldsAR.includes(fieldID)) {
			document.getElementById("searchoperator" + searchField + "_is").selected = true;
			document.getElementById("searchoperator" + searchField + "_is").innerHTML = "= equals";
			document.getElementById("searchoperator" + searchField + "_contains").disabled = true;
			document.getElementById("searchoperator" + searchField + "_contains").innerHTML = "⊂";
			document.getElementById("searchoperator" + searchField + "_before").disabled = false;
			document.getElementById("searchoperator" + searchField + "_before").innerHTML = "&lt; less than";
			document.getElementById("searchoperator" + searchField + "_after").disabled = false;
			document.getElementById("searchoperator" + searchField + "_after").innerHTML = "&gt; more than";
			document.getElementById("searchoperator" + searchField + "_onbefore").disabled = false;
			document.getElementById("searchoperator" + searchField + "_onbefore").innerHTML = "&lt;= less or equals";
			document.getElementById("searchoperator" + searchField + "_onafter").disabled = false;
			document.getElementById("searchoperator" + searchField + "_onafter").innerHTML = "&gt;= more or equals";
		} else {
			document.getElementById("searchoperator" + searchField + "_is").selected = true;
			document.getElementById("searchoperator" + searchField + "_is").innerHTML = "= is";
			document.getElementById("searchoperator" + searchField + "_contains").disabled = false;
			document.getElementById("searchoperator" + searchField + "_contains").innerHTML = "⊂ contains";
			document.getElementById("searchoperator" + searchField + "_before").disabled = true;
			document.getElementById("searchoperator" + searchField + "_before").innerHTML = "&lt;";
			document.getElementById("searchoperator" + searchField + "_after").disabled = true;
			document.getElementById("searchoperator" + searchField + "_after").innerHTML = "&gt;";
			document.getElementById("searchoperator" + searchField + "_onbefore").disabled = true;
			document.getElementById("searchoperator" + searchField + "_onbefore").innerHTML = "&lt;=";
			document.getElementById("searchoperator" + searchField + "_onafter").disabled = true;
			document.getElementById("searchoperator" + searchField + "_onafter").innerHTML = "&gt;=";
		}
	}
}

function resizeIFrame(iframeObj) {
	iframeObj.style.height = iframeObj.contentWindow.document.documentElement.scrollHeight + "px";
}
</script>

<p>Select one or more fields to search on and enter your query, then click the <i>Search</i> button. The search will find submissions matching all non-empty queries. When selecting fields with pre-defined values, the text query box will be replaced with a drop-down list of the values.</p>

<form method="post" target="searchResults" action="' . $_SERVER['PHP_SELF'] . '" id="searchForm">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'] . '" />
';

	for ($i=1; $i <= $searchFieldNum; $i++) {
		print '
<div>
<label>
<select name="searchfield' . $i . '" onchange="updateField(' . $i . ', this.options[this.selectedIndex].value)">
<option value="" selected disabled hidden>select field</option>
' . $fieldOptions . '
</select>
<select name="searchoperator' . $i . '">
<option value="is" id="searchoperator' . $i . '_is">=</option>
<option value="contains" id="searchoperator' . $i . '_contains" disabled>⊂</option>
<option value="before" id="searchoperator' . $i . '_before" disabled>&lt;</option>
<option value="after" id="searchoperator' . $i . '_after" disabled>&gt;</option>
<option value="before" id="searchoperator' . $i . '_onbefore" disabled>&lt;=</option>
<option value="after" id="searchoperator' . $i . '_onafter" disabled>&gt;=</option>
</select>
<span id="searchvalue' . $i . 'span" aria-live="polite">
<input type="text" name="searchvalue' . $i . '" value="" />
</span>
</label>
</div>
';
	}

	print '
<p><input type="submit" name="ocsubmit" value="Search" class="submit" /></p>
</form>

<iframe name="searchResults" id="searchResults" onload="resizeIFrame(this);" aria-live="polite"></iframe>
';

}