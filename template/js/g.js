<!-- INCLUDE js/j.js -->
<!-- INCLUDE js/j.value.js -->
<!-- INCLUDE js/j.periodic.js -->
<!-- INCLUDE js/j.url.js -->
<!-- INCLUDE js/j.textarea.js -->
<!-- INCLUDE js/j.social.js -->
<!-- INCLUDE js/j.search.js -->
<!-- INCLUDE js/j.check.js -->
<!-- INCLUDE js/j.scroll.js -->
<!-- INCLUDE js/j.wheel.js -->
<!-- INCLUDE js/j.fancy.js -->
<!-- INCLUDE js/j.slider.js -->
<!-- INCLUDE js/j.filestyle.js -->
<!-- INCLUDE js/png.js -->

function popup(url, name, width, height) {
	var win = window.open(url, name, 'toolbar = 0, scrollbars = 1, location = 0, statusbar = 0, menubar = 0, resizable = 1, width=' + width + ', height=' + height);
	return false;
}

$(function() {
	var xka = true;
	var $d;
	var doctitle = document.title;
	var docurl = window.location.href;
	var window_size = $(window).width();
	
	$('#searchForm').jQLiteID();
	
	$('ul[id^="expand_"]').hide().addClass('flying');
	
	$('.expand').click(function(event) {
		event.preventDefault();
		
		position = $(this).position();
		$('#expand_' + $(this).attr('id')).css('top', position.top + $(this).height() + 9);
		$('#expand_' + $(this).attr('id')).css('left', position.left + 1);
		
		$('#expand_' + $(this).attr('id')).slideToggle('medium');
		return false;
	});
	
	$('textarea').autoResize({
		onReize: function() {
			$(this).css({opacity: 0.8});
		},
		animateCallback: function() {
			$(this).css({opacity: 1});
		},
		limit: 250
	});
	
	$('.w_broadcast a').click(function(event) {
		event.preventDefault();
		
		popup($(this).attr('href'), '_broadcast', 400, 500);
	});
	
	$('.pub').click(function(event) {
		event.preventDefault();
		$.scrollTo('.publish');
	});
	
	$('.share').each(function() {
		if (docurl && doctitle) {
			$(this).html('<a rel="prettySociable" href="' + docurl + '"></a><span>Arrastra<br />y comparte</span>');
		}
	});
	
	$.prettySociable({
		share_on_label: 'Compartir en ',
		share_label: 'Comparte',
		hover_padding: 0,
		tooltip: {
			offsetTop:500,
			offsetLeft: 0
		}
	});
	
	$("input[type=file]").filestyle({
		image: "//assets.orion.com/style/file.png",
		imagewidth : 90,
		imageheight : 20,
		width : 250
	});
	
	$(".vcheck").vchecks();
	$(".fancy").fancybox();
	
	$('.smile').each(function() {
		$(this).html('&#8594;&#9786;');
		$(this).attr('title', 'Mostrar emociones');
	}).click(function() {
		popup($(this).attr('href'), '_emoticons', 300, 450);
		return false;
	});
	
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
	
	if (xka) {
		// Keep alive
		$.PeriodicalUpdater('/async/ka/', {
			method: 'post',
			data: {ajax: '1'},
			minTimeout: 10000,
			maxTimeout: 15000
		});
	}
	
	$('div[id^="hse_"]').each(function() {
		$d = $('#se_' + this.id.substr(4)).empty();
		$('ins:first', this).appendTo($d).addClass('rows5_top_2');
	});
});

var _ = {
	call: function(action, el, rate, decode) {
		$.PeriodicalUpdater('/async/' + action + '/', {
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
			new Ajax.Request('/async/' + action + '/', v);
			
			_.frame.call(action, el, rate, decode);
		}
	}
}
*/