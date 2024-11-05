<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$hdr = '';
$hdrfn = 1;

require_once '../include.php';

// jQueryUI optionally used for date picker ... until HTML5 datetime becomes cross-browser
oc_addHeader('
<script src="//code.jquery.com/jquery-' . OCC_JQUERY_VERSION . '.min.js"></script>
<script src="//code.jquery.com/ui/' . OCC_JQUERYUI_VERSION . '/jquery-ui.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/' . OCC_JQUERYUI_VERSION . '/themes/smoothness/jquery-ui.css">
');

function oc_statusTime($h, $m) {
	$time = '';
	$ampm = '';
	if ($h > 12) {
		$time = ($h - 12) . ':' . $m;
		$ampm = 'pm';
	} elseif ($h == 0) {
		if ($m == 0) {
			$time = 'midnight';
		} else {
			$time = '12:30';
			$ampm = 'am';
		}
	} elseif ($h == 12) {
		if ($m == 0) {
			$time = 'noon';
		} else {
			$time = '12:30';
			$ampm = 'pm';
		}
	} else {
		$time = ltrim($h, '0') . ':' . $m;
		$ampm = 'am';
	}
	return($h . ':' . $m . ' || ' . $time . $ampm);
}

function oc_statusEvent(&$l) {
	$ret = '';
	if (!empty($l['open'])) {
		$opendate = new DateTime($l['open']);
		$ret .= '<div class="event" title="' . safeHTMLstr($l['name']) . ' event - click &bigotimes; to delete">&#187; will open on ' . safeHTMLstr($opendate->format('j F Y, H:i / g:ia')) . ' ' . safeHTMLstr($GLOBALS['OC_configAR']['OC_timeZone']) . ' <input type="image" name="deleteevent,open,' . safeHTMLstr($l['module']) . ',' . safeHTMLstr($l['setting']) . '," value="Delete Event" alt="Delete Event" title="Delete Event" src="../images/circlex.png" widht="12" height="10" style="border:none;background:none;" onclick="return(confirm(\'Delete event?\'));" /></div>';
	}
	if (!empty($l['close'])) {
		$closedate = new DateTime($l['close']);
		$ret .= '<div class="event" title="' . safeHTMLstr($l['name']) . ' event - click &bigotimes; to delete">&#187; will close on ' . safeHTMLstr($closedate->format('j F Y, H:i / g:ia')) . ' ' . safeHTMLstr($GLOBALS['OC_configAR']['OC_timeZone']) . ' <input type="image" name="deleteevent,close,' . safeHTMLstr($l['module']) . ',' . safeHTMLstr($l['setting']) . '," value="Delete Event" alt="Delete Event" title="Delete Event" src="../images/circlex.png" widht="12" height="10" style="border:none;background:none;" onclick="return(confirm(\'Delete event?\'));" /></div>';
	}
	return($ret);
}

if (isset($_REQUEST['install']) && ($_REQUEST['install'] == 1)) {
	require_once "install-include.php";
	$token = '';
} else {
	beginChairSession();
	printHeader("Open/Close Status",1);
	$token = $_SESSION[OCC_SESSION_VAR_NAME]['chairtoken'];
}

if (isset($_POST['ocsubmit']) && !empty($_POST['ocsubmit'])) {
	// Check for valid submission
	if (OCC_INSTALL_COMPLETE && !validToken('chair')) {
		warn('Invalid submission');
	}

	if ($_POST['ocsubmit'] == "Set Status") {
		if (preg_match("/deleteevent,((?:open|close)),(\w+),(\w+),_x/", implode("|", array_keys($_POST)), $matches)) { // delete scheduled event
			if (ocsql_query("UPDATE `" . OCC_TABLE_STATUS . "` SET `" . safeSQLstr($matches[1]) . "`=NULL WHERE `module`='" . safeSQLstr($matches[2]) . "' AND `setting`='" . safeSQLstr($matches[3]) . "' LIMIT 1")) {
				print '<p style="text-align: center; font-weight: bold;" class="note">Event deleted</p>';
			} else {
				print '<p style="text-align: center;" class="warn">unable to delete event</p>';
			}
		} else { // regular Set Status
			// Update form's OC_ fields - w/exceptions below requiring special handling
			if ((!isset($_REQUEST['install'])) && isset($_POST['OC_submissions_open']) && ($_POST['OC_submissions_open'] == 1) && ($OC_statusAR['OC_submissions_open'] == 0) && (defined('OCC_LICENSE_EXPIRES')) && (strtotime(OCC_LICENSE_EXPIRES) < time())) {
				unset($_POST['OC_submissions_open']);
				print '<p class="warn">' . base64_decode('TmV3IFN1Ym1pc3Npb25zIG1heSBub3QgYmUgb3BlbmVkIGFzIHRoZSBsaWNlbnNlIGhhcyBleHBpcmVkLiAgRXh0ZW5kIHRoZSBzdXBwb3J0IHBlcmlvZCBvciBwdXJjaGFzZSBhIG5ldyBsaWNlbnNlIGlmIHRoaXMgaXMgYSBuZXcgZXZlbnQu') . '</p>';
			}
			foreach (array_keys($_POST) as $p) {
				if (preg_match("/^[\w-]+/",$p) && isset($OC_statusAR[$p]) && preg_match("/^[01]$/i",$_POST[$p]) && ($OC_statusAR[$p] != $_POST[$p])) {
					updateStatusSetting($p, $_POST[$p]);
					$OC_statusAR[$p] = $_POST[$p];
				}
			}
		
			// Success - if install, redirect, else let user know
			if (isset($_REQUEST['install']) && ($_REQUEST['install'] == 1)) {
				header("Location: install-complete.php");
				exit;
			} else {
				print '<p style="text-align: center; font-weight: bold;" class="note">Status saved</p>';
			}
		}
	} elseif ($_POST['ocsubmit'] == "Schedule") { // add an event
		if (
			isset($_POST['status']) && preg_match("/^([a-z0-9_]+)\:([a-z0-9_]+)$/i", $_POST['status'], $matches)
			&& isset($_POST['openclose']) && preg_match("/^(?:open|close)$/", $_POST['openclose'])
			&& isset($_POST['day']) && preg_match("/^\d\d$/", $_POST['day'])
			&& isset($_POST['month']) && preg_match("/^\d\d$/", $_POST['month'])
			&& isset($_POST['year']) && preg_match("/^\d{4}$/", $_POST['year'])
			&& isset($_POST['time']) && preg_match("/^\d\d\:\d\d$/", $_POST['time'])
		) {
			$module = $matches[1];
			$setting = $matches[2];
			if (($module != 'OC') && !oc_moduleActive($module)) {
				print '<p style="text-align: center;" class="warn">module inactive</p>';
			} elseif (!isset($OC_statusAR[$setting])) {
				print '<p style="text-align: center;" class="warn">status not found</p>';
			} else {
				$date = $_POST['year'] . '-' . $_POST['month'] . '-' . $_POST['day'];
				$todayDT = new DateTime(date('Y-m-d')); // date() used otherwise in case same day which would cause todayDT > dateDT
				$dateDT = new DateTime($date);
				if ($dateDT < $todayDT) {
					print '<p style="text-align: center;" class="warn">event date must be in the future</p>';
				} else {
					if (ocsql_query("UPDATE `" . OCC_TABLE_STATUS . "` SET `" . (($_POST['openclose'] == 'open') ? 'open' : 'close') . "`='" . safeSQLstr($date . ' ' . $_POST['time'] . ':00') . "' WHERE `module`='" . safeSQLstr($module) . "' AND `setting`='" . safeSQLstr($setting) . "' LIMIT 1")) {
						print '<p style="text-align: center; font-weight: bold;" class="note">Event scheduled</p>';
					} else {
						print '<p style="text-align: center;" class="warn">unable to schedule event</p>';
					}
				}
			}
		} else {
			print '<p style="text-align: center;" class="warn">invalid event field(s)</p>';
		}
	} else {
		warn('Unknown action');
	}
}

if (isset($_REQUEST['install']) && ($_REQUEST['install'] == 1)) {
	printHeader($hdr,$hdrfn);
	print '<p style="text-align: center; font-weight: bold;">Step 5 of 5: Open Submissions & Sign-Up/In</p>';
	$installFields = '<input type="hidden" name="install" value="1" />';
} else {
	$installFields = '';
}

$ocq = "SELECT * FROM `" . OCC_TABLE_STATUS . "` WHERE `module`='OC' ORDER BY `order`, `setting`";
$ocr = ocsql_query($ocq) or err('Unable to retrieve status settings');

$nonocq = "SELECT * FROM `" . OCC_TABLE_STATUS . "` WHERE `module`!='OC' ORDER BY `module`, `order`, `setting`";
$nonocr = ocsql_query($nonocq) or err('Unable to retrieve additional status settings');

$divnum = 1;

if (empty($installFields)) { // not installing OC
	if (!isset($_POST['ocsubmit'])) {
		print '<p class="note" style="text-align: center;">Make desired changes then click any <i>Set Status</i> button, or schedule an event below:</p>';
	}
	
	print '
<div style="margin: 0 auto; display: table; border: 1px solid #ddd; padding: 10px; background-color: #eee;">
<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
<input type="hidden" name="token" value="' . $token . '" />
<input type="hidden" name="ocsubmit" value="Schedule" />
Set <select name="status"><option></option>';
	while ($ocl = ocsql_fetch_assoc($ocr)) {
		print '<option value="' . safeHTMLstr($ocl['module'] . ':' . $ocl['setting']) . '">General: ' . $ocl['name'] . '</option>';
	}
	ocsql_data_seek($ocr, 0);
	while ($nonocl = ocsql_fetch_assoc($nonocr)) {
		if (!oc_moduleActive($nonocl['module'])) { continue; }
		print '<option value="' . safeHTMLstr($nonocl['module'] . ':' . $nonocl['setting']) . '">' . safeHTMLstr($OC_modulesAR[$nonocl['module']]['name']) . ': ' . $nonocl['name'] . '</option>';
	}
	ocsql_data_seek($nonocr, 0);
	print '</select> to <select name="openclose"><option></option><option value="open">open</option><option value="close">close</option></select><br />on <select id="day" name="day"><option></option>';
	for ($i=1;$i<=31;$i++) {
			if ($i < 10) { $usei = '0' . $i; } else { $usei = $i; }
			print '<option value="' . $usei . '">' . $i . '</option>';
	}
	print '</select><select id="month" name="month"><option></option>';
	for ($m=1; $m<=12; $m++) {
			print '<option value="' . (($m < 10) ? "0$m" : $m) . '">' . oc_monthName($m) . '</option>';
	}
	print '</select><select id="year" name="year"><option></option>';
	$thisYear = date('Y');
	$endYear = $thisYear + 2;
	for ($y = $thisYear; $y <= $endYear; $y++) {
			print '<option value="' . $y . '">' . $y . '</option>';
	}
	print '</select>
<input name="date" id="datepicker" type="hidden" />
<script>
$( function() {
	$( "#datepicker" ).datepicker({
		showOn: "button",
		dateFormat: "yy-mm-dd",
		minDate: 0,
		maxDate: "+2Y",
		changeMonth: true,
		changeYear: true,
		buttonImage: "../images/calendar.png",
		buttonImageOnly: true,
		buttonText: "Select date",
		onSelect: function(dateText, inst) {
			$("#year").val(dateText.split(/-/)[0]);
			$("#month").val(dateText.split(/-/)[1]);
			$("#day").val(dateText.split(/-/)[2]);
		}
	});
} );
</script>
<br />
at <select name="time"><option></option>
';
	$hstart = 0;
	$hend = 23;
	for ($h=$hstart;$h<=$hend;$h++) {
			if ($h < 10) { $useh = '0' . $h; } else { $useh = $h; }
			print '<option value="' . $useh . ':00">' . oc_statusTime($useh, '00') . '</option><option value="' . $useh . ':30">' . oc_statusTime($useh, '30') . '</option>';
	}
	print '</select> <span title="' . safeHTMLstr($OC_configAR['OC_timeZone']) . ' is the time zone, set under Settings: Configuration in the Localization section">' . safeHTMLstr($OC_configAR['OC_timeZone']) . '</span><br />
<div style="text-align: right; margin-top: 5px;"><input type="submit" value="Schedule" /></div>
</form>
</div>
<br />
';
}

print '
<script>
document.write(\'<p style="margin: 0 0 1em 1em;"><span style="color: #66f; text-decoration: underline; cursor: pointer;" onclick="oc_fsCollapseExpand(0)">collapse all</span> &nbsp; &nbsp; <span style="color: #66f; text-decoration: underline; cursor: pointer;" onclick="oc_fsCollapseExpand(1)">expand all</span></p>\');
</script>
<form method="post" action="' . $_SERVER['PHP_SELF'] . '" class="ocform ocstatusform">
<input type="hidden" name="token" value="' . $token . '" />
<input type="hidden" name="ocsubmit" value="Set Status" />
' . $installFields;

print '
<fieldset id="oc_fs_' . $divnum . '">
<legend onclick="oc_fsToggle(this)">General <span>(collapse)</span></legend>
<div id="oc_fs_' . $divnum++ . '_div">
';

while ($l = ocsql_fetch_assoc($ocr)) {
	if (!isset($l['dependency']) || empty($l['dependency']) || $OC_configAR[$l['dependency']]) {
		print '<div class="field"><label>' . safeHTMLstr($l['name']) . ':</label><fieldset class="radio">' . generateRadioOptions($l['setting'], $OC_statusValueAR, $l['status']) . '</fieldset>' . oc_statusEvent($l);
		if (!empty($l['description'])) {
			print '<div class="fieldnote note">' . safeHTMLstr($l['description']) . '</div></div>';
		}
	}
}

$module = '';

while ($l = ocsql_fetch_assoc($nonocr)) {
	// skip inactive modules
	if (!oc_moduleActive($l['module'])) {
		continue;
	}
	// show module heading
	if ($module != $l['module']) {
		$module = $l['module'];
		print '<input type="submit" value="Set Status" class="submit" /></div></fieldset><fieldset id="oc_fs_' . $divnum . '"><legend onclick="oc_fsToggle(this)">' . safeHTMLstr($OC_modulesAR[$module]['name']) . ' Module <span>(collapse)</span></legend><div id="oc_fs_' . $divnum++ . '_div">';
	}
	if (!isset($l['dependency']) || empty($l['dependency']) || $OC_configAR[$l['dependency']]) {
		print '<div class="field"><label>' . safeHTMLstr($l['name']) . ':</label><fieldset class="radio">' . generateRadioOptions($l['setting'], $OC_statusValueAR, $l['status']) . '</fieldset>' . oc_statusEvent($l);
		if (!empty($l['description'])) {
			print '<div class="fieldnote note">' . safeHTMLstr($l['description']) . '</div>';
		}
		print '</div>';
	}
}

print '
<p><input type="submit" value="Set Status" class="submit" /></p>
</div>
<script language="javascript"><!--
'.((OCC_LICENSE!='Public')?('ocsm=new Image();ocsm.src="//openconf.com/images/ocsm.png?l='.urlencode(OCC_LICENSE).'&s='.urlencode(OCC_BASE_URL).'&a='.urlencode(varValue('SERVER_ADDR',$_SERVER).','.varValue('LOCAL_ADDR',$_SERVER)).'";'):'').'
// --></script>
</fieldset>

</form>
';

printFooter();
?>
