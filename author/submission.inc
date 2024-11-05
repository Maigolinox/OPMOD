<?php
// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

if (!isset($oc_authorNum)) {
	$oc_authorNum = 1;
}

// Force author 1 as contact
if ( 
	( ($GLOBALS['OC_configAR']['OC_authorsMinDisplay'] == 1) && ($GLOBALS['OC_configAR']['OC_authorsMax'] == 1) )
	||
	($GLOBALS['OC_configAR']['OC_authorOneContact'] == 1)
) {
	$author1contact = true;
} else {
	$author1contact = false;
}

// Get topics
$topq = "SELECT * FROM `" . OCC_TABLE_TOPIC . "`";
if ($OC_configAR['OC_topicDisplayAlpha']) { 
	$topq .= " ORDER BY `topicname`";
}
$topr = ocsql_query($topq) or err('unable to retrieve topics');
if (($tnum = ocsql_num_rows($topr)) < 1) {
	warn(oc_('We are still waiting for the list of topics to be finalized before opening up for submissions. Please check back later.'));
} else {
	$topicAR = array();
	while ($topl = ocsql_fetch_assoc($topr)) {
		if ($topl['topicname'] == 'N/A') { continue; }
		$topicAR[$topl['topicid']] = $topl['topicname'];
	}
}

// Get authors
$sfAuthorAR = array();
for ($i=1; $i<=$GLOBALS['oc_authorNum']; $i++) {
	$sfAuthorAR[$i] = oc_('Author') . ' ' . $i;  // oc_() only around Author because of JS in include-forms
}

// See include-forms.inc for syntax format

$OC_submissionFieldAR = array();
$OC_submissionFieldSetAR = array();

// Hooks
if (oc_hookSet('author-submission-preinc')) {
	foreach ($GLOBALS['OC_hooksAR']['author-submission-preinc'] as $v) {
		require_once $v;
	}
}

