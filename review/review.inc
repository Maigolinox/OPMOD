<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Review questions
// See include-forms.inc for syntax format

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
if (oc_hookSet('committee-review-preinc')) {
	foreach ($GLOBALS['OC_hooksAR']['committee-review-preinc'] as $v) {
		require_once $v;
	}
}

if (!isset($mod_oc_customforms_customRevForm) || (!$mod_oc_customforms_customRevForm)) { // skip if we have a custom form
	
	// Note: The recommendation field value is used for calculating a submission's score.  
	// The field is defined as TINYINT(3), which may need to be changed if a larger value will be stored
	$OC_reviewQuestionsAR = array(
		'recommendation' => array(
			'name' => oc_('Recommendation'),
			'short' => oc_('Recommendation'),
			'note' => '',
			'type' => 'radio',
			'display' => 'newline',
			'required' => true,
			'score' => true,
			'usekey' => true,
			'longlabel' => true,
			'values' => array(
				1 => oc_('Reject: Content inappropriate to the conference or has little merit'),
				2 => oc_('Probable Reject: Basic flaws in content or presentation or very poorly written'),
				3 => oc_('Marginal Tend to Reject: Not as badly flawed; major effort necessary to make acceptable but content well-covered in literature already'),
				4 => oc_('Marginal Tend to Accept: Content has merit, but accuracy, clarity, completeness, and/or writing should and could be improved in time'),
				5 => oc_('Clear Accept: Content, presentation, and writing meet professional norms; improvements may be advisable but acceptable as is'),
				6 => oc_('Must Accept: Candidate for outstanding submission. Suggested improvements still appropriate')
			)
		),
		
		'category' => array(
			'name' => oc_('Submission Categorization'),
			'short' => oc_('Category'),
			'note' => '',
			'type' => 'radio',
			'display' => 'newline',
			'longlabel' => true,
			'usekey' => true,
			'values' => array(
				1 => oc_('Highly theoretical'),
				2 => oc_('Tends towards theoretical'),
				3 => oc_('Balanced theory and practice'),
				4 => oc_('Tends toward practical'),
				5 => oc_('Highly practical')
			)
		),
		
		'value' => array(
			'name' => oc_('Overall Value Added to the Field'),
			'short' => oc_('Value'),
			'note' => oc_('Check as many as appropriate'),
			'type' => 'checkbox',
			'display' => 'newline',
			'longlabel' => true,
			'usekey' => true,
			'values' => array(
				1 => oc_('New information'),
				2 => oc_('Valuable confirmation of present knowledge'),
				3 => oc_('Clarity to present understanding'),
				4 => oc_('New perspective, issue, or problem definition'),
				5 => oc_('Not much'),
				6 => oc_('Other')
			)
		),
		
		'familiar' => array(
			'name' => oc_('Reviewer Familiarity with Subject Matter'),
			'short' => oc_('Familiarity'),
			'note' => oc_('Relates to the confidence you have in your review'),
			'type' => 'radio',
			'display' => 'newline',
			'longlabel' => true,
			'usekey' => true,
			'values' => array(
				'Low' => oc_('Low'),
				'Moderate' => oc_('Moderate'),
				'High' => oc_('High')
			)
		),
		
		'bpcandidate' => array(
			'name' => oc_('Is this submission a candidate for the best submission award'),
			'short' => oc_('Best Sub.'),
			'note' => '',
			'type' => 'radio',
			'display' => 'newline',
			'longlabel' => true,
			'usekey' => true,
			'values' => array(
				'Yes' => oc_('Yes'),
				'No' => oc_('No'),
				'Unsure' => oc_('Unsure')
			)
		),
		
		'length' => array(
			'name' => oc_('Is the submission length appropriate'),
			'short' => oc_('Length'),
			'note' => '',
			'type' => 'radio',
			'display' => 'newline',
			'longlabel' => true,
			'usekey' => true,
			'values' => array(
				'Yes' => oc_('Yes'),
				'No' => oc_('No'),
				'Unsure' => oc_('Unsure')
			)
		),
		
		'difference' => array(
			'name' => oc_('If from reading the submission you know who the author is, how different is this from earlier submissions on the same topic by the same author? That is, is it the same as or a slight modification of other submissions, with little or no new information'),
			'short' => oc_('Prior Work Diff.'),
			'note' => oc_('We use these suggestions in assigning submissions to sessions for the conference, but not in determining whether the submission is accepted)'),
			'type' => 'radio',
			'display' => 'newline',
			'longlabel' => true,
			'usekey' => true,
			'values' => array(
				1 => oc_('Totally or largely different from other submissions'),
				2 => oc_('Moderately different from other submissions'),
				3 => oc_('Totally or largely identical to other submissions'),
				4 => oc_("Don't know")
			)
		),
	
		'sessions' => array( // NOTE: sessions field should not have showauthor attribute set
			'name' => oc_('Which of the following session(s) would be the most appropriate for this submission'),
			'short' => oc_('Session(s)'),
			'note' => oc_('We use these suggestions in assigning submissions to sessions for the conference, but not in determining whether the submission is accepted)'),
			'type' => 'checkbox',
			'longlabel' => true,
			'usekey' => true,
			'display' => 'newline',
			'required' => false,
			'valuetype' => 'topic'
		),
		
		'authorcomments' => array(
			'name' => oc_('Comments for the Authors'),
			'short' => oc_('Author Comments'),
			'note' => oc_('Constructive comments to the author(s) would be appreciated.'),
			'type' => 'textarea',
			'showauthor' => true,
			'longlabel' => true
		),
		
		'pccomments' => array(
			'name' => oc_('Comments for the Program Committee (authors will not see these comments)'),
			'short' => oc_('PC Comments'),
			'note' => oc_('Reasons must be included for all submissions, because they help us determine what to do when reviewers disagree with each other.'),
			'type' => 'textarea',
			'longlabel' => true
		)
		
	);

	// Set up fieldset
	$OC_reviewQuestionsFieldsetAR = array(
		'fs_review' => array(
			'fieldset' => oc_('Review'),
			'note' => '',
			'fields' => array_keys($OC_reviewQuestionsAR)
		)
	);

	// Unset fields that should not be displayed on form -- defaults to fs_review fieldset
	if (!empty($GLOBALS['OC_configAR']['OC_hideRevFields'])) {
		$hRF = explode(',', $GLOBALS['OC_configAR']['OC_hideRevFields']);
		foreach ($hRF as $hr_f) {
			unset($OC_reviewQuestionsAR[$hr_f]);
			if (in_array($hr_f, $OC_reviewQuestionsFieldsetAR['fs_review']['fields'])) {
				$OC_reviewQuestionsFieldsetAR['fs_review']['fields'] = array_values(array_diff($OC_reviewQuestionsFieldsetAR['fs_review']['fields'], array($hr_f)));
			}
		}
	}
	
} // if ! custom form


