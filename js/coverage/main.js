var Coverage = new function() {

    // private

    function f() {
	alert("f");
    }

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

    function torange(s) {
	if (s != 'unknown') {
	    var n = parseInt(s);
	    var a = Math.pow(10, n-1);
	    var b = Math.pow(10, n) - 1;
	    return a + ' ~ ' + b;
	} else {
	    return 'Unknown';
	}
    }

    function percent(base, x) {
	return Math.round(x / base * 100);
    }

    function setrow(data, i, rng, rec) {
	data.setCell(i, 0, rng);
	data.setCell(i, 1, rec[1]);
	data.setCell(i, 2, rec[2]);
	data.setCell(i, 3, percent(rec[1],rec[2]));
	data.setCell(i, 4, rec[3]);
    }

    // public

    this.drawCoverageTable = function(elementid) {
	var draw = function(tab) {
	    var data = new google.visualization.DataTable();
	    data.addColumn('string','Population range');
	    data.addColumn('number','Languages');
	    data.addColumn('number','OLAC has data');
	    data.addColumn('number','(%)');
	    data.addColumn('number','Items');
	    data.addRows(tab.length + 1);
	    var xrow = null;
	    var sum_lang = 0;
	    var sum_olac = 0;
	    var sum_item = 0;
	    var row = 0;
	    for (var i=0; i<tab.length; i++) {
		if (tab[i][0] != 'extinct') {
		    var rng = torange(tab[i][0]);
		    setrow(data, row, rng, tab[i]);
		    sum_lang += tab[i][1];
		    sum_olac += tab[i][2];
		    sum_item += tab[i][3];
		    row++;
		} else {
		    xrow = i;
		}
	    }

	    setrow(data, row++, 'All living languages',
		   [null, sum_lang, sum_olac, sum_item]);

	    setrow(data, row, 'Extinct languages', tab[xrow]);

	    var e = document.getElementById(elementid);
	    var table = new google.visualization.Table(e);
	    table.draw(data);
	}
	download('/srv/coverage/getLanguageTable', draw);
    }

    this.drawOnlineResourcesTable = function(elementid) {
	var draw = function(tab) {
	    var data = new google.visualization.DataTable();
	    data.addColumn('string','Population range');
	    data.addColumn('number','Languages');
	    data.addColumn('number','OLAC has data');
	    data.addColumn('number','(%)');
	    data.addColumn('number','Items');
	    data.addRows(tab.length + 1);
	    var xrow = null;
	    var sum_lang = 0;
	    var sum_olac = 0;
	    var sum_item = 0;
	    var row = 0;
	    for (var i=0; i<tab.length; i++) {
		if (tab[i][0] != 'extinct') {
		    var rng = torange(tab[i][0]);
		    setrow(data, row, rng, tab[i]);
		    sum_lang += tab[i][1];
		    sum_olac += tab[i][2];
		    sum_item += tab[i][3];
		    row++;
		} else {
		    xrow = i;
		}
	    }

	    setrow(data, row++, 'All living languages',
		   [null, sum_lang, sum_olac, sum_item]);

	    setrow(data, row, 'Extinct languages', tab[xrow]);

	    var e = document.getElementById(elementid);
	    var table = new google.visualization.Table(e);
	    table.draw(data);
	}
	download('/srv/coverage/getOnlineResTable', draw);
    }

    // initialization

    this.init = function(callback) {
	google.load('visualization','1',{packages:['table']});
	google.setOnLoadCallback(callback);
    }

    this.init2 = function(tab1, tab2) {
	var callback = function() {
	    Coverage.drawCoverageTable(tab1);
	    Coverage.drawOnlineResourcesTable(tab2);
	}
	Coverage.init(callback);
    }
}
