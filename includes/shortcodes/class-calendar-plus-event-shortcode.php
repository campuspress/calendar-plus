<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( defined( 'ABSPATH' ) || ! class_exists( 'Calendar_Plus_Event_Shortcode' ) ) {
	return;
}

class Calendar_Plus_Event_Shortcode {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_shortcode( 'calendarp-event', array( $this, 'render' ) );

		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Render the shortcode content
	 *
	 * @param array $atts Shortcode attributes
	 *
	 * @return string Shortcode output
	 */
	public function render( $atts ) {

		if ( empty( $atts['event_id'] ) || ! $event = calendarp_get_event( $atts['event_id'] ) ) {
			return calendarp_is_rest_api_request()
				? calendarp_block_error_msg( __( 'Please, enter the event ID in Event Settings', 'calendar-plus' ) )
				: '';
		}

		if ( ! function_exists( 'calendarp_locate_template' ) ) {
			require_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';
		}

		ob_start();

		include( calendarp_locate_template( 'shortcodes/event-single.php' ) );

		return ob_get_clean();
	}

	/**
	 * Register block for this shortcode
	 */
	public function register_block() {
		if ( function_exists( 'register_block_type' ) ) {
			register_block_type(
				'calendar-plus/event',
				array(
					'render_callback' => array( $this, 'blocks_content' ),
					'attributes'      => array(
						'event_id' => array(
							'type' => 'number',
						),
					),
				)
			);
		}
	}

	/**
	 * Render block on frontend
	 *
	 * @param array $atts
	 * @return void
	 */
	public function blocks_content( $atts ) {
		return $this->render( $atts );
	}
}

