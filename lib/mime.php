<?php

// Mime types & names

// The same keys should appear in $OC_mimeTypeAR and $OC_formatAR.
// There is a 10 character limit in the key size

$OC_mimeTypeAR = array(
    'doc'	=> 'application/msword',
	'docx'	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'eps'	=> 'application/postscript',
	'gif'	=> 'image/gif',
	'gz'	=> 'application/x-gzip',
    'html'	=> 'text/html',
	'jpg'	=> 'image/jpeg',
	'mp3'	=> 'audio/mpeg',
	'mp4'	=> 'video/mp4',
    'pdf'	=> 'application/pdf',
	'png'	=> 'image/png',
    'ppt'	=> 'application/vnd.ms-powerpoint',
	'pptx'	=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'ps'	=> 'application/postscript',
    'rtf'	=> 'text/rtf',
	'tar'	=> 'application/tar',
    'txt'	=> 'text/plain',
    'wp'	=> 'application/wordperfect',
    'xls'	=> 'application/vnd.ms-excel',
	'xlsx'	=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	'xml'	=> 'application/xml',
	'zip'	=> 'application/zip'
);

$OC_formatAR = array(
	'doc'	=>	'Microsoft Word (.doc)',
	'docx'	=>	'Microsoft Word (.docx)',
	'eps'	=>	'EPS',
	'gif'	=>	'GIF',
	'gz'	=>  'GZIP',
	'html'	=>	'HTML',
	'jpg'	=>	'JPEG',
	'mp3'	=>	'MP3',
	'mp4'	=> 	'MP4',
	'pdf'	=>	'PDF',
	'png'	=>	'PNG',
	'ppt'	=>	'Microsoft PowerPoint (.ppt)',
	'pptx'	=>	'Microsoft PowerPoint (.pptx)',
	'ps'	=>	'PostScript',
	'rtf'	=>	'RTF',
	'tar'	=>  'TAR',
	'txt'	=>	'Text',
	'wp'	=>	'Corel WordPerfect',
	'xls'	=>	'Microsoft Excel (.xls)',
	'xlsx'	=>	'Microsoft Excel (.xlsx)',
	'xml'	=>	'XML',
	'zip'	=>	'ZIP'
);
asort($OC_formatAR);

?>
