<!-- INCLUDE js/j.js -->
<!-- INCLUDE js/j.periodic.js -->
<!-- INCLUDE js/j.url.js -->

$(function() {
	var xka = true; 
	
	if ($.url.segment() > 0) {
		switch ($.url.segment(0)) {
			case 'my':
				switch ($.url.segment(1)) {
					case 'register':
						$('#refop').change(function() {
							switch (this.value) {
								case '1':
									text = 'E-mail de tu amigo';
									break;
								case '8':
									text = 'Detalles';
									break;
								default:
									text = 'Nombre';
									break;
							}
							$('#tag_refby').html(text);
							$('#refby').focus();
						});
						break;
				}
				break;
			case 'topic':
			case 'post':
				$('.lsig').each(function() {
					if ($(this).height() > 275) {
						$(this).addClass('sig-of');
					}
				});
				break;
			case 'a':
				if (!$.url.segment(1)) {
					xka = false;
					_.call('athumbs', 'ajx-thumbnails', 30);
				}
				break;
			case 'community':
				xka = false;
				_.call('commol', 'online', 10);
				break;
		}
	}
	
	$('#account').hide();
	
	if (xka) {
		// Keep alive
		$.PeriodicalUpdater('/ajax/ka/', {
			method: 'post',
			data: {ajax: '1'},
			minTimeout: 10000,
			maxTimeout: 15000
		});
	}
});

var _ = {
	call: function(action, el, rate, decode) {
		$.PeriodicalUpdater('/ajax/' + action + '/', {
			method: 'post',
			data: {ajax: '1'},
			minTimeout: ((rate - 1) * 1000),
			maxTimeout: (rate * 1000),
			success: function(data) {
				if (el) {
					response = (decode) ? unescape(data) : data;
					$('#' + el).html(response);
				}
			}
		});
	}
}

/*
var _ = {
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
		}
	}
}
*/