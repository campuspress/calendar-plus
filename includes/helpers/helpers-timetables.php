<?php
/**
 * Calendar Plus: includes/helpers/helpers-timetables.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resort the events order.
 *
 * @param int $event_id Event id.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_sort_calendar_event_cells( $event_id ) {
	global $wpdb;

	$cells = calendarp_schedule_read( 'SELECT * FROM %i WHERE event_id = %d ORDER BY from_date ASC, from_time ASC', array( $wpdb->calendarp_calendar, $event_id ) );

	$current_series_number = 0;
	foreach ( $cells as $cell ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Native schedule ordering write uses typed placeholders; invalidated below.
		$wpdb->update(
			$wpdb->calendarp_calendar,
			array( 'series_number' => $current_series_number ),
			array( 'ID' => $cell->ID ),
			array( '%d' ),
			array( '%d' )
		);

		++$current_series_number;
		calendarp_schedule_changed();
	}
}

/**
 * Handle calendarp get event cells.
 *
 * @param int $event_id Event id.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_get_event_cells( $event_id ) {
	return calendar_plus()->generator->get_all_event_dates( $event_id );
}

/**
 * Handle calendarp get event cell.
 *
 * @param int $cell_id Cell id.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_get_event_cell( $cell_id ) {
	return calendar_plus()->generator->get_single_event_date( $cell_id );
}

/**
 * Handle calendarp delete event cell.
 *
 * @param int $cell_id Cell id.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_delete_event_cell( $cell_id ) {
	calendar_plus()->generator->delete_single_event_date( $cell_id );
}
