<?php
/**
 * Calendar Plus: includes/helpers/helpers-cache.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle calendarp delete calendar cache.
 *
 * @param int $post_id Post id.
 *
 * @return mixed Calendar operation result.
 */
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

/**
 * Handle calendarp delete events since cache.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_delete_events_since_cache() {
	delete_transient( 'calendarp_events_since' );
}


/**
 * Read the existing scheduling tables through a prepared, generation-salted cache.
 * SQL templates are supplied only by the native schedule engine; every value and identifier is bound.
 *
 * @param string $sql SQL template.
 * @param array  $args Placeholder arguments.
 * @param string $mode results, row or var.
 * @return mixed Database result.
 */
function calendarp_schedule_read( $sql, array $args, $mode = 'results' ) {
	global $wpdb;
	$mode = in_array( $mode, array( 'results', 'row', 'var', 'col' ), true ) ? $mode : 'results';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Internal SQL templates bind all values and table identifiers with the supplied argument list.
	$query  = $wpdb->prepare( $sql, $args );
	$key    = md5( $query . $mode . wp_cache_get_last_changed( 'calendarp:events' ) );
	$result = wp_cache_get( $key, 'calendarp:schedule', false, $found );
	if ( ! $found ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Existing custom scheduling table; prepared above and cached below.
		$result = $wpdb->{ 'get_' . $mode }( $query );
		wp_cache_set( $key, $result, 'calendarp:schedule', HOUR_IN_SECONDS );
	}
	return $result;
}

/**
 * Handle calendarp schedule changed.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_schedule_changed() {
	wp_cache_set_last_changed( 'calendarp:events' );
	wp_cache_delete( 'get_calendar_plus_widget', 'calendar' );
	calendarp_delete_events_since_cache();
}

/**
 * Handle calendarp cache get salted.
 *
 * @param mixed $key Key.
 * @param mixed $group Group.
 * @param mixed $salt Salt.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_cache_get_salted( $key, $group, $salt ) {
	return wp_cache_get( $key . ':' . md5( $salt ), $group );
}

/**
 * Handle calendarp cache set salted.
 *
 * @param mixed $key Key.
 * @param mixed $value Value.
 * @param mixed $group Group.
 * @param mixed $salt Salt.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_cache_set_salted( $key, $value, $group, $salt ) {
	return wp_cache_set( $key . ':' . md5( $salt ), $value, $group, HOUR_IN_SECONDS );
}

/**
 * Invalidate calendar queries after native event taxonomy changes.
 *
 * @param int    $object_id Event identifier.
 * @param array  $terms Submitted terms.
 * @param array  $tt_ids Term taxonomy identifiers.
 * @param string $taxonomy Native taxonomy name.
 * @return void
 */
function calendarp_schedule_terms_changed( $object_id, $terms, $tt_ids, $taxonomy ) {
	if ( 'calendar_event' === get_post_type( $object_id ) && in_array( $taxonomy, array( 'calendar_event_category', 'calendar_event_tag', 'calendar_event_type' ), true ) ) {
		calendarp_schedule_changed();
	}
}

/**
 * Invalidate native event reads before a post is permanently removed.
 *
 * @param int $post_id Post identifier.
 * @return void
 */
function calendarp_schedule_post_deleted( $post_id ) {
	if ( in_array( get_post_type( $post_id ), array( 'calendar_event', 'calendar_location' ), true ) ) {
		calendarp_schedule_changed();
	}
}
