<?php
// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Form array format
/*
	fieldsetAR: (index - fieldset id [fs_ID])
		fieldset: Fieldset title
		note: Note to appear atop fieldset
		fields: Array of field IDs
	fieldAR: (index - field database id)
		name: Full field name
		short: Short name for use in confirmation message and review display to chair/reviewers
		note: Note to appear below field
		type: Field type to use (radio, checkbox, text, textarea, dropdown, picklist, password, email, plain*, hidden*)  *special
		width: Text and textarea width
		height: Textarea and picklist height
		minchars/maxchars: Text and textarea character limit
		minwords/maxwords: Text and textarea word limit
		minselections/maxselections: checkbox and picklist selection limit
		placeholder: Placeholder text for text/textarea fields
		usekey: TRUE (default) - use values array key for value storage; FALSE
		values: Array of radio/checkbox options or empty string if N/A
		closeedit: TRUE (default) - field may be edited after Make Submission is closed; FALSE
		newsubonly: TRUE - field included only if new submission or sign up; FALSE (default)
		longlabel: TRUE - use full form width for field name; FALSE (default)
		display: Delimiter to use to separate radio/checbox options (sameline, newline - default)
		enabled: TRUE (default) - field editable; FALSE - not editable (HTML "disabled" attribute included)
		required: TRUE - field required; FALSE (default) - field not required (may not be used in all instances)
		donotvalidate: TRUE - skip validing field; FALSE (default)
		-- review form field options:
		showauthor: TRUE - display review form field to author on Check Status page; FALSE (default) [review form fields only; excludes session field]
		score: TRUE - use numeric value in score calculation; FALSE (default) [review form fields only]
		hidesubtypes: array of submission types for which the field should not be included [review form fields only] - handled by calling script
		-- submission form field options:
		reviewer: TRUE - display on reviewer abstract page; FALSE (default) [submission form fields only]
		advocate: TRUE - display on advocate abstract page; FALSE (default) [submission form fields only]
		chair: TRUE - only Chair may edit field; FALSE (default) [submission form fields only]
	
	NOTE: All fieldset and field IDs need to be unique.  Prefix fieldset IDs with fs_ for OC, fs_[MOD]_ for modules
	
	NOTE: Fieldset fs_authors, and fields named "file" or starting with "password", have special handling
	
	NOTE: Changes to the above types may need to be reflected in modules (e.g., Proceedings, Custom Forms)
*/

$oc_defaultDelimiter = '<br />';
$oc_defaultFieldWidth = '60';
$oc_defaultFieldHeight = '5';

$oc_jsValidateEvent = 'change'; // event type to use for triggering JavaScript char or word number validation -- "change" used instead of "blur" to prevent Safari alert() loop

$formDisplayAR = array(
	'sameline' => ' &nbsp; ',
	'newline' => '<br />'
);

if (!isset($GLOBALS['oc_authorNum'])) {
	$GLOBALS['oc_authorNum'] = $GLOBALS['OC_configAR']['OC_authorsMax'];
}

function oc_fieldEnabled($fieldID, &$fieldsAR) {
	if (!isset($fieldsAR[$fieldID]['enabled']) || $fieldsAR[$fieldID]['enabled']) {
		return(TRUE);
	} else {
		return(FALSE);
	}
}

