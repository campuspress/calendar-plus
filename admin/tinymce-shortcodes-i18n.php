<?php

$values = array();

$values['views'] = array(
	'month'  => __( 'Month', 'calendar-plus' ),
	'week'   => __( 'Week', 'calendar-plus' ),
	'day'    => __( 'Day', 'calendar-plus' ),
	'agenda' => __( 'Agenda', 'calendar-plus' ),
);

$term_args = array( 'fields' => 'id=>name', 'hide_empty' => false );

$values['categories'] = get_terms( 'calendar_event_category', $term_args );
$values['tags'] = get_terms( 'calendar_event_tag', $term_args );

foreach ( $values as $key => $array ) {
	$mapped = array();

	foreach ( $array as $term_id => $term_name ) {
		$mapped[] = array( 'text' => $term_name, 'value' => $term_id );
	}

	$values[ $key ] = $mapped;
}

$strings = array(
	'calendar_title'                         => __( 'Insert Calendar', 'calendar-plus' ),
	'category_field_info'                    => __( 'By default, events from all categories will be shown.', 'calendar-plus' ) . ' ' .
												__( 'To only display events from specific categories, select them below.', 'calendar-plus' ),
	'category_field_title'                   => __( 'Only display events from these categories', 'calendar-plus' ),
	'category_field_values'                  => $values['categories'],
	'tag_field_values'                       => $values['tags'],
	'default_view_title'                     => __( 'Default View', 'calendar-plus' ),
	'default_view_values'                    => $values['views'],
	'events_by_cat_title'                    => __( 'Events List', 'calendar-plus' ),
	'events_by_cat_info'                     => __( 'By default, events from all categories and tags will be shown.', 'calendar-plus' ) . ' ' .
												__( 'To only display events from specific categories or tags, select them below.', 'calendar-plus' ),
	'events_by_cat_category_field_title'     => __( 'Only display posts from any of these categories', 'calendar-plus' ),
	'events_by_cat_tag_field_title'          => __( 'Only display posts with any of these tags', 'calendar-plus' ),
	'events_per_page'                        => sprintf(
		__( 'Events Per Page (Agenda Mode), %d by default', 'calendar-plus' ),
		calendarp_get_events_per_page()
	),
	'events_by_cat_events_count_field_title' => __( 'Number of events to display (set to 0 to show all)', 'calendar-plus' ),
	'single_event_title'                     => __( 'Single Event', 'calendar-plus' ),
	'search_event'                           => __( 'Search Event', 'calendar-plus' ),
	'search_event_by_title'                  => __( 'Search By Title…', 'calendar-plus' ),
	'default_time_format'                    => get_option( 'time_format' ),
);

foreach ( $strings as $i => $string ) {

	if ( is_string( $string ) && 'category_field_info' !== $i ) {
		$strings[ $i ] = esc_js( $string );
	}
}

$strings = array( _WP_Editors::$mce_locale => array( 'calendarp_l10n' => $strings ) );
$strings = 'tinyMCE.addI18n(' . json_encode( $strings ) . ');';
