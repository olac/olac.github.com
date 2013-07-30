// OLACSearch class
// @param searchBoxId: The ID of a container to hold the search control.
// @param resultBoxId: The ID of a container to hold the search results.
// @param areaSelectorId: The ID of a container to hold the area dropdown.
// @param archiveSelectorId: The ID of a container to hold the archive dropdown.
function OLACSearch(searchBoxId,
		    resultBoxId,
		    areaSelectorId,
		    archiveSelectorId)
{
    this.searchBox = document.getElementById(searchBoxId);
    this.resultBox = document.getElementById(resultBoxId);
    this.areaSelector = document.getElementById(areaSelectorId);
    this.archiveSelector = document.getElementById(archiveSelectorId);

    this.searcher = new google.search.WebSearch();
    this.searcher.setSiteRestriction('008121384105191196936:y5ywkrnqcto');
    this.searcher.setResultSetSize(google.search.Search.FILTERED_CSE_RESULTSET);
    this.gsForm = new google.search.SearchForm(false, this.searchBox);

    this.searcher.setSearchCompleteCallback(this, this.onSearchComplete);
    this.gsForm.setOnSubmitCallback(this, this.onSubmit);

    function nav_handler_closure(scope) {
	// @param scope: The object to be prepended to the scope chain.
	return function(event) {
	    scope.onPageIndexClicked.call(scope, event);
	}
    }

    $(this.resultBox).addEvent('click', nav_handler_closure(this));
    this.initializeArchivesSelector();
}

OLACSearch.prototype.initializeArchivesSelector = function()
{
    var xhr = new XMLHttpRequest;
    xhr.scope = this;
    xhr.onreadystatechange = function(event) {
	if (this.readyState == XMLHttpRequest.DONE) {
	    var list = eval('(' + this.responseText + ')');
	    for (var i=0; i<list.length; ++i) {
		var opt = new Option(list[i], list[i]);
		this.scope.archiveSelector.options[i+1] = opt;
	    }
	}
    }
    xhr.open("GET", "/cp/ajax/db/getRegisteredRepositoryIdentifiers");
    xhr.send();
}

OLACSearch.prototype.onPageIndexClicked = function(event)
{
    var target = $(event.target);
    var classValue = target.get('class');
    if (classValue != null) {
	var values = classValue.split();
	for (var i=0; i<values.length; ++i) {
	    if (values[i] == 'pageindex') {
		var page = target.get('text') - 1;
		this.searcher.gotoPage(page);
		break;
	    }
	}
    }
}

OLACSearch.prototype.onSubmit = function(form)
{
    var extraterms = '';
    // obtain search terms for selected area
    var areaidx = this.areaSelector.selectedIndex;
    if (areaidx != 0) {
	extraterms += this.areaSelector[areaidx].value + " ";
    }

    // obtain search terms for selected archive
    var archiveidx = this.archiveSelector.selectedIndex;
    if (archiveidx != 0) {
	extraterms += this.archiveSelector[archiveidx].value + " ";
    }

    this.searcher.setQueryAddition(extraterms);

    var query = form.input.value;
    this.searcher.execute(query);
    return false;
}

OLACSearch.prototype.onSearchComplete = function()
{
    // Clean up whatever remaining inside the result box. (Note that search
    // results have alreay been cleaned up by google api at this point.)
    this.resultBox.innerHTML = '';

    for (var i=0; i<this.searcher.results.length; ++i) {
	var r = this.searcher.results[i];
	this.resultBox.appendChild(r.html);
    }

    if ('cursor' in this.searcher) {
	var cursor = this.searcher.cursor;
	var pages = cursor.pages;
	var cp = cursor.currentPageIndex; // begins from 0
	for (var i=0; i<pages.length; ++i) {
	    var atts = {'class':'pageindex', 'html':pages[i].label}
	    var node = new Element('div', atts);
	    node.inject(this.resultBox);
	    if (i == cp) {
		node.addClass('pageindex-current');
	    }
	}
    }
    else {
	var node = new Element('div', {html:'No records found'});
	node.inject(this.resultBox);
    }
}

function initialize() {
    olacsearch = new OLACSearch('search-box', 'result-box',
				'area-selector', 'archive-selector');
}

google.load("search", "1");
google.load("mootools", "1.2.4");
google.setOnLoadCallback(initialize);
