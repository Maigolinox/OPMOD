<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Committee sign up
// See include-forms.inc for syntax format

// Get topics
$topq = "SELECT * FROM `" . OCC_TABLE_TOPIC . "`";
if ($OC_configAR['OC_topicDisplayAlpha']) { 
	$topq .= " ORDER BY `topicname`";
}
$topr = ocsql_query($topq) or err('unable to retrieve topics');
$topicAR = array();
$shortTopicAR = array();
if (($tnum = ocsql_num_rows($topr)) > 0) {
	while ($topl = ocsql_fetch_assoc($topr)) {
		if ($topl['topicname'] == 'N/A') { continue; }
		$topicAR[$topl['topicid']] = $topl['topicname'];
		if (!empty($topl['short'])) {
			$shortTopicAR[$topl['topicid']] = $topl['short'];
		}
	}
}

$OC_reviewerFieldAR = array();
$OC_reviewerFieldSetAR = array();
	
// Hooks
if (oc_hookSet('committee-profile-preinc')) {
	foreach ($GLOBALS['OC_hooksAR']['committee-profile-preinc'] as $v) {
		require_once $v;
	}
}

if (!isset($mod_oc_customforms_customCmtForm) || (!$mod_oc_customforms_customCmtForm)) { // skip if we have a custom form
	
	// Consent
	if (
		(OCC_LICENSE != 'Public') 
		||
		((OCC_LICENSE == 'Public') && ($OC_configAR['OC_privacy_display'] > 0))
	) {
		$OC_reviewerFieldAR['consent'] = array(
			'name' => oc_('Consent'),
			'short' => oc_('Consent'),
			'note' => '',
			'type' => 'checkbox',
			'usekey' => false,
			'required' => true,
			'delimiter' => '',
			'values' => array(oc_('I consent to the collection and use of my personal information, including receiving emails, consistent with the Privacy Policy linked above.'))
		);
	
		$OC_reviewerFieldSetAR['fs_consent'] = array(
			'fieldset' => oc_('Consent'),
			'note' => '',
			'fields' => array('consent')
		);
	}
	
	// Personal Info
	$OC_reviewerFieldAR['orcid'] = array(
		'name' => oc_('ORCID'),
		'short' => oc_('ORCID'),
		'note' => '',
		'type' => 'text',
		'width' => 30,
		'maxchars' => 30,
		'required' => false
	);

	$OC_reviewerFieldAR['name_first'] = array(
		'name' => oc_('First/Given Name'),
		'short' => oc_('First Name'),
		'note' => '',
		'type' => 'text',
		'maxchars' => 60,
		'required' => false
	);
	
	$OC_reviewerFieldAR['name_last'] = array(
		'name' => oc_('Last/Family Name'),
		'short' => oc_('Last Name'),
		'note' => '',
		'type' => 'text',
		'maxchars' => 40,
		'required' => true
	);
	
	$OC_reviewerFieldAR['email'] = array(
		'name' => oc_('Email'),
		'short' => oc_('Email'),
		'note' => '',
		'type' => 'email',
		'required' => true
	);
	
	$OC_reviewerFieldAR['organization'] = array(
		'name' => oc_('Organization'),
		'short' => oc_('Organization'),
		'note' => '',
		'type' => 'text',
		'maxchars' => 150, // limitation as a result of utf8mb4 keys
		'required' => false
	);
	
	$OC_reviewerFieldAR['country'] = array(
		'name' => oc_('Country'),
		'short' => oc_('Country'),
		'note' => '',
		'type' => 'dropdown',
		'blank' => true,
		'required' => false,
		'valuetype' => 'country'
	);
	
	$OC_reviewerFieldAR['telephone'] = array(
		'name' => oc_('Telephone'),
		'short' => oc_('Telephone'),
		'note' => '',
		'type' => 'text',
		'required' => false
	);
	
	$OC_reviewerFieldAR['url'] = array(
		'name' => oc_('Web Site'),
		'short' => oc_('Web Site'),
		'note' => '',
		'type' => 'text',
		'required' => false,
		'placeholder' => 'https://'
	);
	
	$OC_reviewerFieldSetAR['fs_personal'] = array(
		'fieldset' => oc_('Personal Info'),
		'note' => '',
		'fields' => array('orcid', 'name_first', 'name_last', 'email', 'organization', 'country', 'telephone', 'url')
	);
	
	// Topics
	$OC_reviewerFieldAR['topics'] = array(
		'name' => oc_('Topic Areas'),
		'short' => oc_('Topic(s)'),
		'note' => '',
		'type' => 'checkbox',
		'usekey' => true,
		'display' => 'newline',
		'required' => true,
		'valuetype' => 'topic'
	);
	if ($OC_configAR['OC_multipleCommitteeTopics'] != 1) { // (!1 = limited to 1)
			$OC_reviewerFieldAR['topics']['maxselections'] = 1;
	}
			
	$OC_reviewerFieldSetAR['fs_topics'] = array(
		'fieldset' => oc_('Topic Areas'),
		'note' => oc_('To help match submissions to reviewers, please select the area(s) most applicable to your submission'),
		'fields' => array('topics')
	);

	
	// Comments
	$OC_reviewerFieldAR['comments'] = array(
		'name' => oc_('Comments to Chair'),
		'short' => oc_('Comments'),
		'note' => '',
		'type' => 'textarea',
		'height' => 5,
		'required' => false
	);
	
	$OC_reviewerFieldSetAR['fs_comments'] = array(
		'fieldset' => oc_('Comments'),
		'note' => '',
		'fields' => array('comments')
	);
	
	// Account
	$OC_reviewerFieldAR['username'] = array(
		'name' => oc_('Username'),
		'short' => oc_('Username'),
		//T: %1$d-%2$d = range of letters allowed (e.g., 5-50)
		'note' => sprintf(oc_('%1$d-%2$d characters: letters, numbers, @, period (.), hyphen (-)'), 5, 50),
		'minchars' => 5,
		'maxchars' => 50,
		'type' => 'text',
		'donotvalidate' => true,
		'required' => true	// always true
	);
	$OC_reviewerFieldAR['password1'] = array(
		'name' => oc_('Password'),
		'short' => oc_('Password'),
		'note' => sprintf(oc_('%d or more characters (any)'), 8),
		'type' => 'password',
		'donotvalidate' => true,
		'required' => true
	);
	
	$OC_reviewerFieldAR['password2'] = array(
		'name' => oc_('Re-enter Password'),
		'short' => oc_('Confirm'),
		'note' => '',
		'type' => 'password',
		'donotvalidate' => true,
		'required' => true
	);
	
	$OC_reviewerFieldSetAR['fs_passwords'] = array(
		'fieldset' => oc_('Account'),
		'note' => '',
		'fields' => array('username', 'password1', 'password2')
	);

	// Unset fields that should not be displayed on form
	if (!empty($GLOBALS['OC_configAR']['OC_hideCmtFields'])) {
		$hCF = explode(',', $GLOBALS['OC_configAR']['OC_hideCmtFields']);
		foreach ($hCF as $hc_f) {
			list($a_fs, $a_f) = explode(':', $hc_f);
			unset($OC_reviewerFieldAR[$a_f]);
			if (isset($OC_reviewerFieldSetAR[$a_fs]['fields']) && in_array($a_f, $OC_reviewerFieldSetAR[$a_fs]['fields'])) {
				$OC_reviewerFieldSetAR[$a_fs]['fields'] = array_values(array_diff($OC_reviewerFieldSetAR[$a_fs]['fields'], array($a_f)));
			}
		}
	}
	
} // if ! custom form


