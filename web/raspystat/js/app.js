var API = new API();
var CHART_DATE_FORMAT = 'h:mm a';
var TOKENKEY = 'thermopi_auth';
var SETTINGS = {};
var CHECK_UPDATES_DELAY = 30000;
var UPDATE_CONTROLLER_DELAY = 1000;
var UPDATE_SENSOR_DELAY = 1000;
var UPDATE_SETTINGS_DELAY = 2000;
var UPDATE_HISTORY_DELAY = 15000;
var REPO_BRANCH = "master";
var REPO_URL = "https://api.github.com/repos/willseph/raspystat/commits?sha="+REPO_BRANCH;
var ISTOUCH =  !!("ontouchstart" in window) || window.navigator.msMaxTouchPoints > 0;

var RED = 'd73333';
var GREEN = '56ca3b';
var BLUE = '33c9d7';
var YELLOW = 'efd110';

var chart;
var currentStatus = '';
var displayedObservedTemp = 0;
var popupDepth = 10;
var popupFunctionTable = {};
var realObservedTemp = -1;
var updateSensorChain;

var $html;
var $body;

var $chart;
var $error;
var $header;
var $loading;
var $loginForm;
var $observedTemp;
var $sensorList;
var $sensorListSettings;
var $statusIcon;

var $statusFan;
var $statusHeat;
var $statusCool;
var $statusWarn;

var $btnFan;
var $btnHeat;
var $btnCool;

var $controlDummy;
var $controlSingle;
var $controlDual;

var $setTempSingle;
var $setTempMin;
var $setTempMax;

var $btnCheckUpdates;
var $btnBack;
var $btnSettings;
var $btnSingleDecr;
var $btnSingleIncr;
var $btnMinDecr;
var $btnMinIncr;
var $btnMaxDecr;
var $btnMaxIncr;
var $btnNewSensor;

var $toggleDarkTheme;

// Methods
function init() {
	jQuery('body').first().addClass(ISTOUCH ? 'mobile' : 'desktop');
	
	$html = jQuery('html').first();
	$body = jQuery('body').first();
	
	$chart = jQuery('#charts-wrapper').first();
	$error = jQuery('#error').first();
	$header = jQuery('#header').first();
	$loading = jQuery('#loading').first();
	$loginForm = jQuery('form.login').first();
	$observedTemp = jQuery('#observed-temp').first();
	$sensorList = jQuery('#sensor-list').first();
	$sensorListSettings = jQuery('#sensor-list-settings').first();
	$statusIcon = jQuery('#status-icon').first();
	
	$statusFan = jQuery('#status-fan').first();
	$statusHeat = jQuery('#status-heat').first();
	$statusCool = jQuery('#status-cool').first();
	$statusWarn = jQuery('#status-warn').first();
	
	$btnFan = jQuery('#btn-fan').first();
	$btnHeat = jQuery('#btn-heat').first();
	$btnCool = jQuery('#btn-cool').first();
	
	$controlDummy = jQuery('#control-dummy').first();
	$controlSingle = jQuery('#control-single').first();
	$controlDual = jQuery('#control-dual').first();
	
	$setTempSingle = jQuery('#set-temp-single').first();
	$setTempMin = jQuery('#set-temp-min').first();
	$setTempMax = jQuery('#set-temp-max').first();
	
	$btnBack = jQuery('.back-btn').first();
	$btnSettings = jQuery('#settings-button').first();
	$btnCheckUpdates = jQuery('#check-for-updates-btn').first();
	
	$btnSingleDecr = jQuery('#btn-decrement-single').first();
	$btnSingleIncr = jQuery('#btn-increment-single').first();
	$btnMinDecr = jQuery('#btn-decrement-min').first();
	$btnMinIncr = jQuery('#btn-increment-min').first();
	$btnMaxDecr = jQuery('#btn-decrement-max').first();
	$btnMaxIncr = jQuery('#btn-increment-max').first();
	
	$btnNewSensor = jQuery('#btn-new-sensor').first();
	
	$toggleDarkTheme = jQuery('#toggle-dark-theme').first();
	
	Chart.defaults.global.defaultFontFamily = "'Oxygen',Helvetica, Arial, sans-serif";
	
	$btnFan.click(toggleSettingFan);
	$btnHeat.click(toggleSettingHeat);
	$btnCool.click(toggleSettingCool);
	
	$btnBack.click(showHome);
	$btnSettings.click(showSettings);
	
	$btnSingleDecr.click(setTempSingleDecrement);
	$btnSingleIncr.click(setTempSingleIncrement);
	$btnMinDecr.click(setTempMinDecrement);
	$btnMinIncr.click(setTempMinIncrement);
	$btnMaxDecr.click(setTempMaxDecrement);
	$btnMaxIncr.click(setTempMaxIncrement);
	
	$btnNewSensor.click(showNewSensorPopup);
	
	$toggleDarkTheme.change(toggleTheme);
	
	$error.find('button').click(function() {hide($error);});
	$loginForm.submit(checkLoginSubmission);
	
	checkAuthThen(loadDashboard, logout);
}