function oc_genField(&$fid, &$fAR, &$fVals, $fidxtra='') {
	$usefid = $fid . $fidxtra;

	$field = '';

	$field .= '<div class="field">';
	
	if (isset($fAR['enabled']) && !$fAR['enabled']) {
		$disabled = 'disabled ';
	} else {
		$disabled = '';
	}
	
	if (isset($fAR['required']) && $fAR['required']) {
		$required = $GLOBALS['OC_configAR']['OC_requiredField'];
	} else {
		$required = '';
	}

	if (!empty($fAR['note']) && ($fAR['type'] != 'plain') && ($fAR['type'] != 'hidden')) {
		$fieldnote = '<div class="fieldnote note">' . $fAR['note'] . '</div>';
	} else {
		$fieldnote = '';
	}
	
	if (isset($fAR['longlabel']) && $fAR['longlabel']) {
		$longlabel = $fieldnote . '<label>&nbsp;</label>';
		$longlabelclass = ' class="plain"';
		$fieldnote = '';
	} else {
		$longlabel = '';
		$longlabelclass = '';
	}
	
	switch ($fAR['type']) {

		case 'plain':
			$field .= '<label>' . (empty($fAR['name']) ? '&nbsp;' : ($fAR['name'] . ':')) . '</label>' . $fAR['values'];
			break;

		case 'hidden':
			$field .= '<input name="' . $usefid . '" id="' . $usefid . '" type="hidden" value="' . safeHTMLstr(varValue($usefid, $fVals)) . '" />';
			break;

		case 'text':
			if (isset($fAR['width'])) {
				$width = $fAR['width'];
			} else {
				$width = $GLOBALS['oc_defaultFieldWidth'];
			}
			if (isset($fAR['maxchars'])) {
				$maxchars = $fAR['maxchars'];
			} else {
				$maxchars = '255'; // should match default used in DB VARCHAR fields
			}
			$field .= '<label for="' . $usefid . '"' . $longlabelclass . '>' . $fAR['name'] . ':' . $required . '</label>' . $longlabel . '<input name="' . $usefid . '" id="' . $usefid . '" size="' . safeHTMLstr($width) . '" maxlength="' . safeHTMLstr($maxchars) . '" value="' . safeHTMLstr(varValue($usefid, $fVals)) . '" ' . ((isset($fAR['placeholder']) && !empty($fAR['placeholder'])) ? ('placeholder="' . safeHTMLstr($fAR['placeholder']) . '"') : '') . $disabled . '/>';
			if ( (isset($fAR['maxwords']) && ($fAR['maxwords'] > 0)) || (isset($fAR['minwords']) && ($fAR['minwords'] > 0)) ) { // word limit
				$field .= '
<script language="javascript" type="text/javascript">
<!--
var oc_' . $usefid . 'Field = document.getElementById("' . $usefid . '");
// Add validation check
if (oc_' . $usefid . 'Field.addEventListener) {
	oc_' . $usefid . 'Field.addEventListener("' . $GLOBALS['oc_jsValidateEvent'] . '", function(){oc_checkWordNum("' . $usefid . '", "' . safeHTMLstr($fAR['short']) . '", ' . varValue('maxwords', $fAR, 0, true) . ', ' . varValue('minwords', $fAR, ((isset($fAR['required']) && $fAR['required']) ? 1 : 0), true) . ')}, false);
} else if (oc_' . $usefid . 'Field.attachEvent) {
	oc_' . $usefid . 'Field.attachEvent("on' . $GLOBALS['oc_jsValidateEvent'] . '", function(){oc_checkWordNum("' . $usefid . '", "' . safeHTMLstr($fAR['short']) . '", ' . varValue('maxwords', $fAR, 0, true) . ', ' . varValue('minwords', $fAR, ((isset($fAR['required']) && $fAR['required']) ? 1 : 0), true) . ')});
}
// -->
</script>
';
			}
			if ( ($maxchars > 0) || (isset($fAR['minchars']) && ($fAR['minchars'] > 0)) ) { // character limit
				$field .= '
<script language="javascript" type="text/javascript">
<!--
var oc_' . $usefid . 'Field = document.getElementById("' . $usefid . '");
// Add validation check
if (oc_' . $usefid . 'Field.addEventListener) {
	oc_' . $usefid . 'Field.addEventListener("' . $GLOBALS['oc_jsValidateEvent'] . '", function(){oc_checkCharNum("' . $usefid . '", "' . safeHTMLstr($fAR['short']) . '", ' . varValue('maxchars', $fAR, 0, true) . ', ' . varValue('minchars', $fAR, ((isset($fAR['required']) && $fAR['required']) ? 1 : 0), true) . ')}, false);
} else if (oc_' . $usefid . 'Field.attachEvent) {
	oc_' . $usefid . 'Field.attachEvent("on' . $GLOBALS['oc_jsValidateEvent'] . '", function(){oc_checkCharNum("' . $usefid . '", "' . safeHTMLstr($fAR['short']) . '", ' . varValue('maxchars', $fAR, 0, true) . ', ' . varValue('minchars', $fAR, ((isset($fAR['required']) && $fAR['required']) ? 1 : 0), true) . ')});
}
// -->
</script>
';
			}
			break;

		case 'email':
			$field .= '<label for="' . $usefid . '"' . $longlabelclass . '>' . $fAR['name'] . ':' . $required . '</label>' . $longlabel . '<input name="' . $usefid . '" id="' . $usefid . '" type="email" size="' . safeHTMLstr(varValue('width', $fAR, $GLOBALS['oc_defaultFieldWidth'])) . '" maxlength="100" value="' . safeHTMLstr(varValue($usefid, $fVals)) . '" ' . ((isset($fAR['placeholder']) && !empty($fAR['placeholder'])) ? ('placeholder="' . safeHTMLstr($fAR['placeholder']) . '"') : '') . $disabled . '/>';
			break;

		case 'textarea':
			if (isset($fAR['height'])) {
				$height = $fAR['height'];
			} else {
				$height = $GLOBALS['oc_defaultFieldHeight'];
			}
			$field .= '<label for="' . $usefid . '"' . $longlabelclass . '>' . $fAR['name'] . ':' . $required . '</label>' . $longlabel . '<textarea name="' . $usefid . '" id="' . $usefid . '" cols="' . safeHTMLstr(varValue('width', $fAR, $GLOBALS['oc_defaultFieldWidth'])) . '" rows="' . safeHTMLstr($height) . '" maxlength="' . safeHTMLstr(varValue('maxchars', $fAR)) . '" ' . ((isset($fAR['placeholder']) && !empty($fAR['placeholder'])) ? ('placeholder="' . safeHTMLstr($fAR['placeholder']) . '"') : '') . $disabled . '>' . safeHTMLstr(varValue($usefid, $fVals)) . '</textarea>';
			if (isset($fAR['maxwords'])) {
				$maxwords = $fAR['maxwords'];
			} else {
				$maxwords = 0;
			}
			if ( ($maxwords > 0) || (isset($fAR['minwords']) && ($fAR['minwords'] > 0)) ) { // word limit
				$field .= '
<script language="javascript" type="text/javascript">
<!--
var oc_' . $usefid . 'Field = document.getElementById("' . $usefid . '");
// Add validation check
if (oc_' . $usefid . 'Field.addEventListener) {
	oc_' . $usefid . 'Field.addEventListener("' . $GLOBALS['oc_jsValidateEvent'] . '", function(){oc_checkWordNum("' . $usefid . '", "' . safeHTMLstr($fAR['short']) . '", ' . safeHTMLstr($maxwords) . ', ' . varValue('minwords', $fAR, ((isset($fAR['required']) && $fAR['required']) ? 1 : 0), true) . ')}, false);
} else if (oc_' . $usefid . 'Field.attachEvent) {
	oc_' . $usefid . 'Field.attachEvent("on' . $GLOBALS['oc_jsValidateEvent'] . '", function(){oc_checkWordNum("' . $usefid . '", "' . safeHTMLstr($fAR['short']) . '", ' . safeHTMLstr($maxwords) . ', ' . varValue('minwords', $fAR, ((isset($fAR['required']) && $fAR['required']) ? 1 : 0), true) . ')});
}
// -->
</script>
';
			}
			if ( (isset($fAR['maxchars']) && ($fAR['maxchars'] > 0)) || (isset($fAR['minchars']) && ($fAR['minchars'] > 0)) ) { // character limit
				$field .= '
<script language="javascript" type="text/javascript">
<!--
var oc_' . $usefid . 'Field = document.getElementById("' . $usefid . '");
// Add validation check
if (oc_' . $usefid . 'Field.addEventListener) {
	oc_' . $usefid . 'Field.addEventListener("' . $GLOBALS['oc_jsValidateEvent'] . '", function(){oc_checkCharNum("' . $usefid . '", "' . safeHTMLstr($fAR['short']) . '", ' . varValue('maxchars', $fAR, 0, true) . ', ' . varValue('minchars', $fAR, ((isset($fAR['required']) && $fAR['required']) ? 1 : 0), true) . ')}, false);
} else if (oc_' . $usefid . 'Field.attachEvent) {
	oc_' . $usefid . 'Field.attachEvent("on' . $GLOBALS['oc_jsValidateEvent'] . '", function(){oc_checkCharNum("' . $usefid . '", "' . safeHTMLstr($fAR['short']) . '", ' . varValue('maxchars', $fAR, 0, true) . ', ' . varValue('minchars', $fAR, ((isset($fAR['required']) && $fAR['required']) ? 1 : 0), true) . ')});
}
// -->
</script>
';
			}
			break;

		case 'dropdown':
			$field .= '<label for="' . $usefid . '"' . $longlabelclass . '>' . $fAR['name'] . ':' . $required . '</label>' . $longlabel . '<select name="' . $usefid . '" id="' . $usefid . '" ' . $disabled . '><option value=""></option>' . generateSelectOptions($fAR['values'], varValue($usefid, $fVals), ((!isset($fAR['usekey']) || $fAR['usekey']) ? true : false), false) . '</select>';
			break;

		case 'picklist':
			if (!isset($fAR['multiple']) || $fAR['multiple']) { // default to multiple for picklist
				$multiple = true;
			} else {
				$multiple = false;
			}
			if ($multiple && isset($fVals[$usefid]) && !is_array($fVals[$usefid])) {
				$vals = explode(',', $fVals[$usefid]);
			} else {
				$vals = varValue($usefid, $fVals);
			}
			$field .= '<label for="' . $usefid . '"' . $longlabelclass . '>' . $fAR['name'] . ':' . $required . '</label>' . $longlabel . '<select name="' . $usefid . (($multiple) ? '[]' : '') . '" id="' . $usefid . '" ' . $disabled . (($multiple) ? 'multiple ' : '') . 'size="' . varValue('height', $fAR, $GLOBALS['oc_defaultFieldHeight'], true) . '">' . generateSelectOptions($fAR['values'], $vals, ((!isset($fAR['usekey']) || $fAR['usekey']) ? true : false), (($multiple) ? true : false)) . '</select>';
			break;

		case 'radio':
			if (isset($fAR['display']) && isset($GLOBALS['formDisplayAR'][$fAR['display']])) {
				$delimiter = $GLOBALS['formDisplayAR'][$fAR['display']];
			} elseif (isset($fAR['delimiter'])) {
				$delimiter = $fAR['delimiter'];
			} else {
				$delimiter = $GLOBALS['oc_defaultDelimiter'];
			}
			$field .= '<label' . $longlabelclass . '>' . $fAR['name'] . ':' . $required . '</label>' . $longlabel . '<fieldset class="radio">';
			$field .= generateRadioOptions($usefid, $fAR['values'], varValue($usefid, $fVals), ((!isset($fAR['usekey']) || $fAR['usekey']) ? true : false), $disabled, $delimiter);
			$field .= '</fieldset>';
			break;

		case 'checkbox':
			if (isset($fAR['display']) && isset($GLOBALS['formDisplayAR'][$fAR['display']])) {
				$delimiter = $GLOBALS['formDisplayAR'][$fAR['display']];
			} elseif (isset($fAR['delimiter'])) {
				$delimiter = $fAR['delimiter'];
			} else {
				$delimiter = $GLOBALS['oc_defaultDelimiter'];
			}
			$field .= '<label' . $longlabelclass . '>' . $fAR['name'] . ':' . $required . '</label>' . $longlabel . '<fieldset class="checkbox">';
			if (isset($fVals[$usefid])) {
				if (!is_array($fVals[$usefid])) {
					$vals = explode(',', $fVals[$usefid]);
				} else {
					$vals = $fVals[$usefid];
				}
			} else {
				$vals = array();
			}
			$field .= generateCheckboxOptions($usefid, $fAR['values'], $vals, ((!isset($fAR['usekey']) || $fAR['usekey']) ? true : false), $disabled, $delimiter);
			$field .= '</fieldset>';
			break;

		case 'password':
			$field .= '<label for="' . $usefid . '"' . $longlabelclass . '>' . $fAR['name'] . ':' . $required . '</label>' . $longlabel . '<input name="' . $usefid . '" id="' . $usefid . '" type="password" size="60" value="' . safeHTMLstr(varValue($usefid, $fVals)) . '" />';
			break;

		case 'file':
			$field .= '<br /><label for="' . $usefid . '"' . $longlabelclass . '>' . $fAR['name'] . ':' . $required . '</label>' . $longlabel . '<input name="' . $usefid. '" id="' . $usefid . '" type="file" /> &nbsp; ' . oc_('Format') . ':' . $required . ' ';
			if (count($GLOBALS['extAR']) == 1) {  // Only accept one format?
				$field .= $GLOBALS['OC_formatAR'][$GLOBALS['extAR'][0]] . ' <input type="hidden" name="format" value="' . $GLOBALS['extAR'][0] . '">';
			} else {
				$formatOptions = array();
				foreach ($GLOBALS['extAR'] as $formatVal) {
					$formatOptions[$formatVal] = $GLOBALS['OC_formatAR'][$formatVal];
				}
				$field .= '<select name="format">' . generateSelectOptions($formatOptions, varValue('format', $fVals)) . '</select>';
			}
			break;

		default:
			err('There is an error with field ID ' . $usefid);
	}

	if (!empty($fieldnote)) {
		$field .= $fieldnote;
	}
	
	$field .= '</div>'."\n";	
	
	return($field);
	
}