if (!isset($mod_oc_customforms_customSubForm) || (!$mod_oc_customforms_customSubForm)) { // skip if we have a custom form

	// Consent
	if (
		(OCC_LICENSE != 'Public') 
		||
		((OCC_LICENSE == 'Public') && ($OC_configAR['OC_privacy_display'] > 0))
	) {
		$OC_submissionFieldAR['consent'] = array(
			'name' => oc_('Consent'),
			'short' => oc_('Consent'),
			'note' => '',
			'type' => 'checkbox',
			'reviewer' => false,
			'advocate' => false,
			'usekey' => false,
			'required' => true,
			'delimiter' => '',
			'values' => array(oc_('I consent to the collection and use of my personal information, including receiving emails, consistent with the Privacy Policy linked above.') . ' ' . oc_('I have also obtained the consent of all other individuals whose information I provide.'))
		);
	
		$OC_submissionFieldSetAR['fs_consent'] = array(
			'fieldset' => oc_('Consent'),
			'note' => '',
			'fields' => array('consent')
		);
	}
	
	// General Info
	$OC_submissionFieldAR['title'] = array(
		'name' => oc_('Submission Title'),
		'short' => oc_('Title'),
		'note' => '',
		'type' => 'text',
		'reviewer' => true,
		'advocate' => true,
		'width' => 80,
		'required' => true	// always required
	);
	
	$OC_submissionFieldAR['type'] = array(
		'name' => oc_('Submission Type'),
		'short' => oc_('Submission Type'),
		'note' => '',
		'type' => 'radio',
		'reviewer' => true,
		'advocate' => true,
		'closeedit' => false,
		'usekey' => false,
		'required' => true,
		'display' => 'sameline',
		'valuetype' => 'custom',
		'values' => array()
	);
	if (isset($OC_configAR['OC_subtypes']) && !empty($OC_configAR['OC_subtypes'])) {
		$OC_submissionFieldAR['type']['values'] = explode(',', $OC_configAR['OC_subtypes']);
	}
	
	$OC_submissionFieldAR['student'] = array(
		'name' => oc_('Student'),
		'short' => oc_('Student'),
		'note' => '',
		'type' => 'radio',
		'reviewer' => true,
		'advocate' => true,
		'closeedit' => false,
		'usekey' => true,
		'required' => true,
		'delimiter' => ' &nbsp; ',
		'values' => array('T' => oc_('Yes'), 'F' => oc_('No'))
	);
	
	$OC_submissionFieldSetAR['fs_general'] = array(
		'fieldset' => oc_('General Information'),
		'note' => '',
		'fields' => array('title', 'type', 'student')
	);
	
	// Authors
	$OC_submissionFieldAR['orcid'] = array(
		'name' => oc_('ORCID'),
		'short' => oc_('ORCID'),
		'note' => '',
		'type' => 'text',
		'width' => 30,
		'maxchars' => 30,
		'required' => false
	);
	
	$OC_submissionFieldAR['honorific'] = array(
		'name' => oc_('Honorific'),
		'short' => oc_('Honorific'),
		'note' => '',
		'type' => 'text',
		'reviewer' => true,
		'advocate' => true,
		'required' => false
	);
	
	$OC_submissionFieldAR['name_first'] = array(
		'name' => oc_('First/Given Name'),
		'short' => oc_('First Name'),
		'note' => '',
		'type' => 'text',
		'maxchars' => 60,
		'reviewer' => true,
		'advocate' => true,
		'required' => false
	);
	
	$OC_submissionFieldAR['name_last'] = array(
		'name' => oc_('Last/Family Name'),
		'short' => oc_('Last Name'),
		'note' => '',
		'type' => 'text',
		'maxchars' => 40,
		'reviewer' => true,
		'advocate' => true,
		'required' => true	// always required
	);
	
	$OC_submissionFieldAR['suffix'] = array(
		'name' => oc_('Suffix'),
		'short' => oc_('Suffix'),
		'note' => '',
		'type' => 'text',
		'maxchars' => 60,
		'reviewer' => true,
		'advocate' => true,
		'required' => false
	);
	
	$OC_submissionFieldAR['position_title'] = array(
		'name' => oc_('Position/Title'),
		'short' => oc_('Position'),
		'note' => '',
		'type' => 'text',
		'required' => false
	);
	
	$OC_submissionFieldAR['organization'] = array(
		'name' => oc_('Organization'),
		'short' => oc_('Organization'),
		'note' => '',
		'type' => 'text',
		'maxchars' => 150, // limitation as a result of utf8mb4 keys
		'reviewer' => true,
		'advocate' => true,
		'required' => false
	);
	
	$OC_submissionFieldAR['department'] = array(
		'name' => oc_('Department/Division'),
		'short' => oc_('Department/Division'),
		'note' => '',
		'type' => 'text',
		'required' => false
	);
	
	$OC_submissionFieldAR['address'] = array(
		'name' => oc_('Address'),
		'short' => oc_('Address'),
		'note' => '',
		'type' => 'text',
		'required' => false
	);
	
	$OC_submissionFieldAR['address2'] = array(
		'name' => oc_('Address 2'),
		'short' => oc_('Address 2'),
		'note' => '',
		'type' => 'text',
		'required' => false
	);
	
	$OC_submissionFieldAR['city'] = array(
		'name' => oc_('City'),
		'short' => oc_('City'),
		'note' => '',
		'type' => 'text',
		'required' => false
	);
	
	$OC_submissionFieldAR['spc'] = array(
		'name' => oc_('State/Province'),
		'short' => oc_('State/Province'),
		'note' => '',
		'type' => 'text',
		'required' => false
	);
	
	$OC_submissionFieldAR['postcode'] = array(
		'name' => oc_('Postcode/Zip'),
		'short' => oc_('Postcode/Zip'),
		'note' => '',
		'type' => 'text',
		'required' => false
	);
	
	$OC_submissionFieldAR['country'] = array(
		'name' => oc_('Country'),
		'short' => oc_('Country'),
		'note' => '',
		'type' => 'dropdown',
		'blank' => true,
		'required' => false,
		'usekey' => true,
		'reviewer' => true,
		'advocate' => true,
		'valuetype' => 'country'
	);
	
	$OC_submissionFieldAR['email'] = array(
		'name' => oc_('Email'),
		'short' => oc_('Email'),
		'note' => '',
		'type' => 'email',
		'required' => false	// required for contact author automatically
	
	);
	
	$OC_submissionFieldAR['phone'] = array(
		'name' => oc_('Telephone'),
		'short' => oc_('Telephone'),
		'note' => '',
		'type' => 'text',
		'required' => false
	);
	
	$OC_submissionFieldAR['url'] = array(
		'name' => oc_('Web Site'),
		'short' => oc_('Web Site'),
		'note' => '',
		'type' => 'text',
		'required' => false,
		'placeholder' => 'https://'
	);
	
	$OC_submissionFieldAR['facebook'] = array(
		'name' => oc_('Facebook Page'),
		'short' => oc_('Facebook'),
		'note' => '',
		'type' => 'text',
		'required' => false,
		'placeholder' => 'https://'
	);
	
	$OC_submissionFieldAR['twitter'] = array(
		'name' => oc_('Twitter Page'),
		'short' => oc_('Twitter'),
		'note' => '',
		'type' => 'text',
		'required' => false,
		'placeholder' => 'https://'
	);
	
	$OC_submissionFieldAR['linkedin'] = array(
		'name' => oc_('LinkedIn Page'),
		'short' => oc_('LinkedIn'),
		'note' => '',
		'type' => 'text',
		'required' => false,
		'placeholder' => 'https://'
	);
	
	$OC_submissionFieldAR['photo'] = array(
		'name' => oc_('Photo Link'),
		'short' => oc_('Photo'),
		'note' => '',
		'type' => 'text',
		'required' => false,
		'placeholder' => 'https://'
	);
	
	$OC_submissionFieldAR['presenter'] = array(
		'name' => oc_('Presenter'),
		'short' => oc_('Presenter'),
		'note' => '',
		'type' => 'radio',
		'usekey' => true,
		'required' => false,
		'display' => 'sameline',
		'values' => array('T' => oc_('Yes'), 'F' => oc_('No'))
	);
	
	$OC_submissionFieldAR['biography'] = array(
		'name' => oc_('Biography'),
		'short' => oc_('Biography'),
		'note' => '',
		'type' => 'textarea',
		'required' => false
	);
	
	$OC_submissionFieldAR['role'] = array(
		'name' => oc_('Contributor Role'),
		'short' => oc_('Contributor Role'),
		'note' => '',
		'type' => 'picklist',
		'usekey' => true,
		'required' => false,
		'values' => array(
			1 => oc_('Conceptualization'),
			2 => oc_('Data curation'),
			3 => oc_('Formal Analysis'),
			4 => oc_('Funding acquisition'),
			5 => oc_('Investigation'),
			6 => oc_('Methodology'),
			7 => oc_('Project administration'),
			8 => oc_('Resources'),
			9 => oc_('Software'),
			10 => oc_('Supervision'),
			11 => oc_('Validation'),
			12 => oc_('Visualization'),
			13 => oc_('Writing - original draft'),
			14 => oc_('Writing - review & editing')
		)
	);
	
	$OC_submissionFieldSetAR['fs_authors'] = array(
		'fieldset' => oc_('Author(s)'),
		'note' => '',
		'fields' => array('orcid', 'honorific', 'name_first', 'name_last', 'suffix', 'position_title', 'organization', 'department', 'address', 'address2', 'city', 'spc', 'postcode', 'country', 'email', 'phone', 'url', 'facebook', 'twitter', 'linkedin', 'photo', 'presenter', 'biography', 'role')
	);
	
	// Contact Author
	$OC_submissionFieldAR['contactid'] = array(
		'name' => oc_('Contact Author'),
		'short' => oc_('Contact Author'),
		'note' => oc_('Author who will serve as the point of contact for correspondence about the submission.'),
		'type' => 'dropdown',
		'required' => true,	// always required
		'usekey' => true,
		'valuetype' => 'author'
	);
	
	$OC_submissionFieldAR['altcontact'] = array(
		'name' => oc_('Alternate Contact'),
		'short' => oc_('Alternate Contact'),
		'note' => oc_('Alternate contact information, such as personal email address or telephone number; used only if unable to contact using above email address.'),
		'type' => 'text',
		'required' => false
	);
	
	$OC_submissionFieldSetAR['fs_contactauthor'] = array(
		'fieldset' => oc_('Contact Author'),
		'note' => '',
		'fields' => array('contactid', 'altcontact')
	);
	
	// Topics
	$OC_submissionFieldAR['topics'] = array(
		'name' => oc_('Topic Areas'),
		'short' => oc_('Topic(s)'),
		'note' => '',
		'type' => 'checkbox',
		'usekey' => true,
		'display' => 'newline',
		'required' => true,	// always required
		'valuetype' => 'topic'
	);
	if ($OC_configAR['OC_multipleSubmissionTopics'] != 1) { // (!1 = limited to 1)
		$OC_submissionFieldAR['topics']['maxselections'] = 1;
	}
	
	$OC_submissionFieldSetAR['fs_topics'] = array(
		'fieldset' => oc_('Topic Areas'),
		'note' => oc_('To help match submissions to reviewers and sessions, please select the area(s) most applicable to your submission'),
		'fields' => array('topics')
	);
	
	// Content
	$OC_submissionFieldAR['keywords'] = array(
		'name' => oc_('Keywords'),
		'short' => oc_('Keywords'),
		'note' => '',
		'type' => 'text',
		'required' => false
	);
	
	$OC_submissionFieldAR['abstract'] = array(
		'name' => oc_('Abstract'),
		'short' => oc_('Abstract'),
		'note' => '',
		'reviewer' => true,
		'advocate' => true,
		'type' => 'textarea',
		'height' => 10,
		'required' => false
	);
	
	$OC_submissionFieldSetAR['fs_content'] = array(
		'fieldset' => oc_('Content'),
		'note' => '',
		'fields' => array('keywords', 'abstract')
	);
	
	if ( preg_match("/submit/", $_SERVER['PHP_SELF']) || (isset($mod_oc_customforms_edit) && $mod_oc_customforms_edit) || (isset($mod_oc_formfields_edit) && $mod_oc_formfields_edit) ) {	 // only display file field if submission (or forms editing via module)
		$OC_submissionFieldAR['file'] = array(
			'name' => oc_('File'),
			'short' => oc_('File'),
			'note' => (empty($GLOBALS['fileNotice']) ? '' : $GLOBALS['fileNotice'] . '<br />') . sprintf(oc_('File size limit is %s.'), (empty($OC_configAR['OC_fileLimit']) ? $GLOBALS['OC_maxFileSize'] : toMB($OC_configAR['OC_fileLimit'] . 'M'))),
			'type' => 'file',
			'closeedit' => false,
			'required' => true
		);
		$OC_submissionFieldSetAR['fs_content']['fields'][] = 'file';
	} 
	
	// Password
	$OC_submissionFieldAR['password1'] = array(
		'name' => oc_('Password'),
		'short' => oc_('Password'),
		'note' => '',
		'type' => 'password',
		'required' => true	// always required
	);
	
	$OC_submissionFieldAR['password2'] = array(
		'name' => oc_('Re-enter Password'),
		'short' => oc_('Confirm'),
		'note' => '',
		'type' => 'password',
		'required' => true	// always required
	);
	
	$OC_submissionFieldSetAR['fs_passwords'] = array(
		'fieldset' => oc_('Password'),
		'note' => oc_('Please enter a password you will remember. The submission ID, which you will receive via email upon submission of this form, along with this password will allow you to make future changes to this submission.'),
		'fields' => array('password1', 'password2')
	);
	
	// Comments
	$OC_submissionFieldAR['comments'] = array(
		'name' => oc_('Optional Comments'),
		'short' => oc_('Comments'),
		'note' => '',
		'type' => 'textarea',
		'required' => false
	);
	
	$OC_submissionFieldSetAR['fs_comments'] = array(
		'fieldset' => oc_('Comments'),
		'note' => '',
		'fields' => array('comments')
	);
	
	// Unset fields that should not be displayed on form
	if (!empty($GLOBALS['OC_configAR']['OC_hideSubFields'])) {
		$hSF = explode(',', $GLOBALS['OC_configAR']['OC_hideSubFields']);
		foreach ($hSF as $hs_f) {
			list($a_fs, $a_f) = explode(':', $hs_f);
			unset($OC_submissionFieldAR[$a_f]);
			if (isset($OC_submissionFieldSetAR[$a_fs]['fields']) && in_array($a_f, $OC_submissionFieldSetAR[$a_fs]['fields'])) {
				$OC_submissionFieldSetAR[$a_fs]['fields'] = array_values(array_diff($OC_submissionFieldSetAR[$a_fs]['fields'], array($a_f)));
			}
		}
	}

} // if ! custom form

