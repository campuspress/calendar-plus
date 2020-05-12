(function ($) {
	/**
	 * The model of a single Location
	 */
	window.CalendarPlusAdmin.models.EventLocation = Backbone.Model.extend({
		defaults: {
			id: 0,
			excerpt: '',
			thumbnail: '',
			title: ''
		}
	});
})(jQuery);