function addSensor($content) {
	var $error = $content.find('.input-error');
	
	var name = $content.find('input').val().trim();
	if (name.length < 1 || name.length > 64) {
		$error.text ('Name must be between 1 and 64 characters.');
		return false;
	}
	
	API.addSensor(getToken(), name, function(resp) {
		if (resp.success === true) {
			closePopups ();
		} else {
			$error.text (resp.message);
			if (resp.code == 'auth') {
				logout();
			}
		}
	});
	
	return false;
}

function checkAuthThen(onValid, onInvalid) {
	show($loading);
	
	var token = getToken();
	
	API.checkToken(token, function(resp) {
		if(resp.success){
			onValid();
		} else {
			onInvalid();
		}
	});
}

function checkForUpdates(callback) {
	callback = callback || function(r){console.log(r);};
	
	jQuery.getJSON( 
		REPO_URL, 
		{}, 
		function(commits) { 
			 if (!commits || commits.length < 1) {
				 callback({success:false, data:commits});
				 return;
			 }
			 
			 var headCommit = commits[0];
			 var sha = headCommit.sha;
			 
			 var context = {sha:sha};
			 
			 API.sensors(getToken(), function (r, context) {
				 if(r && r.success && r.sensors) {
					 var sensors = r.sensors;
					 context['sensors'] = sensors;
					 
					  API.controller(getToken(), function (s, context) {
						 if(s && s.success && s.controller) {
							 var sensors = context.sensors;
							 var controller = s.controller;
							 var sha = context.sha;
							 
							 var sensorsWithUpdate = [];
							 var sensorsDirty = [];
							 
							 var controllerCanUpdate = false;
							 var controllerIsDirty = false;
							 
							 // Sensors
							 for (var i=0; i<sensors.length; i++) {
								 var sensor = sensors[i];
								 if(!sensor.ver || sensor.warning) continue;
								 var ver = sensor.ver.split(',');
								 if(!ver || ver.length !== 3) continue;
								 
								 var branch = ver[0];
								 var commit = ver[1];
								 var status = ver[2];
								 
								 if(branch !== REPO_BRANCH || status !== "clean") {
									 sensorsDirty.push(sensor.id);
								 } else if(commit !== sha) {
								 	sensorsWithUpdate.push(sensor.id);
								 }
							 }
							 
							 // Controller
							 if (controller.ver && !controller.warning) {
								 var ver = controller.ver.split(',');
								 if(ver && ver.length === 3) {
									 var branch = ver[0];
									 var commit = ver[1];
									 var status = ver[2];
									 
									 if(branch !== REPO_BRANCH || status !== "clean") {
										 controllerIsDirty = true;
									 } else if(commit !== sha) {
									 	controllerCanUpdate = true;
									 }
								 }
							 }
							 
							 var result = {
								 success:true,
								sha: sha,
								sensorsWithUpdate: sensorsWithUpdate,
								sensorsDirty: sensorsDirty,
								controllerCanUpdate: controllerCanUpdate,
								controllerIsDirty: controllerIsDirty
							 };
							 
							 callback(result);
						 }
					 }, context);
				 }
			 }, context);
		}
	);
}

