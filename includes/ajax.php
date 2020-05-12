<?php

add_action( 'wp_ajax_calendarp_edit_calendar_cell', 'calendarp_edit_calendar_cell' );
function calendarp_edit_calendar_cell() {
	global $wpdb;

	if ( ! current_user_can( 'manage_calendar_plus' ) ) {
		return;
	}

	if ( ! isset( $_POST['cell_id'] ) ) {
		return;
	}

	$cell_id = absint( $_POST['cell_id'] );
	$edit_all_recurrences = ! empty( $_POST['edit_all'] );

	$cell = calendarp_get_event_cell( $cell_id );
	if ( ! $cell ) {
		die();
	}

	$event_id = absint( $cell->event_id );
	$event = calendarp_get_event( $event_id );

	if ( ! $event ) {
		die();
	}

	$from = $_POST['from_time'];
	$to = $_POST['until_time'];

	if ( ! empty( $_POST['from_am_pm'] ) ) {
		$from = explode( ':', $from );
		$from[0] = calendarp_am_pm_to_24h( $from[0], $_POST['from_am_pm'] );
		$from = $from[0] . ':' . $from[1];
	}
	if ( ! empty( $_POST['until_am_pm'] ) ) {
		$to = explode( ':', $to );
		$to[0] = calendarp_am_pm_to_24h( $to[0], $_POST['until_am_pm'] );
		$to = $to[0] . ':' . $to[1];
	}

	// Check if times are wellformed
	$rule = array(
		'rule_type' => 'times',
		'from'      => $from,
		'until'     => $to,
	);
	$formatted_rule = $event->format_rule( $rule );

	if ( ! $formatted_rule ) {
		die();
	}

	if ( $edit_all_recurrences ) {
		$wpdb->update(
			$wpdb->calendarp_calendar,
			array( 'from_time' => $formatted_rule['from'], 'until_time' => $formatted_rule['until'] ),
			array( 'event_id' => $event_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		// Update the new rules
		$rules = $event->get_rules();
		$rules['times'][0]['form'] = $formatted_rule['from'];
		$rules['times'][0]['until'] = $formatted_rule['until'];
		$event->update_rules( $rules );
	} else {
		$wpdb->update(
			$wpdb->calendarp_calendar,
			array( 'from_time' => $formatted_rule['from'], 'until_time' => $formatted_rule['until'] ),
			array( 'ID' => $cell_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	calendarp_delete_calendar_cache( $event_id );
	calendarp_delete_events_in_range_cache();
	calendarp_delete_events_since_cache();
	update_post_meta( $event_id, '_has_custom_dates', true );

	die();
}

add_action( 'wp_ajax_calendarp_remove_user', 'calendarp_remove_user_from_allowed_users_list' );
function calendarp_remove_user_from_allowed_users_list() {
	check_ajax_referer( 'calendarp-users-list', 'nonce' );

	if ( ! current_user_can( 'calendarp_events_manager' ) ) {
		die();
	}

	$id = absint( $_POST['id'] );

	if ( get_current_user_id() == $id ) {
		// Do not remove yourself
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
			// Do not leave a user without roles
			$user->add_role( 'subscriber' );
		}
	}
	die();
}

add_action( 'wp_ajax_calendarp_add_new_allowed_user', 'calendarp_add_new_allowed_user' );
function calendarp_add_new_allowed_user() {
	check_ajax_referer( 'calendarp-users-list', 'nonce' );

	if ( ! current_user_can( 'manage_calendar_plus' ) ) {
		wp_send_json_error();
	}

	$id = absint( $_POST['id'] );

	$user = get_userdata( $id );
	if ( ! $user ) {
		wp_send_json_error();
	}

	$allowed_ids = wp_list_pluck( calendarp_get_allowed_users(), 'ID' );
	if ( in_array( $id, $allowed_ids ) ) {
		// Already allowed
		wp_send_json_error();
	}

	$user->add_role( 'calendarp_events_manager' );

	$send = array(
		'id'        => $user->ID,
		'name'      => $user->display_name,
		'removable' => calendarp_is_user_removable( $user->ID ),
	);

	if ( get_edit_user_link( $user->ID ) ) {
		$send['link'] = get_edit_user_link( $user->ID );
		$send['linkTitle'] = sprintf( __( 'Edit %s user', 'calendar-plus' ), $user->display_name );
	}

	wp_send_json_success( $send );
}
