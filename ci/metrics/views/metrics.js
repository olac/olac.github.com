REPOS = null;
REPOIDS = null;
METRICS = null;
FIELDS = {
    num_resources: "Number of Resources",
    num_online_resources: "Number of Resources Online",
    num_languages: "Distinct Languages",
    num_linguistic_fields: "Distinct Linguistic Subfields",
    num_linguistic_types: "Distinct Linguistic Types",
    num_dcmi_types: "Distinct DCMI Types",
    metadata_quality: "Average Metadata Quality Score",
    avg_num_elements: "Average Elements Per Record",
    std_num_elements: "Standard Deviation for Elements Per Record",
    avg_xsi_types: "Average Encoding Schemes Per Record",
    last_updated: "Last Updated",
    integrity_problems: "Known Integrity Problems",
    hits: "Record views per month",
    clicks: "Click-throughs per month"
};

window.onload = initMetrics;

function initMetrics()
{
	var mytabs = new YAHOO.widget.TabView("tabview");
	// now called by loadArchiveList() because REPOIDS has to be
	// loaded before summary stat table is drawn which is done
	// when metrics table is loaded
	//
	// loadOLACMetrics();
	loadArchiveList();

	//var confbtn = new YAHOO.widget.Button("configure");
	//var mypanel = new YAHOO.widget.Panel("comparativePanel");
	//mypanel.render();
	//mypanel.hide();
	//var handleOnClick = function() {mypanel.show();}
	//confbtn.on("click", handleOnClick);

	//new YAHOO.widget.Button("chkNumRes");
	//new YAHOO.widget.Button("chkNumLang");
	//new YAHOO.widget.Button("chkNumLangFields");
	//new YAHOO.widget.Button("chkNumLangTypes");
	//new YAHOO.widget.Button("chkNumDcmi");
	//new YAHOO.widget.Button("chkScore");
	//new YAHOO.widget.Button("chkNumElements");

}

function computeRating(avg_quality_score, num_integrity_errors, num_records)
{
	var rating = avg_quality_score / 2.0;
	var deduction = 0.0;
	if (num_records > 0.0) {
		deduction = Math.sqrt(num_integrity_errors / num_records);
	}
	rating = Math.round(rating - deduction);
	if (rating < 0) rating = 0;
	return rating;
}

function generateSumStatTab(arcid, containerId)
{
    var row = METRICS[""+arcid];  // "" is for IE
    var keys = [
	'num_resources','num_online_resources', 'num_languages',
	'num_linguistic_fields', 'num_linguistic_types', 'num_dcmi_types',
	'avg_num_elements', 'avg_xsi_types', 'metadata_quality',
	'hits', 'clicks',
	'last_updated', 'integrity_problems'
    ];
    var tab = new Array();
    if (arcid == -1) {
	var num_archives = 0;
	var fresh_archives = 0;
	var five_stars = 0;
	var today = new Date();
	var targetdate = new Date(today.getFullYear()-1,today.getMonth()+1,today.getDate());
	for (var i in METRICS) {
	    if (i >= 0) {
	        num_archives++;
		if (METRICS[i].last_updated) {
		    var a = METRICS[i].last_updated.split('-');
		    var theday = new Date(a[0],a[1],a[2]);
		    if (theday >= targetdate) fresh_archives++;
		}
		if (computeRating(METRICS[i].metadata_quality,
				  Math.floor(METRICS[i].integrity_problems),
				  METRICS[i].num_resources) == 5)
		    five_stars++;
	    }
	}
	tab.push({name:"Number of Archives", value:num_archives});
	tab.push({name:"Archives with Fresh Catalogs", value:fresh_archives});
	tab.push({name:"Archives with Five-star Metadata", value:five_stars});
    }
    for (var i in keys) {
	var key = keys[i];
	if (row[key] === undefined) continue;
	if (FIELDS[key] === undefined) continue;
	if (key == 'last_updated') {
	    if (row[key]) {
		var a = row[key].split('-');
		tab.push({name:FIELDS[key], value:a[0]+'-'+a[1]+'-'+a[2]});
	    }
	    else {
		tab.push({name:FIELDS[key], value: '0000-00-00'});
	    }
	}
	else {
	    tab.push({name:FIELDS[key], value:Math.round(row[key]*10)/10});
	}
    }
    if (arcid != -1) {
	var rating = computeRating(
	    row['metadata_quality'],
	    Math.floor(row['integrity_problems']),
	    row['num_resources']);
	tab.push({name:'Overall Rating', value:rating});
    }
    var dataSource = new YAHOO.util.DataSource(tab);
    dataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
    dataSource.responseSchema = {
	fields: ["name", "value"]
    };
    var myFormatter = function(el, oRecord, oColumn, oData) {
	if (oRecord.getData('name') == 'Last Updated') {
	    YAHOO.widget.DataTable.formatText(el, oRecord, oColumn, oData);
	} else if (oRecord.getData('name') == 'Overall Rating') {
	    el.innerHTML = '<img src="/tools/scores/star' + oData + '.gif"></img>';
	} else if (oRecord.getData('name') == 'Known Integrity Problems' && arcid != -1) {
	    var probs = Math.floor(oData);
	    if (oData > 0) {
		el.innerHTML = '<a href="/checks/' + REPOIDS[arcid] + '">' + probs + '</a>';
	    } else {
		el.innerHTML = probs;
	    }
	} else {
	    YAHOO.widget.DataTable.formatNumber(el, oRecord, oColumn, oData);
	}
    };
    var cols = [
	{key:"name", label:"Name"},
	{key:"value", label:"Value", formatter:myFormatter}
    ];
    var dataTable = new YAHOO.widget.DataTable(containerId, cols, dataSource);
}