function checkLoginSubmission(e) {
	e.preventDefault();
	show($loading);
	hide($error);
	hide(jQuery('#login'));
	
	user = $loginForm.find('input[name="login-user"]').val();
	pass = $loginForm.find('input[name="login-password"]').val();
	
	API.auth(user, pass, function(resp) {
		hide($loading);
		
		if (resp.success == true && resp.token) {
			hide($error);
			localStorage.setItem(TOKENKEY, resp.token);
			loadDashboard();
		} else {
			showError(resp.message);
			logout();
		}
	});
}

function closePopup ($popup) {
	$popup.find('button.popup-button').each(function() {
		var $btn = jQuery(this);
		var uuid = $btn.attr('uuid');
		if (uuid in popupFunctionTable) {
			delete popupFunctionTable[uuid];
		}
	});
	$popup.remove();
}

function closePopups () {
	jQuery('.popup').each(function() {
		closePopup(jQuery(this));
	});
}

function createSensor(sensor) {
	var html = 
'<li class="sensor card" sensor-id="'+sensor.id+'"><div class="row">'+
'<div class="col-xs-2 ta-c"><i class="icon sensor-icon"></i></div>'+
'<div class="col-xs-6 sensor-name"></div>'+
'<div class="col-xs-2 ta-c"><strong class="sensor-temp"></strong></div>'+
'<div class="col-xs-2 ta-c"><button class="sensor-toggle"><i class="icon icon-eye"></i><i class="icon icon-eye-blocked"></i></button></div>'+
'</div></li>';
	var $row = jQuery(html);
	$sensorList.append($row);
	
	var $toggle = $row.find('button.sensor-toggle').first();
	$toggle.click(function() {
		var $btn = jQuery(this);
		$btn.off('mouseenter mouseleave');
		toggleSensor($btn.parent().parent().parent(), $btn);
	});
	updateSensor(sensor, $row, true);
	
	var htmlSettings = 
'<li class="sensor card" sensor-id="'+sensor.id+'"><div class="row">'+
'<div class="col-xs-2 ta-c"><i class="icon sensor-icon"></i></div>'+
'<div class="col-xs-6 sensor-name"></div>'+
'<div class="col-xs-2 ta-c"><strong class="sensor-temp"></strong></div>'+
'<div class="col-xs-2 ta-c"><button class="sensor-edit"><i class="icon icon-pencil"></i></button></div>'+
'</div><div class="row"><div class="col-xs-12"><div class="sensor-info select cur-a">'+
'</div></div></div></li>';
	var $rowSettings = jQuery(htmlSettings);
	$sensorListSettings.append($rowSettings);
	updateSensor(sensor, $rowSettings, false);
	
	var commit = sensor.ver.split(',');
	if (commit && commit.length > 2) {
		commit = commit[1];
		if (commit && commit.length >= 32) {
			commit = commit.substr(0, 8);
		} else {
			commit = 'unknown';
		}
	} else {
		commit = 'unknown';
	}
	
	$rowSettings.find('.sensor-info').html(
		'<strong>Secret: </strong> <code>'+sensor.secret+'</code><br/>'+
		'<strong>Ver: </strong> <code>'+commit+'</code>'
	);
}

function generateUUID () { // Public Domain/MIT
	var d = new Date().getTime();
	if (typeof performance !== 'undefined' && typeof performance.now === 'function'){
		d += performance.now(); //use high-precision timer if available
	}
	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
		var r = (d + Math.random() * 16) % 16 | 0;
		d = Math.floor(d / 16);
		return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
	});
}

