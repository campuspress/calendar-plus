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

		if ( ! function_exists( 'calendarp_get_the_event_thumbnail' ) ) {
			require_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';
		}

		setup_postdata( $event->ID );
		ob_start();
		?>
		<div class="calendarp-event-shortcode">
			<div class="calendarp-event-shortcode-header">
				<?php echo calendarp_get_the_event_thumbnail( $event->ID, 'event_thumbnail' ); ?>
				<h3>
					<a href="<?php echo esc_attr( get_permalink( $event->ID ) ); ?>"
					   title="<?php esc_attr( sprintf( __( 'Permalink to %s', 'calendar-plus' ), get_permalink() ) ); ?>">
						<?php echo get_the_title( $event->ID ); ?>
					</a>
				</h3>

				<div class="event-meta">

					<?php if ( $date = calendarp_event_human_read_dates( 'date', $event->ID ) ) : ?>
						<div class="event-meta-item event-dates">
							<span class="dashicons dashicons-calendar-alt"></span><?php echo $date; ?>
						</div>
					<?php endif; ?>

					<?php if ( $recurrence = calendarp_event_human_read_dates( 'recurrence', $event->ID ) ) : ?>
						<div class="event-meta-item event-recurrence">
							<span class="dashicons dashicons-update"></span><?php echo $recurrence; ?>
						</div>
					<?php endif; ?>

					<?php if ( $time = calendarp_event_human_read_dates( 'time', $event->ID ) ) : ?>
						<div class="event-meta-item event-time">
							<span class="dashicons dashicons dashicons-clock"></span><?php echo $time; ?>
						</div>
					<?php endif; ?>

					<div class="event-meta-item event-categories">
						<?php calendarp_event_categories_list( $event->ID ); ?>
					</div>

					<div class="event-meta-item event-ical">
						<?php calendarp_event_ical_file_button( $event->ID ); ?>
					</div>

				</div>
			</div>
			<div class="calendarp-event-shortcode-content">
				<?php do_action( 'calendarp_content_event_content', $event ); ?>
			</div>
			<div class="calendarp-event-shortcode-footer">

			</div>
		</div>
		<?php
		wp_reset_postdata();
		$content = ob_get_clean();

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

