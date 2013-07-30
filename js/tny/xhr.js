(function() {
    if (typeof(Tny) === 'undefined')
	return;
    
    function xhr_async(url, request, callback, error_callback, timeout) {
	var timeout_val;
	var http = new XMLHttpRequest();
	var mode = request ? "POST" : "GET";
	http.open(mode, url, true);
	if (mode == "POST") {
	    http.setRequestHeader('Content-Type',
				  'application/x-www-form-urlencoded');
	}
	http.onreadystatechange = function() {
            if (http.readyState == 4) {
		clearTimeout(timeout_val);
		if (http.status == 200) {
		    try {
			var res = eval('(' + http.responseText + ')');
		    } catch (err) {
			if (error_callback) {
			    var error = {
				type: 'server',
				message: 'Invalid JSON: ' + http.responseText
			    }
			    error_callback(error);
			}
			return;
		    }
		    callback(res);
		} else {
		    if (error_callback) {
			var error = {
			    type: 'server',
			    message: 'xhr request rejected by server'
			}
			error_callback(error);
		    }
		}
            }
	}

	var timeout_callback = function() {
	    http.abort();
	    clearTimeout(timeout_val);
	    if (error_callback) {
		var error = {
		    type: 'timeout',
		    message: 'xhr request timed out'
		}
		error_callback(error);
	    }
	}
        if (timeout && timeout > 0)
	    timeout_val = setTimeout(timeout_callback, timeout);
	
	http.send(request);
	return http;
    }

    function xhr_sync(url,request) {
	var http = new XMLHttpRequest();
	var mode = request ? "POST" : "GET";
	http.open(mode, url, false);
	if (mode == "POST") {
	    http.setRequestHeader('Content-Type',
				  'application/x-www-form-urlencoded');
	}
	http.send(request);
	try {
	    return eval('(' + http.responseText + ')');
	} catch (e) {
	    // intended to catch evaluation error when respose is an empty string
	    // or while spaces
	    return undefined;
	}
    }
    
    Tny.xhr = function(url, request, callback, error_callback, timeout) {
	var req = '';
	for (var key in request) {
	    var v = request[key];
	    if (v instanceof Array) {
		// assume it's an array
		for (var i in v) {
                    var v1 = v[i] == null ? '' : v[i];
		    req += "&" + key + "[]=" + encodeURIComponent(v[i]);
                }
	    } else {
                if (v == null)
                    v = '';
		req += "&" + key + "=" + encodeURIComponent(v);
	    }
	}
	
	if (callback == null)
            return xhr_sync(url, req.slice(1));
	else {
            return xhr_async(url, req.slice(1), callback, error_callback,
                             timeout);
	}
    }
})();

