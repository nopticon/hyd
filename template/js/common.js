var ns4 = document.layers;
var ns6 = document.getElementById && !document.all;
var ie4 = document.all;

var ajx_control = new Array()
var ajx_index = new Array(0);
var ajx_request = null;

function da_obj(obj, sw) {
	eval("obj.disabled = sw;");
}

function sc_obj(obj, sc) {
	eval("obj.value = '" + sc + "'");
}

function ddl(id) {
	obj = hdr_ref(id);
	if (!obj) {
		return false;
	}
	setTimeout("da_obj(obj, true);", 0);
	setTimeout("da_obj(obj, false);", 5000);
}

function dcm(id) {
	obj = hdr_ref(id);
	if (!obj) {
		return false;
	}
	setTimeout("da_obj(obj, true); sc_obj(obj, 'Enviando...');", 0);
}

function addpoll(id) {
	var obj = hdr_ref(id);
	if (!obj) {
		return false;
	}
	else if (obj.style) {
		obj.style.display = (obj.style.display != "none") ? "none" : "";
	} else {
		obj.visibility = "show";
	}
}

function set_style(id, display) {
	var obj = hdr_ref(id);
	
	if (!obj) {
		return false;
	}
	else if (obj.style) {
		obj.style.display = display;
	} else {
		obj.visibility = display;
	}
}

function hdr_ref(object) {
	if (document.getElementById) {
		return document.getElementById(object);
	} else if (document.all) {
		return eval('document.all.' + object);
	} else {
		return false;
	}
}

function hdr_expand(object) {
	var object = hdr_ref(object);

	if (!object.style) {
		return false;
	} else {
		object.style.display = '';
	}
	if (window.event) {
		window.event.cancelBubble = true;
	}
}

function hdr_contract(object) {
	var object = hdr_ref(object);

	if (!object.style) {
		return false;
	} else {
		object.style.display = 'none';
	}
	if (window.event) {
		window.event.cancelBubble = true;
	}
}

function hdr_toggle(object, open_close, open_icon, close_icon) {
	var object = hdr_ref(object);
	var icone = hdr_ref(open_close);

	if (!object.style) {
		return false;
	}
	if (object.style.display == 'none') {
		object.style.display = '';
		icone.src = close_icon;
	} else {
		object.style.display = 'none';
		icone.src = open_icon;
	}
}

function popup(url, name, width, height) {
/*var width = width ? width : 500;
var name = name || "RRN";
var height = height ? height : 600;*/
var win = window.open(url, name, 'toolbar = 0, scrollbars = 1, location = 0, statusbar = 0, menubar = 0, resizable = 1, width=' + width + ', height=' + height);
return false;
}

function insert_text(text) {
	if (document.forms[form_name].elements[text_name].createTextRange && document.forms[form_name].elements[text_name].caretPos) {
		var caretPos = document.forms[form_name].elements[text_name].caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? caretPos.text + text + ' ' : caretPos.text + text;
	} else {
		var selStart = document.forms[form_name].elements[text_name].selectionStart;
		var selEnd = document.forms[form_name].elements[text_name].selectionEnd;

		mozWrap(document.forms[form_name].elements[text_name], text, '')
		document.forms[form_name].elements[text_name].selectionStart = selStart + text.length;
		document.forms[form_name].elements[text_name].selectionEnd = selEnd + text.length;
	}
	document.forms[form_name].elements[text_name].focus();
}

// From http://www.massless.org/mozedit/
function mozWrap(txtarea, open, close) {
	var selLength = txtarea.textLength;
	var selStart = txtarea.selectionStart;
	var selEnd = txtarea.selectionEnd;
	if (selEnd == 1 || selEnd == 2) {
		selEnd = selLength;
	}
	var s1 = (txtarea.value).substring(0,selStart);
	var s2 = (txtarea.value).substring(selStart, selEnd)
	var s3 = (txtarea.value).substring(selEnd, selLength);
	txtarea.value = s1 + open + s2 + close + s3;
	return;
}

function update_frame(fn,id,rr,decode) {
	var me = this;
	
	this.callback = function(response) {
		me.ajax = null;
		try {
			hdr_ref(id).innerHTML = (decode) ? unescape(response.responseText) : response.responseText;
		} catch(e) {
			clearInterval(me.interv);
		}
		ajx_control[ajx_index] = new Ajax(me.callback);
		me.ajax = ajx_control[ajx_index];
		ajx_index++;
	}
	
	ajx_control[ajx_index] = new Ajax(me.callback);
	this.ajax = ajx_control[ajx_index];
	ajx_index++;
	
	this.refresh = function() {
    if(!me.ajax.state()) me.ajax.process('/ajax/' + fn + '/','');
  };
  this.interv = setInterval(me.refresh,rr*1000);
}

function kalive(fn, id, rr, decode) {
	var me = this;
	this.callback = function(response) {
		me.ajax = null;
		ajx_control[ajx_index] = new Ajax(me.callback);
		me.ajax = ajx_control[ajx_index];
		ajx_index++;
	}
	ajx_control[ajx_index] = new Ajax(me.callback);
	this.ajax = ajx_control[ajx_index];
	ajx_index++;
	
	this.refresh = function() {
    if(!me.ajax.state()) me.ajax.process('/ajax/ka/','');
  };
  this.interv = setInterval(me.refresh,30*1000);
}