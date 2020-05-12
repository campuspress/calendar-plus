export const _e = (slug) => {
	return calendarPlusi18n.messages[slug] ? calendarPlusi18n.messages[slug] : '';
};

export const getMessages = () => {
	return calendarPlusi18n.messages
};

export const leftPad = (value, length = 2) => {
	return (value.toString().length < length) ? leftPad("0" + value, length) : value;
};

export const getRealCurrentDate = () => {
	return new Date(calendarPlusi18n.currentTimestamp * 1000);
};

export const isMobile = () => {
	return window.outerWidth <= 500;
};

export const debounce = (fn, delay) => {
	let timeout;

	return function () {
		let args = arguments;

		if (timeout) {
			clearTimeout(timeout);
		}

		timeout = setTimeout(
			(function () {
				return fn.apply(self, args);
			}).bind(this),
			delay
		);
	};
};
//
// /**
//  * Convert a date in AM/PM format into pieces ready to save as a MySQL date
//  *
//  * @param year
//  * @param month
//  * @param day
//  * @param hour
//  * @param minute
//  * @param {string} meridiem am|pm
//  *
//  * @returns {string}
//  */
// export const meridiemTimeToMySQLDate = ( year, month, day, hour, minute, meridiem = 'am' ) => {
//     let date = moment(
//         `${year}-${leftPad( month )}-${leftPad( day )} ${leftPad( hour )}:${leftPad( minute )}:00 ${meridiem}`,
//         'YYYY-MM-DD hh:mm:00 a'
//     );
//
//     if ( ! date.isValid() ) {
//         return false;
//     }
//
//     return {
//         date: date.format( 'YYYY-MM-DD' ),
//         time: date.format( 'HH:mm:ss' )
//     }
// };
//
// /**
//  * Parses an AM/PM time format into MySQL format
//  */
// export const mySQLtoMeridiemTime = ( date ) => {
//     let d = moment( date );
//     return {
//         year: d.year(),
//         month: d.month() + 1,
//         day: d.day(),
//         hour: d.hour(),
//         minute: d.minute(),
//         meridiem: d.format( 'a' )
//     }
// };

// /**
//  *
//  * @param {Date} date
//  * @returns {{year: (Moment|number), month: *, day: (number|Moment), hour: (number|Moment), minute: (Moment|number), meridiem: string}}
//  */
// export const jsDatetoMeridiemDate = ( date ) =>  {
//     let d = moment({
//         year: date.getFullYear(),
//         month: date.getMonth(),
//         day: date.getDay(),
//         hour: date.getHours(),
//         minute: date.getMinutes()
//     });
//
//     return {
//         year: d.year(),
//         month: d.month() + 1,
//         day: d.day(),
//         hour: d.hour(),
//         minute: d.minute(),
//         meridiem: d.format( 'a' )
//     }
// };