function generateElemUsageTab(arcid, containerId)
{
	var callback = {
		success: function(o) {
			var lst = eval('('+o.responseText+')');
			var tab = new Array();
			for (var key in lst) {
				var row = lst[key];
				tab.push({name:row.label, value:row.cnt});
			}
			var dataSource = new YAHOO.util.DataSource(tab);
			dataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			dataSource.responseSchema = {
				fields: ["name", "value"]
			};
			var cols = [
				{key:"name", label:"Name"},
				{key:"value", label:"Value", formatter:"number"}
			];
			var dataTable = new YAHOO.widget.DataTable(containerId, cols, dataSource);
		},
		failure: function(o) {
		}
	}
	var sUrl = baseurl + '/api/elementUsage/' + arcid;
	var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback, null);
}

function generateRefineUsageTab(arcid, containerId)
{
	var callback = {
		success: function(o) {
			var lst = eval('('+o.responseText+')');
			var tab = new Array();
			for (var key in lst) {
				var row = lst[key];
				tab.push({name:row.label, value:row.cnt});
			}
			var dataSource = new YAHOO.util.DataSource(tab);
			dataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			dataSource.responseSchema = {
				fields: ["name", "value"]
			};
			var cols = [
				{key:"name", label:"Name"},
				{key:"value", label:"Value", formatter:"number"}
			];
			var dataTable = new YAHOO.widget.DataTable(containerId, cols, dataSource);
		},
		failure: function(o) {
		}
	}
	var sUrl = baseurl + '/api/refinementUsage/' + arcid;
	var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback, null);
}

function generateCoreComponentsGraph(arcid, containerId)
{
	var callback = {
		success: function(o) {
			var h = eval('('+o.responseText+')');
			var graph = '/mode/percentage/percent/1/width/700/labelWidth/150/title/Percent used/ylabel/Component/data/';
			for (var key in h) {
				var p = Math.round(h[key] * 1000) / 10;
				graph += key + "^" + p + "^^";
			}
			var container = document.getElementById(containerId);
			var img = document.createElement("img");
			img.src = baseurl + "/api/barChart" + graph;
			img.width = 700;
			container.innerHTML = '';
			container.appendChild(img);
		},
		failure: function(o) {
		}
	}
	var sUrl = baseurl + '/api/coreElementPct/' + arcid;
	var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback, null);
}

function generateEncodingSchemeGraph(arcid, containerId)
{
	var callback = {
		success: function(o) {
			var h = eval('('+o.responseText+')');
			var graph = '/mode/maximum/width/700/labelWidth/150/title/Times used/ylabel/Encoding/data/';
			for (var i in h) {
				var row = h[i];
				graph += row.Type + "^" + row.cnt + "^^";
			}
			var container = document.getElementById(containerId);
			var img = document.createElement("img");
			img.src = baseurl + "/api/barChart" + graph;
			img.width = 700;
			container.innerHTML = '';
			container.appendChild(img);
		},
		failure: function(o) {
		}
	}
	var sUrl = baseurl + '/api/EncodingSchemeUsage/' + arcid;
	var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback, null);
}

function generateElemUsageGraph(arcid, containerId)
{
	var callback = {
		success: function(o) {
			var lst = eval('('+o.responseText+')');
			var tab = new Array();
			var graph = '/mode/maximum/width/700/labelWidth/150/title/Times used/ylabel/Element/data/';
			for (var key in lst) {
				var row = lst[key];
				graph += row.label + "^" + row.cnt + "^^";
			}
			var container = document.getElementById(containerId);
			var img = document.createElement("img");
			img.src = baseurl + "/api/barChart" + graph;
			img.width = 700;
			container.innerHTML = '';
			container.appendChild(img);
		},
		failure: function(o) {
		}
	}
	var sUrl = baseurl + '/api/elementUsage/' + arcid;
	var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback, null);
}

