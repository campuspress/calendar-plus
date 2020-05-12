(function ($) {
	window.CalendarPlusAdmin.models.GMap = Backbone.Model.extend({
		defaults: {
			mapOptions: {},
			markerOptions: {},
			address: false,
			marker: false,
			center: false,
			address: '',
			infowindow: false,
			textBox: false,
			htmlBox: false
		},
		initialize: function () {
			this.set('address', this.get('markerOptions').address);
		}
	});
})(jQuery);
