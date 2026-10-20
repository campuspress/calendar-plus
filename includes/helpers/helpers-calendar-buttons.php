<?php
/**
 * Calendar Plus: includes/helpers/helpers-calendar-buttons.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return an external calendar button link.
 *
 * - from_date
 * - until_date
 * - from_time
 * - until_time
 *
 * @param string $cal Calendar Type.
 * @param int    $event_id Event ID.
 * @param object $date_cell Date cell data. It must include these properties:.
 * @return string URL
 */
function calendarp_get_external_calendar_button_link( $cal, $event_id, $date_cell ) {
	if ( ! $date_cell ) {
		return '';
	}

	$external_calendars = apply_filters(
		'calendarp_external_calendars',
		array(
			'gcal' => 'Calendar_Plus_GCal_Calendar_Button',
			'ical' => 'Calendar_Plus_iCal_Calendar_Button',
		)
	);

	if ( ! array_key_exists( $cal, $external_calendars ) ) {
		return '';
	}

	$event        = calendarp_get_event( $event_id );
	$button_class = $external_calendars[ $cal ];

	$args = array(
		'from' => strtotime( $date_cell->from_date . ' ' . $date_cell->from_time ),
		'to'   => strtotime( $date_cell->until_date . ' ' . $date_cell->until_time ),
	);

	/** Calendar render context. @var Calendar_Plus_External_Calendar_Button $button */
	$button = new $button_class( $event );
	return $button->get_button_link( $args );
}

/**
 * Kept for backward compatibility.
 *
 * @param array $args Args.
 * @return string
 */
function calendarp_get_ical_file_url( $args = array() ) {

	$url = admin_url( 'admin-ajax.php', 'relative' );

	if ( isset( $args['event'] ) ) {
		$url = add_query_arg( 'event', $args['event'], $url );
	}

	if ( isset( $args['category'] ) ) {
		$url = add_query_arg( 'event-category', $args['category'], $url );
	}

	if ( isset( $args['location'] ) ) {
		$url = add_query_arg( 'event-location', $args['location'], $url );
	}

	if ( isset( $args['s'] ) ) {
		$url = add_query_arg( 'event-search', $args['s'], $url );
	}

	$url = add_query_arg( 'action', 'download_ical_file', $url );

	return $url;
}