function getTemperatureStringParts(intTemp, convertFormat, decimalPoints) {
	decimalPoints = decimalPoints || 2;
	
	var displayTemp = intTemp / 1000.0;
	if(convertFormat && SETTINGS.format === 'F')
		displayTemp = c2f(displayTemp);
		
	displayTemp = Math.round(displayTemp*100.0)/100.0;
	var displayTempString = displayTemp.toFixed(decimalPoints);
	var displayTempDecimalString = displayTempString.substr(displayTempString.indexOf('.'), decimalPoints+1);
	displayTempString = displayTempString.substring(0, displayTempString.indexOf('.'));
	
	return [ displayTempString, displayTempDecimalString ];
}

function getTemperatureStringHtml(intTemp, convertFormat, addDegree, decimalPoints) {
	var tempStringParts = getTemperatureStringParts(intTemp, convertFormat, decimalPoints);
	var html = tempStringParts[0]+'<span class="deg-small">'+tempStringParts[1]+'</span>';
	if(addDegree) {
		html = html+'&deg;';
	}
	return html;
}

function getToken() {
	return localStorage.getItem(TOKENKEY);
}

function hide($e) {
	$e.addClass('hidden');
}

function loadDashboard() {
	API.settings(getToken(), function(resp) {
		if(resp.success) {
			SETTINGS = resp.settings;
			updateSettings();
			
			API.controller(getToken(), function(resp) {
				if(resp.success && resp.controller) {
					updateController (resp.controller);
					hide($loading);
					show($btnSettings);
					hide(jQuery('#login'));
					show(jQuery('#main'));
					updateObservedTemperature([]);
					
					loadWindowByHash();
					
					tryCheckUpdatesLoop();
					tryUpdateControllerLoop();
					tryUpdateHistoryLoop();
					tryUpdateSensorsLoop();
					tryUpdateSettingsLoop();
				} else {
					console.log(resp);
					logout();
				}
			});
		} else {
			console.log(resp);
			logout();
		}
	});
}

function loadWindowByHash() {
	var hash = window.location.hash.toLowerCase().trim();
	while(hash.substr(0, 1) === '#') {
		hash = hash.substr(1, hash.length - 1);
	}	
	
	hide(jQuery('#main'));
	hide(jQuery('#settings'));

	if(hash === '') {
		show(jQuery('#main'));
	} else {
		show(jQuery('#'+hash));
	}
}

function logout() {
	setStatus('');
	$html.removeClass('dark');
	hide($loading);
	localStorage.removeItem(TOKENKEY);
	show(jQuery('#login'));
	hide(jQuery('#main'));
	hide(jQuery('#settings'));
	hide($btnSettings);
	closePopups();
	
	window.location.hash = '';
	
	jQuery('#login-user-field').select();
}

function popupButtonClicked () {
	var $button = jQuery(this);
	var $popup = $button.parent().parent().parent().parent().parent();
	var uuid = $button.attr('uuid');
	var close = true;
	if (uuid in popupFunctionTable) {
		var f = popupFunctionTable[uuid];
		if (!f ($popup.find('.popup-content'))) {
			close = false;
		}
	}
	if (close) {
		closePopup ($popup);
	}
}

function pushSettings() {
	API.updateSettings(getToken(), SETTINGS.min, SETTINGS.max, SETTINGS.fan, SETTINGS.heat, SETTINGS.cool, SETTINGS.format, SETTINGS.theme, function(resp) {
		if(resp.success) {
			SETTINGS = resp.settings;
			updateSettings();
		} else {
			console.log(resp);
			if(resp.code == 'auth') {
				logout();
			}
		}
	});
}

function removeHash () { 
    history.pushState("", document.title, window.location.pathname + window.location.search);
}

function setAccentColor(color) {
	if(color) {
		updateMetaThemeColor(color);
		$header.css('border-bottom-color','#'+color);
	} else {
		updateMetaThemeColor('');
		$header.css('border-bottom-color','');
	}
}

