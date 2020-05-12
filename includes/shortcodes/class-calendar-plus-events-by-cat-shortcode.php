<?php

class Calendar_Plus_Events_By_Category_Shortcode {

	public $enqueue_scripts = false;

	public function __construct() {
		add_shortcode( 'calendarp-events-by-category', array( $this, 'render' ) );
		add_shortcode( 'calendarp-events-list', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action('init', array($this,'register_block'));
	}

	public function enqueue_scripts() {
		if ( ! $this->enqueue_scripts ) {
			return;
		}

		calendarp_enqueue_public_script_and_styles();

		wp_enqueue_style(
			'calendarp-events-by-cat',
			calendarp_get_plugin_url() . 'public/css/calendar-plus-events-by-cat-shortcode.css',
			[], calendarp_get_version()
		);
	}

	public function render( $atts ) {
		$this->enqueue_scripts = true;
		$this->enqueue_scripts();

		$current_time = current_time( 'timestamp' );

		$args = array();

		foreach ( array( 'category', 'tag' ) as $taxonomy ) {
			$terms = array();

			if ( isset( $atts[ $taxonomy ] ) ) {

				$atts[ $taxonomy ] = explode( ',', $atts[ $taxonomy ] );

				foreach ( $atts[ $taxonomy ] as $term ) {
					if ( term_exists( absint( $term ), 'calendar_event_' . $taxonomy ) ) {
						$terms[] = absint( $term );
					}
				}
			}

			$args[ $taxonomy ] = $terms;
		}

		if ( isset( $atts['events'] ) && absint( $atts['events'] ) ) {
			$args['events_per_page'] = absint( $atts['events'] );
		}

		$sticky_ids = get_option( 'sticky_posts' );

		if ( empty( $sticky_ids ) ) {

			$event_groups = array(
				calendarp_get_events_in_date_range( $current_time, false, $args ),
			);

		} else {

			$event_groups = array(
				calendarp_get_events_in_date_range( $current_time, false, $args + array( 'include_ids' => $sticky_ids ) ),
				calendarp_get_events_in_date_range( $current_time, false, $args + array( 'exclude_ids' => $sticky_ids ) ),
			);
		}

		ob_start();
		foreach ( $event_groups as $events_by_date ) {
			foreach ( $events_by_date as $date => $events ) {
				$month_name = mysql2date( 'M', $date, true );
				$day = mysql2date( 'd', $date, true );

				?>
				<div class="calendarp">
					<div class="calendarp_calendar agenda-minified-calendar">
						<div class="calendarp-date-item row">
							<div class="calendarp-date large-2 columns text-center">
								<?php if ( 'sticky' !== $date ) : ?>
									<div class="calendarp-date-month"><?php echo $month_name; ?></div>
									<div class="calendarp-date-day"><?php echo $day; ?></div>
								<?php endif; ?>
							</div>
							<div class="calendarp-events large-10 columns">
								<?php
								/** @var Calendar_Plus_Event $event */
								foreach ( $events as $event ) :
									?>
									<div class="calendar-event">
										<h3>
											<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>">
												<?php echo get_the_title( $event->ID ); ?>
											</a>
										</h3>
										<div class="calendarp-event-meta">
											<?php echo calendarp_get_human_read_dates( $event->ID ); ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
		}

		$content = ob_get_clean();
		if ( empty( $content ) && calendarp_is_rest_api_request() ) {
			$content = calendarp_block_error_msg(
				__( 'There are no events matching your query to show.', 'calendar-plus' )
			);
		}
		return apply_filters( 'calendarp_events_list_shortcode_content', $content, $event_groups, $atts );
	}
	
	/**
	 * Register block for this shortcode
	 */
	public function register_block() {
		if(function_exists('register_block_type')) {
			register_block_type( 'calendar-plus/events-list', 
				array(
					'render_callback' => array( $this, 'blocks_content' ),
					'attributes' => array(
						'events' => array(
							'type' => 'number',
							'default' => 5,
						),
						'category' => array(
							'type' => 'string',
						),
					)
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
	public function blocks_content($atts) {
		return $this->render($atts);
	}
}
