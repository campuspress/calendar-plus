(function ($) {
	window.CalendarPlusAdmin.views.LocationSelector = Backbone.View.extend({
		$currentTab: false,
		tabs: [],
		$currentSelector: false,
		$metaboxInside: false,

		events: {
			'click .location-selector': 'setSelector'
		},

		initialize: function () {
			this.$metaboxInside = $('#calendar-location-location').first();
			this.listenTo(this.model, 'change:type', this.changeType);
		},
		render: function () {
			let template = CalendarPlusAdmin.helpers.template('location-type-selector-template');
			this.$el.html(template({}));

			if (!this.tabs.length) {
				let tabs = this.$el.find('.location-selector-tab');
				let self = this;
				tabs.each(function (index, tab) {
					self.tabs[tab.id] = $(tab);
				});
			}

			return this;
		},
		setSelector: function (e) {
			e.preventDefault();
			e.stopPropagation();
			let new_type = $(e.currentTarget).data('type');
			if (!new_type || new_type === this.model.get('type')) {
				return;
			}
			this.model.set('type', new_type);
		},
		changeType: function () {
			let type = this.model.get('type');
			if (!type || !this.tabs['location-' + type]) {
				return;
			}

			if (this.$currentSelector) {
				this.$currentSelector.removeClass('selected');
			}

			let $oldTab = this.$currentTab;

			$('input[name=location-type]').val(type);

			this.$currentTab = this.tabs['location-' + type].first();
			this.$currentSelector = $('#location-selector-' + type);
			this.$currentSelector.addClass('selected');

			this.$currentTab.show();
			if ($oldTab && $oldTab[0] !== this.$currentTab[0]) {
				$oldTab.hide();
			}

			if (type === 'gmaps') {
				this.loadMap();
			}
		},
		loadMap: function () {
			if (!this.mapModel) {
				this.mapModel = new CalendarPlusAdmin.models.GMap({
					mapOptions: this.model.get('mapOptions'),
					markerOptions: this.model.get('markerOptions'),
				});

				this.mapView = new CalendarPlusAdmin.views.GMap({model: this.mapModel});
				this.mapView.render();

			}
		}
	});
})(jQuery);
