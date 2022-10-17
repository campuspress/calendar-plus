(function ($) {
	/**
	 * View for CalendarPlusAdmin.models.LocationSearcher Model
	 */
	window.CalendarPlusAdmin.views.LocationSearcher = Backbone.View.extend({
		searching: false,
		xhr: false,
		searchTimer: false,
		selectedView: false,
		$spinner: false,
		$locationResultsWrapper: false,
		$searchInput: false,

		events: {
			'keyup #location-search-string': 'preSearchLocation',
			'click #location-search-close': 'cleanSearchBox',
		},

		render: function () {
			let template = CalendarPlusAdmin.helpers.template('location-search-template');
			this.$el.html(template(this.model.toJSON()));

			// Cache the spinner element
			let spinner = this.$el.find('.spinner');
			this.$spinner = spinner.length ? spinner : false;

			// Cache the results container element
			let resultsWrapper = this.$el.find('#location-results');
			this.$resultsWrapper = resultsWrapper.length ? resultsWrapper : false;

			// Cache the search input element
			let searchInput = this.$el.find('#location-search-string');
			this.$searchInput = searchInput.length ? searchInput : false;

			return this;
		},
		showResults: function() {
			if (this.$resultsWrapper.children().length) {
				this.$resultsWrapper.css({
					left: this.$searchInput.offset().left,
					top: this.$searchInput.offset().top - this.$resultsWrapper.height() - 10
				});
				this.$resultsWrapper.show();
			}
		},
		hideResults: function() {
			this.$resultsWrapper.hide();
		},
		preSearchLocation: function (e) {
			this.initTimer();

			console.log(e.keyCode);
		},
		searchLocation: function () {
			let model = this.model;
			let self = this;

			model.set('searchString', this.$searchInput.val());

			// Abort the previous ajax call if is still in process
			if (typeof self.xhr.abort === 'function')
				self.xhr.abort();

			// Do not seach if we are currently searching.
			// The search needs to be more than 2 characters
			if (!self.searching && model.get('searchString').length > 2) {
				this.hideResults();
				// Show the spinner gif
				if (this.$spinner)
					this.$spinner.show();

				// We are now searching
				self.searching = true;

				// AJAX Call!
				self.xhr = $.ajax({
					url: CalendarPlusi18n.ajaxurl,
					data: {
						action: 'search_event_location',
						s: model.get('searchString')
					},
				})
					.done(function (results) {
						self.searching = false;
						if (results.length) {
							// Create the new collection and view
							let searchResultsCollection = new CalendarPlusAdmin.collections.searchResults(results);
							let searchResults = new CalendarPlusAdmin.views.searchResults({
								collection: searchResultsCollection,
								searchInput: self
							});
							self.displayResults(searchResults);
						}
					})
					.always(function () {
						if (self.$spinner)
							self.$spinner.hide();
						self.searching = false;
					});
			}
		},
		initTimer: function () {
			let self = this;
			window.clearTimeout(this.searchTimer);
			this.searchTimer = window.setTimeout(function () {
				self.searchLocation();
			}, 1000);
		},
		cleanSearchBox: function () {
			this.$searchInput.val('');
			this.model.set('searchString', '');
			this.$resultsWrapper.hide();
			this.$resultsWrapper.html('');
		},
		cleanSearch: function () {
			this.$resultsWrapper.fadeOut();
			this.$resultsWrapper.html('');
		},
		/**
		 * Display the search results
		 *
		 * @param  Collection results searchResults Collection
		 */
		displayResults: function (results) {
			this.cleanSearch();
			this.$resultsWrapper.append(results.render().el);
			this.$resultsWrapper.css({
				left: this.$searchInput.offset().left,
				top: this.$searchInput.offset().top - this.$resultsWrapper.height() - 10
			});
			this.$resultsWrapper.show();
		}
	});

	/**
	 * View for CalendarPlusAdmin.models.EventLocation Model
	 */
	window.CalendarPlusAdmin.views.SearchResult = Backbone.View.extend({
		tagName: 'li',
		events: {
			'mousedown .location-result': 'selectLocation'
		},
		render: function () {
			let template = CalendarPlusAdmin.helpers.template('location-search-result-template');
			this.$el.html(template(this.model.toJSON()));
			return this;
		},
		selectLocation: function (e) {
			let location_id = this.model.get('id');
			$('#event-location-hidden').val(location_id);

			const dispatcher = Backbone.Events;
			dispatcher.trigger('location:selected', this.model);
		}
	});

	/**
	 * View for CalendarPlusAdmin.collections.searchResults Collection
	 */
	window.CalendarPlusAdmin.views.searchResults = Backbone.View.extend({
		tagName: 'ul',
		render: function () {
			this.collection.each(this.addOne, this);
			return this;
		},
		addOne: function (searchResult) {
			let searchResultView = new CalendarPlusAdmin.views.SearchResult({model: searchResult});
			this.$el.append(searchResultView.render().el);
		}
	});
})(jQuery);
