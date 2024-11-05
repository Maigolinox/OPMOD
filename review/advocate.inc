<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Advocate fields
// See include-forms.inc for syntax format

// Get advocate values
$accValues = array();
foreach ($OC_acceptanceValuesAR as $acc) {
	$accValues[] = $acc['value'];
}
$accValues[] = 'Undecided';

// Get topics
$topq = "SELECT * FROM `" . OCC_TABLE_TOPIC . "`";
if ($OC_configAR['OC_topicDisplayAlpha']) { 
	$topq .= " ORDER BY `topicname`";
}
$topr = ocsql_query($topq) or err('unable to retrieve topics');
$topicAR = array();
if (($tnum = ocsql_num_rows($topr)) > 0) {
	while ($topl = ocsql_fetch_assoc($topr)) {
		$topicAR[$topl['topicid']] = $topl['topicname'];
	}
}

// Hooks
if (oc_hookSet('committee-advocate-preinc')) {
	foreach ($GLOBALS['OC_hooksAR']['committee-advocate-preinc'] as $v) {
		require_once $v;
	}
}

if (!isset($mod_oc_customforms_customAdvForm) || (!$mod_oc_customforms_customAdvForm)) { // skip if we have a custom form
	
	$OC_advocateQuestionsAR = array(
		'adv_recommendation' => array(
			'name' => oc_('Recommendation'),
			'short' => oc_('Recommendation'),
			'note' => '',
			'type' => 'radio',
			'display' => 'newline',
			'required' => false,
			'longlabel' => true,
			'usekey' => false,
			'values' => $accValues
		),
		
		'adv_comments' => array(
			'name' => oc_('Committee Comments'),
			'short' => oc_('Committee Comments'),
			'note' => oc_('Reasons must be included for all submissions, because they help us determine what to do when reviewers disagree with each other.'),
			'longlabel' => true,
			'type' => 'textarea'
		)
		
	);

	// Set up fieldset
	$OC_advocateQuestionsFieldsetAR = array(
		'fs_advocate' => array(
			'fieldset' => '',
			'note' => '',
			'fields' => array_keys($OC_advocateQuestionsAR)
		)
	);
	
} // if ! custom form


if (oc_hookSet('committee-advocate-inc')) {
	foreach ($GLOBALS['OC_hooksAR']['committee-advocate-inc'] as $v) {
		require_once $v;
	}
}

// Make updates if not customforms edit
if (! isset($mod_oc_customforms_edit) || ! $mod_oc_customforms_edit) {
	// Update valuetypes
	foreach ($OC_advocateQuestionsAR as $sfk => $sf) {
		if (isset($sf['valuetype']) && !empty($sf['valuetype']) && ($sf['valuetype'] != 'custom')) {
			switch($sf['valuetype']) {
				case 'country':
					require_once OCC_COUNTRY_FILE;
					$OC_advocateQuestionsAR[$sfk]['values'] = $GLOBALS['OC_countryAR'];
					break;
					
				case 'topic':
					$OC_advocateQuestionsAR[$sfk]['values'] = $topicAR;
					break;
					
				default: // lib file
					require_once OCC_LIB_DIR . $sf['valuetype'] . '.inc';
					$OC_advocateQuestionsAR[$sfk]['values'] = $GLOBALS['OC_' . $sf['valuetype'] . 'AR'];
					break;
			}
		}
	}
}
