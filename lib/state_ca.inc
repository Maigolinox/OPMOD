<?php

// OpenConf CA Provinces & Territories

if ($GLOBALS['OC_translate'] && !preg_match("/^fr/", $GLOBALS['OC_locale'])) {
	$GLOBALS['OC_state_caAR'] = array(
		'AB'=>'Alberta', 
		'BC'=>'British Columbia', 
		'MB'=>'Manitoba', 
		'NB'=>'New Brunswick', 
		'NL'=>'Newfoundland and Labrador', 
		'NT'=>'Northwest Territories', 
		'NS'=>'Nova Scotia', 
		'NU'=>'Nunavut',
		'ON'=>'Ontario', 
		'PE'=>'Prince Edward Island', 
		'QC'=>'Quebec', 
		'SK'=>'Saskatchewan', 
		'YT'=>'Yukon Territory'
	);
} else {
	$GLOBALS['OC_state_caAR'] = array(
		"AB"=>"Alberta",
		"BC"=>"Colombie-Britannique",
		"PE"=>"Île-du-Prince-Édouard",
		"MB"=>"Manitoba",
		"NB"=>"Nouveau-Brunswick",
		"NS"=>"Nouvelle-Écosse",
		"NU"=>"Nunavut",
		"ON"=>"Ontario",
		"QC"=>"Québec",
		"SK"=>"Saskatchewan",
		"NL"=>"Terre-Neuve-et-Labrador",
		"NT"=>"Territoires du Nord-Ouest",
		"YT"=>"Yukon"
	);
}

