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
	 * Renders shortcode
	 *
	 * @param array $atts Shortcode attributes
	 *
	 * @return string Shortcode html
	 */
	public function render( $atts ) {
		return $this->render_attributes( $atts, 'shortcodes/event-single.php' );
	}

	/**
	 * Renders shortcode. The function is used for compatibility with themes
	 *
	 * @param array $atts Shortcode attributes
	 *
	 * @return string Shortcode html
	 */
	public function render_compat( $atts ) {
		return $this->render_attributes( $atts, 'shortcodes/event-single.plain.php' );
	}

	/**
	 * Render the shortcode content
	 *
	 * @param array $atts Shortcode attributes
	 *
	 * @return string Shortcode output
	 */
	private function render_attributes( $atts, $template ) {

		if ( empty( $atts['event_id'] ) || ! $event = calendarp_get_event( $atts['event_id'] ) ) {
			return calendarp_is_rest_api_request()
				? calendarp_block_error_msg( __( 'Please, enter the event ID in Event Settings', 'calendar-plus' ) )
				: '';
		}

		if ( ! function_exists( 'calendarp_locate_template' ) ) {
			require_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';
		}

		setup_postdata( $event->ID );

		ob_start();

		include( calendarp_locate_template( $template ) );

		wp_reset_postdata();

		/**
		 * Filters single event shortcode content
		 *
		 * @since 2.2.6.7
		 *
		 * @param string $content Shortcode content.
		 * @param object $event Calendar event being rendered.
		 * @param array  $atts Shortcode attributes.
		 *
		 * @return string
		 */

		$content = ob_get_clean();

		return apply_filters(
			'calendarp_event_shortcode_content',
			$content,
			$event,
			$atts
		);
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

