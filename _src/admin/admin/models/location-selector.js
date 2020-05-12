(function ($) {
	window.CalendarPlusAdmin.models.LocationSelector = Backbone.Model.extend({
		defaults: {
			type: false,
			mapOptions: {},
			markerOptions: {},
			mapModel: false,
			mapView: false
		}
	});
})(jQuery);
