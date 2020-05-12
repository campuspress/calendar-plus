<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */


if ( ! defined( 'ABSPATH' ) || class_exists( 'Calendar_Plus_iCal_Calendar_Button' ) ) {
	return;
}

class Calendar_Plus_iCal_Calendar_Button extends Calendar_Plus_Calendar_Button implements Calendar_Plus_External_Calendar_Button {

	public function get_button_link( $args = array() ) {

		$url = admin_url( 'admin-ajax.php', 'relative' );

		$url = add_query_arg( 'event', $this->event->ID, $url );

		if ( isset( $args['category'] ) ) {
			$url = add_query_arg( 'event-category', $args['category'], $url );
		}

		if ( isset( $args['location'] ) ) {
			$url = add_query_arg( 'event-location', $args['location'], $url );
		}

		if ( isset( $args['s'] ) ) {
			$url = add_query_arg( 'event-search', $args['s'], $url );
		}

		$url = add_query_arg( 'action', 'download_ical_file', $url );

		return $url;
	}

	public static function get_event_ids( $args = array() ) {
		$defaults = array(
			'event'    => false,
			'category' => false,
			'location' => false,
			's'        => false,
		);

		$args = wp_parse_args( $args, $defaults );

		$event = calendarp_get_event( $args['event'] );
		$category = get_term( $args['category'], 'calendar_event_category' );
		if ( ! $category || is_wp_error( $category ) ) {
			$category = false;
		}

		$location = calendarp_get_location( $args['location'] );

		$query_args = array(
			'post_type'           => 'calendar_event',
			'ignore_sticky_posts' => true,
			'fields'              => 'ids',
			'posts_per_page'      => -1,
		);

		if ( $event ) {
			$query_args['p'] = $event->ID;
		}

		if ( $args['s'] ) {
			$query_args['s'] = $args ['s'];
		}

		if ( $location ) {
			$query_args['meta_query'] = array(
				array(
					'key'   => '_location_id',
					'value' => $location->ID,
				),
			);
		}

		if ( $category ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'calendar_event_category',
					'field'    => 'term_id',
					'terms'    => $category->term_id,
				),
			);
		}

		$event_ids = get_posts( $query_args );

		return $event_ids;
	}

	public static function download_file() {

		$fields = array(
			'event'    => 'event',
			'category' => 'event-category',
			'location' => 'event-location',
			's'        => 'event-search',
		);

		$args = array();

		foreach ( $fields as $arg_name => $query_var ) {
			$args[ $arg_name ] = isset( $_GET[ $query_var ] ) ?
				absint( $_GET[ $query_var ] ) : false;
		}

		$event_ids = self::get_event_ids( $args );
		$generator = new Calendar_Plus_iCal_Generator( $event_ids );
		$generator->download_file();
	}
}
