<?php
if ( defined( 'ABSPATH' ) || ! class_exists( 'Calendar_Plus_Event_Shortcode' ) ) {
	return;
}

class Calendar_Plus_Location_Shortcode {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_shortcode( 'calendarp-location', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode content
	 *
	 * @param array $atts Shortcode attributes
	 *
	 * @return string Shortcode output
	 */
	public function render( $atts ) {

		if ( ! function_exists( 'calendarp_event_human_read_dates' ) ) {
			require_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';
		}

		$atts = shortcode_atts( array(
			'data' => 'address',
			'before' => '',
			'after' => ''
		), $atts, 'calendarp-date' );

		$location = calendarp_the_event_location();

		$data = '';
		if( $location ) {
			if( $atts['data'] == 'description' ) {
				$data = calendarp_get_location_description();
			}
			elseif( 'map' ) {
				$data = calendarp_get_google_map_html( $location->ID );
			}
			else {
				$data = $location->get_full_address();
			}

			if( $data ) {
				$data = '<span class="calendarp-block-event-location">' . esc_html( $atts['before'] ) . $data . esc_html( $atts['after'] ) . '</span>';
			}
		}

		return $data;
	}
}

