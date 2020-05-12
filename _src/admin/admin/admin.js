(function ($) {
	window.CalendarPlusAdmin = {

		models: {},
		views: {},
		collections: {},
		misc: {},

		/*****************************************************************/
		/**                        HELPERS                                **/
		/*****************************************************************/
		helpers: {
			/**
			 * Load a Backbone template from the HTML code
			 * @param  string id HTML ID attribute
			 * @return object    Underscore Template object
			 */
			template: function (id) {
				return _.template(document.getElementById(id).innerHTML);
			},

			/**
			 * Set a new selected location in Location Meta box
			 * @param object model CalendarPlusAdmin.models.EventLocation Model
			 */
			setSelectedLocation: function (model) {
				if (!model.toJSON().id)
					return;

				let selectedLocationView = new CalendarPlusAdmin.views.SelectedLocation({model: model});
				$('#selected-location')
					.html('')
					.hide()
					.append(selectedLocationView.render().el)
					.fadeIn();

			}
		}// End helpers
	};
})(jQuery);

require('./models/location-selector.js');
require('./views/location-selector.js');
require('./models/location-gmap.js');
require('./views/location-gmap.js');
require('./misc/event-details.js');
require('./misc/calendar.js');
