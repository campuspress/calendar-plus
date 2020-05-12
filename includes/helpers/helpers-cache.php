<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function calendarp_delete_calendar_cache( $post_id ) {
	wp_cache_set( 'get_calendar_plus_widget', false, 'calendar' );
	$event = calendarp_get_event( $post_id );
	if ( ! $event ) {
		return;
	}

	wp_cache_delete( $post_id, 'calendarp_events_calendars' );
	Calendar_Plus_Cache::delete_cache_group( 'calendarp_months_dates' );
}

function calendarp_delete_events_since_cache() {
	delete_transient( 'calendarp_events_since' );
}

function calendarp_delete_events_in_range_cache() {
	delete_transient( 'calendarp_events_in_range' );
	Calendar_Plus_Cache::delete_cache_group( 'calendarp_months_dates' );
}
