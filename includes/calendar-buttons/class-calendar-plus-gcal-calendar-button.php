<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */


if ( ! defined( 'ABSPATH' ) || class_exists( 'Calendar_Plus_GCal_Calendar_Button' ) ) {
	return;
}

class Calendar_Plus_GCal_Calendar_Button extends Calendar_Plus_Calendar_Button implements Calendar_Plus_External_Calendar_Button {

	public function get_button_link( $args = array() ) {
		$defaults = array(
			'from' => 0,
			'to'   => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$base_url = 'https://calendar.google.com/calendar/r/eventedit';

		$location = $this->event->get_location();
		if ( $this->event->is_all_day_event() ) {
			$format = 'Ymd';
		} else {
			$format = 'Ymd\THis\Z';
		}

		$dates = get_gmt_from_date( date( 'Y-m-d H:i:s', $args['from'] ), $format )
		         . '/' . get_gmt_from_date( date( 'Y-m-d H:i:s', $args['to'] ), $format );

		return add_query_arg(
			array(
				'text'     => urlencode( get_the_title( $this->event->ID ) ),
				'details'  => urlencode( get_the_excerpt( $this->event->ID ) ),
				'location' => $location ? urlencode( $location->get_full_address() ) : '',
				'dates'    => $dates,
			),
			$base_url
		);
	}
}
