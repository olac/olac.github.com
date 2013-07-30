<HTML>
<HEAD>
<TITLE>Metadata Usage Survey</TITLE>
<script type="text/javascript" src="/js/gatrack.js"></script>
<LINK REL="stylesheet" TYPE="text/css" HREF="../olac.css">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<script src="/js/yui/build/yahoo/yahoo-min.js"></script>
<script src="/js/yui/build/event/event-min.js"></script>
<script src="/js/yui/build/yahoo-dom-event/yahoo-dom-event.js"></script>
<script src="/js/yui/build/connection/connection-min.js"></script>
<script src="/js/yui/build/element/element-beta-min.js"></script>
<script>
var cache = {};
var rcache = {};
var currentElementName = null;
var currentElementId = null;
var currentRecordTableId = null;
var baseurl = "/cp/ajax";
function apply(f, list) {
	for (var i in list) {
		f(list[i]);
	}
}
function EmptyElement(elem) {
	while (elem.childNodes[0]) {
		elem.removeChild(elem.childNodes[0]);
	}
	return elem;
}
function nonEmptyText(text) {
	if (!text)
		return '\u00A0';
	else
		return text;
}
function Text(text) {
	return document.createTextNode(nonEmptyText(text));
}
function Anchor(text) {
	var a = document.createElement("a");
	a.appendChild(Text(text));
	return a;
}
function ById(id) {
	var obj = document.getElementById(id);
	obj.show = function() { this.style.display="inline"; },
	obj.hide = function() { this.style.display="none"; }
	return obj;
}
function selectDisplay(id) {
	ById("slowScreenSplash").hide();
	ById("canvas").hide();
	ById("records").hide();
	ById("item").hide();
	ById(id).show();
}
function setNav(id, anchor) {
	var ids = ["navtag", "navtagarr", "navrecords", "navrecordsarr", "navitem"];
	for (var i=0; i<ids.length && ids[i]!=id; ++i) ById(ids[i]).show();
	EmptyElement(ById(id)).appendChild(anchor);
	ById(id).show();
	for (i=i+1; i<ids.length; ++i) ById(ids[i]).hide();
}
	
function drawTableSub(elementName) {
	var tab = cache[elementName];
	selectDisplay("canvas");
	ById("nav").show();
	var anchor = Anchor(elementName);
	anchor.href = "javascript:drawTable('" + currentElementName + "'," + currentElementId + ");";
	setNav("navtag", anchor);
	var title = ById("elementName");
	EmptyElement(title).appendChild(Text(elementName));
	var string = new Array();
	string.push('<table border="1"><tr><th>freq</th><th>lang</th><th>type</th><th>code</th><th>content</th></tr>');
	for (var i in tab) {
		string.push('<tr><td><a href="javascript:drawRecordIds(' + i + ');">' + tab[i].Freq + '</a></td>');
		string.push('<td>' + nonEmptyText(tab[i].Lang) + '</td>');
		string.push('<td>' + nonEmptyText(tab[i].Type) + '</td>');
		string.push('<td>' + nonEmptyText(tab[i].Code) + '</td>');
		string.push('<td>' + nonEmptyText(tab[i].Content) + '</td></td>');
	}
	string.push('</table>');
	ById('tbody').innerHTML = string.join('');
}
function drawTable(elementName, elementId) {
	var callback = {
		success: function(o) {
			var tab = eval('(' + o.responseText + ')');
			cache[elementName] = tab;
			rcache[elementName] = {}
			drawTableSub(elementName);
		},
		failure: function(o) {
		}
	}
	selectDisplay("slowScreenSplash");
	ById("nav").hide();
	if (elementName == currentElementName) {
		selectDisplay("canvas");
		var anchor = Anchor(elementName);
		anchor.href = "javascript:drawTable('" + elementName + "'," + elementId + ");";
		setNav("navtag", anchor);
		ById("nav").show();
	}
	else {
		currentElementName = elementName;
		currentElementId = elementId;
		if (cache[elementName]) {
			drawTableSub(elementName);
		} else {
			var surl = baseurl + "/survey/element/" + elementId;
			var transaction = YAHOO.util.Connect.asyncRequest('GET', surl, callback, null);
		}
	}
}
function drawRecordIdsSub(tab) {
	var body = EmptyElement(ById("recordsBody"));
	selectDisplay("records");
	var anchor = Anchor("records");
	anchor.href = "javascript:drawRecordIds(" + currentRecordTableId + ");";
	setNav("navrecords", anchor);
	EmptyElement(ById("navrecords")).appendChild(anchor);
	for (var i in tab) {
		var li = document.createElement('li');
		var a = Anchor(tab[i]);
		a.href = "javascript:loadItem(" + i + ");";
		li.appendChild(a);
		body.appendChild(li);
	}
}
function myEscape(s) {
	if (encodeURIComponent) {
		return encodeURIComponent(s);
	} else {
		return escape(s);
	}
}
function drawRecordIds(rowid) {
	var callback = {
		success: function(o) {
			var tab = eval('(' + o.responseText + ')');
			rcache[currentElementName][rowid] = tab;
			currentRecordTableId = rowid;
			drawRecordIdsSub(tab);
		},
		failure: function(o) {
		}
	}
	selectDisplay("slowScreenSplash");
	if (rcache[currentElementName][rowid]) {
		drawRecordIdsSub(rcache[currentElementName][rowid]);
	} else {
		var surl = baseurl + "/survey/recordids";
		var row = cache[currentElementName][rowid];
		var query = "lang=" + myEscape(row.Lang);
		query += "&type=" + myEscape(row.Type);
		query += "&code=" + myEscape(row.Code);
		query += "&content=" + myEscape(row.Content);
		query += "&langNull=" + (row.Lang==null);
		query += "&typeNull=" + (row.Type==null);	
		query += "&codeNull=" + (row.Code==null);
		query += "&contentNull=" + (row.Content==null);
		var transaction = YAHOO.util.Connect.asyncRequest('POST', surl, callback, query);
	}
}
function loadItem(rowid) {
	selectDisplay('item');
	var oaiid = rcache[currentElementName][currentRecordTableId][rowid];
	setNav("navitem", Text(oaiid));
	var iframe = ById("itemframe");
	iframe.src = "/item/" + oaiid;
}
function setHeight()
{
	var frame = ById("itemframe");
	frame.height = frame.contentWindow.document.body.scrollHeight;
	frame.style.width = '100%';
}
</script>
</HEAD>

