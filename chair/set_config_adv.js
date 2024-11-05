// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2022 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

// init http request
var config_http = false;
try {
	config_http = new XMLHttpRequest();
} catch (trymicrosoft) {
	try {
		config_http = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (othermicrosoft) {
		try {
			config_http = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (failed) {
			config_http = false;
		}
	}
}
// number of notices awaiting call back
var OC_notices = 0;
// notice div ID (see init())
var OC_notice = false;
// default timeout
var TimeOut = 2000;

function oc_init() {
	OC_notice = document.getElementById("oc_notice");
}
function hideNotice() {
	OC_notices--;
	if (OC_notices <= 0) {
		document.getElementById("oc_notice").style.display = "none";
	}
}
function showNotice(noticeClass, noticeHTML, noticeTimeout) {
	OC_notice.className = noticeClass;
	OC_notice.innerHTML = noticeHTML;
	TimeOut = noticeTimeout;
	// display notice
	OC_notice.style.display = "block";
	// set timeout to hide notice box, but only after last notice done displaying
	OC_notices++;
	setTimeout('hideNotice()', TimeOut);
}
function clearFields() {
	document.getElementById('fields').style.display = 'none';
	document.getElementById('name').innerHTML = '';
	document.getElementById('description').innherHTML = '';
	document.getElementById('parse').innherHTML = '';
	document.getElementById('value').value = '';
}

function updateSettingValueCallback() {
	if (config_http.readyState == 4) {
		// Check for invalid response - likely due to time out?
		if ((config_http.status != 200) || (config_http.responseText == '')) {
			showNotice('ocerror', 'Unable to update setting.<br />Use standard configuration page or edit value in database config table', 30000);
		} else { // display server response as appropriate
			var Response = eval("(" + config_http.responseText + ")");
			if (Response['error'] == '') {
				if (Response['success']) {
					showNotice('ocnotice', Response['success'], 3000);
				} else {
					showNotice('ocerror', 'Unable to update setting', 5000);
				}
			} else {
				showNotice('ocerror', Response['error'], 5000);
			}
		}
	}
}
function updateSettingValue(octoken) {
	if (config_http) { 
		var params = "m=" + encodeURIComponent(document.getElementById('module').value) + 
			"&s=" + encodeURIComponent(document.getElementById('setting').value) +
			"&v=" + encodeURIComponent(document.getElementById('value').value) +
			"&token=" + encodeURIComponent(octoken);
		config_http.open("POST","set_config_adv-update.php", true);
		config_http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		config_http.onreadystatechange = updateSettingValueCallback;
		config_http.send(params);
		return(false);
	} else {
		return(true);
	}
}

function updateSettingCallback() {
	if (config_http.readyState == 4) {
		// Check for invalid response - likely due to time out?
		if ((config_http.status != 200) || (config_http.responseText == '')) {
			showNotice('ocerror', 'Unable to retrieve module settings.<br />Use standard configuration page or edit value in database config table', 30000);
		} else { // display server response as appropriate
			var Response = eval("(" + config_http.responseText + ")");
			if (Response['error'] == "") {
				if (Response['setting']) {
					document.getElementById('name').innerHTML = Response['setting']['name'];
					document.getElementById('description').innerHTML = Response['setting']['description'];
					if (Response['setting']['parse'] == 1) {
						document.getElementById('parse').innerHTML = 'Yes';
					} else {
						document.getElementById('parse').innerHTML = 'No';
					}
					document.getElementById('value').value = Response['setting']['value'];
					document.getElementById('fields').style.display = 'block';
				}
			} else {
				showNotice('ocerror', Response['error'], 5000);
			}
		}
	}
}
function updateSetting(obj, octoken) {
	clearFields();
	document.getElementById("oc_notice").style.display = "none";
	if (obj.value != '') {
		if (config_http) { 
			var params = "m=" + encodeURIComponent(document.getElementById('module').value) + 
				"&s=" + encodeURIComponent(obj.value) +
				"&token=" + encodeURIComponent(octoken);
			config_http.open("POST","set_config_adv-setting.php", true);
			config_http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			config_http.onreadystatechange = updateSettingCallback;
			config_http.send(params);
		} else {
			alert('Your browser has denied this operation.  Please use standard config form, or edit values in the database config table');
			return(false);
		}
	}
}

function updateModuleCallback() {
	if (config_http.readyState == 4) {
		// Check for invalid response - likely due to time out?
		if (config_http.status != 200) {
			showNotice('ocerror', 'Unable to retrieve module settings.  Use standard configuration page or edit value in database config table', 30000);
		} else { // display server response as appropriate
			var Response = eval("(" + config_http.responseText + ")");
			if (Response['error'] == "") {
				if (Response['settings']) {
					var i = 1;
					for (v in Response['settings']) {
						document.getElementById('setting').options[i++] = new Option(Response['settings'][v], Response['settings'][v], false, false);
					}
					document.getElementById('settingMenu').style.display = "block";
				}
			} else {
				showNotice('ocerror', Response['error'], 5000);
			}
		}
	}
}
function updateModule(obj, octoken) {
	clearFields();
	document.getElementById("oc_notice").style.display = "none";
	document.getElementById("settingMenu").style.display = "none";
	var settingObj = document.getElementById('setting');
	settingObj.options.length = 1;
	if (obj.value != '') {
		if (config_http) { 
			var params = "m=" + encodeURIComponent(obj.value) + 
				"&token=" + encodeURIComponent(octoken);
			config_http.open("POST","set_config_adv-module.php", true);
			config_http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			config_http.onreadystatechange = updateModuleCallback;
			config_http.send(params);
		} else {
			alert('Your browser has denied this operation.  Please use standard config form, or edit values in the database config table');
			return(false);
		}
	}
}