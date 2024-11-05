<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once "../include.php";

beginChairSession();

if (is_file(OCC_PLUGINS_DIR . 'chartjs/Chart.min.js')) {
	oc_addHeader('<script src="' . OCC_PLUGINS_DIR . 'chartjs/Chart.min.js"></script>');
} else {
	oc_addHeader('<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>');
}

printHeader("Summary", 1);

$colorAR = array('#5668E2', '#56AEE2', '#56E2CF', '#56E289', '#68E256', '#AEE256', '#E2CF56', '#E28956', '#E25668', '#E256AE', '#CF56E2', '#8A56E2');
$statusAR = array(
	0 => '<span style="color: #f00;" title="closed">&#9940;</span>',
	1 => '<span style="color: #090;" title="open">&#9711;</span>'
);

// Get count of papers
$r = ocsql_query("SELECT COUNT(`" . OCC_TABLE_PAPER . "`.`paperid`) AS `papertotal` FROM `" . OCC_TABLE_PAPER . "`") or err("Unable to query submission info");
$l = ocsql_fetch_assoc($r);
$l1['papertotal'] = $l['papertotal'];

// Get count of withdrawn submissions
$r = ocsql_query("SELECT COUNT(*) AS `withdrawtotal` FROM `" . OCC_TABLE_WITHDRAWN . "`") or err("Unable to query withdrawn submission info");
$l = ocsql_fetch_assoc($r);
$l1['withdrawtotal'] = $l['withdrawtotal'];

// Get count of reviews
$r = ocsql_query("SELECT COUNT(*) AS `reviewstotal` FROM `" . OCC_TABLE_PAPERREVIEWER . "`") or err("Unable to query reviews info");
$l = ocsql_fetch_assoc($r);
$l1['reviewstotal'] = $l['reviewstotal'];

// Get # of papers w/reviewers assigned
$r = ocsql_query("SELECT COUNT(DISTINCT(`paperid`)) AS `revassigned` FROM `" . OCC_TABLE_PAPERREVIEWER . "`") or err("Unable to query assigned count");
$l = ocsql_fetch_assoc($r);
$l1['revassigned'] = $l['revassigned'];

// Get count of advocate assignments
$r = ocsql_query("SELECT COUNT(*) AS `advocatingtotal` FROM `" . OCC_TABLE_PAPERADVOCATE . "`") or err("Unable to query advocate assignment info");
$l = ocsql_fetch_assoc($r);
$l1['advocatingtotal'] = $l['advocatingtotal'];

