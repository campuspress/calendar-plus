require('es6-promise').polyfill();
import createHash from 'hash-generator';
import moment from 'moment';
import formatPHP from './formatPHP';
import buildUrl from 'urijs';
import 'whatwg-fetch';

/**
 * Load a list of events from server
 *
 * @param year
 * @param month
 * @param filter
 */
export const loadEvents = (year, month, filter) => {
	let headers = {
		'X-WP-Nonce': calendarPlusi18n.apinonce
	},
	getParams = {
		credentials: 'same-origin',
		//headers: headers
		...('1' === calendarPlusi18n.includeRestNonce ? { headers } : {})
	};

	let url = calendarPlusi18n.baseurl + '/events?';

	let _filter = {};
	for (let i in filter) {
		if (filter[i]) {
			_filter[i] = filter[i];
		}
	}
	if ( calendarPlusi18n.hasOwnProperty('lang') ) {
		_filter.lang = calendarPlusi18n.lang;
	}

	const filterParams = Object.assign({}, {year, month}, _filter);

	url += Object.keys(filterParams)
		.map(k => encodeURIComponent(k) + '=' + encodeURIComponent(filterParams[k]))
		.join('&');

	return fetch(url, getParams)
		.then(res => res.json());
};


/**
 * Parse the list of events and transform them so React Calendar can read it
 *
 * @param events
 */
export const parseEvents = (events) => {
	return events.map((event) => {
		let start = Object.assign({}, {'hour': 0, 'minute': 0}, event.start);
		let end = Object.assign({}, {'hour': 0, 'minute': 0}, event.end);
		event.start = new Date(start.year, start.month - 1, start.day, start.hour, start.minute);
		event.end = new Date(end.year, end.month - 1, end.day, end.hour, end.minute);
		event.hash = createHash(12);
		event.startTime = moment(event.start).format(formatPHP(calendarPlusi18n.i18n.timeFormat));
		return Object.assign({}, {
				desc: '',
				url: '',
				title: '',
				categories: [],
				calendars: {}
			},
			event
		);
	});
};

/**
 * Filter events. It strips out any event that has already finished
 *
 * @param events
 * @param currentDate
 */
export const filterPassedEvents = (events, currentDate) => {
	return events.filter((event) => {
		return moment(event.end).format('YY-MM-DD HH:mm') >= moment(currentDate).format('YY-MM-DD HH:mm');
	});
};

/**
 * Add a redirection parameter to the event URL
 *
 * @param event
 */
export const getEventUrl = (event) => {
	let eventUrl = new buildUrl(event.url);
	eventUrl.addQuery('cal', location.href);
	return eventUrl;
}
