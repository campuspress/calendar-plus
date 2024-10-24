<?php
if ( defined( 'ABSPATH' ) || ! class_exists( 'Calendar_Plus_Event_Shortcode' ) ) {
	return;
}

class Calendar_Plus_Date_Shortcode {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_shortcode( 'calendarp-date', array( $this, 'render' ) );

		add_filter( 'render_block_core/shortcode', function( $content ) {
			return do_shortcode( $content );
		}, 10 );
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
			'data' => 'date',
			'before' => '',
			'after' => ''
		), $atts, 'calendarp-date' );

		$data = calendarp_event_human_read_dates( $atts['data'] );

		if( $data ) {
			$data = '<span class="calendarp-block-event-date">' . esc_html( $atts['before'] ) . $data . esc_html( $atts['after'] ) . '</span>';
		}

		return $data;
	}
}