// Get # of uploads
$r = ocsql_query("SELECT COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPER . "` WHERE `format` IS NOT NULL") or err("Unable to query file uploads");
$l = ocsql_fetch_assoc($r);
$uploadtotal = $l['count'];

// Get count of signed up reviewers, advocates
$r = ocsql_query("SELECT `" . OCC_TABLE_REVIEWER . "`.`onprogramcommittee`, COUNT(`" . OCC_TABLE_REVIEWER . "`.`reviewerid`) AS `count` FROM `" . OCC_TABLE_REVIEWER . "` GROUP BY `" . OCC_TABLE_REVIEWER . "`.`onprogramcommittee`") or err("Unable to query reviewer info");
$rcount = array();
$rcount['T'] = 0;
$rcount['F'] = 0;
while ($l = ocsql_fetch_assoc($r)) {
	$rcount[$l['onprogramcommittee']] = $l['count'];
}
// Get count of pc decision
$r = ocsql_query("SELECT `" . OCC_TABLE_PAPER . "`.`accepted`, COUNT(`" . OCC_TABLE_PAPER . "`.`paperid`) AS `count` FROM `" . OCC_TABLE_PAPER . "` GROUP BY `" . OCC_TABLE_PAPER . "`.`accepted`") or err("Unable to query submission info");
$pcount = array();
$pcount['T'] = 0;
$pcount['F'] = 0;
while ($l = ocsql_fetch_assoc($r)) {
	$pcount[$l['accepted']] = $l['count'];
}
// Get count of completed reviews
$r = ocsql_query("SELECT COUNT(`" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`) FROM `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`completed`='T'") or err("Unable to query reviews");
$l = ocsql_fetch_row($r);
$reviewscompleted = $l[0];
// Get count of started reviews
$r = ocsql_query("SELECT COUNT(`" . OCC_TABLE_PAPERREVIEWER . "`.`paperid`) FROM `" . OCC_TABLE_PAPERREVIEWER . "` WHERE `" . OCC_TABLE_PAPERREVIEWER . "`.`completed`='F' AND `" . OCC_TABLE_PAPERREVIEWER . "`.`updated` IS NOT NULL") or err("Unable to query started reviews");
$l = ocsql_fetch_row($r);
$reviewsstarted = $l[0];
// Get count of advocate recommendations
$r = ocsql_query("SELECT COUNT(`" . OCC_TABLE_PAPERADVOCATE . "`.`paperid`) FROM `" . OCC_TABLE_PAPERADVOCATE . "` WHERE `" . OCC_TABLE_PAPERADVOCATE . "`.`adv_recommendation` IS NOT NULL") or err("Unable to query recommendations");
$l = ocsql_fetch_row($r);
$advocaterecommendations = $l[0];
// Get acceptance type
$r = ocsql_query("SELECT `accepted`, COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPER . "` GROUP BY `accepted` ORDER BY `accepted`") or err("Unable to get score count");
$accCountAR = array();
while ($l = ocsql_fetch_assoc($r)) {
	if (empty($l['accepted'])) {
		$accCountAR['Pending'] = $l['count'];
	} else {
		$accCountAR[$l['accepted']] = $l['count'];
	}
}
// Get submission types
$r = ocsql_query("SELECT `type`, COUNT(*) AS `count` FROM `" . OCC_TABLE_PAPER . "` GROUP BY `type` ORDER BY `count` DESC, `type`") or err("Unable to get score count");
$typeCountAR = array();
$unknown = '';
while ($l = ocsql_fetch_assoc($r)) {
	if ($l['type'] == '') {
		$unknown = $l['count'];
		continue;
	}
	$typeCountAR[$l['type']] = $l['count'];
}
if ((count($typeCountAR) > 0) && !empty($unknown)) {
	$typeCountAR['unknown'] = $unknown;
}

$summaryAR = array();
$SummaryIndex = 0;

//// Submissions
$summaryAR[$SummaryIndex] = array(
	'header' => 'Submissions',
	'status' => array('( <span style="white-space: nowrap;"><a href="set_status.php" title="new submission is ' . safeHTMLstr(oc_strtolower($OC_statusValueAR[$OC_statusAR['OC_submissions_open']])) . ' - click to change status">New</a> ' . $statusAR[$OC_statusAR['OC_submissions_open']] . '</span> | <span style="white-space: nowrap;"><a href="set_status.php" title="edit submission is ' . safeHTMLstr(oc_strtolower($OC_statusValueAR[$OC_statusAR['OC_submissions_open']])) . ' - click to change status">Edit</a> ' . $statusAR[$OC_statusAR['OC_edit_open']] . ' |</span> <span style="white-space: nowrap;"><a href="set_status.php" title="upload file is ' . safeHTMLstr(oc_strtolower($OC_statusValueAR[$OC_statusAR['OC_submissions_open']])) . ' - click to change status">Upload</a> ' . $statusAR[$OC_statusAR['OC_upload_open']] . '</span> | <span style="white-space: nowrap;"><a href="set_status.php" title="view file is ' . safeHTMLstr(oc_strtolower($OC_statusValueAR[$OC_statusAR['OC_submissions_open']])) . ' - click to change status">View file</a> ' . $statusAR[$OC_statusAR['OC_view_file_open']] . '</span> | <span style="white-space: nowrap;"><a href="set_status.php" title="check status is ' . safeHTMLstr(oc_strtolower($OC_statusValueAR[$OC_statusAR['OC_status_open']])) . ' - click to change status">Status</a> ' . $statusAR[$OC_statusAR['OC_view_file_open']] . '</span> )'),
	'charts' => array()
);

// Submissions - Submissions
$summaryAR[$SummaryIndex]['charts']['sub'] = array(
	'title' => 'Total',
	'link' => 'list_papers.php',
	'linktitle' => 'view submissions',
	'label' => $l1['papertotal'],
	'sublabel' => 'active',
	'values' => array(
		array(
			'value' => $l1['papertotal'],
			'color' => '#99ccff',
			'label' => 'Active'
		),
		array(
			'value' => $l1['withdrawtotal'],
			'color' => '#cccccc',
			'label' => 'Withdrawn'
		)
	)
);

// Submission - Files
$summaryAR[$SummaryIndex]['charts']['files'] = array(
	'title' => OCC_WORD_AUTHOR . ' Files',
	'link' => 'list_papers.php',
	'linktitle' => 'view submissions and files',
	'label' => $uploadtotal,
	'sublabel' => 'uploaded',
	'values' => array(
		array(
			'value' => $uploadtotal,
			'color' => '#99CCFF',
			'label' => 'Uploaded'
		),
		array(
			'value' => ($l1['papertotal'] - $uploadtotal),
			'color' => '#f0f0f0',
			'label' => 'Pending'
		)
	)
);

// Submissions - Type
if (count($typeCountAR) > 0) {
	$summaryAR[$SummaryIndex]['charts']['type'] = array(
		'title' => 'Type',
		'link' => 'list_papers.php',
		'linktitle' => 'view submissions',
		'values' => array()
	);
	$c = 0;
	foreach ($typeCountAR as $type => $count) {
		if ($type == 'unknown') {
			$color = '#f0f0f0';
		} elseif ($c >= 12) {
			$color = $colorAR[($c%12+1)];
		} else {
			$color = $colorAR[$c];
		}
		$summaryAR[$SummaryIndex]['charts']['type']['values'][] = array(
			'value' => $count,
			'color' => $color,
			'label' => $type
		);
		$c++;
	}	
}

// Submissions - Acceptance
$summaryAR[$SummaryIndex]['charts']['acc'] = array(
	'title' => 'Acceptance',
	'link' => 'list_scores.php',
	'linktitle' => 'view scores & accept/reject',
	'label' => 0,
	'sublabel' => 'accepted',
	'values' => array()
);
foreach ($OC_acceptanceValuesAR as $acc) {
	$summaryAR[$SummaryIndex]['charts']['acc']['values'][] = array(
		'value' => (isset($accCountAR[$acc['value']]) ? $accCountAR[$acc['value']] : '0'),
		'color' => '#' . $OC_acceptanceColorAR[$acc['value']],
		'label' => $acc['value']
	);
	if (in_array($acc['value'], $OC_acceptedValuesAR)) {
		$summaryAR[$SummaryIndex]['charts']['acc']['label'] += (isset($accCountAR[$acc['value']]) ? $accCountAR[$acc['value']] : '0');
	}
}
$summaryAR[$SummaryIndex]['charts']['acc']['values'][] = array(
	'value' => (isset($accCountAR['Pending']) ? $accCountAR['Pending'] : '0'),
	'color' => '#f0f0f0',
	'label' => 'Pending'
);


//// Committees & Assignments
$SummaryIndex++;
$summaryAR[$SummaryIndex] = array(
	'header' => 'Committees & Assignments',
	'status' => array('<span style="white-space: nowrap;">( Reviewer &ndash; <a href="set_status.php" title="reviewer sign up is ' . safeHTMLstr(oc_strtolower($OC_statusValueAR[$OC_statusAR['OC_rev_signup_open']])) . ' - click to change status">Sign Up</a> ' . $statusAR[$OC_statusAR['OC_rev_signup_open']] . ' | <a href="set_status.php" title="reviewer sign in is ' . safeHTMLstr(oc_strtolower($OC_statusValueAR[$OC_statusAR['OC_rev_signin_open']])) . ' - click to change status">Sign In</a> ' . $statusAR[$OC_statusAR['OC_rev_signin_open']] . '  | <a href="set_status.php" title="reviewing is ' . safeHTMLstr(oc_strtolower($OC_statusValueAR[$OC_statusAR['OC_reviewing_open']])) . ' - click to change status">Reviewing</a> ' . $statusAR[$OC_statusAR['OC_reviewing_open']] . ' )</span>'),
	'charts' => array()
);

// Committee - Members
$summaryAR[$SummaryIndex]['charts']['cmt'] = array(
	'title' => 'Committee Members',
	'link' => 'list_reviewers.php',
	'linktitle' => 'view committee members',
	'label' => $rcount['F'],
	'sublabel' => 'total',
	'values' => array(
		array(
			'value' => $rcount['F'],
			'color' => '#99ccff',
			'label' => 'Review'
		)
	)
);

// Committee - Review Assignments
$summaryAR[$SummaryIndex]['charts']['rev'] = array(
	'title' => 'Review Assignments',
	'link' => 'list_reviews.php',
	'linktitle' => 'view review assignments',
	'label' => $l1['reviewstotal'],
	'sublabel' => 'total',
	'values' => array(
		array(
			'value' => $reviewscompleted,
			'color' => '#68E256',
			'label' => 'Completed'
		),
		array(
			'value' => $reviewsstarted,
			'color' => '#AEE256',
			'label' => 'Started'
		),
		array(
			'value' => ($l1['reviewstotal'] - $reviewscompleted - $reviewsstarted),
			'color' => '#F0F0F0',
			'label' => 'Pending'
		)
	)
);

// Advocates?
if ($OC_configAR['OC_paperAdvocates']) {
	// Add advocates to Committee - Members
	$summaryAR[$SummaryIndex]['charts']['cmt']['label'] += $rcount['T'];
	$summaryAR[$SummaryIndex]['status'][] = '<span style="white-space: nowrap;">( Program &ndash; <a href="set_status.php" title="advocate sign up is ' . safeHTMLstr(oc_strtolower($OC_statusValueAR[$OC_statusAR['OC_pc_signup_open']])) . ' - click to change status">Sign Up</a> ' . $statusAR[$OC_statusAR['OC_pc_signup_open']] . ' | <a href="set_status.php" title="advocate sign in is ' . safeHTMLstr(oc_strtolower($OC_statusValueAR[$OC_statusAR['OC_pc_signin_open']])) . ' - click to change status">Sign In</a> ' . $statusAR[$OC_statusAR['OC_pc_signin_open']] . '  | <a href="set_status.php" title="advocating is ' . safeHTMLstr(oc_strtolower($OC_statusValueAR[$OC_statusAR['OC_advocating_open']])) . ' - click to change status">Advocating</a> ' . $statusAR[$OC_statusAR['OC_advocating_open']] . ' )</span>';
	$summaryAR[$SummaryIndex]['charts']['cmt']['values'][] = array(
			'value' => $rcount['T'],
			'color' => '#E28956',
			'label' => 'Program'
	);
	// Committee - Advocate Assignments
	$summaryAR[$SummaryIndex]['charts']['adv'] = array(
		'title' => 'Advocate Assignments',
		'link' => 'list_advocates.php',
		'linktitle' => 'view advocate assignments',
		'label' => $l1['advocatingtotal'],
		'sublabel' => 'total',
		'values' => array(
			array(
				'value' => $advocaterecommendations,
				'color' => '#68E256',
				'label' => 'Completed'
			),
			array(
				'value' => ($l1['advocatingtotal'] - $advocaterecommendations),
				'color' => '#F0F0F0',
				'label' => 'Pending'
			)
		)
	);
	
}

print '
<style type="text/css">
#summary dt { margin-top: 1.5em; margin-bottom: 1.5em;}
#summary .summhdr { font-weight: bold; }
h2 { font-size: 1.1em; margin-top: 1.5em; color: #444; padding-top: 1.5em; border-top: 1px solid #999; }
h2 .statusInfo {padding-left: 20px; font-size: 0.9em; font-weight: normal; }
h3 { font-size: 1em; text-align: center; font-size: 1.05em;}
.chartDiv { float: left; width: 200px; margin-right: 50px; }
.chartLegend { list-style: none; padding-left: 25px; }
.chartLegend li {
  display: block;
  padding-left: 30px;
  position: relative;
  border-radius: 5px;
  padding: 2px 8px 2px 20px;
  cursor: default;
  -webkit-transition: background-color 200ms ease-in-out;
  -moz-transition: background-color 200ms ease-in-out;
  -o-transition: background-color 200ms ease-in-out;
  transition: background-color 200ms ease-in-out;
}
.chartLegend li span {
  display: block;
  position: absolute;
  left: 0;
  top: 4px;
  width: 13px;
  height: 13px;
  border-radius: 6px;
}
.canvasDiv { position: relative; }
.chartLabel {
	z-index: 1;
	position: absolute;
	width: 100%;
	text-align: center;
	top: 53px;
	font-size: 2em;
	line-height: 0.8;
	color: #555;
	display: none;
}
.chartLabel span {
	font-size: 9pt;
	border-top: 1px solid #999;
	color: #999;
}
canvas { z-index: 2; position: relative; } /* displays tooltip above label */
</style>
';

if (oc_hookSet('chair-summary-pre')) {
	foreach ($OC_hooksAR['chair-summary-pre'] as $v) {
		include_once $v;
	}
}


$chartNames = array();
$chartJS = '';
$barChartNames = array();
$barChartJS = '';
foreach ($summaryAR as $sectionID => $section) {
	if ($sectionID > 0) {
		print '<br style="clear: left;" />';
	}
	print '<h2>' . safeHTMLstr($section['header']) . ' ';
	foreach ($section['status'] as $status) {
		print '<span class="statusInfo">' . $status . '</span> ';
	}
	print '</h2>';
	foreach ($section['charts'] as $chartIndex => $chart) {
		if (isset($chart['type']) && ($chart['type'] == 'bar')) {
			$labels = '';
			$data = '';
			$legend = '';
			foreach ($chart['values'] as $value) {
				$labels .= '"' . safeHTMLstr($value['label']) . '",';
				$data .= $value['value'] . ',';
				$legend .= safeHTMLstr($value['label'] . ': ' . $value['value']) . '<br />';
			}
			$labels = rtrim($labels, ',');
			$data = rtrim($data, ',');
			$barChartJS .= '
"' . $chartIndex . '": {
	labels: [ ' . $labels . ' ],
	datasets: [ {
    	label: "",
		backgroundColor: "rgba(153,204,244,0.5)",
		borderColor: "rgba(153,204,244,0.8)",
		hoverBackgroundColor: "rgba(153,187,204,0.75)",
		hoverBorderColor: "rgba(153,204,244,1)",
        borderWidth: "2",
		data: [' . $data . '] 
	} ] 
}, ';
			$barChartNames[] = $chartIndex;
			print '<div class="barChartDiv">';
			print '<canvas id="' . $chartIndex . 'Chart" width="' . (isset($chart['width']) ? $chart['width'] : '600') . '" height="' . (isset($chart['height']) ? $chart['height'] : (50 * count($chart['values']))) . '" alt="' . safeHTMLstr($section['header'] . ' - ' . oc_strtolower($chart['title'])) . ' chart">' . $legend . '</canvas>';
			print '</div>';
			
		} else { // default to doughnut

			$chartNames[] = $chartIndex;
			print '
<div class="chartDiv">
<h3>' . 
				( (isset($chart['link']) && !empty($chart['link']) ) ?
						'<a href="' . $chart['link'] . '" title="' . safeHTMLstr($chart['linktitle']) . '">' . safeHTMLstr($chart['title']) . '</a>'
						:
						safeHTMLstr($chart['title'])
				) . 
'</h3>
<div class="canvasDiv">
';

			if (isset($chart['label']) && ($chart['label'] !== '')) {
				print '<div class="chartLabel">' . safeHTMLstr($chart['label']);
				if (isset($chart['sublabel']) && !empty($chart['sublabel'])) {
					print '<br /><span>' . safeHTMLstr($chart['sublabel']) . '</span>';
				}
				print '</div>';
			}
			
			print '
<canvas id="' . $chartIndex . 'Chart" width="' . (isset($chart['width']) ? $chart['width'] : '200') . '" height="' . (isset($chart['height']) ? $chart['height'] : '150') . '" alt="' . safeHTMLstr($section['header'] . ' - ' . oc_strtolower($chart['title'])) . ' chart"></canvas>
</div>
<ul class="chartLegend" id="' . $chartIndex . 'Legend">';
			// make sure there are no spaces between the <ul> and <li> tags otherwise activeSegment JS error will result -- OBE as activeSegment no longer in use
			$total = 0;
			$valuesStr = '';
			$jsStr = '';
            $labels = array();
            $data = array();
            $colors = array();
			foreach ($chart['values'] as $value) {
				$total += $value['value'];
				$valuesStr .= '<li><span style="background-color: ' . $value['color'] . ';"></span>' . safeHTMLstr($value['label']) . ': ' . $value['value'] . '</li>';
                $labels[] = safeHTMLstr($value['label']);
                $colors[] = safeHTMLstr($value['color']);
                $data[] = $value['value'];
			}
			$chartJS .= '	"' . $chartIndex . '": { labels: [ "' . implode('","', $labels) . '" ], datasets: [ { data: [' . implode(',', $data) . '], backgroundColor: [ "' . implode('","', $colors) . '" ], hoverBackgroundColor: [ "' . implode('","', $colors) . '" ] }] },' . "\n";
			print $valuesStr . '</ul>
</div>
';
		}
	}
}


print '
<br style="clear: left;" />

<script>
<!--
var

	chartAR = { },
	
	barChartAR = { },
	
	chartNameAR = [ "' . implode('", "', $chartNames) . '" ],

	barChartNameAR = [ "' . implode('", "', $barChartNames) . '" ],

	helpers = Chart.helpers,

	chartOptions = {
		cutoutPercentage : 50,
		animation : false,
		legend: { display: false },
	},

	barChartOptions = {
		animation : false,
		responsive: false,
		legend: { display: false },
	},

	chartDataAR = {
' . $chartJS . '
	},
	
	barChartDataAR = {
' . $barChartJS . '
	};

document.write("<style>.chartLabel { display: block; }</style>");

window.addEventListener("load", function(event) {
	for (var c in chartNameAR) {
		chartAR[chartNameAR[c]] = new Chart(document.getElementById(chartNameAR[c] + "Chart"), { type: "doughnut", data: chartDataAR[chartNameAR[c]], options: chartOptions });
	}
	for (var c in barChartNameAR) {
		if (barChartNameAR[c]) {
			barChartAR[barChartNameAR[c]] = new Chart(document.getElementById(barChartNameAR[c] + "Chart"), { type: "horizontalBar", data: barChartDataAR[barChartNameAR[c]], options: barChartOptions });
		}
	}
}, false);
// -->
</script>
';

if (oc_hookSet('chair-summary')) {
	foreach ($OC_hooksAR['chair-summary'] as $v) {
		include_once $v;
	}
}

printFooter();

?>