function generateRefineUsageGraph(arcid, containerId)
{
	var callback = {
		success: function(o) {
			var lst = eval('('+o.responseText+')');
			var tab = new Array();
			var graph = '/mode/maximum/width/700/labelWidth/150/title/Times used/ylabel/Element/data/';
			for (var key in lst) {
				var row = lst[key];
				graph += row.label + "^" + row.cnt + "^^";
			}
			var container = document.getElementById(containerId);
			var img = document.createElement("img");
			img.src = baseurl + "/api/barChart" + graph;
			img.width = 700;
			container.innerHTML = '';
			container.appendChild(img);
		},
		failure: function(o) {
		}
	}
	var sUrl = baseurl + '/api/refinementUsage/' + arcid;
	var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback, null);
}

function archiveSelected(e)
{
	var select = YAHOO.util.Dom.get("archivelist");
	var optidx = select.selectedIndex;
	var opt = select[optidx];
	if (METRICS == null) {
		alert("OLAC Metrics hasn't been loaded.");
		return;
	}
	selectArchive(opt.value);
}

function selectArchive(arcid)
{
    generateSumStatTab(arcid, "sumstat");
    generateCoreComponentsGraph(arcid, "corecomp");
    generateElemUsageGraph(arcid, "elemuse");
    generateRefineUsageGraph(arcid, "refineuse");
    generateEncodingSchemeGraph(arcid, "encschm");

    if (METRICS) {
	var span = document.getElementById("pvdate");
	span.innerHTML = METRICS[-2];
    } else {
	var div = document.getElementById("note");
	div.style.display = "none";
    }
}

function loadOLACMetrics()
{
	var callback = {
		success:function(o) {
			METRICS = eval('('+o.responseText+')');
			var e = document.getElementById("defaultArchiveId");
			selectArchive(parseInt(e.value));
			populateComparativeMetricsTable();
		},
		failure:function(o) {
			alert("Failed to obtain OLAC Metrics.");
		}
	}
	var sUrl = baseurl + '/api/OLACMetrics';
	var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback, null);
}

function loadArchiveList()
{
	var e = document.getElementById("defaultArchiveId");
	if (e.value)
		var defaultArcId = parseInt(e.value);
	var callback = {
		success:function(o) {
			loadOLACMetrics();
			var tmp = eval('(' + o.responseText + ')');
			REPOS = new Object;
			REPOIDS = new Object;
			var select = document.getElementById("archivelist");
			select.options[0] = new Option("ALL ARCHIVES", -1);
			for (var i in tmp) {
				var arcid = tmp[i]['Archive_ID'];
				var reponam = tmp[i]['RepositoryName'];
				var repoid = tmp[i]['RepositoryIdentifier'];
				REPOS[arcid] = reponam;
				REPOIDS[arcid] = repoid;
				var bsel = (arcid==defaultArcId);
				select.options[select.length] = new Option(REPOS[arcid], arcid, bsel, bsel);
			}
			populateComparativeMetricsTable();
		},
		failure: function(o) {
			alert('Failed to obtain the archive list.');
		}
	}
	var sUrl = baseurl + '/api/archiveList';
	var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback, null);
	
	/* don't need to wait for the list data to arrive */
	YAHOO.util.Event.addListener('archivelist', 'change', archiveSelected); 	
}

