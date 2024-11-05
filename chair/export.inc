<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

$OC_exportCharLimit = 32767; // max number of characters to include in a XLS(X)/CSV/TXT cell - optional based on user checkbox selection

$OC_exportFormatAR = array(
	'csv'	=> array('name'=>'CSV', 'mime'=>'text/plain'),
	'xls'	=> array('name'=>'Microsoft Excel 2000', 'mime'=>'application/vnd.ms-excel'),
	'xlsx'	=> array('name'=>'Microsoft Excel 2007', 'mime'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
	'txt'	=> array('name'=>'Text (tab-delimited)', 'mime'=>'text/plain'),
	'xml'	=> array('name'=>'XML', 'mime'=>'application/xml')
);

function oc_export_headers($filename, $format) {
	oc_sendNoCacheHeaders();
	header('Content-Type: ' . $GLOBALS['OC_exportFormatAR'][$format]['mime']);
	header('Content-Disposition: attachment; filename="' . $filename . '"');
}

function oc_export_err($e) {
	if (isset($GLOBALS['hdr'])) {
		$hdr = $GLOBALS['hdr'];
	} else {
		$hdr = 'Export Error';
	}
	if (isset($GLOBALS['hdrfn'])) {
		$hdrfn = $GLOBALS['hdrfn'];
	} else {
		$hdrfn = 1;
	}
	err($e, $hdr, $hdrfn);
}

function oc_colID($cols) { // converts a column number to its alpha representation (e.g., 0=A, 25=Z, 26=AA)
	for ($r=""; $cols>=0; ($cols = intval($cols/26)-1)) {
		$r = chr($cols%26 + 0x41) . $r;
	}
	return $r;
}
			
function oc_export(&$scope, &$exportFieldsAR, &$fieldNameAR, &$dbR, &$extraAR=array(), &$fieldAR=array()) {

	if (isset($_POST['format']) && isset($GLOBALS['OC_exportFormatAR'][$_POST['format']])) {
		$format = $_POST['format'];
	} else {
		$format = 'csv';
	}
	
	// set filename
	$fileName = 'openconf';
	if (preg_match("/^\w+$/", $GLOBALS['OC_configAR']['OC_confName'])) {
		$fileName .= '-' . $GLOBALS['OC_configAR']['OC_confName'];
	}
	$fileName .= '-' . oc_strtolower($scope) . '-' . date('YmdHi') . '.' . $format;
	
	// field limit?
	if (isset($_POST['charlimit']) && ($_POST['charlimit'] == 1)) {
		$useCharLimit = $GLOBALS['OC_exportCharLimit'];
	} else {
		$useCharLimit = 0;
	}
	
	// export file
	switch ($format) {
	
		case 'xlsx':	// *****************************************************************
			
			$rows = ocsql_num_rows($dbR) + 1; // +1 = header
			$cols = count($exportFieldsAR);
			$celltotal = $rows * $cols;
			
			if (!class_exists('ZipArchive')) {
				oc_export_err('ZipArchive library missing');
			}
			
			$tempZip = tempnam('/tmp/', 'ocexport') or oc_export_err('could not generate Excel file (1)');
			copy(OCC_LIB_DIR . 'xlsx-template.zip', $tempZip) or oc_export_err('could not generate Excel file (2)');
			$zip = new ZipArchive;
			if (($res = $zip->open($tempZip)) && ($res === true)) {
				// Create/add sheet1.xml
				$sheetFile = tempnam('/tmp/', 'ocexport') or oc_export_err('could not generate Excel file (3)');
				$fp = fopen($sheetFile, 'w') or oc_export_err('could not generate Excel file (4)');
				fputs($fp, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac"><dimension ref="A1:' . oc_colID($cols) . $rows . '"/><sheetViews><sheetView tabSelected="1" workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="15" x14ac:dyDescent="0.25"/><sheetData>');
				$v = 0;
				for ($row=1; $row<=$rows; $row++) {
					fputs($fp, '<row r="' . $row . '" spans="1:' . $cols . '" ' . (($row==1) ? 'customFormat="1" ' : '') . 'x14ac:dyDescent="0.25">');
					for ($col=0; $col<$cols; $col++) {
						fputs($fp, '<c r="' . oc_colID($col) . $row . '" ' . (($row==1) ? 's="1" ' : '') . 't="s"><v>' . $v++ . '</v></c>');
					}
					fputs($fp, '</row>');
				}
				fputs($fp, '</sheetData><pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>');
				fclose($fp);
				if (($res = $zip->addFile($sheetFile, 'xl/worksheets/sheet1.xml')) && ($res === false)) {
					$zip->close();
					unlink($sheetFile);
					unlink($tempZip);
					oc_export_err('could not generate Excel file (5)');
				}
				
				// create/add shareStrings.xml
				$sharedStringsFile = tempnam('/tmp/', 'ocexport') or oc_export_err('could not generate Excel file (6)');
				$fp = fopen($sharedStringsFile, 'w') or oc_export_err('could not generate Excel file (7)');
				fputs($fp, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $celltotal . '" uniqueCount="' . $celltotal . '">');
				foreach ($exportFieldsAR as $f) { // header
					fputs($fp, '<si><t>' . htmlspecialchars(isset($fieldNameAR[$f]) ? $fieldNameAR[$f] : ucwords(preg_replace("/_/", " ", trim($f, "_")))) . '</t></si>');
				}
				while ($l = ocsql_fetch_array($dbR)) { // data
					foreach ($exportFieldsAR as $f) {
						if (preg_match("/^_/", $f)) {  
							if (isset($extraAR[$l['id']][$f])) {
								$val = $extraAR[$l['id']][$f];
							} else {
								$val ='';
							}
						} else {
							$val = oc_getFieldValue($fieldAR, $l, $f, '', $useCharLimit, false);
						}
						fputs($fp, '<si><t>' . htmlspecialchars($val) . '</t></si>');
					}
				}
				fputs($fp, '</sst>');
				fclose($fp);
				if (($res = $zip->addFile($sharedStringsFile, 'xl/sharedStrings.xml')) && ($res === false)) {
					$zip->close();
					unlink($sheetFile);
					unlink($sharedStringsFile);
					unlink($tempZip);
					oc_export_err('could not generate Excel file (8)');
				}
			
				// create/add core.xml
				$coreFile = tempnam('/tmp/', 'ocexport') or die('could not generate Excel file (6)');
				$fp = fopen($coreFile, 'w') or oc_export_err('could not generate Excel file (9)');
				fputs($fp, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>OpenConf</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">' . date("Y-m-d\TH:i:s.00\Z") . '</dcterms:created></cp:coreProperties>');
				fclose($fp);
				if (($res = $zip->addFile($coreFile, 'docProps/core.xml')) && ($res === false)) {
					$zip->close();
					unlink($sheetFile);
					unlink($sharedStringsFile);
					unlink($coreFile);
					unlink($tempZip);
					oc_export_err('could not generate Excel file (10)');
				}
			
			
				$zip->close();
			
				unlink($sheetFile);
				unlink($sharedStringsFile);
				unlink($coreFile);

				oc_export_headers($fileName, $format);
				readfile($tempZip);
				
				unlink($tempZip);
			
			} else {
				oc_export_err('could not generate Excel file (0)');
			}

			break;

		case 'xls':	// *****************************************************************
			
			oc_export_headers($fileName, $format);
			
			print '<html xmlns:o="urn:schemas-microsoft-com:office:office"
	xmlns:x="urn:schemas-microsoft-com:office:excel"
	xmlns="http://www.w3.org/TR/REC-html40">
	
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html>
	<head>
		<meta http-equiv="Content-type" content="text/html;charset=utf-8" />
	<style id="Classeur1_16681_Styles">
	</style>
	
	</head>
	<body>
	
	<div id="Classeur1_16681" align=center x:publishsource="Excel">
	
	<table x:str border=0 cellpadding=0 cellspacing=0 width=100% style="border-collapse: collapse">
	';
			// Title Row
			print '<tr>';
			foreach ($exportFieldsAR as $f) {
				print '<td class=xl2216681 nowrap><strong>' . htmlspecialchars(isset($fieldNameAR[$f]) ? $fieldNameAR[$f] : ucwords(preg_replace("/_/", " ", trim($f, "_")))) . '</strong></td>';
			}
			print '</tr>';
			
			// Iterate through records
			while ($l = ocsql_fetch_array($dbR)) {
				print '<tr>';
				foreach ($exportFieldsAR as $f) {
					if (preg_match("/^_/", $f)) {  
						if (isset($extraAR[$l['id']][$f])) {
							$val = $extraAR[$l['id']][$f];
						} else {
							$val ='';
						}
					} else {
						$val = oc_getFieldValue($fieldAR, $l, $f, '', $useCharLimit, false);
					}
					print '<td class=xl2216681 nowrap>' . htmlspecialchars($val) . '</td>';
				}
				print '</tr>';		
			}
			print '
	</table>
	</div>
	</body>
	</html>
	';
	
			break;
			
		case 'xml':	// *****************************************************************
		
			oc_export_headers($fileName, $format);
			
			print '<?xml version="1.0" encoding="utf-8" ?>
	<!--
	-
	- OpenConf Export: ' . $scope . '
	-
	- Version: ' . $GLOBALS['OC_configAR']['OC_version'] . '
	- Created: ' . date('Y-m-d H:i:s') . '
	-
	-->
	<openconf>		
	';
	
			// Iterate through records
			while ($l = ocsql_fetch_array($dbR)) {
				print "	<entry>\n";
				foreach ($exportFieldsAR as $f) {
					$tag = (isset($fieldNameAR[$f]) ? $fieldNameAR[$f] : ucwords(preg_replace("/_/", " ", trim($f, "_"))));
					$tag = preg_replace("/[^\w]/", "", $tag);
					if (preg_match("/^_/", $f)) {  
						if (isset($extraAR[$l['id']][$f])) {
							$val = $extraAR[$l['id']][$f];
						} else {
							$val ='';
						}
					} else {
						$val = oc_getFieldValue($fieldAR, $l, $f, '', $useCharLimit, false);
					}
					if ($val == '') {
						print "		<$tag />\n";
					} else {
						print "		<$tag>" . htmlspecialchars($val) . "</$tag>\n";
					}
				}
				print "	</entry>\n";
			}
	
			print '</openconf>';
		
			break;
			
		default:	// *****************************************************************
		
			oc_export_headers($fileName, $format);
			
			if ($format == 'txt') {
				$delim = "\t";
			} else {
				$delim = ',';
			}
			
			// Title Row
			$titlerow = '';
			foreach ($exportFieldsAR as $f) {
				$titlerow .= '"' . str_replace("\"", "\"\"", (isset($fieldNameAR[$f]) ? $fieldNameAR[$f] : preg_replace("/_/", " ", trim($f, "_")))) . '"' . $delim;
			}
			print oc_strtoupper(rtrim($titlerow, $delim)) . "\r\n";
			
			// Iterate through records
			while ($l = ocsql_fetch_array($dbR)) {
				$row = '';
				// Add non-author/extra fields to row
				foreach ($exportFieldsAR as $f) {
					if (preg_match("/^_/", $f)) {
						$row .= '"' . (isset($extraAR[$l['id']][$f]) ? str_replace("\"", "\"\"", $extraAR[$l['id']][$f]) : '') . '"' . $delim;
					} else {
						$row .= '"' . str_replace("\"", "\"\"", oc_getFieldValue($fieldAR, $l, $f, '', $useCharLimit, false)) . '"' . $delim;
					}
				}
			
				$row = preg_replace("/\015(\012)?/", "\012", $row);
				print rtrim($row, $delim) . "\015\012";
			}
	
			break;
			
	}
	
	// exit so not additional code is executed
	exit;
} // oc_export f'n
