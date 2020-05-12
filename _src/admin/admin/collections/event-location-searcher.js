/**
 * Collection for the Search Results
 */
(function ($) {
	window.CalendarPlusAdmin.collections.searchResults = Backbone.Collection.extend({
		model: CalendarPlusAdmin.models.EventLocation
	});
})(jQuery);
