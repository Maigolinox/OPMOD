<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Following variables must be pre-defined:
//    $hdr & $hdrfn = page title and user level
//    $dir = directory of files to be zip'd
//    $savePath = directory to use for files in ZIP archive
//    $formatDBFldName = DB field name with file format
//    $urlBase = URL base of calling script
//    $q = SQL for retrieving file IDs and format, ordered by paperid
//    $zipFilePrefix = ZIP filenane prefix to use
//    $_GET['t'] = file type

// Zip extension installed?
if ( ! class_exists('ZipArchive') ) {
	warn('PHP configuration missing ZipArchive', $hdr, $hdrfn);
}

// Validate file type ID
if ( !isset($_GET['t']) || !preg_match("/^\d+$/", $_GET['t'])) {
	warn(oc_('Invalid file type'), $hdr, $hdrfn);
}

// Define variables
$zipFileCount = 20; // number of files at which to reload page to keep number of open files to a minimum
$zipDir = $OC_configAR['OC_dataDir'] . 'zip/'; // directory in which to create ZIP file
$zipFileName = $zipDir . $zipFilePrefix . '-' . $_GET['t'] . '.zip'; // ZIP file name to use
$memoryLimit = trim(ini_get('memory_limit')); 
if (preg_match("/^(\d+)([a-z])[a-z]*$/i", $memoryLimit, $matches)) { // catch case where mem limit uses shorthand (e.g., 128M)
	$memUnit = strtolower($matches[2]);
	$memoryLimit = $matches[1];
	switch ($memUnit) {
		case 'g':
			$memoryLimit *= 1024; // no break, fall through
		case 'm':
			$memoryLimit *= 1024; // no break, fall through
		case 'k':
			$memoryLimit *= 1024;
			break;
	}
}
$memoryLimit = $memoryLimit * 95 / 100; // set memory limit to 95% of capacity

// Display common header
function displayZipNotice($min, $max) {
    print '<p>' . oc_('We are generating a ZIP archive of the files.  Please do not close this page.  Once the ZIP archive is created, you will be prompted to save the file.') . '</p>';
    print '<p style="text-align: center;"><progress value="' . $min . '" max="' . $max . '" /></p>';
}

