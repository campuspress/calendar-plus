(function ($) {


	window.CalendarPlusAdmin.misc.Calendar = function (mode) {
		this.init(mode);
	};

	window.CalendarPlusAdmin.misc.Calendar.prototype.init = function (mode) {
		let self = this;

		this.fillWeekCalendar();

		$('.calendarp_calendar .event').click(function (e) {
			self.showPopup($(e.target).data('calendar-cell-id'));
		});

		$('.calendarp-calendar-backdrop').click(this.hidePopups);
		$('.calendar_popup .close-popup').click(this.hidePopups);
		$('.calendar_popup .delete-calendar-cell').click(function (e) {
			self.deleteCell.apply(self, [e]);
		});

		$('.calendar_popup .edit-calendar-cell').click(function (e) {
			self.toggleToEditCell.apply(self, [e]);
		});


	};

	window.CalendarPlusAdmin.misc.Calendar.prototype.showPopup = function (cellId) {
		let popup = $('#calendar-popup-cell-id-' + cellId);
		this.hidePopups();
		popup.show();
		$('.calendarp-calendar-backdrop').show();
	};

	window.CalendarPlusAdmin.misc.Calendar.prototype.hidePopups = function () {
		$('.calendar_popup').hide();
		$('.calendarp-calendar-backdrop').hide();
		$('.calendar_popup .popup-inner-content').show();
		$('.calendar_popup .popup-footer-inner-content').show();
		$('.calendar_popup .popup-inner-content-editable').hide();
		$('.calendar_popup .popup-footer-inner-content-editable').hide();
	};

	window.CalendarPlusAdmin.misc.Calendar.prototype.deleteCell = function (e) {
		e.preventDefault();

		if (!confirm(CalendarPlusi18n.delete_calendar_event)) {
			this.hidePopups();
			return;
		}

		let cellId = $(e.target).data('cell-id');
		$('.event-cell-' + cellId).hide();
		this.hidePopups();

		$.ajax({
			url: CalendarPlusi18n.ajaxurl,
			type: 'POST',
			data: {
				action: 'calendarp_remove_calendar_cell',
				cell_id: cellId
			},
		});

	};

	window.CalendarPlusAdmin.misc.Calendar.prototype.toggleToEditCell = function (e) {
		e.preventDefault();

		let self = this;
		let cellId = $(e.target).data('cell-id');
		let cell = $('#calendar-popup-cell-id-' + cellId);

		if (!cell.length) {
			return;
		}

		cell.find('.popup-inner-content').hide();
		cell.find('.popup-footer-inner-content').hide();
		cell.find('.popup-inner-content-editable').show();
		cell.find('.popup-footer-inner-content-editable').show();

		cell.find('.save-calendar-cell').click(function (e) {
			e.preventDefault();
			self.saveCell.apply(cell, [e, self]);
		});
	};

	window.CalendarPlusAdmin.misc.Calendar.prototype.saveCell = function (e, self) {
		let cell = this;
		let cellId = cell.data('cell-id');

		let spinner = cell.find('.spinner');
		spinner.css('visibility', 'visible');
		spinner.show();

		let from_time = cell.find('select[name="from-time-hour-' + cellId + '"]').first().val() + ':' + cell.find('select[name="from-time-minute-' + cellId + '"]').first().val();
		let until_time = cell.find('select[name="to-time-hour-' + cellId + '"]').first().val() + ':' + cell.find('select[name="to-time-minute-' + cellId + '"]').first().val();
		let from_am_pm = cell.find('select[name="from-time-am-pm-' + cellId + '"]').first().val();
		let until_am_pm = cell.find('select[name="to-time-am-pm-' + cellId + '"]').first().val();

		let data = {
			from_time: from_time,
			until_time: until_time,
			from_am_pm: from_am_pm,
			until_am_pm: until_am_pm,
			cell_id: cellId,
			action: 'calendarp_edit_calendar_cell'
		};

		$.ajax({
			url: CalendarPlusi18n.ajaxurl,
			type: 'POST',
			data: data
		})
			.always(function () {
				location.reload(true);
			});

	};

	window.CalendarPlusAdmin.misc.Calendar.prototype.fillWeekCalendar = function () {
		let events = $('#calendar-slots .event');
		events.each(function (i) {
			let el = $(this);
			let dateClass = el.data('date');
			el.detach().appendTo($('#' + dateClass));
		});
	};

})(jQuery);
