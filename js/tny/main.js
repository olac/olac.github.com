var Tny = {
    baseUrl: (function() {
	var ss = document.getElementsByTagName('script');
	var src = ss[ss.length-1].getAttribute('src');
	return src.replace(/[^\/]*$/, '');
    })()
}

Tny.$ = function(id) {
    return document.getElementById(id);
}

Tny.loadjs = function(url, callback) {
    var s = document.getElementsByTagName('script')[0];
    var script = document.createElement('script');
    script.setAttribute('type', 'text/javascript');
    script.setAttribute('src', url);
    if (navigator.userAgent.match(/MSIE\s.*/)) {
	script.onreadystatechange = function() {
	    if (this.readyState === 'loaded' ||
		this.readyState === 'complete') {
		callback();
	    }
	}
    } else {
	script.onload = callback;
	script.onerror = callback;
    }
    s.parentNode.insertBefore(script, s);
}

Tny.load = function(module, callback) {
    switch (module) {
    case 'GUI':
	Tny.loadjs(Tny.baseUrl + 'gui.js', callback);
	break;
    case 'XHR':
	Tny.loadjs(Tny.baseUrl + 'XMLHttpRequest.js', function() {
	    Tny.loadjs(Tny.baseUrl + 'xhr.js', callback);
	});
	break;
    case 'URL':
        Tny.loadjs(Tny.baseUrl + 'url.js', callback);
        break;
    default:
	callback();
	break;
    }
}
