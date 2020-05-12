<?php


function calendarp_get_calendar_admin_month_template() {
	return array(
		'calendar_wrapper_start'        => '<div class="calendarp_calendar month-calendar"><div class="row">',

		'heading'                       => '<h3 class="heading-row columns large-4">{heading}</h3><div class="clear"></div>',

		'week_days_table_wrapper_start' => '',
		'week_row_start'                => '</div><table class="week-day-row"><tr>',
		'week_row_cell'                 => '<td>{day_of_week}</td>',
		'week_row_end'                  => '</tr></table>',
		'week_days_table_wrapper_end'   => '',

		'cal_rows_wrapper_start'        => '',

		'cal_row_start'                 => '<table class="week-row" cellpadding="4" cellspacing="0"><tr>',
		'cal_cell_start'                => '<td id="{daytime_id}" class="{cell_class}">',
		'cal_cell_start_today'          => '<td id="{daytime_id}" class="{cell_class}">',
		'cal_cell_content'              => '<div class="day-label">{day}</div><div class="day-content">{content}</div>',
		'cal_cell_content_today'        => '<div class="day-label today">{day}</div><div class="day-content">{content}</div>',
		'cal_cell_no_content'           => '<div class="day-label">{day}</div>',
		'cal_cell_no_content_today'     => '<div class="day-label today">{day}</div>',
		'cal_cell_blank'                => '<div class="day-label"></div>',
		'cal_cell_end'                  => '</td>',
		'cal_cell_end_today'            => '</td>',
		'cal_row_end'                   => '</tr></table>',

		'cal_rows_wrapper_end'          => '',

		'calendar_wrapper_end'          => '</div>',
	);
}

function calendarp_get_calendar_front_month_template() {
	return array(
		'calendar_wrapper_start'        => '<div class="calendarp_calendar month-calendar">',

		'heading'                       => '<div class="row"><h3 class="heading-row columns large-3">{heading}</h3><div class="clear"></div></div>',

		'week_days_table_wrapper_start' => '<div class="row"><div class="large-12 columns">',
		'week_row_start'                => '<table cellpadding="0" cellspacing="0" class="cp-week-day-row"><thead><tr>',
		'week_row_cell'                 => '<th class="cp-week-day-name">{day_of_week}</th>',
		'week_row_end'                  => '</tr></thead></table>',
		'week_days_table_wrapper_end'   => '</div></div>',

		'cal_rows_wrapper_start'        => '<div class="row"><div class="cp-week-rows-wrap large-12 columns">',

		'cal_row_start'                 => '<table class="cp-week-row" cellpadding="0" cellspacing="0"><tr>',
		'cal_cell_start'                => '<td id="{daytime_id}" class="{cell_class}">',
		'cal_cell_start_today'          => '<td id="{daytime_id}" class="{cell_class}">',
		'cal_cell_content'              => '<div class="day-label"><span class="day-label-inner">{day}</span></div><div class="day-content">{content}</div>',
		'cal_cell_content_today'        => '<div class="day-label"><span class="day-label-inner">{day}</span></div><div class="day-content">{content}</div>',
		'cal_cell_no_content'           => '<div class="day-label">{day}</div>',
		'cal_cell_no_content_today'     => '<div class="day-label">{day}</div>',
		'cal_cell_blank'                => '<div class="day-label"></div>',
		'cal_cell_end'                  => '</td>',
		'cal_row_end'                   => '</tr></table>',

		'cal_rows_wrapper_end'          => '</div></div>',

		'calendar_wrapper_end'          => '</div>',
	);
}


