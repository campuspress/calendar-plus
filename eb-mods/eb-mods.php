<?php

add_filter( 'calendarp_flush_rewrite_rules', '__return_false' );

add_action( 'init', 'edublogs_calendarp_teacher_capabilities_init' );

function edublogs_calendarp_teacher_capabilities_init() {
	global $current_user;
	if ( ! class_exists( 'Calendar_Plus' ) || ! defined( 'CLASSES_VERSION' ) ) {
		return;
	}

	// If it's a teacher but it has not Calendar+ caps, let's add it
	if ( ! in_array( 'classteacher', $current_user->roles ) || current_user_can( 'manage_calendar_event_terms' ) ) {
		return;
	}

	$role = get_role( 'classteacher' );
	$caps = array(
		'edit_calendar_event',
		'read_calendar_event',
		'delete_calendar_event',
		'edit_calendar_events',
		'edit_others_calendar_events',
		'publish_calendar_events',
		'read_private_calendar_events',
		'delete_calendar_events',
		'delete_private_calendar_events',
		'delete_published_calendar_events',
		'delete_others_calendar_events',
		'edit_private_calendar_events',
		'edit_published_calendar_events',
		'edit_calendar_events',

		'edit_calendar_location',
		'read_calendar_location',
		'delete_calendar_location',
		'edit_calendar_locations',
		'edit_others_calendar_locations',
		'publish_calendar_locations',
		'read_private_calendar_locations',
		'delete_calendar_locations',
		'delete_private_calendar_locations',
		'delete_published_calendar_locations',
		'delete_others_calendar_locations',
		'edit_private_calendar_locations',
		'edit_published_calendar_locations',
		'edit_calendar_locations',

		'calendarp_events_manager',
		'manage_calendar_plus',
		'manage_calendar_event_terms',
		'edit_calendar_event_terms',
		'delete_calendar_event_terms',
		'assign_calendar_event_terms',
	);

	if ( is_a( $role, 'WP_Role' ) ) {
		foreach ( $caps as $cap ) {
			if ( ! current_user_can( $cap ) ) {
				$role->add_cap( $cap );
			}
		}
	}
}

