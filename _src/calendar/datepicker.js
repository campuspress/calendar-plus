/* global jQuery */

(function ($) {

	window.calendar_plus.libs.datepicker = {
		init: function () {
			let datepickers = $('.calendarp-datepicker');
			if (datepickers.length) {
				datepickers.datepicker({
					dateFormat: 'yy-mm-dd'
				});
			}
		}
	};

})(jQuery);
