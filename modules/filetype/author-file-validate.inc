<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Skip if chair?
if ( ! isset($chair) || ! $chair || ! $OC_configAR['MOD_FILETYPE_chairoverride']) {
	$MOD_FILETYPE_file = $_FILES['file'.(isset($uploadTypeID) ? ('_' . $uploadTypeID) : '')]['tmp_name']; // uploaded file loc
	$MOD_FILETYPE_name = $_FILES['file'.(isset($uploadTypeID) ? ('_' . $uploadTypeID) : '')]['name']; // uploaded file original name
	// uploaded file original name extension
	if (($MOD_FILETYPE_extbegin = strrpos($MOD_FILETYPE_name, '.')) !== FALSE) {
		$MOD_FILETYPE_ext = substr($MOD_FILETYPE_name, ($MOD_FILETYPE_extbegin + 1));
	} else {
		$MOD_FILETYPE_ext = '';
	}
	$MOD_FILETYPE_format = $_POST['format'.(isset($uploadTypeID) ? ('_' . $uploadTypeID) : '')]; // user selected format
	
	// Valid extensions for each format w/o automated detection
	$MOD_FILETYPE_validExtAR = array(
		'txt'	=> array('txt', 'text'),
		'jpg'	=> array('jpg', 'jpeg'),
		'mpg'	=> array('mpg', 'mpeg', 'mp4')
	);
	
	// Check using Fileinfo
	if (function_exists('finfo_open') 
		&& isset($GLOBALS['OC_mimeTypeAR'][$MOD_FILETYPE_format])
		&& ($mod_filetype_finfo = finfo_open(FILEINFO_MIME_TYPE)) 
		&& (finfo_file($mod_filetype_finfo, $MOD_FILETYPE_file) == $GLOBALS['OC_mimeTypeAR'][$MOD_FILETYPE_format])
	) {
		$MOD_FILETYPE_valid = true;
	} else {  // Check using signature
		$MOD_FILETYPE_valid = false;
		
		if ($MOD_FILETYPE_fp = fopen($MOD_FILETYPE_file, 'r')) {
			$MOD_FILETYPE_bytes = fread($MOD_FILETYPE_fp, 512);
			switch ($MOD_FILETYPE_format) {
				case 'doc':
				case 'ppt':
				case 'xls':
					if (mod_filetype_checksig($MOD_FILETYPE_bytes, "\320\317\021\340\241\261\032\341")) {
						$MOD_FILETYPE_bytes = fread($MOD_FILETYPE_fp, 512);
						if (($MOD_FILETYPE_format == 'doc') && mod_filetype_checksig($MOD_FILETYPE_bytes, "\354\245\301\0")) {
							$MOD_FILETYPE_valid = true;
						} 
						elseif (($MOD_FILETYPE_format == 'doc') && $OC_configAR['MOD_FILETYPE_allow_rtfforword'] && mod_filetype_checksig($MOD_FILETYPE_bytes, "{\\rtf")) {
							$MOD_FILETYPE_valid = true;
						} 
						elseif (($MOD_FILETYPE_format == 'ppt') && preg_match("/^ppt/", $MOD_FILETYPE_ext)) {  # mod_filetype_checksig($MOD_FILETYPE_bytes, "")) {
							$MOD_FILETYPE_valid = true;
						} elseif (($MOD_FILETYPE_format == 'xls') && preg_match("/^xls/", $MOD_FILETYPE_ext)) {  # && mod_filetype_checksig($MOD_FILETYPE_bytes, "")) {
							$MOD_FILETYPE_valid = true;
						} 
					}
					break;
				case 'docx':
				case 'pptx':
				case 'xlsx':
					if (
						mod_filetype_checksig($MOD_FILETYPE_bytes, "PK\003\004\024\0\006\0")
					&&
						preg_match("/^" . substr($MOD_FILETYPE_format, 0, 3) . "/", $MOD_FILETYPE_ext)
					) {
						$MOD_FILETYPE_valid = true;
					}
					break;
				case 'pdf':
					if (mod_filetype_checksig($MOD_FILETYPE_bytes, '%PDF')) {
						$MOD_FILETYPE_valid = true;
					}
					break;
				case 'ps':
					if (mod_filetype_checksig($MOD_FILETYPE_bytes, '%!PS-Adobe')) {
						$MOD_FILETYPE_valid = true;
					}
					break;
				case 'rtf':
					if (mod_filetype_checksig($MOD_FILETYPE_bytes, '{\\rtf')) {
						$MOD_FILETYPE_valid = true;
					}
					break;
				case 'wp':
					if (mod_filetype_checksig($MOD_FILETYPE_bytes, '\377WPC')) {
						$MOD_FILETYPE_valid = true;
					}
					break;
				case 'xml':
					if (mod_filetype_checksig($MOD_FILETYPE_bytes, '<?xml')) {
						$MOD_FILETYPE_valid = true;
					}
					break;
				case 'html':
					if (mod_filetype_checksig($MOD_FILETYPE_bytes, '<html', 0, 0)
						|| mod_filetype_checksig($MOD_FILETYPE_bytes, '<!DOCTYPE HTML', 0, 0)
						|| mod_filetype_checksig($MOD_FILETYPE_bytes, '<?xml version="1.0" encoding="', 0, 0)
					) {
						$MOD_FILETYPE_valid = true;
					}
					break;
				default: // check file extension - yes, we've wasted the fopen call :(
					if (($MOD_FILETYPE_ext == $MOD_FILETYPE_format) 
						|| (isset($MOD_FILETYPE_validExtAR[oc_strtolower($MOD_FILETYPE_format)]) && in_array(oc_strtolower($MOD_FILETYPE_ext), $MOD_FILETYPE_validExtAR[oc_strtolower($MOD_FILETYPE_format)]))
					) { 
						$MOD_FILETYPE_valid = true;
					}
					break;
			}
			fclose($MOD_FILETYPE_fp);
		} else {
			err(oc_('Unable to open file to check format'));
		}
	}

	if ($mod_filetype_finfo) {
		finfo_close($mod_filetype_finfo);
	}
						
	// Warn on invalid type
	if (! $MOD_FILETYPE_valid) {
		if (preg_match("/submit/", $_SERVER['PHP_SELF'])) { // submission form upload
			$fileUploaded = false;
			$err .= '<li>' . oc_('File uploaded does not match format selected') . '</li>';
		} elseif (isset($uploadTypeID)) { // multi-files upload
			$uploadErrAR[] = oc_('File format invalid');
		} else { // single-file upload
			warn(oc_('File uploaded does not match format selected.  Please re-check and try again.  If you are unable to upload the file, contact the Chair.'));
		}
	}
} // skip if chair