// Action to execute
if (!isset($_GET['daction']) || empty($_GET['daction'])) {
	// redirect when starting out in case of double-click and so user knows what is going on
    printHeader($hdr, $hdrfn);
    displayZipNotice(10, 100);
print '
<noscript>
<p style="font-weight: bold">' . sprintf(oc_('<a href="%s">Click here</a> to continue processing download.'), $urlBase . 't=' . urlencode($_GET['t']) . '&daction=start') . '</p>
</noscript>
';
    printFooter();
    flush();
    print '
<script language="javascript" type="text/javascript">
<!--
window.location.replace("' . $urlBase . 't=' . safeHTMLstr($_GET['t']) . '&daction=start");
// -->
</script>
';
	exit;
} elseif ($_GET['daction'] == 'download') {
	// send out archive
	header("Content-Type: application/zip");
	header("Content-Disposition: attachment; filename=files-" . $_GET['t'] . ".zip");
	header("Content-Length: " . filesize($zipFileName));
	header("Cache-control: private");
	header("Pragma: public"); // IE issue work around
	ob_end_flush();
	readfile($zipFileName) or err('Unable to read ZIP file', $hdr, $hdrfn);
	// delete archive
	unlink($zipFileName);
	// clean up old ZIP files still around
	if ($dh = opendir($zipDir)) {
		$fourHoursAgo = time() - (60 * 60 * 4);
		while (($file = readdir($dh)) !== false) {
			if (preg_match("/\.zip$/", $file) && ($mtime = filemtime($zipDir . $file)) && ($mtime < $fourHoursAgo)) {
				unlink($zipDir . $file);
			}
		}
	}
	exit;
} elseif ($_GET['daction'] == 'complete') {
	printHeader($hdr, $hdrfn);
	if (is_file($zipFileName) && (filesize($zipFileName) > 0)) {
		displayZipNotice(1, 1);
		print '<p>' . oc_('The ZIP archive has been created, and the download should begin shortly.') . '</p>';
		if (isset($_GET['skipped']) && ($_GET['skipped'] == 1)) {
			print '<p class="warn">' . oc_('One or more files were not found or too large to include in the ZIP archive.') . '</p>';
		}
		print '
<noscript>
<p>' . sprintf(oc_('If the download does not begin automatically, <a href="%s">click here to download</a>.'), $urlBase . 't=' . urlencode($_GET['t']) . '&daction=download') . '</p>
</noscript>
';
		printFooter();
		flush();
		print '
<script language="javascript" type="text/javascript">
<!--
window.location.replace("' . $urlBase . 't=' . safeHTMLstr($_GET['t']) . '&daction=download");
// -->
</script>
';
	} else {
		warn('Failed creating ZIP archive (4)');
	}
	exit;
} else { // daction == start
	$skippedFiles = false; // track if a file was skipped

	// let user know what's going on
	printHeader($hdr, $hdrfn);
	
	// Retrieve assigned submissions
	$r = ocsql_query($q) or err('Unable to retrieve file data');
	$fileAR = array();
	while ($l = ocsql_fetch_assoc($r)) {
		if (!empty($l[$formatDBFldName])) {
			if (oc_isFile($dir . $l['paperid'] . '.' . $l[$formatDBFldName])) {
				$fileAR[$l['paperid']] = $l['paperid'] . '.' . $l[$formatDBFldName];
			} else {
				$skippedFiles = true;
			}
		}
	}
	ocsql_free_result($r); // free up memory as ZIP could use up a bit
	$fileARcount = count($fileAR);
	if ($fileARcount == 0) {
		warn(oc_('There are no files available'));
	}
	
	// Open ZIP archive
	$zip = new ZipArchive();
	$res = $zip->open($zipFileName, (($_GET['daction']=='start') ? (ZipArchive::OVERWRITE | ZipArchive::CREATE) : ZipArchive::CREATE)) or err('Failed creating ZIP archive (1)');
	if (($zip->numFiles > 0) && preg_match("/^\d+$/", $zip->getArchiveComment())) {
		$stoppedAt = $zip->getArchiveComment();
	} else {
		$stoppedAt = 0;
	}
	// display progress
	displayZipNotice((($zip->numFiles > 0) ? $zip->numFiles : ceil($fileARcount * 0.1)), $fileARcount);
	// add files
	$files = 0; // count of files added per zip->open
	if (isset($_GET['skipped']) && ($_GET['skipped'] == 1)) { // track whether we had to skip files
		$skippedFiles = true;
	}
	foreach ($fileAR as $pid => $fname) {
		// skip previously processed files
		if ($pid <= $stoppedAt) { continue; }

		// reload page in case limit(s) being reached
		if (
			oc_checkTimeout()												// processing time reaching timeout
			|| ($files++ >= $zipFileCount) 									// too many files open
			|| ( ($fileSize = oc_fileSize($dir . $fname)) === false )		// err getting file size
			|| ((memory_get_usage() + ( 2 * $fileSize )) > $memoryLimit)	// memory usage will be over limit if file added - 2x factor used (w/memoryLimit @ 95%)
		) {
			if ($files == 1) {
				$zip->setArchiveComment($pid); 		// skip file because it's too large
				$skippedFiles = true;
			} else {
				$zip->setArchiveComment($pid - 1);	// record 1 less than current file ID so it starts w/current file again
			}
			$zip->close();
			print '
	<noscript>
	<p style="font-weight: bold">' . sprintf(oc_('<a href="%s">Click here</a> to continue processing download.'), $urlBase . 't=' . urlencode($_GET['t']) . '&daction=continue' . ($skippedFiles ? '&skipped=1' : '') ) . '</p>
	</noscript>
	';
			printFooter();
			flush();
			print '
	<script language="javascript" type="text/javascript">
	<!--
	window.location.replace("' . $urlBase . 't=' . safeHTMLstr($_GET['t']) . '&daction=continue' . ($skippedFiles ? '&skipped=1' : '') . '");
	// -->
	</script>
	';
			exit;
		}

		// add file
		if (oc_hookSet('zip_add_file')) {
			call_user_func($GLOBALS['OC_hooksAR']['zip_add_file'][0], $zip, $dir . $fname, $savePath . $fname);
		} elseif (is_readable($dir . $fname)) {
			if ( ! $zip->addFile($dir . $fname, $savePath . $fname) ) {
				$zcontent = ocGetFile($dir . $fname) or err('Failed creating ZIP archive (3a)');
				$zip->addFromString($savePath . $fname, $zcontent) or err('Failed creating ZIP archive (3b)');
			}
		}
	}
	$zip->close();
	
	// done - redirect user
	print '
	<noscript>
	<p style="font-weight: bold">' . sprintf(oc_('<a href="%s">Click here</a> to continue processing download.'), $urlBase . 't=' . urlencode($_GET['t']) . '&daction=complete' . ($skippedFiles ? '&skipped=1' : '') ) . '</p>
	</noscript>
	';
	
	printFooter();
	flush();
	
	print '
	<script language="javascript" type="text/javascript">
	<!--
	window.location.replace("' . $urlBase . 't=' . safeHTMLstr($_GET['t']) . '&daction=complete' . ($skippedFiles ? '&skipped=1' : '') . '");
	// -->
	</script>
	';
}
	
exit;

?>
