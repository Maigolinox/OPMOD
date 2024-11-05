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

printHeader("Files Directory",1);

$skipAR = array('.','..','index.html','.htaccess');

$dir = $OC_configAR['OC_paperDir'];
$formatField = '`format`'; // paper table format field
$linkParams = 'c=1';

if (oc_hookSet('chair-list_files-preprocess')) {
	foreach ($GLOBALS['OC_hooksAR']['chair-list_files-preprocess'] as $hook) {
		require_once $hook;
	}
}

// Delete Files form sub?
if (isset($_POST['subaction']) && ($_POST['subaction'] == 'Delete Files')) {
	// Check for valid submission
	if (!validToken('chair')) {
		warn('Invalid submission');
	}

	foreach ($_POST['files'] as $file) {
		if (preg_match("/^(\d+)\.(\w+)$/", $file, $filematch)) {
			if (!oc_deleteFile($dir . $file)) {
				print '<p class="warn">Unable to delete file ' . safeHTMLstr($file) . '</p>';
			}
			issueSQL("UPDATE `" . OCC_TABLE_PAPER . "` SET " . $formatField . "=NULL WHERE `paperid`='" . safeSQLstr($filematch[1]) . "' AND " . $formatField . "='" . safeSQLstr($filematch[2]) . "' LIMIT 1");
		}
	}
}

// Display files
if ($pdh = opendir($dir)) {
	$fAR = array();
	while(($f = readdir($pdh)) !== false) {
		if (is_file($dir.$f) && !in_array($f,$skipAR)) {
			$fAR[$f] = oc_fileMtime($dir.$f);
		}
	}
	closedir($pdh);

	if (oc_hookSet('chair-list_files')) {
		foreach ($GLOBALS['OC_hooksAR']['chair-list_files'] as $hook) {
			require_once $hook;
		}
	}
	
	$type = ((isset($_GET['oc_multifile_type']) && ctype_digit($_GET['oc_multifile_type'])) ? urlencode($_GET['oc_multifile_type']) : 1);
		
	if (count($fAR) > 0) {
		if (isset($_GET['s']) && ($_GET['s'] == 'date')) {
			$dsort = 'Last Updated<br />' . $OC_sortImg;
			$fsort = '<a href="' . $_SERVER['PHP_SELF'] . '?s=file' . (($type > 1) ? ('&oc_multifile_type=' . urlencode($_GET['oc_multifile_type'])) : '') . '">File</a>';
			arsort($fAR,SORT_NUMERIC);
		} else {
			$fsort = 'File<br />' . $OC_sortImg;
			$dsort = '<a href="' . $_SERVER['PHP_SELF'] . '?s=date' . (($type > 1) ? ('&oc_multifile_type=' . urlencode($_GET['oc_multifile_type'])) : '') . '">Last Updated</a>';
			ksort($fAR,SORT_NUMERIC);
		}
		
		print '<div style="text-align: center; width: 150px; margin: 1em auto;"><table border="0" cellspacing="0" cellpadding="0"><tr><td style="padding-right: 20px;"><a href="download.php?t=' . ((isset($_GET['oc_multifile_type']) && ctype_digit($_GET['oc_multifile_type'])) ? urlencode($_GET['oc_multifile_type']) : 1) . '"><img src="../images/documentmulti-sm.gif" width="17" height="20" alt="icon" border="0" /><br />ZIP<br />all&nbsp;files</a></td><td style="padding-left: 20px;"><a href="download.php?t=' . $type . '&acc=1"><img src="../images/documentmulti-sm.gif" width="17" height="20" alt="icon" border="0" /><br />ZIP<br />accepted&nbsp;only</a></td></tr></table></div>';
		
		print '
<form method="post" action="' . $_SERVER['PHP_SELF'] . (($type > 1) ? ('?oc_multifile_type=' . urlencode($_GET['oc_multifile_type'])) : '') . '">
<input type="hidden" name="token" value="' . safeHTMLstr($_SESSION[OCC_SESSION_VAR_NAME]['chairtoken']) . '" />
<input type="hidden" name="type" value="' . safeHTMLstr($type) . '" />
<table border="0" cellspacing="1" cellpadding="4" style="margin: 0 auto;">
<tr class="rowheader"><th class="del">&nbsp;</th><th>' . $fsort . '</th><th>Size</th><th>' . $dsort . '</th></tr>
';
		$row = 1;
		foreach ($fAR as $f => $d) {
			print '<tr class="row' . $row . '"><td class="del"><input type="checkbox" name="files[]" id="file_' . urlencode($f) . '" value="' . urlencode($f) . '" title="file ' . urlencode($f) . '" /></td><td><a href="../review/paper.php?' . $linkParams . '&p=' . urlencode($f) . '" target="paper">' . urlencode($f) . '</a></td><td align="right">' . safeHTMLstr(oc_formatNumber(oc_fileSize($dir.$f))) . '</td><td>' . safeHTMLstr(date("d M Y H:i:s", $d)) . "</td></tr>\n";
			$row = $rowAR[$row];
		}
		print '
<tr><td colspan="6" style="padding:0; margin:0;" valign="top">
  <table border="0" cellpadding="5" cellspacing="0" bgcolor="#ccccff">
  <tr><td><span style="white-space: nowrap;"><input type="submit" name="subaction" value="Delete Files" onclick="return confirm(\'Once deleted, files cannot be recovered.  Proceed?\');" /></span></td></tr>
  </table>
</td></tr>
</table>
</form>
';
	} else { // 0 files
		print '<p style="text-align: center;"><span class="warn">No files found</span></p>';
	}
} else {
	print '<p class="warn">Unable to open files directory</p>';
}

printFooter();

?>
