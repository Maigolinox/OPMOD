<?php

// OpenConf CA Provinces & US States

require_once OCC_LIB_DIR . 'state_us.inc';
require_once OCC_LIB_DIR . 'state_ca.inc';

$GLOBALS['OC_state_causAR'] = array_merge($GLOBALS['OC_state_caAR'], $GLOBALS['OC_state_usAR']);
asort($GLOBALS['OC_state_causAR'], SORT_LOCALE_STRING);