function setStatus(status) {
	if (currentStatus === status) {
		return;
	}
	currentStatus = status;
	
	hide(jQuery('.status-icon'));
	setAccentColor(false);
	$observedTemp.removeClass('red');
	$observedTemp.removeClass('green');
	$observedTemp.removeClass('blue');
	$observedTemp.removeClass('yellow');
	
	$statusIcon.removeClass('red');
	$statusIcon.removeClass('green');
	$statusIcon.removeClass('blue');
	$statusIcon.removeClass('yellow');
			
	switch (status) {
		case 'fan':
			setAccentColor(GREEN);
			show($statusFan);
			$observedTemp.addClass('green');
			$statusIcon.addClass('green');
			break;
		case 'heat':
			setAccentColor(RED);
			show($statusHeat);
			$observedTemp.addClass('red');
			$statusIcon.addClass('red');
			break;
		case 'cool':
			setAccentColor(BLUE);
			show($statusCool);
			$observedTemp.addClass('blue');
			$statusIcon.addClass('blue');
			break;
		case 'warn':
			setAccentColor(YELLOW);
			show($statusWarn);
			$observedTemp.addClass('yellow');
			$statusIcon.addClass('yellow');
			break;
	}
}

function setTempSingleDecrement() { 
	if(SETTINGS.heat) SETTINGS.min -= 500;
	if(SETTINGS.cool) SETTINGS.max -= 500;
	pushSettings();
}
function setTempSingleIncrement() { 
	if(SETTINGS.heat) SETTINGS.min += 500;
	if(SETTINGS.cool) SETTINGS.max += 500;
	pushSettings();
}
function setTempMinDecrement() { 
	SETTINGS.min -= 500;
	pushSettings();
}
function setTempMinIncrement() { 
	SETTINGS.min += 500;
	pushSettings();
}
function setTempMaxDecrement() { 
	SETTINGS.max -= 500;
	pushSettings();
}
function setTempMaxIncrement() { 
	SETTINGS.max += 500;
	pushSettings();
}

function show($e) {
	$e.removeClass('hidden');
}

function showError(msg) {
	jQuery('#error-msg').text(msg);
	show($error);
}

function showHome() {
	removeHash ();
	loadWindowByHash ();
}

function showNewSensorPopup () {
	var html =
	'<h4>New Sensor</h4>'+
	'<p>After creating the sensor, you will be able to use the <strong>secret</strong> to assign to your Raspberry Pi.</p>'+
	'<form method="post" action="">'+
		'<div class="form-group">'+
			'<input type="text" class="form-control" placeholder="Sensor name" name="login-user" maxlength="64">'+
			'<div class="input-error"></div>'+
		'</div>'+
	'</form>';
	
	showPopup (html, [
		{html: 'Save', func: addSensor, btnClass:'affirmative'},
		{html: 'Cancel', func:function(c){return true;}, btnClass:'cancel'}
	], function(){}, true);
}

function showPopup (html, buttons, onDismiss, dismissibleFromOutside) {
	popupDepth++;
	
	var $popup = jQuery('<div class="popup" p="'+popupDepth+'" style="z-index:'+popupDepth+';"></div>');
	
	var $popupShade = jQuery('<div class="popup-shade"></div>');
	if (dismissibleFromOutside) {
		var $popupShadeBtn = jQuery('<button class="popup-shade-btn"></button>');
		$popupShadeBtn.click (function () {
			closePopup($popup);
			if (onDismiss) {
				onDismiss();
			}
		});
		$popupShade.append ($popupShadeBtn);
	}
	$popup.append ($popupShade);
	
	var $popupWell = jQuery('<div class="popup-well"></div>');
	
	var $popupContent = jQuery('<div class="popup-content"></div>');
	$popupContent.append(html);
	$popupWell.append ($popupContent);
	
	if (buttons.length > 0) {
		var $buttonsContainer = jQuery('<div class="popup-buttons"></div>');
		var $buttonList = jQuery('<ul></ul>');
	
		for (var i=0; i<buttons.length; i++) {
			var buttonHtml = buttons[i].html;
			var buttonFunc = buttons[i].func;
			var buttonClass = buttons[i].btnClass;
			
			var uuid = generateUUID ();
			
			var $button = jQuery('<button class="popup-button '+buttonClass+'"></button>');
			$button.attr('uuid',uuid);
			
			popupFunctionTable[uuid] = buttonFunc;
			
			var $buttonItem = jQuery('<li></li>');
			$buttonItem.append($button);
			$buttonList.append ($buttonItem);
			
			$button.html (buttonHtml);
			$button.click (popupButtonClicked);
		}
		
		$buttonsContainer.append($buttonList);
		$popupWell.append ($buttonsContainer);
	}
	
	$popup.append ($popupWell);
	$body.append ($popup);
	return $popup;
}

