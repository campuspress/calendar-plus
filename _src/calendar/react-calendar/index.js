import 'core-js';
import React from 'react';
import {render} from 'react-dom';
import BigCalendar from 'react-big-calendar';
import moment from 'moment';
import formatPHP from './helpers/formatPHP';
import CalendarPlus from './CalendarPlus';

if (typeof calendarPlusi18n !== 'undefined') {
	// Setup the localizer by providing the moment (or globalize) Object
	// to the correct localizer.

	// Set the week start
	moment.updateLocale(moment().locale(), {week: {dow: calendarPlusi18n.dowStart}});
	const localizer = BigCalendar.momentLocalizer(moment);

	const dateRangeFormat = function dateRangeFormat(monthNameFormat, yearFormat) {
		return function (_ref, culture, local) {
			return local.format(_ref.start, monthNameFormat + ' ' + yearFormat, culture);
		}
	};

	const timeRangeFormat = function timeRangeFormat(timeFormat) {
		return function (_ref2, culture, local) {
			return local.format(_ref2.start, timeFormat, culture) + ' — ' + local.format(_ref2.end, timeFormat, culture);
		}
	};

	const weekRangeFormat = function weekRangeFormat(monthNameFormat, dayFormat) {
		return function (_ref3, culture, local) {
			// If the months at start and end are different, show both months names
			if (_ref3.start.getMonth() !== _ref3.end.getMonth()) {
				return local.format(_ref3.start, monthNameFormat + ' ' + dayFormat, culture)
					+ ' - '
					+ local.format(_ref3.end, monthNameFormat + ' ' + dayFormat, culture);
			}

			return local.format(_ref3.start, monthNameFormat + ' ' + dayFormat, culture)
				+ ' - '
				+ local.format(_ref3.end, dayFormat, culture);
		}
	};

	for (let instance of calendarPlusi18n.instances) {
		if ( ! document.querySelector( `#${ ( instance || {} ).id || '' }` ) ) {
			continue;
		}

		let views = instance.views ? instance.views : ['month', 'week', 'day', 'agenda'];
		let editable = instance.editable === undefined ? false : instance.editable;
		let filterable = instance.filterable === undefined ? true : instance.filterable;
		let category = instance.category === undefined ? '' : instance.category;

		let timeFormat = formatPHP(instance.time_format);
		let dowFormat = formatPHP(instance.dow_format);
		let shortDowFormat = formatPHP('l');
		let monthNameFormat = formatPHP(instance.month_name_format);
		let yearFormat = formatPHP('Y');
		let dayFormat = formatPHP(instance.day_format);
		let dateFormat = formatPHP(instance.date_format);

		let formats = {
			dateFormat: 'D',
			dayFormat: shortDowFormat + ' ' + dateFormat,
			weekdayFormat: dowFormat,

			selectRangeFormat: timeRangeFormat(timeFormat),
			eventTimeRangeFormat: timeRangeFormat(timeFormat),

			timeGutterFormat: timeFormat,

			monthHeaderFormat: monthNameFormat + ' ' + yearFormat,
			dayHeaderFormat: dowFormat + ' ' + monthNameFormat + ' ' + dayFormat,
			dayRangeHeaderFormat: weekRangeFormat(monthNameFormat, dayFormat),
			agendaHeaderFormat: dateRangeFormat(monthNameFormat, yearFormat),

			agendaDateFormat: shortDowFormat + ' ' + monthNameFormat + ' ' + dayFormat,
			agendaTimeFormat: timeFormat,
			agendaTimeRangeFormat: timeRangeFormat(timeFormat)
		};

		// Make sure that the requested view is in the list of views
		let view = instance.view;
		if (!views.find((element) => element === view)) {
			views.push(view);
		}

		render(
			<CalendarPlus
				localizer={localizer}
				messages={calendarPlusi18n.messages}
				rtl={!!calendarPlusi18n.i18n.rtl}
				view={instance.view}
				date={instance.currentDate}
				showPopups={instance.showPopups}
				views={views}
				formats={formats}
				categories={calendarPlusi18n.categories}
				editable={editable}
				filterable={filterable}
				category={category}
			/>,
			document.getElementById(instance.id)
		);
	}
}


