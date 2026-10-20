<?php
/**
 * Calendar Plus: admin/tinymce-shortcodes-i18n.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$calendarp_values = array();

$calendarp_values['views'] = array(
	'month'  => __( 'Month', 'calendar-plus' ),
	'week'   => __( 'Week', 'calendar-plus' ),
	'day'    => __( 'Day', 'calendar-plus' ),
	'agenda' => __( 'Agenda', 'calendar-plus' ),
);

$calendarp_term_args = array(
	'fields'     => 'id=>name',
	'hide_empty' => false,
);

$calendarp_values['categories'] = get_terms( array_merge( $calendarp_term_args, array( 'taxonomy' => 'calendar_event_category' ) ) );
$calendarp_values['tags']       = get_terms( array_merge( $calendarp_term_args, array( 'taxonomy' => 'calendar_event_tag' ) ) );

foreach ( $calendarp_values as $calendarp_key => $calendarp_array ) {
	$calendarp_mapped = array();

	foreach ( $calendarp_array as $calendarp_term_id => $calendarp_term_name ) {
		$calendarp_mapped[] = array(
			'text'  => $calendarp_term_name,
			'value' => $calendarp_term_id,
		);
	}

	$calendarp_values[ $calendarp_key ] = $calendarp_mapped;
}

$calendarp_strings = array(
	'calendar_title'                         => __( 'Insert Calendar', 'calendar-plus' ),
	'category_field_info'                    => __( 'By default, events from all categories will be shown.', 'calendar-plus' ) . ' ' .
												__( 'To only display events from specific categories, select them below.', 'calendar-plus' ),
	'category_field_title'                   => __( 'Only display events from these categories', 'calendar-plus' ),
	'category_field_values'                  => $calendarp_values['categories'],
	'tag_field_values'                       => $calendarp_values['tags'],
	'default_view_title'                     => __( 'Default View', 'calendar-plus' ),
	'default_view_values'                    => $calendarp_values['views'],
	'events_by_cat_title'                    => __( 'Events List', 'calendar-plus' ),
	'events_by_cat_info'                     => __( 'By default, events from all categories and tags will be shown.', 'calendar-plus' ) . ' ' .
												__( 'To only display events from specific categories or tags, select them below.', 'calendar-plus' ),
	'events_by_cat_category_field_title'     => __( 'Only display posts from any of these categories', 'calendar-plus' ),
	'events_by_cat_tag_field_title'          => __( 'Only display posts with any of these tags', 'calendar-plus' ),
	'events_per_page'                        => sprintf(
		/* translators: %d: default number of events per page. */
		__( 'Events Per Page (Agenda Mode), %d by default', 'calendar-plus' ),
		calendarp_get_events_per_page()
	),
	'events_by_cat_events_count_field_title' => __( 'Number of events to display (set to 0 to show all)', 'calendar-plus' ),
	'single_event_title'                     => __( 'Single Event', 'calendar-plus' ),
	'search_event'                           => __( 'Search Event', 'calendar-plus' ),
	'search_event_by_title'                  => __( 'Search By Title…', 'calendar-plus' ),
	'default_time_format'                    => get_option( 'time_format' ),
);

foreach ( $calendarp_strings as $calendarp_i => $calendarp_string ) {

	if ( is_string( $calendarp_string ) && 'category_field_info' !== $calendarp_i ) {
		$calendarp_strings[ $calendarp_i ] = esc_js( $calendarp_string );
	}
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Native WordPress static property name; plugin locals remain prefixed.
$calendarp_strings = array( _WP_Editors::$mce_locale => array( 'calendarp_l10n' => $calendarp_strings ) );
$calendarp_strings = 'tinyMCE.addI18n(' . wp_json_encode( $calendarp_strings ) . ');';
