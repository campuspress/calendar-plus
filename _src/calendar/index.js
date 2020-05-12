/* global jQuery */
import 'core-js';
(function ($) {
	'use strict';

	window.calendar_plus = {
		libs: {}
	};

})(jQuery);

require('./accessible');

require('./datepicker.js');
require('./google-maps.js');
require('./calendar.js');

jQuery(document).ready(function ($) {
	for (let i in calendar_plus.libs) {
		if (calendar_plus.libs.hasOwnProperty(i) && typeof calendar_plus.libs[i].init === 'function') {
			calendar_plus.libs[i].init();
		}
	}
});

require('./react-calendar/index.js');
