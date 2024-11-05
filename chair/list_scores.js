// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// Updates checkboxes based on form selection
function selectBoxes() {
	var boxSelectID = document.getElementById("boxselect");
	var boxSelect = boxSelectID.options[boxSelectID.selectedIndex].value;
	var score = document.getElementById("score").value;
	switch (boxSelect) {
		case "all":
			for (i=0; i<document.scoresForm.elements.length; i++) {
				if (document.scoresForm.elements[i].type=="checkbox") {
					document.scoresForm.elements[i].checked=true;
				}
			}
			break;
		case "pending":
			for (i=0; i<document.scoresForm.elements.length; i++) {
				if (document.scoresForm.elements[i].type=="checkbox") {
					if (document.getElementById("decision" + document.scoresForm.elements[i].value).innerHTML == "&nbsp;") {
						document.scoresForm.elements[i].checked=true;
					} else {
						document.scoresForm.elements[i].checked=false;
					}
				}
			}
			break;
		case "gt":
			if ((score == "") || isNaN(score)) {
				alert("Enter a valid score");
			} else {
				intscore = parseFloat(score);
				for (i=0; i<document.scoresForm.elements.length; i++) {
					if (document.scoresForm.elements[i].type=="checkbox") {
						subscore = document.getElementById("subscore" + document.scoresForm.elements[i].value).innerHTML;
						if ( ! isNaN(subscore) && (subscore >= intscore)) {
							document.scoresForm.elements[i].checked=true;
						} else {
							document.scoresForm.elements[i].checked=false;
						}
					}
				}
			}
			break;
		case "eq":
			if ((score == "") || isNaN(score)) {
				alert("Enter a valid score"+score);
			} else {
				intscore = parseFloat(score);
				for (i=0; i<document.scoresForm.elements.length; i++) {
					if (document.scoresForm.elements[i].type=="checkbox") {
						subscore = document.getElementById("subscore" + document.scoresForm.elements[i].value).innerHTML;
						if ( ! isNaN(subscore) && (subscore == intscore)) {
							document.scoresForm.elements[i].checked=true;
						} else {
							document.scoresForm.elements[i].checked=false;
						}
					}
				}
			}
			break;
		case "lt":
			if ((score == "") || isNaN(score)) {
				alert("Enter a valid score"+score);
			} else {
				intscore = parseFloat(score);
				for (i=0; i<document.scoresForm.elements.length; i++) {
					if (document.scoresForm.elements[i].type=="checkbox") {
						subscore = document.getElementById("subscore" + document.scoresForm.elements[i].value).innerHTML;
						if ( ! isNaN(subscore) && (subscore <= intscore)) {
							document.scoresForm.elements[i].checked=true;
						} else {
							document.scoresForm.elements[i].checked=false;
						}
					}
				}
			}
			break;
	}
}

// check that key pressed is a number
function checkNumberFieldKeyPress(e) {
        var key1 = (e.keyCode) ? e.keyCode : e.charCode;
        var thekey = (key1) ? key1 : e.which;
        if ((thekey >= 48) && (thekey <= 57))   // 0-9
                return true;
        switch (thekey) {
                case 0:
                case 8:  // backspace
                case 9:  // tab
                case 37: // left arrow
                case 39: // right arrow
				case 46: // period
                //case 46: // delete
                    return true;
                        break;
        }
        return false;
}
