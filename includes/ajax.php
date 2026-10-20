<?php
/**
 * Calendar Plus: includes/ajax.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


add_action( 'wp_ajax_calendarp_edit_calendar_cell', 'calendarp_edit_calendar_cell' );
/**
 * Handle calendarp edit calendar cell.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_edit_calendar_cell() {
	global $wpdb;
	check_ajax_referer( 'calendarp-admin', 'nonce' );

	if ( ! current_user_can( 'manage_calendar_plus' ) ) {
		return;
	}

	if ( ! isset( $_POST['cell_id'] ) ) {
		return;
	}

	$cell_id              = absint( calendarp_form_input( 'cell_id' ) );
	$edit_all_recurrences = (bool) calendarp_form_input( 'edit_all' );

	$cell = calendarp_get_event_cell( $cell_id );
	if ( ! $cell ) {
		die();
	}

	$event_id = absint( $cell->event_id );
	$event    = calendarp_get_event( $event_id );

	if ( ! $event || ! current_user_can( 'edit_post', $event_id ) ) {
		die();
	}

	$from = calendarp_form_input( 'from_time' );
	$to   = calendarp_form_input( 'until_time' );
	$from = Calendar_Plus_Event_Rules_Times_Formatter::format_single_time( $from );
	$to   = Calendar_Plus_Event_Rules_Times_Formatter::format_single_time( $to );
	if ( false === $from || false === $to ) {
		die();
	}

	if ( calendarp_form_input( 'from_am_pm' ) ) {
		$from    = explode( ':', $from );
		$from[0] = calendarp_am_pm_to_24h( $from[0], calendarp_form_input( 'from_am_pm' ) );
		$from    = $from[0] . ':' . $from[1];
	}
	if ( calendarp_form_input( 'until_am_pm' ) ) {
		$to    = explode( ':', $to );
		$to[0] = calendarp_am_pm_to_24h( $to[0], calendarp_form_input( 'until_am_pm' ) );
		$to    = $to[0] . ':' . $to[1];
	}

	// Check if times are wellformed.
	$rule           = array(
		'rule_type' => 'times',
		'from'      => $from,
		'until'     => $to,
	);
	$formatted_rule = $event->format_rule( $rule );

	if ( ! $formatted_rule ) {
		die();
	}

	if ( $edit_all_recurrences ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Native schedule write uses typed update placeholders; both schedule and event caches are invalidated below.
		$wpdb->update(
			$wpdb->calendarp_calendar,
			array(
				'from_time'  => $formatted_rule['from'],
				'until_time' => $formatted_rule['until'],
			),
			array( 'event_id' => $event_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		// Update the new rules.
		$rules                      = $event->get_rules();
		$rules['times'][0]['from']  = $formatted_rule['from'];
		$rules['times'][0]['until'] = $formatted_rule['until'];
		$event->update_rules( $rules );
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Native schedule write uses typed update placeholders; both schedule and event caches are invalidated below.
		$wpdb->update(
			$wpdb->calendarp_calendar,
			array(
				'from_time'  => $formatted_rule['from'],
				'until_time' => $formatted_rule['until'],
			),
			array( 'ID' => $cell_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	calendarp_delete_calendar_cache( $event_id );
	calendarp_delete_events_since_cache();
	update_post_meta( $event_id, '_has_custom_dates', true );

	die();
}

add_action( 'wp_ajax_calendarp_remove_user', 'calendarp_remove_user_from_allowed_users_list' );
/**
 * Handle calendarp remove user from allowed users list.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_remove_user_from_allowed_users_list() {
	check_ajax_referer( 'calendarp-users-list', 'nonce' );

	if ( ! current_user_can( 'manage_calendar_plus' ) ) {
		die();
	}

	$id = absint( calendarp_form_input( 'id' ) );

	if ( get_current_user_id() === $id ) {
		// Do not remove yourself.
		die();
	}

	$user = get_userdata( $id );
	if ( ! $user ) {
		die();
	}

	$removable = calendarp_is_user_removable( $id );
	if ( ! $removable ) {
		die();
	}

	if ( user_can( $id, 'manage_calendar_plus' ) ) {
		foreach ( calendarp_get_default_capabilities() as $cap ) {
			$user->remove_cap( $cap );
		}
		$user->remove_role( 'calendarp_events_manager' );

		if ( empty( $user->roles ) ) {
			// Do not leave a user without roles.
			$user->add_role( 'subscriber' );
		}
	}
	die();
}

add_action( 'wp_ajax_calendarp_add_new_allowed_user', 'calendarp_add_new_allowed_user' );
/**
 * Handle calendarp add new allowed user.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_add_new_allowed_user() {
	check_ajax_referer( 'calendarp-users-list', 'nonce' );

	if ( ! current_user_can( 'manage_calendar_plus' ) ) {
		wp_send_json_error();
	}

	$id = absint( calendarp_form_input( 'id' ) );

	$user = get_userdata( $id );
	if ( ! $user ) {
		wp_send_json_error();
	}

	$allowed_ids = wp_list_pluck( calendarp_get_allowed_users(), 'ID' );
	if ( in_array( $id, $allowed_ids, true ) ) {
		// Already allowed.
		wp_send_json_error();
	}

	$user->add_role( 'calendarp_events_manager' );

	$send = array(
		'id'        => $user->ID,
		'name'      => $user->display_name,
		'removable' => calendarp_is_user_removable( $user->ID ),
	);

	if ( get_edit_user_link( $user->ID ) ) {
		$send['link']      = get_edit_user_link( $user->ID );
		$send['linkTitle'] = sprintf( /* translators: %s: user display name. */
			__( 'Edit %s user', 'calendar-plus' ),
			$user->display_name
		);
	}

	wp_send_json_success( $send );
}