function calendarp_get_calendar_front_week_template() {
	return array(
		'calendar_wrapper_start'        => '<div class="calendarp_calendar {mode}-calendar">',

		'heading'                       => '<div class="row"><h3 class="heading-row columns large-5">{heading}</h3><div class="clear"></div></div>',

		'week_days_table_wrapper_start' => '<div class="row"><div class="large-12 columns">',
		'week_row_start'                => '<table cellpadding="0" cellspacing="0" class="cp-week-day-row"><thead><tr>',
		'week_row_cell'                 => '<th class="cp-week-day-name">{day_of_week}</th>',
		'week_row_end'                  => '</tr></thead></table>',
		'week_days_table_wrapper_end'   => '</div></div>',

		'cal_rows_wrapper_start'        => '<div class="row"><div class="cp-week-rows-wrap large-12 columns">',

		'cal_row_start'                 => '<table class="cp-week-row" cellpadding="0" cellspacing="0"><tr>',

		'cal_time_col_start'            => '<td class="times-col">',
		'cal_time_content'              => '<div class="time-label">{time}</div>',
		'cal_time_col_end'              => '</td>',

		'cal_day_col_start'             => '<td class="day-col {class}">',
		'cal_day_col_time_inner_start'  => '<div class="day-col-inner">',

		'cal_day_col_events_wrap_start' => '<div class="events-wrap" id="{date_time_id}">',
		'cal_day_col_events_content'    => '<div class="events-cell-datetime">{content}</div>',
		'cal_day_col_events_wrap_end'   => '</div>',

		'cal_day_col_time_inner_end'    => '</div>',
		'cal_day_col_end'               => '</td>',

		'cal_row_end'                   => '</tr></table>',

		'cal_rows_wrapper_end'          => '</div></div>',

		'calendar_wrapper_end'          => '</div>',
	);
}

function calendarp_get_calendar_admin_week_template() {
	return array(
		'calendar_wrapper_start'        => '<div class="calendarp_calendar {mode}-calendar">',

		'heading'                       => '<h3 class="heading-row">{heading}</h3><div class="clear"></div>',

		'week_days_table_wrapper_start' => '',
		'week_row_start'                => '<table class="week-day-row"><tr>',
		'week_row_cell'                 => '<td>{day_of_week}</td>',
		'week_row_end'                  => '</tr></table>',
		'week_days_table_wrapper_end'   => '',

		'cal_rows_wrapper_start'        => '',

		'cal_row_start'                 => '<table class="week-row"><tr>',

		'cal_time_col_start'            => '<td scope="row" class="time-col">',
		'cal_time_content'              => '<div class="time-label">{time}</div>',
		'cal_time_col_end'              => '</td>',

		'cal_day_col_start'             => '<td class="day-col {class}">',
		'cal_day_col_time_inner_start'  => '<div class="day-col-inner">',

		'cal_cell_start'                => '<td id="{daytime_id}" class="events-col">',
		'cal_cell_start_today'          => '<td id="{daytime_id}" class="events-col">',
		'cal_cell_content'              => '<div class="day-label">{day}</div><div class="day-content">{content}</div>',
		'cal_cell_content_today'        => '<div class="day-label today">{day}</div><div class="day-content">{content}</div>',
		'cal_cell_no_content'           => '<div class="day-label">{day}</div>',
		'cal_cell_no_content_today'     => '<div class="day-label today">{day}</div>',
		'cal_cell_blank'                => '&nbsp;',
		'cal_cell_end'                  => '</td>',
		'cal_cell_end_today'            => '</td>',
		'cal_row_end'                   => '</tr></table>',

		'cal_day_col_time_inner_end'    => '</div>',

		'cal_rows_wrapper_end'          => '',

		'calendar_wrapper_end'          => '</div>',
	);
}

function calendarp_get_calendar_agenda_template() {
	return array(
		'calendar_wrapper_start'    => '<div class="calendarp_calendar agenda-calendar">',
		'heading'                   => '<h3 class="heading-row columns large-10">{heading}</h3><div class="clear"></div>',
		'week_row_start'            => '<table class="week-day-row"><tr>',
		'week_row_cell'             => '<td>{day_of_week}</td>',
		'week_row_end'              => '</tr></table>',
		'cal_row_start'             => '<table class="week-row" cellpadding="4" cellspacing="0"><tr>',
		'cal_cell_start'            => '<td id="{daytime_id}" class="{cell_class}">',
		'cal_cell_start_today'      => '<td id="{daytime_id}" class="{cell_class}">',
		'cal_cell_content'          => '<div class="day-label">{day}</div><div class="day-content">{content}</div>',
		'cal_cell_content_today'    => '<div class="day-label today">{day}</div><div class="day-content">{content}</div>',
		'cal_cell_no_content'       => '<div class="day-label">{day}</div>',
		'cal_cell_no_content_today' => '<div class="day-label today">{day}</div>',
		'cal_cell_blank'            => '<div class="day-label"></div>',
		'cal_cell_end'              => '</td>',
		'cal_cell_end_today'        => '</td>',
		'cal_row_end'               => '</tr></table>',
		'calendar_wrapper_end'      => '</div>',
	);
}




