(function ($) {
	tinymce.PluginManager.add('calendarp_shortcodes', function (editor) {
		let ed = tinymce.activeEditor;

		let buildTermCheckboxes = function (values, prefix) {
			let checkboxes = [];

			for (let i in values) {
				if (values.hasOwnProperty(i)) {
					checkboxes.push({
						type: 'checkbox',
						name: prefix + values[i].value,
						text: values[i].text,
						value: values[i].value
					});
				}
			}

			return checkboxes;
		};

		let categoryValues = ed.getLang('calendarp_l10n.category_field_values');
		let tagValues = ed.getLang('calendarp_l10n.tag_field_values');

		let categoriesCheckboxes = buildTermCheckboxes(categoryValues, 'category_');
		let tagsCheckboxes = buildTermCheckboxes(tagValues, 'tag_');

		let categoriesValues = categoryValues;

		let calendarp_menu = [
			{
				text: ed.getLang('calendarp_l10n.calendar_title'),
				onclick: function () {
					editor.windowManager.open({
						title: ed.getLang('calendarp_l10n.calendar_title'),
						body: [
							{
								type: 'infobox',
								classes: 'categories-infobox',
								minHeight: 50,
								border: 'none'
							},
							{
								type: 'container',
								name: 'categories_list_start',
								label: ed.getLang('calendarp_l10n.category_field_title'),
								items: categoriesCheckboxes,
								html: '<div class="calp_mce_categories_list">'
							},
							{
								type: 'container',
								name: 'categories_list_end',
								label: '',
								html: '</div>'
							},
							{
								type: 'listbox',
								name: 'default_view_field',
								label: ed.getLang('calendarp_l10n.default_view_title'),
								values: ed.getLang('calendarp_l10n.default_view_values'),
							},
							{
								type: 'listbox',
								name: 'time_format',
								label: 'Time format',
								values: [
									{text: '11:00 pm', value: 'g:i a'},
									{text: '23:00', value: 'H:i'}
								]
							},
							{
								type: 'listbox',
								name: 'dow_format',
								label: 'Day of the week format',
								values: [
									{text: 'Sunday', value: 'l'},
									{text: 'Sun', value: 'D'}
								]
							},
							{
								type: 'listbox',
								name: 'month_name_format',
								label: 'Month name format',
								values: [
									{text: 'Jan', value: 'M'},
									{text: 'January', value: 'F'}
								]
							},
							{
								type: 'listbox',
								name: 'day_format',
								label: 'Day format',
								values: [
									{text: '09', value: 'd'},
									{text: '9', value: 'j'}
								]
							},
							{
								type: 'listbox',
								name: 'date_format',
								label: 'Date format',
								values: [
									{text: '15/09', value: 'd/m'},
									{text: '15/9', value: 'j/n'},
									{text: '09/15', value: 'm/d'},
									{text: '9/15', value: 'n/j'}
								]
							},
							{
								type: 'infobox',
								classes: 'day-format-infobox',
								minHeight: 34,
								border: 'none'
							}
						],
						onopen: function (e) {
							e.target.$el.find('.mce-day-format-infobox')
								.append('<a target="_blank" href="https://codex.wordpress.org/Formatting_Date_and_Time">Documentation on date and time formatting</a>')
								.css({
									color: '#0073aa',
								});

							e.target.$el.find('.mce-categories-infobox')
								.prepend('<p>' + ed.getLang('calendarp_l10n.category_field_info') + '</p>');
						},
						onsubmit: function (e) {
							let category_field,
								default_view_field,
								selectedCategories,
								time_format,
								dow_format,
								month_name_format,
								day_format,
								date_format;

							selectedCategories = [];
							for (i in categoriesValues) {
								if (categoriesValues.hasOwnProperty(i)) {
									if (true === e.data['category_' + categoriesValues[i].value]) {
										selectedCategories.push(categoriesValues[i].value);
									}
								}
							}

							if (0 !== selectedCategories.length) {
								category_field = ' category="' + selectedCategories.join(',') + '"';
							} else {
								category_field = '';
							}

							if (e.data.default_view_field !== 'month') {
								default_view_field = ' view="' + e.data.default_view_field + '"';
							} else {
								default_view_field = '';
							}

							if (e.data.time_format !== ed.getLang('calendarp_l10n.default_time_format')) {
								time_format = ' time_format="' + e.data.time_format + '"';
							}
							else {
								time_format = '';
							}

							if (e.data.dow_format !== 'l') {
								dow_format = ' dow_format="' + e.data.dow_format + '"';
							}
							else {
								dow_format = '';
							}

							if (e.data.month_name_format !== 'M') {
								month_name_format = ' month_name_format="' + e.data.month_name_format + '"';
							}
							else {
								month_name_format = '';
							}

							if (e.data.day_format !== 'd') {
								day_format = ' day_format="' + e.data.day_format + '"';
							}
							else {
								day_format = '';
							}

							if (e.data.date_format !== 'd/m') {
								date_format = ' date_format="' + e.data.date_format + '"';
							}
							else {
								date_format = '';
							}


							editor.insertContent('[calendarp-calendar'
								+ category_field
								+ default_view_field
								+ time_format
								+ dow_format
								+ month_name_format
								+ date_format
								+ day_format
								+ ']');
						}
					});
				}
			},
			{
				text: ed.getLang('calendarp_l10n.events_by_cat_title'),
				onclick: function () {
					editor.windowManager.open({
						title: ed.getLang('calendarp_l10n.events_by_cat_title'),
						body: [
							{
								type: 'infobox',
								classes: 'categories-infobox',
								minHeight: 50,
								border: 'none'
							},
							{
								type: 'container',
								name: 'events_by_cat_categories_list_start',
								label: ed.getLang('calendarp_l10n.events_by_cat_category_field_title'),
								items: categoriesCheckboxes,
								html: '<div class="calp_mce_categories_list">'
							},
							{
								type: 'container',
								name: 'events_by_cat_categories_list_end',
								label: '',
								html: '</div>'
							},
							{
								type: 'container',
								name: 'events_by_cat_tags_list_start',
								label: ed.getLang('calendarp_l10n.events_by_cat_tag_field_title'),
								items: tagsCheckboxes,
								html: '<div class="calp_mce_tags_list">'
							},
							{
								type: 'container',
								name: 'events_by_cat_tags_list_end',
								label: '',
								html: '</div>'
							},
							{
								type: 'textbox',
								name: 'events_by_cat_events_count_field',
								label: ed.getLang('calendarp_l10n.events_by_cat_events_count_field_title'),
								value: '0',
								maxLength: 3,
								size: 3
							}
						],
						onopen: function (e) {
							e.target.$el.find('.mce-categories-infobox')
								.prepend('<p>' + ed.getLang('calendarp_l10n.events_by_cat_info') + '</p>');
						},
						onsubmit: function (e) {

							let extractTerms = function (values, prefix) {
								let selected = [];

								for (let i in values) {
									if (values.hasOwnProperty(i)) {
										if (true === e.data[prefix + values[i].value]) {
											selected.push(values[i].value);
										}
									}
								}

								return selected;
							};

							let selectedCategories = extractTerms(categoryValues, 'category_');
							let selectedTags = extractTerms(tagValues, 'tag_');

							let category_field = (0 === selectedCategories.length) ? '' :
								' category="' + selectedCategories.join(',') + '"';

							let tag_field = (0 === selectedTags.length) ? '' :
								' tag="' + selectedTags.join(',') + '"';

							let events_field = (e.data.events_by_cat_events_count_field !== '0') ?
								' events="' + e.data.events_by_cat_events_count_field + '"' : '';

							editor.insertContent('[calendarp-events-list' + category_field + tag_field + events_field + ']');
						}
					});
				}
			},
			{
				text: ed.getLang('calendarp_l10n.single_event_title'),
				onclick: function () {
					editor.windowManager.open({
						title: ed.getLang('calendarp_l10n.single_event_title'),
						body: [
							{
								type: 'textbox',
								name: 'event_title',
								classes: 'event-title-field',
								label: ed.getLang('calendarp_l10n.search_event'),
								value: '',
								size: 25
							},
							{
								type: 'textbox',
								name: 'event_id',
								classes: 'event-id-field',
								value: '',
								hidden: true
							}
						],
						onopen: function (e) {
							let $el = jQuery(e.target.$el.find('.mce-event-title-field'));
							// Set a placeholder
							$el.attr('placeholder', ed.getLang('calendarp_l10n.search_event_by_title'));
							let $idField = jQuery(e.target.$el.find('.mce-event-id-field'));
							$el.autocomplete({
								source: function (request, response) {
									let eventApi = new wp.api.collections.Calendar_event();
									eventApi
										.fetch({data: {search: request.term}})
										.done(function (data) {
											let dataLength = data.length,
												parsedData = [],
												i;

											for (i = 0; i < dataLength; i++) {
												parsedData.push({
													id: data[i].id,
													// HTML is expected in title, just get the text
													label: jQuery('<span />').html(data[i].title.rendered).text()
												});
											}

											// Return the data to autocomplete
											response(parsedData);
										});

								},
								appendTo: $el.parent(),
								search: function () {
									$idField.val('');
								},
								select: function (event, ui) {
									$idField.val(ui.item.id);
								}
							});
						},
						onsubmit: function (e) {
							let event_id_field;
							if (e.data.event_id != 'all')
								event_id_field = ' event_id="' + e.data.event_id + '"';
							else
								event_id_field = '';

							editor.insertContent('[calendarp-event' + event_id_field + ']');
						}
					});
				}
			}
		];

		editor.addButton('calendarp_shortcodes', {
			icon: 'mce-i-calendarp',
			menu: calendarp_menu,
			type: 'menubutton'
		});
	});


})(jQuery);