<BODY>
<HR>
<TABLE CELLPADDING="10">
<TR>
<TD> <A HREF="http://www.language-archives.org/"><IMG
SRC="http://www.language-archives.org/images/olac100.gif"
BORDER="0"></A></TD>
<TD> <H1><FONT COLOR="0x00004a">Metadata Usage Survey
</FONT>
</H1>
</TD>
</TR>
</TABLE>
<HR>

<p>This page permits users to see how any attribute or field of
OLAC metadata has been used by OLAC archives.  This will be useful
in our efforts to create good controlled vocabularies.</p>

<table>
<tr>
<?
$elements = array(
"contributor" => 100,
"coverage" => 200,
"creator" => 300,
"date" => 400,
"description" => 500,
"format" => 600,
"identifier" => 700,
"language" => 800,
"publisher" => 900,
"relation" => 1000,
"rights" => 1100,
"source" => 1200,
"subject" => 1300,
"title" => 1400,
"type" => 1500,
"abstract" => 502,
"accessRight" => 1101,
"alternative" => 1401,
"available" => 403,
"bibliographicCitation" => 701,
"conformsTo" => 1013,
"created" => 401,
"dateAccepted" => 406,
"dateCopyrighted" => 407,
"dateSubmitted" => 408,
"extent" => 601,
"hasFormat" => 1012,
"hasPart" => 1008,
"hasVersion" => 1002,
"isFormatOf" => 1011,
"isPartOf" => 1007,
"isReferencedBy" => 1009,
"isReplacedBy" => 1003,
"isRequiredBy" => 1005,
"issued" => 404,
"isVersionOf" => 1001,
"license" => 1102,
"medium" => 602,
"modified" => 405,
"provenance" => 1900,
"references" => 1010,
"replaces" => 1004,
"requires" => 1006,
"rightsHolder" => 2000,
"spatial" => 201,
"tableOfContents" => 501,
"temporal" => 202,
"valid" => 402
);

  $count = 0;
  foreach ($elements as $tag => $tag_id) {
    echo "<td><button onclick=\"javascript:drawTable('$tag',$tag_id);\">$tag</button></td>";
    $count += 1;
    if ($count % 5 == 0) echo "\n</tr><tr>\n";
  }
?>
</tr>
</table>

<div id="nav" style="display: none;">
<hr>
<span id="navtag"></span>
<span id="navtagarr">-&gt;</span>
<span id="navrecords"></span>
<span id="navrecordsarr">-&gt;</span>
<span id="navitem"></span>
</div>
<div id="slowScreenSplash" style="display: none;">
<hr>
Please wait while the table is being loaded...
</div>
<div id="canvas" style="display: none;">
<hr>
<h2>Element: <span id="elementName"></span></h2>
<div id="tbody">
</div>
</div>
<div id="records" style="display: none;">
<hr>
<ul id="recordsBody">
</ul>
</div>
<div id="item" style="display: none;">
<hr>
<iframe id="itemframe" onload="setHeight();" scrolling="no" frameborder="0">
</iframe>
</div>

</BODY>
</HTML>
