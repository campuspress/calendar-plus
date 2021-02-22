<?php

/**
 * Calendar+ core hooks
 *
 * @since 2.0
 */

$calendar_plus = calendar_plus();

// Clear generated months when an event rules are updated
add_action( 'calendarp_update_event_rules', array( $calendar_plus->generator, 'generate_event_dates' ) );
add_action( 'calendarp_update_event_rules', array( $calendar_plus->generator, 'refresh_event_min_max_dates' ) );

// Initializes the core class
add_action( 'init', array( $calendar_plus, 'init' ), 9 );

// Add Calendar+ image sizes
add_action( 'after_setup_theme', array( $calendar_plus, 'add_image_sizes' ) );

// Plugin i18n
add_action( 'plugins_loaded', array( $calendar_plus->i18n, 'load_plugin_textdomain' ) );

// Regenerate calendar when something changes
add_action( 'delete_post', 'calendarp_delete_event_dates' );
add_action( 'delete_post', array( $calendar_plus->generator, 'delete_event_min_max_dates' ) );
add_action( 'save_post', 'calendarp_delete_calendar_cache' );
add_action( 'update_option_start_of_week', 'calendarp_delete_calendar_cache' );
add_action( 'update_option_gmt_offset', 'calendarp_delete_calendar_cache' );
add_action( 'calendarp_update_event_rules', 'calendarp_delete_calendar_cache' );

// Delete old dates every day
add_action( 'calendar_plus_delete_old_dates', array( $calendar_plus->generator, 'delete_old_dates' ) );

// Sync iCal events
add_action( 'calendar_plus_sync_ical_events', 'calendarp_ical_sync_events' );
add_action( 'calendar_plus_sync_ical_events', 'calendarp_ical_clear_legacy_scheduling', 0 );