if (oc_hookSet('committee-profile-inc')) {
	foreach ($GLOBALS['OC_hooksAR']['committee-profile-inc'] as $v) {
		require_once $v;
	}
}

// Make updates if not customforms edit
if (! isset($mod_oc_customforms_edit) || ! $mod_oc_customforms_edit) {
	foreach ($OC_reviewerFieldAR as $sfk => $sf) {
		// remove fields designated for new submissions (sign up) only if profile being edited
		if (isset($sf['newsubonly']) && $sf['newsubonly'] && isset($GLOBALS['OC_cmtEdit']) && $GLOBALS['OC_cmtEdit']) {
			unset($OC_reviewerFieldAR[$sfk]);
			foreach ($OC_reviewerFieldSetAR as $fsid => $fsar) {
				if (in_array($sfk, $fsar['fields'])) {
					$OC_reviewerFieldSetAR[$fsid]['fields'] = array_diff($OC_reviewerFieldSetAR[$fsid]['fields'], array($sfk));
					continue; // field should only be in one fieldset
				}
			}
		} else { // update valuetypes
			if (isset($sf['valuetype']) && !empty($sf['valuetype']) && ($sf['valuetype'] != 'custom')) {
				switch($sf['valuetype']) {
					case 'country':
						require_once OCC_COUNTRY_FILE;
						$OC_reviewerFieldAR[$sfk]['values'] = $GLOBALS['OC_countryAR'];
						break;
						
					case 'topic':
						$OC_reviewerFieldAR[$sfk]['values'] = $topicAR;
						break;
						
					default: // lib file
						require_once OCC_LIB_DIR . $sf['valuetype'] . '.inc';
						$OC_reviewerFieldAR[$sfk]['values'] = $GLOBALS['OC_' . $sf['valuetype'] . 'AR'];
						break;
				}
			}
		}
	}
	
	// Update Topics to radio if required and max selections = 1
	if (isset($OC_reviewerFieldAR['topics']['required']) && $OC_reviewerFieldAR['topics']['required'] && isset($OC_reviewerFieldAR['topics']['maxselections']) && ($OC_reviewerFieldAR['topics']['maxselections'] == 1) && ($OC_reviewerFieldAR['topics']['type'] == 'checkbox')){
		$OC_reviewerFieldAR['topics']['type'] = 'radio';
	}
}