function showPopupBasicHtml (title, html, btnText, onDismiss, dismissibleFromOutside) {
	
}

function showPopupBasicText (title, message, btnText, onDismiss, dismissibleFromOutside) {
	
}

function showPopupConfirmHtml (title, html, okBtnText, cancelBtnText, onOk, onCancel, dismissibleFromOutside) {
}

function showPopupConfirmText (title, message, okBtnText, cancelBtnText, onOk, onCancel, dismissibleFromOutside) {
	
}

function showSettings() {
	window.location.hash = '#settings';
	loadWindowByHash ();
}

function showSuccess(msg) {
	jQuery('#success-msg').text(msg);
	show(jQuery('#success'));
}

function toggleSensor($row, $button) {
	var newStatus = $row.hasClass('off');
	var id = $row.attr('sensor-id');
	
	API.observe(getToken(), id, newStatus, function(resp) {
		if(resp.success) {
			updateSensors(resp.sensors);
		} else {
			if(resp.code == 'auth') {
				logout();
			}
		}
	});
}

function toggleSettingCool() {
	SETTINGS.cool = !SETTINGS.cool;
	pushSettings();
}

function toggleSettingFan() {
	SETTINGS.fan = !SETTINGS.fan;
	pushSettings();
}

function toggleSettingHeat() {
	SETTINGS.heat = !SETTINGS.heat;
	pushSettings();
}

function toggleTheme() {
	var on = this.checked;
	SETTINGS.theme = on ? 'dark' : 'light';
	pushSettings();
	$chart.empty();
	
	API.history (getToken(), SETTINGS.historyperiod, function(resp) {
		if(resp.success && resp.history) {
			updateHistory (resp.history);
			setTimeout(tryUpdateHistoryLoop, UPDATE_HISTORY_DELAY);
		} else {
			if(resp.code == 'auth') {
				logout ();
			}
		}
	});
}

function tryCheckUpdatesLoop () {
	checkForUpdates(function(resp) {
		setTimeout(tryCheckUpdatesLoop, CHECK_UPDATES_DELAY);
	});
}

function tryUpdateControllerLoop() {
	API.controller (getToken(), function(resp) {
		if(resp.success && resp.controller) {
			updateController(resp.controller);
			setTimeout(tryUpdateControllerLoop, UPDATE_CONTROLLER_DELAY);
		} else {
			if(resp.code == 'auth') {
				logout ();
			}
		}
	});
}

function tryUpdateHistoryLoop() {
	API.history (getToken(), SETTINGS.historyperiod, function(resp) {
		if(resp.success && resp.history) {
			updateHistory (resp.history);
			setTimeout(tryUpdateHistoryLoop, UPDATE_HISTORY_DELAY);
		} else {
			if(resp.code == 'auth') {
				logout ();
			}
		}
	});
}

function tryUpdateSensorsLoop() {
	API.sensors(getToken(), function(resp) {
		if(resp.success) {
			updateSensors(resp.sensors);
			setTimeout(tryUpdateSensorsLoop, UPDATE_SENSOR_DELAY);
		} else {
			if(resp.code == 'auth') {
				logout();
			}
		}
	});
}

function tryUpdateSettingsLoop() {
	API.settings(getToken(), function(resp) {
		if(resp.success && resp.settings) {
			SETTINGS = resp.settings;
			updateSettings();
			setTimeout(tryUpdateSettingsLoop, UPDATE_SETTINGS_DELAY);
		} else {
			if(resp.code == 'auth') {
				logout();
			}
		}
	});
}

