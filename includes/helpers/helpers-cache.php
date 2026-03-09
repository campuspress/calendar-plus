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

	if ( function_exists( 'wp_cache_set_last_changed' ) ) {
		// Sets last changed date for calendarp:events cache group to now.
		// This invalidates all cached queries for this group.
		wp_cache_set_last_changed( 'calendarp:events' );
	}
}

function calendarp_delete_events_since_cache() {
	delete_transient( 'calendarp_events_since' );
}

