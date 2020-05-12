/* global jQuery */

(function ($) {

	window.calendar_plus.libs.calendar = {
		$calendars: [],
		current_day: [],
		init: function () {
			let $calendars = $('.calendarp_calendar');
			if ($calendars.length) {
				$calendars.each(this.bindEvents);
			}

			let calendarpForm = $('#calendarp-controls-form');
			let calendarpHeading = $('.calendarp_calendar .heading-row');

			calendarpHeading.css('float', 'left');
			calendarpForm.remove().css('float', 'right').insertAfter(calendarpHeading).show();
			let mode = $('input[name="calendar_mode"]').val();
			if (mode !== 'month' && mode !== 'agenda')
				this.fillWeekCalendar();

		},
		bindEvents: function (i) {
			let calendar = $(this);
			calendar.data('id', 'calendarp-' + i);
			window.calendar_plus.libs.calendar.$calendars[i] = calendar;
			calendar.find('.with-events .day-label').on('hover', window.calendar_plus.libs.calendar.toggleEventsForDay);

			$('#calendar_mode').change(function () {
				$(this).closest('form').submit();
			});
		},
		toggleEventsForDay: function () {

			$('.day-label').removeClass('selected');
			let slotsContainer = $('#calendar-slots');

			let day = $(this);
			let dayOfMonth = day.text();
			let calendarId = day.closest('.calendarp_calendar').data('id');
			if (window.calendar_plus.libs.calendar.current_day[calendarId] === dayOfMonth) {
				return;
			}

			window.calendar_plus.libs.calendar.current_day[calendarId] = dayOfMonth;

			day.addClass('selected');
			let daytime_id = day.parent().attr('id');
			let tableRow = day.closest('.cp-week-row');


			slotsContainer.slideUp();
			slotsContainer.find('.event').hide();
			slotsContainer.find('.event-date-id-' + daytime_id).show();
			slotsContainer.hide().insertAfter(tableRow);
			slotsContainer.fadeIn('fast', function () {
				$(this).clearQueue();

			});

		},
		fillWeekCalendar: function () {
			let events = $('#calendar-slots .event');
			events.each(function (i) {
				let el = $(this);
				let dateClass = el.data('date');
				el.detach().appendTo($('#' + dateClass).addClass('with-events'));
			});
		}
	};

})(jQuery);
