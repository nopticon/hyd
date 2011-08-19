var _ = {
	config: {
		list: [],
		get: function(name) {
			return _.config.list[name];
		},
		set: function(name, value) {
			_.config.list[name] = value;
		}
	},
	glob: {
		len: function(ary) {
			return ary.length;
		},
		in_disable: function(el, mode) {
			eval("el.disabled = mode;");
		},
		in_value: function(el, value) {
			eval("el.value = '" + value + "'");
		},
		in_sending: function(el) {
			el = $(el);
			setTimeout("_.glob.in_disable(el, true); _.glob.in_value(el, 'Enviando...');", 0);
		},
		in_timed_disable: function(el) {
			el = $(el);
			setTimeout("_.glob.in_disable(obj, true);", 0);
			setTimeout("_.glob.in_disable(obj, false);", 5000);
		}
	},
	register: {
		change: function(t) {
			switch (t.selectedIndex) {
				case 0:
					text = 'E-mail de tu amigo';
					break;
				case 7:
					text = 'Detalles';
					break;
				default:
					text = 'Nombre';
					break;
			}
			Element.update('tag_refby', text);
			$('refby').focus();
		}
	},
	frame: {
		list: [],
		call: function(action, el, rate, decode) {
			clearTimeout(_.frame.list[el]);
			_.frame.list[el] = setTimeout("_.frame.ajax('" + action + "', '" + el + "', '" + rate + "', '" + decode + "');", rate * 1000);
		},
		ajax: function(action, el, rate, decode) {
			var v = {
				method: 'post',
				postBody: 'ajax=1',
				asynchronous: true,
				onSuccess: function(t) {
					if (el) {
						response = (decode) ? unescape(t.responseText) : t.responseText;
						Element.update(el, response);
					}
				}
			}
			new Ajax.Request('/ajax/' + action + '/', v);
			
			_.frame.call(action, el, rate, decode);
		},
		alive: function() {
			var v = {
				method: 'post',
				postBody: 'ajax=1',
				asynchronous: true,
				frequency: 30,
				onSuccess: Prototype.emptyFunction
			}
			new Ajax.PeriodicalUpdater('keepalive', '/ajax/ka/', v);
		}
	},
	signature: function(a) {
		this.callback = function() {
			size = _.glob.len(a);
			if (!size) {
				return;
			}
			
			for (var i = 0; i < size; i++) {
				try {
					each = '_sig_' + a[i];
					height = Element.getHeight(each);
					
					if (height > 275) {
						Element.addClassName(each, 'sig-of');
					}
				} catch (e) {}
			}
		}
		Event.observe(window, 'load', this.callback);
	}
}

var radio = {
	cfg: [],
	config: function(name, value) {
		radio.cfg[name] = value;
	},
	playlist: {
		every: 5000,
		_timeout: 0,
		
		timer: function() {
			last = radio.playlist.last();
			var v = {
				asynchronous: true,
				method: 'post',
				postBody:'ajax=1&mode=playlist&last=' + last,
				onSuccess: radio.playlist.callback,
				onFailure: function(t) {
					alert('Error ' + t.status + ' -- ' + t.statusText);
				}
			}
			//new Ajax.Request(radio.cfg['playlist'], v);
		},
		callback: function(t) {
			_x = t.responseXML;
			ul = $('playlist');
			var root = _x.getElementsByTagName('root');
			var node = _x.getElementsByTagName('song');
			
			last = radio.playlist.last();
			
			for (var i = 0, end = _.glob.len(node); i < end; i++) {
				var _title = node[i].getElementsByTagName('title');
				var _time = node[i].getElementsByTagName('time');
				id = node[i].getAttribute('id');
				
				li = '<li id="' + id + '">' + _time[0].firstChild.nodeValue + ' <strong>' + _title[0].firstChild.nodeValue + '</strong></li>';
				new Insertion.Bottom(ul, li);
			}
			
			a = Element.findChildren(ul, false, false, 'li');
			if (_.glob.len(a) > 10) {
				end = _.glob.len(a) - 10;
				for (var i = 0; i < end; i++) {
					Element.remove(ul.firstChild);
				}
			}
			
			//
			radio.playlist.timeout();
		},
		timeout: function() {
			clearTimeout(radio.playlist._timeout);
			radio.playlist._timeout = setTimeout("radio.playlist.timer()", radio.playlist.every);
		},
		last: function() {
			last = 0;
			a = Element.findChildren($('playlist'), false, false, 'li');
			for (var i = 0, end = _.glob.len(a); i < end; i++) {
				last = a[i].id;
			}
			return last;
		}
	},
	
	listener: {
		start: function() { }
	}
}

function popup(url, name, width, height) {
	var win = window.open(url, name, 'toolbar = 0, scrollbars = 1, location = 0, statusbar = 0, menubar = 0, resizable = 1, width=' + width + ', height=' + height);
	return false;
}