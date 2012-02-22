var TNWSC = {

	init: function() {
		var t;
		var autohide = true;		
		jQuery('.tooltip-wrapper, #sharing-widget').hover(function() {
			clearTimeout(t);
			TNWSC.show_tooltip(true);
			autohide = true;
		}, function() {
			if(autohide === true) {
				t = setTimeout("TNWSC.show_tooltip(false)", 800);
			}
		});	

		jQuery('#tooltip-close').click(function() {
			clearTimeout(t);
			TNWSC.show_tooltip(false);
			autohide = true;
		});	
		
		jQuery(".popup-link").click(function(e) {
			TNWSC.open_popup($(this).attr("href"));
			e.preventDefault();
		});
	},
	
	show_tooltip: function(show) {
		if(show) {
			jQuery('.icon-share').addClass('active');
			jQuery('.tooltip-wrapper').show();		
		} else {
			jQuery('.icon-share').removeClass('active');
			jQuery('.tooltip-wrapper').hide();		
		}
	},

	open_popup: function(url) {
	
		var width = 640;
		var height = 420; 
		var popupName = 'popup_' + width + 'x' + height;
		
		var left = (screen.width-width)/2;
		var top = ((screen.height-height)/2)+25;
		var params = 'width=' + width + ',height=' + height + ',location=no,menubar=no,scrollbars=yes,status=no,toolbar=no,left=' + left + ',top=' + top;
		
		window[popupName] = window.open(url, popupName, params);
		
		if(window.focus) {
			window[popupName].focus();
		}
	}

};

jQuery(document).ready(function() {

	TNWSC.init();
	
});