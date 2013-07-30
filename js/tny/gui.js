if (typeof(Tny) != 'undefined') {

    // Widget
    Tny.Widget = function() {
    }

    Tny.Widget.prototype.bind = function(id) {
	this.dom = Tny.$(id);
    }
    
    // Label
    Tny.Label = function(id, label) {
	this.bind(id);
	if (label)
	    this.setText(label);
    }
    
    Tny.Label.prototype = new Tny.Widget;
    
    Tny.Label.prototype.setText = function(text) {
	if (this.dom.firstChild)
	    this.dom.firstChild.nodeValue = text;
	else {
	    var node = document.createTextNode(text);
	    this.dom.appendChild(node);
	}
    }
    
    Tny.Label.prototype.getText = function(text) {
	return this.dom.firstChild.nodeValue;
    }
    
    // LineEdit
    Tny.LineEdit = function(id, upvar) {
	this.bind(id);
	this.dom.onchange = function() {
	    if (upvar)
		upvar(this.value);
	}
    }
    
    Tny.LineEdit.prototype = new Tny.Widget;
    
    Tny.LineEdit.prototype.setText = function(text) {
	this.dom.value = text;
    }
    
    Tny.LineEdit.prototype.getText = function() {
	return this.dom.value;
    }

    // TextEdit
    Tny.TextEdit = function(id, upvar, text) {
	this.bind(id);
	this.dom.onchange = function() {
	    if (upvar)
		upvar(this.value);
	}
	if (text)
	    this.setText(text);
	else
	    this.setText('');
    }
    
    Tny.TextEdit.prototype = new Tny.Widget;
    
    Tny.TextEdit.prototype.setText = function(text) {
	this.dom.value = text;
    }
    
    Tny.TextEdit.prototype.getText = function() {
	return this.dom.value;
    }

    // MenuList
    Tny.MenuList = function(id, upvar, vllst, default_value) {
	this.bind(id);
	this.dom.innerHTML = '';
	if (vllst)
	    this.setList(vllst);
	if (default_value)
	    this.selectValue(default_value);
	this.dom.onchange = function() {
	    upvar(this.options[this.selectedIndex].value);
	}
    }

    Tny.MenuList.prototype = new Tny.Widget;

    Tny.MenuList.prototype.addItem = function(label, value, index) {
	var opt = new Option(label, value);
	this.dom.appendChild(opt);
    }

    Tny.MenuList.prototype.setList = function(vllst) {
	for (var i=0; i < vllst.length; ++i) {
	    this.addItem(vllst[i][1], vllst[i][0]);
	}
    }

    Tny.MenuList.prototype.selectValue = function(value) {
	for (var i=0; i < this.dom.options.length; ++i) {
	    if (this.dom.options[i].value == value) {
		this.dom.selectedIndex = i;
		break;
	    }
	}
    }

    // RadioGroup
    Tny.RadioGroup = function(id, upvar, vllist, default_value) {
	this.bind(id);
	this.dom.innerHTML = '';
	this.upvar = upvar;
	if (vllist) {
	    for (var i=0; i < vllist.length; ++i)
		this.addButton(vllist[i][0], vllist[i][1]);
	}
	if (default_value)
	    this.selectValue(default_value);
    }

    Tny.RadioGroup.prototype = new Tny.Widget;

    Tny.RadioGroup.prototype.addButton = function(value, label) {
	var radio = document.createElement('input');
	var text = document.createTextNode(label);
	radio.setAttribute('type', 'radio');
	radio.setAttribute('name', this.dom.id);
	radio.value = value;
	this.dom.appendChild(radio);
	this.dom.appendChild(text);
	var scope = this;
	radio.onclick = function() {
	    if (scope.upvar)
		scope.upvar(this.value);
	}
    }

    Tny.RadioGroup.prototype.selectValue = function(v) {
	var rg = this.dom[this.dom.id];
	for (var i=0; i < rg.length; ++i) {
	    if (rg[i].value == v) {
		rg[i].checked = true;
		break;
	    }
	}
    }

    Tny.RadioGroup.prototype.clearSelection = function(v) {
	this.dom.selectedIndex = -1;
    }

    // Checkbox
    Tny.CheckBox = function(id, upvar, yesval, noval, default_value) {
	this.bind(id);
	if (upvar) {
	    this.dom.onclick = function() {
		if (this.checked)
		    upvar(yesval);
		else
		    upvar(noval);
	    }
	}
	this.setChecked(yesval == default_value);
    }

    Tny.CheckBox.prototype = new Tny.Widget;

    Tny.CheckBox.prototype.setChecked = function(v) {
	this.dom.checked = (v == true);
    }
}