function updateController (controller) {
	var newStatus = controller.warning ? 'warn' : controller.status;
	setStatus(newStatus);
}

function updateHistory (history) {
	var colors = ['#33a9d7', '#ad3bca', '#d73333', '#56ca3b', '#efd110',  '#3b5dca', '#afafaf'];
	var currentColor = 0;
	var datasets = [];
	
	var lightTheme = SETTINGS.theme === 'light';
	
	datasets.push({
		label: 'Avg.',
		data: history.avg,
		backgroundColor: lightTheme ? 'rgba(15,15,15,0.75)' : 'rgba(255,255,255,0.75)',
		borderColor: lightTheme ? 'rgba(15,15,15,0.75)' : 'rgba(255,255,255,0.75)',
		fill: false,
		pointRadius: 0,
		borderWidth: 1,
		spanGaps:false
	});
	
	for (var sensor in history.sensors) {
		if (history.sensors.hasOwnProperty (sensor)) {
			var color = colors[currentColor];
			currentColor = (currentColor+1) % colors.length;
			
			var newSet = {
				label: sensor,
				data: history.sensors[sensor],
				backgroundColor:color,
				borderColor: color,
				fill: false,
				pointRadius: 0,
				borderWidth: 3,
				spanGaps:false
			};
			
			datasets.push(newSet);
		}
	}
	
	if (chart) {
		chart.destroy ();
		chart = null;
	}
	$chart.empty ();
	
	var $chartContainer = jQuery('<canvas></canvas>');
	$chartContainer.addClass('chart-container');
	$chart.append($chartContainer);
	
		
	chart = new Chart($chartContainer, {
		type: 'line',
		data: {
			datasets: datasets
		},
		options: {
			tooltips: {enabled: false},
			hover: {mode: null},
			animation: {
				duration: 0
			},
			responsive:true,
			maintainAspectRatio: false,
			layout: {
				padding: {
					left: 20,
					right: 20,
					top: 0,
					bottom: 10
				}
			},
			legend: {
				onClick: null
			},
			scales: {
				xAxes: [{
					type: "time",
					display: true,
					scaleLabel: {
						display: false
					},
					time: {
						displayFormats: {
							'millisecond': CHART_DATE_FORMAT,
							'second': CHART_DATE_FORMAT,
							'minute': CHART_DATE_FORMAT,
							'hour': CHART_DATE_FORMAT,
							'day': CHART_DATE_FORMAT,
							'week': CHART_DATE_FORMAT,
							'month': CHART_DATE_FORMAT,
							'quarter': CHART_DATE_FORMAT,
							'year': CHART_DATE_FORMAT
						}
					},
					gridLines: {
						color: "rgba(0, 0, 0, 0.05)",
					}
				}],
				yAxes: [{
					display: true,
					scaleLabel: {
						display: false
					},
					ticks: {
						// Include a dollar sign in the ticks
						callback: function(value, index, values) {
							var displayTemp = value / 1000.0;
							if (SETTINGS.format === 'F')
								displayTemp = c2f(displayTemp);
								
							displayTemp = Math.round(displayTemp*100.0)/100.0;
							return  displayTemp+' °'+SETTINGS.format;
						}
					},
					gridLines: {
						color: "rgba(0, 0, 0, 0.05)",
					}
				}]
			}
		}
	});
}

function updateMetaThemeColor (color) {
	jQuery('meta[name=theme-color]').remove();
	jQuery('head').append('<meta name="theme-color" content="#'+color+'">');
}

function updateObservedTemperature (sensors) {
	var sumTemps = 0;
	var numSensors = 0;
	
	sensors.forEach(function(sensor) {
		if(sensor.warning === false && sensor.observed === true) {
			sumTemps += sensor.temp;
			numSensors++;
		}
	});
	
	realObservedTemp = numSensors>0 ? Math.round(1.0*sumTemps/numSensors) : 0;
	
	if(realObservedTemp < 1) {
		$observedTemp.html('<i class="icon icon-cancel-circle2"></i>');
	} else {
		$observedTemp.html(getTemperatureStringHtml(realObservedTemp, true, true, 2));
	}
}

