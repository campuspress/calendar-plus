<?php
/**
 * Calendar Plus: includes/calendar-buttons/class-calendar-plus-gcal-calendar-button.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) || class_exists( 'Calendar_Plus_GCal_Calendar_Button' ) ) {
	return;
}

/** Calendar component: Calendar_Plus_GCal_Calendar_Button. */
class Calendar_Plus_GCal_Calendar_Button extends Calendar_Plus_Calendar_Button implements Calendar_Plus_External_Calendar_Button {

	/**
	 * Retrieve button link.
	 *
	 * @param array $args Args.
	 *
	 * @return mixed Calendar operation result.
	 */
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

		$dates = get_gmt_from_date( gmdate( 'Y-m-d H:i:s', $args['from'] ), $format )
				. '/' . get_gmt_from_date( gmdate( 'Y-m-d H:i:s', $args['to'] ), $format );

		return add_query_arg(
			array(
				'text'     => rawurlencode( get_the_title( $this->event->ID ) ),
				'details'  => rawurlencode( get_the_excerpt( $this->event->ID ) ),
				'location' => $location ? rawurlencode( $location->get_full_address() ) : '',
				'dates'    => $dates,
			),
			$base_url
		);
	}
}