function populateComparativeMetricsTable()
{
    if (!REPOS || !METRICS) return;

    var tab = new Array();
    for (var rowid in METRICS) {
	if (rowid < 0) continue;
	var row = new Array();
	for (var key in METRICS[rowid]) {
	    if (key == "archive_id") {
		row["archive"] = REPOS[METRICS[rowid][key]];
		row["archive_id"] = METRICS[rowid][key];
	    } else if (key == 'last_updated') {
		if (METRICS[rowid][key]) {
		    var a = METRICS[rowid][key].split('-');
		    row[key] = a[0]+'-'+a[1]+'-'+a[2]
		} else {
		    row[key] = '0000-00-00';
		}
	    } else {
		if (METRICS[rowid][key]) {
		    //row[key] = Math.round(parseFloat(METRICS[rowid][key])*10)/10;
		    row[key] = parseFloat(METRICS[rowid][key]);
		} else {
		    row[key] = 0;
		}
	    }
	}
	row['overall_rating'] = computeRating(
	    row['metadata_quality'],
	    Math.floor(row['integrity_problems']),
	    row['num_resources']);
	tab.push(row);
    }
    var dataSource = new YAHOO.util.DataSource(tab);
    dataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
    dataSource.responseSchema = {
	fields: ["archive_id", "archive", "overall_rating", "num_resources", "num_online_resources", "num_languages",
		 "num_linguistic_fields", "num_linguistic_types", "num_dcmi_types", "avg_num_elements", "avg_xsi_types",
		 //"hits", "clicks",
		 "metadata_quality", "last_updated", "integrity_problems"]
    };
    
    var format_ip = function(el, oRecord, oColumn, oData) {
	var probs = Math.floor(oData);
	if (oData > 0) {
	    el.innerHTML = '<div style="text-align: right;"><a href="/checks/' + REPOIDS[oRecord.getData('archive_id')] + '">' + probs + '</a></div>';
	} else {
	    el.innerHTML = '<div style="text-align: right;">' + probs + '</div>';
	}
    };
    var format_right = function(el, oRecord, oColumn, oData) {
	el.innerHTML = '<div style="text-align: right;">' + parseInt(oData) + '</div>';
    }
    var format_right2 = function(el, oRecord, oColumn, oData) {
	el.innerHTML = '<div style="text-align: right;">' + parseFloat(oData).toFixed(1) + '</div>';
    }
    var format_or = function(el, oRecord, oColumn, oData) {
	el.innerHTML = '<div style="text-align: right;"><img src="/tools/scores/star' + oData + '.gif"></img>';
    }
    var sort_or = function(a, b, desc) { 
	var comp = YAHOO.util.Sort.compare; 
	var compState = comp(a.getData("overall_rating"), b.getData("overall_rating"), desc); 
	return (compState !== 0) ? compState : comp(a.getData("metadata_quality"), b.getData("metadata_quality"), desc); 
    }; 
    var sort_archive = function(a, b, desc) {
	var comp = YAHOO.util.Sort.compare; 
	var s1 = a.getData("archive");
	var s2 = b.getData("archive");
	return comp(s1.replace(/^(the|an?)\s+/i,''), s2.replace(/^(the|an?)\s+/i,''), desc);
    };
    var cols = [
	{key:"archive", label:"Archive", sortable:true, sortOptions:{defaultOrder:"asc", sortFunction:sort_archive}},
	{key:"overall_rating", label:"Overall Rating", sortable:true, sortOptions:{defaultOrder:"desc", sortFunction:sort_or}, formatter:format_or},
	{key:"num_resources", label:FIELDS["num_resources"], sortable:true, sortOptions:{defaultOrder:"desc"}, formatter:format_right},
	{key:"num_online_resources", label:FIELDS["num_online_resources"], sortable:true, sortOptions:{defaultOrder:"desc"}, formatter:format_right},
	{key:"num_languages", label:FIELDS["num_languages"], sortable:true, sortOptions:{defaultOrder:"desc"}, formatter:format_right},
	{key:"num_linguistic_fields", label:FIELDS["num_linguistic_fields"], sortable:true, sortOptions:{defaultOrder:"desc"}, formatter:format_right},
	{key:"num_linguistic_types", label:FIELDS["num_linguistic_types"], sortable:true, sortOptions:{defaultOrder:"desc"}, formatter:format_right},
	{key:"num_dcmi_types", label:FIELDS["num_dcmi_types"], sortable:true, sortOptions:{defaultOrder:"desc"}, formatter:format_right},
	{key:"avg_num_elements", label:FIELDS["avg_num_elements"], sortable:true, sortOptions:{defaultOrder:"desc"}, formatter:format_right2},
	{key:"avg_xsi_types", label:FIELDS["avg_xsi_types"], sortable:true, sortOptions:{defaultOrder:"desc"}, formatter:format_right2},
	//{key:"hits", label:FIELDS["hits"], sortable:true, sortOptions:{defaultOrder:"desc"}, formatter:format_right},
	//{key:"clicks", label:FIELDS["clicks"], sortable:true, sortOptions:{defaultOrder:"desc"}, formatter:format_right},
	{key:"metadata_quality", label:FIELDS["metadata_quality"], sortable:true, sortOptions:{defaultOrder:"desc"}, formatter:format_right2},
	{key:"last_updated", label:FIELDS["last_updated"], sortOptions:{defaultOrder:"desc"}, sortable:true},
	{key:"integrity_problems", label:FIELDS["integrity_problems"], sortable:true, formatter:format_ip}
    ];
    var dataTable = new YAHOO.widget.DataTable("table", cols, dataSource);
    dataTable.sortColumn(dataTable.getColumn("archive"));
}