function updateSensor(sensor, $row, showWarningIcon) {
	var $name = $row.find('.sensor-name');
	$name.text(sensor.name);
	
	var $icon = $row.find('.sensor-icon');
	$icon.attr('class','');
	$icon.addClass('icon');
	$icon.addClass('sensor-icon');
	$icon.addClass('icon-'+(sensor.warning ? 'warning2' : sensor.icon));
	
	var $temp = $row.find('.sensor-temp');
	if(sensor.warning) {
		$temp.html('--&nbsp;');
	} else {
		$temp.html(getTemperatureStringHtml(sensor.temp, true, true, 2));
	}
	
	$row.removeClass('on');
	$row.removeClass('off');
	$row.removeClass('warning');
	$row.addClass(sensor.observed ? 'on' : 'off');
	
	if(sensor.warning === true && showWarningIcon) {
		$row.addClass('warning');
	}
}

function updateSensors(sensors) {
	updateObservedTemperature(sensors);
	
	var sensorIds = {};
	for(var i=0; i<sensors.length; i++) {
		var sensor = sensors[i];
		sensorIds[sensor.id] = sensor;
	}
	
	var removeSensorFunc = function($row, showWarningIcon) {
		var id = $row.attr('sensor-id');
		if(!id || (id in sensorIds) !== true) {
			$row.remove();
		} else {
			updateSensor(sensorIds[id], $row, showWarningIcon);
			sensors.removeIf(function(s){return s.id == id;});
		}
	};
	
	$sensorList.find('li').each(function () { removeSensorFunc (jQuery(this), true); });
	$sensorListSettings.find('li').each(function () { removeSensorFunc (jQuery(this), false); });
	
	for(var i=0; i<sensors.length; i++) {
		createSensor(sensors[i]);
	}
}

function updateSettings() {
	$btnFan.removeClass('on');
	$btnFan.removeClass('off');
	$btnFan.addClass(SETTINGS.fan === true ? 'on' : 'off');
	
	$btnHeat.removeClass('on');
	$btnHeat.removeClass('off');
	$btnHeat.addClass(SETTINGS.heat === true ? 'on' : 'off');
	
	$btnCool.removeClass('on');
	$btnCool.removeClass('off');
	$btnCool.addClass(SETTINGS.cool === true ? 'on' : 'off');
	
	var controls = 0;
	controls += SETTINGS.heat ? 1 : 0;
	controls += SETTINGS.cool ? 1 : 0;
	
	if (controls == 0) {
		show($controlDummy);
		hide($controlSingle);
		hide($controlDual);
	} else if (controls == 1) {
		hide($controlDummy);
		show($controlSingle);
		hide($controlDual);
	} else if (controls == 2) {
		hide($controlDummy);
		hide($controlSingle);
		show($controlDual);
	}
	
	if (SETTINGS.heat && SETTINGS.cool) {
		$setTempMin.html(getTemperatureStringHtml(SETTINGS.min, false, false, 1));
		$setTempMax.html(getTemperatureStringHtml(SETTINGS.max, false, false, 1));
	} else if (SETTINGS.heat) {
		$setTempSingle.html(getTemperatureStringHtml(SETTINGS.min, false, false, 1));
	} else if (SETTINGS.cool) {
		$setTempSingle.html(getTemperatureStringHtml(SETTINGS.max, false, false, 1));
	}
	
	if (SETTINGS.theme == 'dark') {
		$html.addClass('dark');
		Chart.defaults.global.defaultFontColor = "#f9f9f9";
		$toggleDarkTheme.prop('checked', true);
	} else {
		$html.removeClass('dark');
		Chart.defaults.global.defaultFontColor = "#223";
		$toggleDarkTheme.prop('checked', false);
	}
}

// Main
function main() {
	init();
}

jQuery(document).ready(main);