// Fill in session topics
if (isset($OC_reviewQuestionsAR['sessions'])) {
	$tq = "SELECT * FROM `" . OCC_TABLE_TOPIC . "`";
	$tr = ocsql_query($tq) or err("Unable to retrieve topics");
	while ($tt = ocsql_fetch_assoc($tr)) {
		$OC_reviewQuestionsAR['sessions']['values'][$tt['topicid']] = $tt['topicname'];
	}
}

if (oc_hookSet('committee-review-inc')) {
	foreach ($GLOBALS['OC_hooksAR']['committee-review-inc'] as $v) {
		require_once $v;
	}
}

// Make updates if not customforms edit
if (! isset($mod_oc_customforms_edit) || ! $mod_oc_customforms_edit) {
	// Update valuetypes
	foreach ($OC_reviewQuestionsAR as $sfk => $sf) {
		if (isset($sf['valuetype']) && !empty($sf['valuetype']) && ($sf['valuetype'] != 'custom')) {
			switch($sf['valuetype']) {
				case 'country':
					require_once OCC_COUNTRY_FILE;
					$OC_reviewQuestionsAR[$sfk]['values'] = $GLOBALS['OC_countryAR'];
					break;
					
				case 'topic':
					$OC_reviewQuestionsAR[$sfk]['values'] = $topicAR;
					break;
					
				default: // lib file
					require_once OCC_LIB_DIR . $sf['valuetype'] . '.inc';
					$OC_reviewQuestionsAR[$sfk]['values'] = $GLOBALS['OC_' . $sf['valuetype'] . 'AR'];
					break;
			}
		}
	}
	
	// Update Topics to radio if required and max selections = 1
	if (isset($OC_reviewQuestionsAR['sessions']['required']) && $OC_reviewQuestionsAR['sessions']['required'] && isset($OC_reviewQuestionsAR['sessions']['maxselections']) && ($OC_reviewQuestionsAR['sessions']['maxselections'] == 1) && ($OC_reviewQuestionsAR['sessions']['type'] == 'checkbox')){
		$OC_reviewQuestionsAR['sessions']['type'] = 'radio';
	}
}
