// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

var oc_ftAR = new Array();
var oc_accAR = new Array();

// Updates checkboxes based on form selection
function selectBoxes() {
	var boxSelectID = document.getElementById("boxselect");
	var boxSelect = boxSelectID.options[boxSelectID.selectedIndex].value;
	if (boxSelect == "all") {
		for (i=0; i<document.subsForm.elements.length; i++) {
			if (document.subsForm.elements[i].type=="checkbox") {
				document.subsForm.elements[i].checked=true;
			}
		}
	} else if (boxSelect in oc_ftAR) {
		for (i=0; i<document.subsForm.elements.length; i++) {
			if (document.subsForm.elements[i].type=="checkbox") {
				if (oc_ftAR[boxSelect].indexOf(parseInt(document.subsForm.elements[i].value)) >= 0) {
					document.subsForm.elements[i].checked=true;
				} else {
					document.subsForm.elements[i].checked=false;
				}
			}
		}
	} else if (boxSelect in oc_accAR) {
		for (i=0; i<document.subsForm.elements.length; i++) {
			if (document.subsForm.elements[i].type=="checkbox") {
				if (oc_accAR[boxSelect].indexOf(parseInt(document.subsForm.elements[i].value)) >= 0) {
					document.subsForm.elements[i].checked=true;
				} else {
					document.subsForm.elements[i].checked=false;
				}
			}
		}
	}
}