function calendarp_get_calendar_event_template() {
	return array(
		'title_start'   => '<div class="event event-cell-{calendar_cell_id} event-date-id-{date_id}" data-date="{date_id}" style="background-color:{event_color};color:{text_color}" data-event-id="{event_id}" data-calendar-cell-id="{calendar_cell_id}">',
		'time'          => '<span class="event-time">{event_time}</span>',
		'event_content' => '{event_content}',
		'title_end'     => '</div>',
		'event_popup'   => '{event_popup}',
	);
}

function calendarp_get_calendar_event_shortcode_template() {
	return array(
		'title_start'   => '<div class="event event-cell-{calendar_cell_id} event-date-id-{date_id}" data-date="{date_id}" style="background-color:{event_color};color:{text_color}" data-event-id="{event_id}" data-calendar-cell-id="{calendar_cell_id}">',
		'time'          => '<span class="event-time {event_time_class}">{event_time}</span>',
		'event_content' => '{event_content} {event_permalink}',
		'title_end'     => '</div>',
		'event_popup'   => '{event_popup}',
	);
}

function calendarp_get_calendar_event_popup_template() {
	return array(
		'popup_start'                                     => '<div class="calendar_popup" id="calendar-popup-cell-id-{calendar_cell_id}" data-cell-id="{calendar_cell_id}"><div class="calendar-popup-inner"><div class="close-popup"><span class="dashicons dashicons-no-alt"></span></div><div class="popup-content">',
		'popup_content_header'                            => '<div class="popup-header"><h4 style="background:{event_color};color:{text_color}">{event_title}</h4></div>',
		'popup_content_inner_start'                       => '<div class="popup-inner-content">',
		'popup_content_inner_date'                        => '<div class="popup-date">{event_date}</div>',
		'popup_content_inner_time'                        => '<div class="popup-time">{event_time_start} {event_time_end}</div>',
		'popup_content_inner_end'                         => '</div>',
		'popup_content_inner_editable_start'              => '<div class="popup-inner-content-editable hidden">',
		'popup_content_inner_editable_date'               => '<div class="popup-date">{event_date}</div>',
		'popup_content_inner_editable_time'               => '<div class="popup-time"><span class="starts-at">{event_starts_at_title}</span> {event_time_start}<br/><span class="finishes-at">{event_finishes_at_title}</span> {event_time_end}</div>',
		'popup_content_inner_editable_end'                => '</div>',
		'popup_content_footer_start'                      => '<div class="popup-footer">',
		'popup_content_inner_footer_content_start'        => '<div class="popup-footer-inner-content">',
		'popup_content_inner_footer_content'              => '{edit_button} {delete_button} <a class="popup-edit-event-link" href="{edit_event_link}" title="{edit_even_link_title}">{edit_even_link_title} <span class="dashicons dashicons-redo"></span></a>',
		'popup_content_inner_footer_content_end'          => '</div>',
		'popup_content_inner_footer_editable_content_start' => '<div class="popup-footer-inner-content-editable hidden">',
		'popup_content_inner_footer_editable_content'     => '<a class="button-primary save-calendar-cell" data-cell-id="{calendar_cell_id}">{save_cell_text}</a> <span class="spinner"></span>',
		'popup_content_inner_footer_editable_content_end' => '</div>',
		'popup_content_footer_end'                        => '</div>',
		'popup_end'                                       => '</div></div></div>',
	);
}

function calendarp_get_calendar_event_popup_shortcode_template() {
	return array();
}

function calendarp_get_times_list() {
	return array( '00:00', '00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00', '21:30', '22:00', '22:30', '23:00', '23:30' );
}
