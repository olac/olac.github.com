(function() {

    var board;
    var ajax = 'call.php';

    var loadcount = 1;
    Tny.load('XHR', setup_init);
    Tny.load('URL', setup_init);

    function setup_init() {
	if (--loadcount > 0)
	    return;

	window.onload = init;
    }

    function init() {
	board = $('panel-board'); 
	var qs = Tny.queryString();
	if (qs.id) {
	    var p = write('Recording your harvest request ' +
			  '(takes up to 15 seconds)...');
	    register_request(qs.id, p, 15000)
	} else if (qs.confirm) {
	    var p = write('Please be patient. Harvesting...');
	    var http = harvest(qs.confirm, p);
	} else {
	    var baseurl =
		window.location.protocol + '://' +
		window.location.host +
		window.location.pathname;
	    write('If you want to request a new harvest, ' +
		  'please specify your repository ID, e.g.');
	    write_html("<b>" + baseurl + "?id=&lt;repository_id&gt;</b>");
	    write('If you want to confirm your harvest request, ' +
		  'please specify the confirmation token we sent you, e.g.');
	    write_html("<b>" + baseurl + "?confirm=&lt;token&gt;</b>");
	}
    }

    function $(id) {
	return document.getElementById(id);
    }

    function write(msg) {
	var p = document.createElement('p');
	var text = document.createTextNode(msg);
	p.appendChild(text);
	board.appendChild(p);
	return p;
    }

    function write_pre(msg) {
	var p = document.createElement('pre');
	var text = document.createTextNode(msg);
	p.appendChild(text);
	board.appendChild(p);
	return p;
    }

    function write_html(msg) {
	var p = document.createElement('p');
	p.innerHTML = msg;
	board.appendChild(p);
	return p;
    }

    function register_request(repoid, p, timeout) {
	var updater = function() {
	    p.innerHTML += '.';
	}

	var ival = setInterval(updater, 500);

	var req = {
	    func: 'register_request',
	    repoid: repoid
	}

	var cb = function(v) {
	    clearInterval(ival);
	    if (v.hasOwnProperty('error')) {
		p.innerHTML += 'FAILURE';
		write('Error message: ' + v.error);
	    } else {
		p.innerHTML += 'OK';
		write('An email has been sent to ' + v.adminemail + '. ' +
		      'Please follow instructions in the email to confirm ' +
		      'your re-harvest request.');
	    }
	}

	var errcb = function(v) {
	    clearInterval(ival);
	    if (v.type == 'timeout') {
		p.innerHTML += 'TIMEOUT';
		var msg =
		'Your request timed out. Please try again. ' +
		'If problem persists, please let us know.';
		write(msg);
	    } else {
		p.innerHTML += 'FAILUER';
		write('Error message: ' + v.message);
	    }
	}

	Tny.xhr(ajax, req, cb, errcb, timeout);
    }

    function harvest(tok, p) {
	var counter = 0;
	var frames = "|/-\\";
	var basestr = p.innerHTML;
	var pre = write_pre('');
	var http;

	var updater = function() {
	    p.innerHTML = basestr + frames.charAt(counter++ % 4);
	    pre.firstChild.nodeValue = http.responseText;
	}

	var ival = setInterval(updater, 500);

	var print_output = function() {
	    pre.firstChild.nodeValue = http.responseText;
	}

	var req = {
	    func: 'harvest',
	    token: tok
	}

	var cb = function(v) {
	    clearInterval(ival);
	    if (v.hasOwnProperty('error')) {
		p.innerHTML = basestr + 'FAILURE';
		write('Error message: ' + v.error);
	    } else {
		p.innerHTML = basestr + 'OK';
	    }
	}

	var errcb = function(v) {
	    clearInterval(ival);
	    if (v.type == 'timeout') {
		p.innerHTML = basestr + 'TIMEOUT';
		var msg =
		'Your request timed out. Please try again. ' +
		'If problem persists, please let us know.';
		write(msg);
	    } else if (v.type == 'server' &&
		       v.message.search(/^Invalid JSON:/) == 0) {
		p.innerHTML = basestr + 'OK';
		print_output();
	    } else {
		p.innerHTML = basestr + 'FAILURE';
		write('Error message: ' + v.message);
	    }
	}

	http = Tny.xhr(ajax, req, cb, errcb);
    }

})();