// Hooks
if (oc_hookSet('author-submission-inc')) {
	foreach ($GLOBALS['OC_hooksAR']['author-submission-inc'] as $v) {
		require_once $v;
	}
}

// Make updates if form not being edited by module
if (
	(! isset($mod_oc_customforms_edit) || ! $mod_oc_customforms_edit)
	&&
	(! isset($mod_oc_formfields_edit) || ! $mod_oc_formfields_edit)
){
	$GLOBALS['updateAuthorFieldsAR'] = array();
	foreach ($OC_submissionFieldAR as $sfk => $sf) {
		// remove field if either: 
		// - designated for new submissions only if sub being edited, 
		// - designated for Chair only and author viewing/editing
		if (
			(isset($sf['newsubonly']) && $sf['newsubonly'] && isset($GLOBALS['OC_subEdit']) && $GLOBALS['OC_subEdit']) 
			||
			(
				(isset($sf['chair']) && $sf['chair'])
				&&
				(
					(isset($OC_subNew) && $OC_subNew)
					||
					(isset($OC_subEdit) && $OC_subEdit && (!isset($chair) || !$chair))
					||
					(isset($OC_subShow) && $OC_subShow)
				)
			)
		) {
			unset($OC_submissionFieldAR[$sfk]);
			foreach ($OC_submissionFieldSetAR as $fsid => $fsar) {
				if (in_array($sfk, $fsar['fields'])) {
					$OC_submissionFieldSetAR[$fsid]['fields'] = array_diff($OC_submissionFieldSetAR[$fsid]['fields'], array($sfk));
					continue; // field should only be in one fieldset
				}
			}
		} else { // update values & valuetypes
			if (isset($sf['valuetype']) && !empty($sf['valuetype']) && ($sf['valuetype'] != 'custom')) {
				switch($sf['valuetype']) {
					case 'author':
						$OC_submissionFieldAR[$sfk]['values'] = $sfAuthorAR;
						$GLOBALS['updateAuthorFieldsAR'][] = $sfk;
						break;
						
					case 'country':
						require_once OCC_COUNTRY_FILE;
						$OC_submissionFieldAR[$sfk]['values'] = $GLOBALS['OC_countryAR'];
						break;
						
					case 'topic':
						$OC_submissionFieldAR[$sfk]['values'] = $topicAR;
						break;
						
					default: // lib file
						require_once OCC_LIB_DIR . $sf['valuetype'] . '.inc';
						$OC_submissionFieldAR[$sfk]['values'] = $GLOBALS['OC_' . $sf['valuetype'] . 'AR'];
						break;
				}
			} elseif (
				isset($sf['values']) 
				&& is_array($sf['values']) 
				&& ( !isset($mod_oc_customforms_customSubForm) || ! $mod_oc_customforms_customSubForm ) 
			) {
				// Translate values
				if (!isset($sf['usekey']) || $sf['usekey']) {
					foreach ($sf['values'] as $vk => $vv) {
						$OC_submissionFieldAR[$sfk]['values'][$vk] = oc_($vv);
					}
				} else { // change to usekey in support of field values translation (e.g., consent, sub type)
					$OC_submissionFieldAR[$sfk]['usekey'] = true;
					$valuesAR = array();
					foreach ($sf['values'] as $vv) {
						$valuesAR[$vv] = oc_($vv);
					}
					$OC_submissionFieldAR[$sfk]['values'] = $valuesAR;
				}
			}
		}
	}
	
	// Update Topics to radio if required and max selections = 1
	if (isset($OC_submissionFieldAR['topics']['required']) && $OC_submissionFieldAR['topics']['required'] && isset($OC_submissionFieldAR['topics']['maxselections']) && ($OC_submissionFieldAR['topics']['maxselections'] == 1) && ($OC_submissionFieldAR['topics']['type'] == 'checkbox')){
		$OC_submissionFieldAR['topics']['type'] = 'radio';
	}

	// Remove file field if present and not making new submission
	if (isset($OC_submissionFieldAR['file']) && !preg_match("/author\/submit\.php/", $_SERVER['PHP_SELF']) && !preg_match("/preview\.php/", $_SERVER['QUERY_STRING'])) {
		unset($OC_submissionFieldAR['file']);
		foreach ($OC_submissionFieldSetAR as $fsid => $fsar) {
			if (in_array('file', $fsar['fields'])) {
				if (count($fsar['fields']) == 1) {
					unset($OC_submissionFieldSetAR[$fsid]);
				} else {
					$OC_submissionFieldSetAR[$fsid]['fields'] = array_diff($OC_submissionFieldSetAR[$fsid]['fields'], array('file'));
				}
				break;
			}
		}
	}
	
	// Set contact author field to 1 and hide it if (minDisplayAuthors=1 && maxAuthors=1) || (OC_authorOneContact=1)
	if ( isset($OC_submissionFieldAR['contactid']) && $author1contact ) {
		$OC_submissionFieldAR['contactid']['type'] = 'hidden';
		$_POST['contactid'] = 1;
		$GLOBALS['updateAuthorFieldsAR'] = array_diff($GLOBALS['updateAuthorFieldsAR'], array('contactid')); // remove contactid field from list of fields to be updated via Add Author
	}
	
}

