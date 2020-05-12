<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resort the events order
 *
 * @param $event_id
 */
function calendarp_sort_calendar_event_cells( $event_id ) {
	global $wpdb;

	$cells = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->calendarp_calendar WHERE event_id = %d ORDER BY from_date ASC, from_time ASC", $event_id ) );

	$current_series_number = 0;
	foreach ( $cells as $cell ) {
		$wpdb->update(
			$wpdb->calendarp_calendar,
			array( 'series_number' => $current_series_number ),
			array( 'ID' => $cell->ID ),
			array( '%d' ),
			array( '%d' )
		);

		$current_series_number++;
	}

}

function calendarp_get_event_cells( $event_id ) {
	return calendar_plus()->generator->get_all_event_dates( $event_id );
}

function calendarp_get_event_cell( $cell_id ) {
	return calendar_plus()->generator->get_single_event_date( $cell_id );
}

function calendarp_delete_event_cell( $cell_id ) {
	calendar_plus()->generator->delete_single_event_date( $cell_id );
}
