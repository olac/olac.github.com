(function() {

    if (typeof(Tny) == 'undefined')
	return;

    Tny.queryString = function(url) {
	var qs;
	if (url == null) {
	    qs = window.location.search;
	} else {
	    var i = url.indexOf('?');
	    if (i != -1) {
		qs = url.substring(i);
	    } else {
		qs = null;
	    }
	}
	var res = {};
	if (qs) {
	    var a = qs.substr(1).split('&');
	    for (var i=0; i < a.length; ++i) {
		var b = a[i].split('=');
		res[b[0]] = decodeURIComponent(b[1]);
	    }
	}
	return res;
    }

})();
