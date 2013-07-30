(function() {

    function download(url, callback) {
	var xhr = new XMLHttpRequest;
	xhr.onreadystatechange = function(event) {
	    if (this.readyState == XMLHttpRequest.DONE) {
		var tab = eval('(' + this.responseText + ')');
		callback(tab);
	    }
	}
	xhr.open("GET", url);
	xhr.send();
    }

    function displayPolicies(elementid) {
	var display = function(tab) {
	    var container = document.getElementById(elementid);
	    for (var i=0; i < tab.length; ++i) {
		var name = document.createElement('p');
		var policy = document.createElement('blockquote');
		name.innerHTML = '<a href="/archive/' + tab[i][1] + '">'
		    + tab[i][0] + '</a>';
		policy.innerHTML = tab[i][2];
		container.appendChild(name);
		container.appendChild(policy);
	    }
	}
	download('/srv/submissionPolicies/getPolicies', display);
    }

    window.onload = function() {
	displayPolicies('policies');
    }

})();
