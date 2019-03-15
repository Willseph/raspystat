function API()  {
	this.buildUri = function(call) {
		return '/raspystat/call/'+call;
	};
	
	this.get = function(call, data, callback, context) {
		try {
			callback = callback || function(r){console.log(r);};
			jQuery.getJSON( 
				this.buildUri(call), 
				data, 
				function(resp) { 
					callback(resp, context); 
				} 
			);
		} catch(err) {
			console.log({'error':err});
			location.reload (true);
		}
	};
	
	this.post = function(call, data, callback, progress, context) {
		try {
			callback = callback || function(r){console.log(r);};
			$.ajax({
				xhr: function() {
					var xhr = new window.XMLHttpRequest();
					xhr.upload.addEventListener("progress", function(evt) {
						if (evt.lengthComputable) {
							var percentComplete = evt.loaded / evt.total;
							if(progress)
								progress(percentComplete, context);
						}
					}, false);
					return xhr;
				},
				type: 'POST',
				url: this.buildUri(call),
				data: data,
				success: function(resp) { 
					callback(resp, context); 
				}
			});
		} catch(err) {
			console.log({'error':err});
			location.reload (true);
		}
	};
	
	////////////////////////////////////////////////////////////////////////////////
	
	this.addSensor = function(token, name, callback, context) {
		this.post('addsensor', {token: token, name: name}, callback, context);
	};
	
	this.auth = function(user, pass, callback, context) {
		this.get('auth', {user: user, pass: pass}, callback, context);
	};
	
	this.checkToken = function(token, callback, context) {
		this.get('check', {token: token}, callback, context);
	};
	
	this.controller = function(token, callback, context) {
		this.get('controller', {token: token}, callback, context);
	};
	
	this.history = function(token, time, callback, context) {
		this.get('history', {token: token, time: time}, callback, context);
	};
	
	this.initialUser = function(user, pass, callback, context) {
		this.post('initialuser', {user: user, pass: pass}, callback, context);
	};
	
	this.observe = function(token, sensor, observe, callback, context) {
		this.post('observe', {token: token, sensor: sensor, observe: (observe ? '1' : '0')}, callback, context);
	};
	
	this.sensors = function(token, callback, context) {
		this.get('sensors', {token: token}, callback, context);
	};
	
	this.settings = function(token, callback, context) {
		this.get('settings', {token: token}, callback, context);
	};
	
	this.updateSettings = function(token, min, max, fan, heat, cool, format, theme, callback, context) {
		this.post('updatesettings', {token: token, min: min, max: max, fan: (fan ? '1' : '0'), heat: (heat ? '1' : '0'), cool: (cool ? '1' : '0'), format: format, theme: theme}, callback, context);
	};
}
