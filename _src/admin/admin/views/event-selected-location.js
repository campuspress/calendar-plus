(function ($) {
	window.CalendarPlusAdmin.views.SelectedLocation = Backbone.View.extend({
		tagName: 'div',
		className: 'location-selected',
		events: {
			'click #remove-location': 'removeSelectedLocation'
		},
		initialize: function () {
			this.listenTo(this.model, "change", this.render);
		},
		render: function () {
			let template = CalendarPlusAdmin.helpers.template('location-selected-template');
			this.$el.html(template(this.model.toJSON()));
			return this;
		},
		removeSelectedLocation: function (e) {
			e.preventDefault();
			$('#event-location-hidden').val('');
			let self = this;
			this.$el.slideUp(400, function () {
				for (let attr in self.model.attributes) {
					self.model.set(attr, self.model.defaults[attr]);
				}
			});


		}
	});
})(jQuery);
