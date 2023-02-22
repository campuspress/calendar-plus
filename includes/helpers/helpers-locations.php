<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve a single location
 *
 * @param int|Calendar_Plus_Location|WP_Post $location
 *
 * @return bool|Calendar_Plus_Location
 */
function calendarp_get_location( $location ) {
	return Calendar_Plus_Location::get_instance( $location );
}

/**
 * Search events locations by name
 *
 * @param $location_name
 *
 * @return bool|Calendar_Plus_Location|null
 */
function calendarp_find_location( $location_name ) {
	$location_post = get_page_by_title(
		esc_sql( $location_name ),
		OBJECT,
		'calendar_location'
	);

	if ( $location_post ) {
		return calendarp_get_location( $location_post );
	}

	return null;
}

/**
 * Determine whether an object is a valid location
 *
 * @param int|Calendar_Plus_Location|WP_Post $location
 *
 * @return bool
 */
function calendarp_is_location( $location ) {
	$location = calendarp_get_location( $location );
	if ( $location instanceof Calendar_Plus_Location ) {
		return true;
	}

	return false;
}

/**
 * Get all locations
 *
 * @param array $args Array of arguments
 *
 * @return array Array of Calendar_Plus_Location Objects
 */
function calendarp_get_locations( $args = array() ) {

	$defaults = array(
		's' => false,
	);
	$args = wp_parse_args( $args, $defaults );

	$args['post_type'] = 'calendar_location';
	$args['ignore_sticky_posts'] = true;

	$locations = get_posts( $args );

	$locations = array_map( 'calendarp_get_location', $locations );

	return $locations;
}

/**
 * Displays or returns a dropdown of locations
 *
 * @param array $args Array of arguments
 *
 * @return string HTML code
 */
function calendarp_locations_dropdown( $args = array() ) {
	$defaults = array(
		'name'        => 'calendarp-location',
		'id'          => false,
		'selected'    => false,
		'show_empty'  => true,
		'echo'        => true,
		'class'       => '',
		'empty_label' => __( '-- Select location --', 'calendar-plus' ),
	);

	$args = wp_parse_args( $args, $defaults );

	$id = $args['id'] ? $args['id'] : $args['name'];

	$content = sprintf(
		'<select name="%s" id="%s" class="%s">',
		esc_attr( $args['name'] ),
		esc_attr( $id ),
		esc_attr( $args['class'] )
	);

	if ( $args['show_empty'] ) {
		$content .= sprintf( '<option value=""%s>%s</option>', selected( $args['selected'], false, false ), $args['empty_label'] );
	}

	$locations = calendarp_get_locations( array( 'posts_per_page' => -1 ) );

	/** @var Calendar_Plus_Location $location */
	foreach ( $locations as $location ) {
		$content .= sprintf(
			'<option value="%d"%s>%s</option>',
			$location->ID, selected( $args['selected'], $location->ID, false ), get_the_title( $location->ID )
		);
	}

	$content .= '</select>';

	if ( $args['echo'] ) {
		echo $content;
	}

	return $content;
}

/**
 * @param int $location_id
 *
 * @return string
 */
function calendarp_get_google_map_html( $location_id ) {

	$location = calendarp_get_location( $location_id );
	if ( ! $location ) {
		return '';
	}

	if ( ! $location->has_map() ) {
		return '';
	}

	$height = calendarp_get_setting( 'event_single_map_height' );

	$html = '<div id="map_canvas" style="width=100%;height:' . $height . 'px"';
	$map_options = $location->gmaps_options;
	foreach ( $map_options as $option => $value ) {
		$html .= ' data-' . $option . '="' . $value . '"';
	}
	$html .= '></div>';

	return $html;

}