function oc_displayFields(&$fieldsAR, &$fVals=array()) {
	foreach ($fieldsAR as $fieldID => $fieldAR) {
		print '<div class="question">' . oc_genField($fieldID, $fieldAR, $fVals) . '</div>';
	}
}

function oc_displayFieldSet(&$fsAR, &$fieldsAR, &$fVals=array()) {
	// Include field validation translation strings
	print '
<script>
oc_transAR[\'%1$s field contains %2$d words; limit is %3$s words.\'] = \'' . preg_replace("/'/", "\\'", oc_('%1$s field contains %2$d words; limit is %3$s words.')) .  '\';
oc_transAR[\'%1$s field contains %2$d characters; limit is %3$s characters.\'] = \'' . preg_replace("/'/", "\\'", oc_('%1$s field contains %2$d characters; limit is %3$s characters.')) .  '\';
</script>
';

	// Iterate through fieldsets and fields
	foreach ($fsAR as $fieldset => $fieldsetAR) {
		// skip fieldset if no fields included -- e.g., review fields removed because of sub type
		$fcount = 0;
		foreach ($fieldsetAR['fields'] as $f) {
			if (isset($fieldsAR[$f])) {
				$fcount++;
			}
		}
		if ($fcount == 0) {
			continue;
		}
		
		if ((count($fieldsetAR['fields']) == 1) && ($fieldsAR[$fieldsetAR['fields'][0]]['type'] == 'hidden')) {	// handle special case of single hidden field
			print oc_genField($fieldsetAR['fields'][0], $fieldsAR[$fieldsetAR['fields'][0]], $fVals);
			continue;
		}
		
		print '<fieldset id="' . safeHTMLstr($fieldset) . '"><legend>' . safeHTMLstr($fieldsetAR['fieldset']) . '</legend>';
		if (!empty($fieldsetAR['note'])) {
			print '<div class="fieldsetnote note">' . $fieldsetAR['note'] . '</div>';
		}
		
		if ($fieldset == 'fs_authors') {
			// setup dynamic add authors
			if (($GLOBALS['oc_authorNum'] == 1) && ($GLOBALS['OC_configAR']['OC_authorsMax'] == 1)) {
				$singleAuthor = true;
			} else {
				$singleAuthor = false;
				$blankAR = array();
				$authorFields = '<legend>' . oc_('Author') . ' [:authornum:]</legend>';
				foreach ($fieldsetAR['fields'] as $fieldID) {
					if (!isset($fieldsAR[$fieldID])) { continue; }
					$authorFields .= oc_genField($fieldID, $fieldsAR[$fieldID], $blankAR, '[:authornum:]');
				}
				if (preg_match("/edit\.p/", $_SERVER['PHP_SELF'])) {
					//T: Single quotes (') must be written as \\' for translation of string "X = Delete Author ..."
					$authorFields .= '<div class="ocsubauthorutil"><span onclick="alert(\'' . oc_('X = Delete Author\n+ = Move Author Up\n&ndash; = Move Author Down\n\nNote: Only update author fields\nafter deleting/moving them') . '\')" title="' . oc_('Info') . '">?</span><span onclick="oc_delAuthor([:authornum:])" title="' . oc_('Delete Author') . '">X</span><span onclick="oc_moveAuthor([:authornum:], 1)" title="' . oc_('Move Author Up') . '">+</span><span onclick="oc_moveAuthor([:authornum:], 0)" title="' . oc_('Move Author Down') . '">&ndash;</span></div>';
				}
				print '
<script language="javascript" type="text/javascript">
<!--
var oc_authorsMax = ' . $GLOBALS['OC_configAR']['OC_authorsMax'] . ';
var oc_authorsMinDisplay = ' . $GLOBALS['OC_configAR']['OC_authorsMinDisplay'] . ';
var oc_authorNum = ' . $GLOBALS['oc_authorNum'] . ';
var authorFields = ' . json_encode($authorFields) . ';
var updateAuthorFieldsAR = new Array(' . ((count($GLOBALS['updateAuthorFieldsAR']) > 0) ? ('"' . implode('","', $GLOBALS['updateAuthorFieldsAR']) . '"') : '') . ');
function oc_addAuthor() {
	if ( ! document.getElementById ) { return; }
	if (oc_authorNum >= oc_authorsMax) { alert("' . oc_('Maximum number of authors allowed by the Chair reached') . '"); return; }
	oc_authorNum++;
	var newAuthorFields = authorFields.replace(/\[:authornum:\]/g, oc_authorNum);
	var newFS = document.createElement("fieldset");
	newFS.id = "ocauthor" + oc_authorNum;
	newFS.innerHTML = newAuthorFields;
	var addAuthorObj = document.getElementById("addauthor");
	addAuthorObj.parentNode.insertBefore(newFS, addAuthorObj);
	document.getElementById("authornum").value = oc_authorNum;
	for (var f in updateAuthorFieldsAR) {
		var fEl = document.getElementById(updateAuthorFieldsAR[f]);
		fEl.options[fEl.length] = new Option("' . oc_('Author') . ' " + oc_authorNum, oc_authorNum);
	}
	if (oc_authorNum == oc_authorsMax) { 
		addAuthorObj.style.display = "none";
	}
}
function oc_updateAuthor(snip, from, to) {
	var re = new RegExp("((?:name|id|for)=\"\\[a-z_]+)" + from + "\"", "g");
	var renew = "$1" + to + "\"";
	snip = snip.replace(re, renew);
	re = new RegExp("(oc_(?:del|move)Author.)" + from, "g");  // . used as JS choking on \\( and \\)
	renew = "$1" + to;
	snip = snip.replace(re, renew);
	return(snip);
}
function oc_getAuthoBlock(id) {
	// set field values based on current content, otherwise original (last saved) value attributes are retrieved via innerHTML
	var els = document.getElementById(id).getElementsByTagName("input");
	for (var i = 0; i < els.length; i++) {
		if (els[i].type == "text") {
			els[i].setAttribute("value", els[i].value);
		} else if ((els[i].type == "checkbox") || (els[i].type == "radio")) {
			if (els[i].checked === true) {
				els[i].setAttribute("checked", els[i].checked);
			} else {
				els[i].removeAttribute("checked");
			}
		}
	}
	els = document.getElementById(id).getElementsByTagName("textarea");
	for (var i = 0; i < els.length; i++) {
		els[i].setAttribute("value", els[i].value);
	}
	els = document.getElementById(id).getElementsByTagName("select");
	for (var i = 0; i < els.length; i++) {
		if (els[i].multiple == true) {
			for (var j=0; j < els[i].options.length; j++) {
				if (els[i].options[j].selected) {
					els[i].options[j].setAttribute("selected", "selected");
				} else {
					els[i].options[j].removeAttribute("selected");
				}
			}
		} else {
			els[i].options[els[i].options.selectedIndex].setAttribute("selected", "selected");
		}
	}
	return(document.getElementById(id).innerHTML);
}
oc_transAR[\'Delete Author %1$d\'] = \'' . preg_replace("/'/", "\\'", oc_('Delete Author %1$d')) .  '\';
function oc_delAuthor(a) {
	var msg = "Delete Author %1$d";
	if (oc_authorNum == 1) {
		alert("' . oc_('Invalid request') . '");
	} else if ((parseFloat(a) != parseInt(a)) || isNaN(a) || (a < 1) || (a > oc_authorNum)) {
		alert("' . oc_('Invalid request') . '");
	} else if (confirm(oc_transAR[msg].oc_sprintf(a))) {
		document.getElementById("ocauthors").removeChild(document.getElementById("ocauthor" + a));
		while (a < oc_authorNum) {
			var aplusone = a + 1;
			var snip = oc_getAuthoBlock("ocauthor" + aplusone);
			document.getElementById("ocauthor" + aplusone).innerHTML = oc_updateAuthor(snip, aplusone, a);
			document.getElementById("ocauthor" + aplusone).children[0].innerHTML = "' . oc_('Author') . ' " + a;
			document.getElementById("ocauthor" + aplusone).id = "ocauthor" + a;
			a++;
		}
		oc_authorNum--;
		for (var f in updateAuthorFieldsAR) {
			var fEl = document.getElementById(updateAuthorFieldsAR[f]);
			fEl.remove(fEl.length-1);
		}
		oc_updateSubmitEmailAddresses(null, true, ' . safeHTMLstr($OC_configAR['OC_emailAuthorRecipients']) . ');
	}
}
function oc_moveAuthor(from, dir) {
	var to=0;
	if ((parseFloat(from) != parseInt(from)) || isNaN(from) || (from < 1) || (from > oc_authorNum)) {
		alert("' . oc_('Invalid request') . '");
	} else if ((dir == 1) && (from > 1)) {
		to = from - 1;
	} else if ((dir == 0) && (from < oc_authorNum)) {
		to = from + 1;
	}
	if (to == 0) {
		alert("' . oc_('Invalid request') . '");
	} else {
		var snip1 = oc_getAuthoBlock("ocauthor" + from);
		var snip2 = oc_getAuthoBlock("ocauthor" + to);
		snip1 = oc_updateAuthor(snip1, from, to);
		snip2 = oc_updateAuthor(snip2, to, from);
		document.getElementById("ocauthor" + from).innerHTML = snip2;
		document.getElementById("ocauthor" + to).innerHTML = snip1;
		document.getElementById("ocauthor" + from).children[0].innerHTML = "' . oc_('Author') . ' " + from;
		document.getElementById("ocauthor" + to).children[0].innerHTML = "' . oc_('Author') . ' " + to;
		oc_updateSubmitEmailAddresses(null, true, ' . safeHTMLstr($OC_configAR['OC_emailAuthorRecipients']) . ');
	}
}
// -->
</script>
';
			}
			
			// display author fieldsets
			print '<div id="ocauthors" aria-live="polite" aria-relevant="additions" region="group">';
			for ($i=1; $i<=$GLOBALS['oc_authorNum']; $i++) {
				print '<fieldset id="ocauthor' . $i . '">' . ($singleAuthor ? '' : ('<legend>' . oc_('Author') . ' ' . $i . '</legend>'));
				foreach ($fieldsetAR['fields'] as $fieldID) {
					if (!isset($fieldsAR[$fieldID])) { continue; }
					print oc_genField($fieldID, $fieldsAR[$fieldID], $fVals, $i);
				}
				if (preg_match("/edit\.p/", $_SERVER['PHP_SELF']) && !$singleAuthor) {
					//T: Single quotes (') must be written as \\' for translation of string "X = Delete Author ..."
					print '<div class="ocsubauthorutil"><span onclick="alert(\'' . oc_('X = Delete Author\n+ = Move Author Up\n&ndash; = Move Author Down\n\nNote: Only update individual author fields once\nall deleting and moving has been done') . '\')" title="' . oc_('Info') . '" tabindex="0">?</span><span onclick="oc_delAuthor(' . $i . ')" title="' . oc_('Delete Author') . '" tabindex="0">X</span><span onclick="oc_moveAuthor(' . $i . ', 1)" title="' . oc_('Move Author Up') . '" tabindex="0">+</span><span onclick="oc_moveAuthor(' . $i . ', 0)" title="' . oc_('Move Author Down') . '" tabindex="0">&ndash;</span></div>';
				}
				print '</fieldset>';
			}
			
			// display extra author link
			if ($i <= $GLOBALS['OC_configAR']['OC_authorsMax']) {
				print '
<script language="javascript" type="text/javascript">
<!--
document.write(\'<div id="addauthor"><a href="#" onclick="oc_addAuthor(); return false;" title="' . htmlspecialchars(oc_('click to add more authors'), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(oc_('Add Author'), ENT_QUOTES, 'UTF-8') . '</a></div>\');
// -->
</script>
<noscript>
<div class="warn">' . oc_('To add more authors, enable JavaScript and reload page.') . '</div>
</noscript>
';
			}
			print '</div>';
		} else {	// not authors fieldset
			foreach ($fieldsetAR['fields'] as $fieldID) {
				if (!isset($fieldsAR[$fieldID])) { continue; }
				print oc_genField($fieldID, $fieldsAR[$fieldID], $fVals);
			}
		}
		print '</fieldset>'."\n";
	}
}

function oc_getFieldValue(&$fieldsAR, &$fVals, $fieldID, $valID='', $charLimit=0, $prefixMultiValues=true) {
	if (empty($valID)) {
		$valID = $fieldID;
	}

	if (!isset($fVals[$valID])) {
		return('');
	} else {
		$retval = ''; // return value
		if (isset($fieldsAR[$fieldID]) 
				&& (
						($fieldsAR[$fieldID]['type'] == 'checkbox')
						|| ($fieldsAR[$fieldID]['type'] == 'picklist')
					) 
				&& !is_array($fVals[$valID])
				&& ($fieldID != 'consent') // skip consent in case it has a comma in text as it is set for usekey:false
		) {
			$vals = explode(',', $fVals[$valID]);
		} else {
			$vals = $fVals[$valID];
		}
		if (is_array($vals)) {
			if (count($vals) == 1) {
				if (is_array($fieldsAR[$fieldID]['values']) && (!isset($fieldsAR[$fieldID]['usekey']) || $fieldsAR[$fieldID]['usekey'])) {
					$retval = $fieldsAR[$fieldID]['values'][$vals[0]];
				} else {
					$retval = $vals[0];
				}
			} else {
				if ($prefixMultiValues) {
					$fret = "\n";
					$prefix = '    - ';
				} else {
					$fret = '';
					$prefix = '';
				}
				foreach ($vals as $f) {
					if (is_array($fieldsAR[$fieldID]['values']) && (!isset($fieldsAR[$fieldID]['usekey']) || $fieldsAR[$fieldID]['usekey'])) {
						$fret .= $prefix . $fieldsAR[$fieldID]['values'][$f] . "\n";
					} else {
						$fret .= $prefix . $f . "\n";
					}
				}
				$retval = rtrim($fret, "\n");
			}
		} elseif (isset($fieldsAR[$fieldID]['values']) && is_array($fieldsAR[$fieldID]['values']) 
					&& (!isset($fieldsAR[$fieldID]['usekey']) || $fieldsAR[$fieldID]['usekey']) 
					&& ($vals != '')
					&& isset($fieldsAR[$fieldID]['values'][$vals])
		) {
			$retval = $fieldsAR[$fieldID]['values'][$vals];
		} else {
			$retval = varValue($valID, $fVals);
		}
		
		if (($charLimit > 0) && (oc_strlen($retval) > $charLimit)) {
			return(utf8_encode(substr(utf8_decode($retval), 0, $charLimit)));
		} else {
			return($retval);
		}
	}
}

function oc_stripHREF($s) {
	$from = array(
		'/<a href[^>]+>/',
		'/<\/a>/'
	);
	$to = array(
		'',
		''
	);
	return(preg_replace($from, $to, $s));
}

function oc_genFieldMessage(&$fsAR, &$fieldsAR, &$fVals=array()) {
	$fmsg = '';
	foreach ($fsAR as $fieldset => $fieldsetAR) {
		if ($fieldset == 'fs_authors') {
			for ($i=1; $i<=$GLOBALS['oc_authorNum']; $i++) {
				if (empty($fVals['name_last' . $i])) { continue; }
				$fmsg .= oc_('Author') . ' ' . $i . ":\n";
				foreach ($fieldsetAR['fields'] as $fieldID) {
					if (!oc_fieldEnabled($fieldID, $fieldsAR)) { continue; }
					$fmsg .= '    ' . $fieldsAR[$fieldID]['short'] . ": " . oc_stripHREF(oc_getFieldValue($fieldsAR, $fVals, $fieldID, $fieldID . $i)) . "\n";
				}
				$fmsg .=  "\n\n";
			}
		} else {
			foreach ($fieldsetAR['fields'] as $fieldID) {
				if (preg_match("/^password/i", $fieldID)
					|| ($fieldID == 'file')
					|| empty($fieldsAR[$fieldID]['name'])
					|| (!oc_fieldEnabled($fieldID, $fieldsAR))
				) { 
					continue;
				}
				$fmsg .= $fieldsAR[$fieldID]['short'] . ': ' . oc_stripHREF(oc_getFieldValue($fieldsAR, $fVals, $fieldID)) . "\n\n";
			}
		}
	}
	return($fmsg);
}

function oc_showFieldSet(&$fsAR, &$fieldsAR, &$fVals, $blind=false, $committee=false, $references=false) {
	foreach ($fsAR as $fieldset => $fieldsetAR) {
		if (count($fieldsetAR['fields']) == 0) { continue; }	// skip fieldset if no fields included
		if (preg_match("/^fs_passwords$/i", $fieldset)) { continue; }	// skip password fs
		if ($fieldset == 'fs_authors') {	// display author fields
			if (!$blind) {
				for ($i=1; $i<=$GLOBALS['oc_authorNum']; $i++) {
					if (!isset($fVals['name_last' . $i])) { continue; }
					print '<tr><th>' . oc_('Author') . ' ' . $i . ':</th><td>';
					if ($references) {
						print '[ <a href="https://citeseerx.ist.psu.edu/search?q=author' . urlencode(':("' . $fVals['name'.$i] . '")') . '" target="_blank" rel="noopener noreferrer">CiteSeer</a> | <a href="https://scholar.google.com/scholar?q=author' . urlencode(':"' . $fVals['name'.$i] . '"') . '" target="_blank" rel="noopener noreferrer">Google Scholar</a> | <a href="https://www.sciencedirect.com/search?qs=' . urlencode('"' . $fVals['name'.$i] . '"') . '" target="_blank" rel="noopener noreferrer">ScienceDirect</a> ]<br /><br />';
					}
					print '<table class="ocfields">';
					foreach ($fieldsetAR['fields'] as $fieldID) {
						if (!isset($fieldsAR[$fieldID]) || preg_match("/^password/", $fieldID) || ($committee && empty($fVals[$fieldID.$i]))) { continue; }
						if ($committee && isset($_SESSION[OCC_SESSION_VAR_NAME]['acpc']) && preg_match("/\/review\//", $_SERVER['PHP_SELF'])) {
							if (
								(($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == "T") && (!isset($fieldsAR[$fieldID]['advocate']) || !$fieldsAR[$fieldID]['advocate'])) 
								||
								(($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] != "T") && (!isset($fieldsAR[$fieldID]['reviewer']) || !$fieldsAR[$fieldID]['reviewer']))
							 ) {
								 continue;
							 }
						}
						print '<tr><th>' . safeHTMLstr(($committee ? oc_($fieldsAR[$fieldID]['short']) : $fieldsAR[$fieldID]['short'])) . ':</th><td>';
						$fieldVal = oc_getFieldValue($fieldsAR, $fVals, $fieldID, $fieldID . $i);
						if (($fieldID == 'email') && validEmail($fieldVal)) { // email
							print '<a href="mailto:' . $fieldVal . '">' . safeHTMLstr($fieldVal) . '</a>';
						} elseif (preg_match("/^https?\:\/\//", $fieldVal) && !preg_match("/[\r\n]/", $fieldVal)) { // url
							print '<a href="' . $fieldVal . '" target="_blank" rel="noopener noreferrer">' . safeHTMLstr($fieldVal) . '</a>';
						} else { // other
							print nl2br(safeHTMLstr(oc_getFieldValue($fieldsAR, $fVals, $fieldID, $fieldID . $i)));
						}
						print "</td></tr>\n";
					}
					print "</table></td></tr>\n";
				}
			}
		} else {	// not authors fieldset
			foreach ($fieldsetAR['fields'] as $fieldID) {
				if (!isset($fieldsAR[$fieldID]) || preg_match("/^password/", $fieldID) || ($committee && empty($fVals[$fieldID]))) { continue; }
				if ($committee && isset($_SESSION[OCC_SESSION_VAR_NAME]['acpc']) && preg_match("/\/review\//", $_SERVER['PHP_SELF'])) {
					if (
						(($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] == "T") && (!isset($fieldsAR[$fieldID]['advocate']) || !$fieldsAR[$fieldID]['advocate'])) 
						||
						(($_SESSION[OCC_SESSION_VAR_NAME]['acpc'] != "T") && (!isset($fieldsAR[$fieldID]['reviewer']) || !$fieldsAR[$fieldID]['reviewer']))
					 ) {
						 continue;
					 }
				}
				if (preg_match("/^(?:file|format)/", $fieldID)) { continue; }	// skip file/format fields
				print '<tr><th>' . safeHTMLstr(($committee ? oc_($fieldsAR[$fieldID]['short']) : $fieldsAR[$fieldID]['short'])) . ':</th><td>' . nl2br(safeHTMLstr(trim(oc_getFieldValue($fieldsAR, $fVals, $fieldID))));
				if (($fieldID == 'title') && $references) {
					print ' &nbsp; &nbsp; <span style="white-space: nowrap;">[ <a href="http://citeseerx.ist.psu.edu/search?q=title' . urlencode(':("' . $fVals['title'] . '"') . ')" target="_blank" rel="noopener noreferrer">CiteSeer</a> | <a href="http://scholar.google.com/scholar?q=intitle' . urlencode(':"' . $fVals['title'] . '"') . '" target="_blank" rel="noopener noreferrer">Google Scholar</a> | <a href="http://www.sciencedirect.com/science/quicksearch?query=' . urlencode('"' . $fVals['title'] . '"') . '" target="_blank" rel="noopener noreferrer">ScienceDirect</a> ]</span>';
				}
				print "</td></tr>\n";
			}
		}
	}
}

function oc_limitString($min, $max, $required=false) {
	$limit = '';
	if ($min > 0) {
		$limit = $min;
	} elseif ($required) {
		$limit = 1;
	} else {
		$limit = 0;
	}
	if ($max > 0) {
		if (empty($limit) || ($min == $max)) {
			$limit = $max;
		} else {
			$limit .= '-' . $max;
		}
	} else {
		$limit .= '+';
	}
	return($limit);
}

function oc_validateField($fieldID, &$fieldsAR, &$saveFieldAR, &$err, $authorID=0, $requiredOverride=false) {
	if (!isset($fieldsAR[$fieldID])) { 
		return(false); // field not defined
	}
	
	if (oc_fieldEnabled($fieldID, $fieldsAR)
			&& (!isset($fieldsAR[$fieldID]['donotvalidate']) || !$fieldsAR[$fieldID]['donotvalidate'])
	) {
		// Author field?  Add author ID
		if ($authorID > 0) {
			$postFieldID = $fieldID . $authorID;
		} else {
			$postFieldID = $fieldID;
		}
		// Validate if field is set and has a value
		if (isset($_POST[$postFieldID]) && ((is_array($_POST[$postFieldID]) && (count($_POST[$postFieldID]) > 0)) || ($_POST[$postFieldID] != ''))) {
			// Check fields with values array set
			if (isset($fieldsAR[$fieldID]['values']) && is_array($fieldsAR[$fieldID]['values'])) {
				$values = array();
				// handle both single and multi-selection fields
				if (is_array($_POST[$postFieldID])) {
					$checkAR = $_POST[$postFieldID];
				} else {
					$checkAR = array($_POST[$postFieldID]);
				}
				// validate values
				foreach ($checkAR as $val) {
					if (
						((!isset($fieldsAR[$fieldID]['usekey']) || $fieldsAR[$fieldID]['usekey']) && isset($fieldsAR[$fieldID]['values'][$val]))
							|| 
						(isset($fieldsAR[$fieldID]['usekey']) && ( ! $fieldsAR[$fieldID]['usekey']) && in_array($val, $fieldsAR[$fieldID]['values'])) // backward compatibilty for sub. type & old custom fields
					) {
						$values[] = $val;
					} else {
						if ($authorID > 0) {
							//T: %1$d = author number, %2$s = topic field short name; %3$s = invalid field value
							$err .= '<li>' . sprintf(oc_('Author %1$d %2$s field value is invalid: %3$s'), $authorID, $fieldsAR[$fieldID]['short'], safeHTMLstr($val)) . '</li>';
						} else {
							//T: %1$s = topic field short name; %2$s = invalid field value
							$err .= '<li>' . sprintf(oc_('%1$s field value is invalid: %2$s'), $fieldsAR[$fieldID]['short'], safeHTMLstr($val)) . '</li>';
						}
					}
				}

				// check picklist and checkbox selection count
				$count = count($values);
				if (($fieldsAR[$fieldID]['type'] == 'picklist') || ($fieldsAR[$fieldID]['type'] == 'checkbox')) {
					$min = (isset($fieldsAR[$fieldID]['minselections']) ? $fieldsAR[$fieldID]['minselections'] : 0);
					$max = (isset($fieldsAR[$fieldID]['maxselections']) ? $fieldsAR[$fieldID]['maxselections'] : 0);
					if ((($min > 0) && ($count < $min)) 
							|| (($max > 0) && ($count > $max))
					) {
						$limit = oc_limitString($min, $max, varValue('required', $fieldsAR[$fieldID], false));
						if ($authorID > 0) {
							// %1$d = author number, %2$s = field name, $3$d = number; %4$s = number or number range (e.g., 4 or 1-3)
							$err .= '<li>' . sprintf(oc_('Author %1$d %2$s field contains %3$d selection(s); limit is %4$s selection(s)'), $authorID, oc_($fieldsAR[$fieldID]['short']), $count, $limit) . '</li>';
						} else {
							// %1$s = field name, $2$d = number; %3$s = number or number range (e.g., 1-3)
							$err .= '<li>' . sprintf(oc_('%1$s field contains %2$d selection(s); limit is %3$s selection(s)'), oc_($fieldsAR[$fieldID]['short']), $count, $limit) . '</li>';
						}
					}
				}
				// store value(s)
				if (empty($limit) && ($count > 0)) {
					$saveFieldAR[$fieldID] = "'" . safeSQLstr(implode(',', $values)) . "'";
				} else {
					$saveFieldAR[$fieldID] = "NULL";
				}

			} else { // regular field w/no values
				$wlimit = '';
				$climit = '';
				// Check text and textarea input lengths
				if (($fieldsAR[$fieldID]['type'] == 'text') || ($fieldsAR[$fieldID]['type'] == 'textarea')) {
					// check words
					$min = (isset($fieldsAR[$fieldID]['minwords']) ? 
								$fieldsAR[$fieldID]['minwords'] 
								: 
								((isset($fieldsAR[$fieldID]['required']) && $fieldsAR[$fieldID]['required']) ? 1 : 0)
							);
					$max = (isset($fieldsAR[$fieldID]['maxwords']) ? $fieldsAR[$fieldID]['maxwords'] : 0);
					$count = count(explode(' ', preg_replace("/\s+/", " ", trim($_POST[$postFieldID])))); // We're not using str_word_count as numbers are not counted
					if ((($min > 0) && ($count < $min)) 
							|| (($max > 0) && ($count > $max))
					) {
						$wlimit = oc_limitString($min, $max, varValue('required', $fieldsAR[$fieldID], false));
						if ($authorID > 0) {
							// %1$d = author number, %2$s = field name, $3$d = number; %4$s = number or number range (e.g., 4 or 1-3)
							$err .= '<li>' . sprintf(oc_('Author %1$d %2$s field contains %3$d words; limit is %4$s words.'), $authorID, oc_($fieldsAR[$fieldID]['short']), $count, $wlimit) . '</li>';
						} else {
							// %1$s = field name, $2$d = number; %3$s = number or number range (e.g., 1-3)
							$err .= '<li>' . sprintf(oc_('%1$s field contains %2$d words; limit is %3$s words.'), oc_($fieldsAR[$fieldID]['short']), $count, $wlimit) . '</li>';
						}
					}
					
					// check chars
					$min = (isset($fieldsAR[$fieldID]['minchars']) ? 
								$fieldsAR[$fieldID]['minchars']
								:
								((isset($fieldsAR[$fieldID]['required']) && $fieldsAR[$fieldID]['required']) ? 1 : 0)
							);
					$max = (isset($fieldsAR[$fieldID]['maxchars']) ? $fieldsAR[$fieldID]['maxchars'] : 0);
					$count = oc_strlen(preg_replace("/\s+/", " ", trim($_POST[$postFieldID])));
					if ((($min > 0) && ($count < $min)) 
							|| (($max > 0) && ($count > $max))
					) {
						$climit = oc_limitString($min, $max, varValue('required', $fieldsAR[$fieldID], false));
						if ($authorID > 0) {
							// %1$d = author number, %2$s = field name, $3$d = number; %4$s = number or number range (e.g., 4 or 1-3)
							$err .= '<li>' . sprintf(oc_('Author %1$d %2$s field contains %3$d characters; limit is %4$s characters.'), $authorID, oc_($fieldsAR[$fieldID]['short']), $count, $climit) . '</li>';
						} else {
							// %1$s = field name, $2$d = number; %3$s = number or number range (e.g., 1-3)
							$err .= '<li>' . sprintf(oc_('%1$s field contains %2$d characters; limit is %3$s characters.'), oc_($fieldsAR[$fieldID]['short']), $count, $climit) . '</li>';
						}
					}
					
				} elseif (($fieldsAR[$fieldID]['type'] == 'email') && !validEmail($_POST[$postFieldID])) {
					if ($authorID > 0) {
						//T: %1$d = author number, %2$s = field name, %3$s = field value
						$err .= '<li>' . sprintf(oc_('Author %1$d %2$s field value is invalid: %3$s'), $authorID, oc_($fieldsAR[$fieldID]['short']), safeHTMLstr($_POST[$postFieldID])) . '</li>';
					} else {
						//T: %1$s = field name, %2$s = field value
						$err .= '<li>' . sprintf(oc_('%1$s field value is invalid: %2$s'), oc_($fieldsAR[$fieldID]['short']), safeHTMLstr($_POST[$postFieldID])) . '</li>';
					}
				}
				
				// store value
				if (empty($wlimit) && empty($climit)) {
					$saveFieldAR[$fieldID] = "'" . safeSQLstr(trim($_POST[$postFieldID])) . "'";
				} else {
					$saveFieldAR[$fieldID] = "NULL";
				}
			}
		}
		
		if (!isset($saveFieldAR[$fieldID])) {
			if ( isset($fieldsAR[$fieldID]['required']) && $fieldsAR[$fieldID]['required'] && ! $requiredOverride) {
				if ($authorID > 0) {
					//T: %1$d = author number, %2$s = field name
					$err .= '<li>' . sprintf(oc_('Author %1$d %2$s field is required'), $authorID, oc_($fieldsAR[$fieldID]['short'])) . '</li>';
				} else {
					//T: %s = field name
					$err .= '<li>' . sprintf(oc_('%s field is required'), oc_($fieldsAR[$fieldID]['short'])) . '</li>';
				}
			} else {
				$saveFieldAR[$fieldID] = "NULL";
			}
		}
	}
}
