(function ($) {

	window.CalendarPlusAdmin.misc.eventDetailsMetabox = function (args) {
		this.args = {
			selector: false,
			settings: {
				regular: {},
				recurrent: {},
				datespan: {}
			},
			type: 'regular',
			wrapper: false,
			disabled: 0
		};

		$.extend(this.args, args);

		this.settings = this.args.settings;
		this.$selector = this.args.selector;
		this.type = this.args.type;
		this.$el = this.args.wrapper || $('#event-dates');

		if (this.args.disabled == '1') {
			this.disableInputs();
		}

		if (!this.$selector || !this.$selector.length) {
			return false;
		}

		this.init();
	};

	window.CalendarPlusAdmin.misc.eventDetailsMetabox.prototype.disableInputs = function () {
		if (!$('#edit-dates').length)
			return;

		this.$el.find('input, select, #event-dates-recurrent-exclusions-add, #add-regular-event').not('#edit-dates').attr('disabled', 'disabled');
		this.$el.find('a.remove-link').hide();
	};

	window.CalendarPlusAdmin.misc.eventDetailsMetabox.prototype.enableInputs = function () {
		this.$el.find('input, select, #event-dates-recurrent-exclusions-add, #add-regular-event').not('#edit-dates').removeAttr('disabled');
		this.$el.find('a.remove-link').show();
	};


	window.CalendarPlusAdmin.misc.eventDetailsMetabox.prototype.init = function () {
		let self = this;
		let $options = this.$el.find('.event-dates-option');
		this.$options = {};

		this.$options.regular = new window.CalendarPlusAdmin.misc.eventDetailRegularItem({
			element: $options.filter('#event-dates-regular').first(),
			add_new_button: $options.find('#add-regular-event').first(),
			settings: this.settings.regular
		});

		this.$options.recurrent = new window.CalendarPlusAdmin.misc.eventDetailRecurrentItem({
			element: $options.filter('#event-dates-recurrent').first(),
			settings: this.settings.recurrent
		});

		this.$options.datespan = new window.CalendarPlusAdmin.misc.eventDetailDatespanItem({
			element: $options.filter('#event-dates-datespan').first(),
			settings: this.settings.datespan
		});

		this.$options[this.type].display();

		this.$selector.change(function (e) {
			self.setSelection.call(self, $(this).val());
		});

		this.$editDatesCheckbox = this.$el.find('#edit-dates').change(function (e) {
			if ($(this).is(':checked'))
				self.enableInputs();
			else
				self.disableInputs();
		});

	};

	window.CalendarPlusAdmin.misc.eventDetailsMetabox.prototype.setSelection = function (new_value) {
		if (new_value !== this.type) {
			this.$options[this.type].hide();
			this.type = new_value;
			this.$options[this.type].display();

			if (this.$el.find('#edit-dates').is(':checked'))
				this.enableInputs();
			else
				this.disableInputs();
		}
	};


	/** REGULAR OPTIONS */
	window.CalendarPlusAdmin.misc.eventDetailRegularItem = function (args) {
		this.args = {
			settings: {},
			element: false,
			add_new_button: false,
			template: $('#event-regular-date-template')
		};

		$.extend(this.args, args);

		this.index = $('#standard-dates-index').val();
		this.$el = this.args.element;
		this.$add_new_button = this.args.add_new_button;
		this.$template = this.args.template;

		this.init();
	};

	window.CalendarPlusAdmin.misc.eventDetailRegularItem.prototype.init = function () {
		let self = this;

		if (this.index == '0') {
			this.addRegularDate();
		}

		this.$add_new_button.click(function (e) {
			e.preventDefault();
			self.addRegularDate();
			return false;
		});

		$('.remove-regular-event').click(function (e) {
			e.preventDefault();
			self.removeRegularDate($(this).data('regular-date-id'));
			return false;
		});

		this.$el.find('.datepicker').datepicker({
			dateFormat: 'yy-mm-dd'
		});

		let AllDayEventCheckbox = $('#event-dates-regular-time-all-day-event');
		AllDayEventCheckbox.change(function () {
			if ($(this).is(':checked')) {
				self.$el.addClass('all-day-event');
			}
			else {
				self.$el.removeClass('all-day-event');
			}
		});

		AllDayEventCheckbox.trigger('change');
	};

	window.CalendarPlusAdmin.misc.eventDetailRegularItem.prototype.addRegularDate = function () {
		this.index++;
		let self = this;
		let new_element = this.$template.clone();
		new_element
			.attr('id', 'event-regular-date-' + this.index)
			.addClass('event-regular-date-item')
			.show();

		let remove_link = new_element.find('.remove-regular-event').first();

		remove_link.attr('data-regular-date-id', this.index);

		remove_link.click(function (e) {
			e.preventDefault();
			self.removeRegularDate($(this).data('regular-date-id'));
			return false;
		});

		new_element.find('.standard-dates-datepicker').datepicker({
			dateFormat: 'yy-mm-dd'
		});

		new_element.insertBefore(this.$add_new_button);
	};

	window.CalendarPlusAdmin.misc.eventDetailRegularItem.prototype.removeRegularDate = function (index) {
		this.$el.find('#event-regular-date-' + index).slideUp(function () {
			$(this).detach();
		});
		return this.$el;
	};

	window.CalendarPlusAdmin.misc.eventDetailRegularItem.prototype.display = function () {
		this.$el.fadeIn();
		return this.$el;
	};

	window.CalendarPlusAdmin.misc.eventDetailRegularItem.prototype.hide = function () {
		this.$el.fadeOut();
		return this.$el;
	};


	/** DATESPAN OPTIONS */
	window.CalendarPlusAdmin.misc.eventDetailDatespanItem = function (args) {
		this.args = {
			settings: {},
			element: false,
			add_new_button: false,
			template: $('#event-datespan-date-template')
		};

		$.extend(this.args, args);

		this.$el = this.args.element;
		this.$template = this.args.template;

		this.init();
	};

	window.CalendarPlusAdmin.misc.eventDetailDatespanItem.prototype.init = function () {
		let self = this;

		this.$el.find('.datepicker').datepicker({
			dateFormat: 'yy-mm-dd'
		});

		let AllDayEventCheckbox = $('#event-dates-datespan-time-all-day-event');
		AllDayEventCheckbox.change(function () {
			if ($(this).is(':checked')) {
				self.$el.addClass('all-day-event');
			}
			else {
				self.$el.removeClass('all-day-event');
			}
		});

		AllDayEventCheckbox.trigger('change');
	};

	window.CalendarPlusAdmin.misc.eventDetailDatespanItem.prototype.addDatespanDate = function () {
		this.index++;
		let self = this;
		let new_element = this.$template.clone();
		new_element
			.attr('id', 'event-regular-date-' + this.index)
			.addClass('event-regular-date-item')
			.show();

		let remove_link = new_element.find('.remove-regular-event').first();

		remove_link.attr('data-regular-date-id', this.index);

		remove_link.click(function (e) {
			e.preventDefault();
			self.removeDatespanDate($(this).data('regular-date-id'));
			return false;
		});

		new_element.find('.standard-dates-datepicker').datepicker({
			dateFormat: 'yy-mm-dd'
		});

		new_element.insertBefore(this.$add_new_button);
	};

	window.CalendarPlusAdmin.misc.eventDetailDatespanItem.prototype.removeDatespanDate = function (index) {
		this.$el.find('#event-regular-date-' + index).slideUp(function () {
			$(this).detach();
		});
		return this.$el;
	};

	window.CalendarPlusAdmin.misc.eventDetailDatespanItem.prototype.display = function () {
		this.$el.fadeIn();
		return this.$el;
	};

	window.CalendarPlusAdmin.misc.eventDetailDatespanItem.prototype.hide = function () {
		this.$el.fadeOut();
		return this.$el;
	};


	/** RECURRENT OPTIONS */
	window.CalendarPlusAdmin.misc.eventDetailRecurrentItem = function (args) {
		this.args = {
			settings: {},
			element: false,
		};

		$.extend(this.args, args);

		this.exclusionsIndex = $('#recurring-exclusions-index').val();
		this.$el = this.args.element;

		this.$addExclusionsButton = this.$el.find('#event-dates-recurrent-exclusions-add');
		this.$exclusionsList = this.$el.find('#event-dates-recurrent-exclusions-list');
		this.$exclusionsTemplate = this.$el.find('#event-dates-recurrent-exclusions-date-template');
		this.$everyContainers = this.$el.find('.recurring-every-container');
		this.$everySelector = this.$el.find('#recurring-frequency-every');

		this.init();
	};

	window.CalendarPlusAdmin.misc.eventDetailRecurrentItem.prototype.init = function () {
		let self = this;

		this.$addExclusionsButton.click(function (e) {
			e.preventDefault();
			self.addExcludedDate();
			return false;
		});

		this.showEveryContainer(this.$everySelector.val());
		this.$everySelector.change(function () {
			self.showEveryContainer($(this).val());
		});

		let removeLinks = this.$el.find('.remove-exclusions-date');
		removeLinks.click(function (e) {
			e.preventDefault();
			self.removeExcludedDate($(this).data('excluded-date-id'));
			return false;
		});

		let recurrentAllDayEventCheckbox = this.$el.find('#event-dates-recurrent-time-all-day-event');
		recurrentAllDayEventCheckbox.change(function () {

			if ($(this).is(':checked')) {
				$('#event-dates-recurrent-time').find('.recurring-times-from').slideUp();
			}
			else {
				if (!$('#edit-dates').is(':checked'))
					$('#event-dates-recurrent-time').find('.recurring-times-from').hide();

				$('#event-dates-recurrent-time').find('.recurring-times-from').slideDown();
			}

		});

		recurrentAllDayEventCheckbox.trigger('change');

	};

	window.CalendarPlusAdmin.misc.eventDetailRecurrentItem.prototype.hideEveryContainers = function () {
		this.$everyContainers.hide();
		this.$everyContainers.find('.recurring-every-field').attr('disabled', true);
	};

	window.CalendarPlusAdmin.misc.eventDetailRecurrentItem.prototype.showEveryContainer = function (slug) {
		this.hideEveryContainers();
		this.$everyContainers.filter('.recurring-every-' + slug).show();
		this.$everyContainers.find('.recurring-every-field-' + slug).attr('disabled', false);
	};

	window.CalendarPlusAdmin.misc.eventDetailRecurrentItem.prototype.display = function () {
		this.$el.fadeIn();
		return this.$el;
	};

	window.CalendarPlusAdmin.misc.eventDetailRecurrentItem.prototype.hide = function () {
		this.$el.fadeOut();
		return this.$el;
	};

	window.CalendarPlusAdmin.misc.eventDetailRecurrentItem.prototype.addExcludedDate = function () {
		let self = this;

		this.exclusionsIndex++;
		let newElement = this.$exclusionsTemplate.clone();
		newElement
			.attr('id', 'recurring-exclusions-' + this.exclusionsIndex)
			.removeClass('hidden');


		let removeLink = newElement.find('.remove-exclusions-date').first();

		removeLink.attr('data-excluded-date-id', this.exclusionsIndex);

		removeLink.click(function (e) {
			e.preventDefault();
			self.removeExcludedDate($(this).data('excluded-date-id'));
			return false;
		});

		newElement = this.$exclusionsList.append(newElement);

		newElement.find('.recurring-exclusions-datepicker').datepicker({
			dateFormat: 'yy-mm-dd'
		});
	};

	window.CalendarPlusAdmin.misc.eventDetailRecurrentItem.prototype.removeExcludedDate = function (index) {
		this.$exclusionsList.find('#recurring-exclusions-' + index).detach();
		return this.$exclusionsList;
	};

	window.CalendarPlusAdmin.misc.eventLocationSearch = function() {
		this.handleSearchBlur = this.handleSearchBlur.bind(this);
		this.handleSearchFocus = this.handleSearchFocus.bind(this);
	};

	window.CalendarPlusAdmin.misc.eventLocationSearch.prototype.init = function(args) {
		if(! args.model) {
			this.location = new CalendarPlusAdmin.models.EventLocation();
		} else if(! (args.model instanceof CalendarPlusAdmin.models.EventLocation)) {
			this.location = new CalendarPlusAdmin.models.EventLocation(args.model)
		} else {
			this.location = $.clone(defaultLocation);
		}
		const dispatcher = Backbone.Events;
		const self = this;

		dispatcher.on('location:selected', function(model){
			const searchArgs = {...args, model:model};
			self.renderLocation(searchArgs);
			self.releaseSidebar();
		});

		this.renderLocation({...args, model: this.location});
	};

	window.CalendarPlusAdmin.misc.eventLocationSearch.prototype.renderLocation = function(args) {
		const $search = $('#location-search');
		$search.children().remove();

		this.locationSearchView  = new CalendarPlusAdmin.views.LocationSearcher(args);
		$search.append(this.locationSearchView.render().el);
		this.attachEvents();
	};

	window.CalendarPlusAdmin.misc.eventLocationSearch.prototype.attachEvents = function(){
		this.locationSearchView.$searchInput.on('focus', this.handleSearchFocus);
		this.locationSearchView.$searchInput.on('blur', this.handleSearchBlur);
	};

	window.CalendarPlusAdmin.misc.eventLocationSearch.prototype.handleSearchFocus = function(){
		this.lockSidebar();
		this.locationSearchView.showResults();
	};
	window.CalendarPlusAdmin.misc.eventLocationSearch.prototype.lockSidebar = function() {
		const sidebar = document.getElementsByClassName('interface-interface-skeleton__sidebar')[0];
		var scrollTop = sidebar.scrollTop;
		var scrollLeft = sidebar.scrollLeft;
		sidebar.onscroll = function() {
			sidebar.scrollTo(scrollLeft, scrollTop);
		};
	}

	window.CalendarPlusAdmin.misc.eventLocationSearch.prototype.handleSearchBlur = function() {
		this.locationSearchView.hideResults();
		this.releaseSidebar();
	};
	window.CalendarPlusAdmin.misc.eventLocationSearch.prototype.releaseSidebar = function() {
		const sidebar = document.getElementsByClassName('interface-interface-skeleton__sidebar')[0];
		sidebar.onscroll = function(){};
	};
})(jQuery);
