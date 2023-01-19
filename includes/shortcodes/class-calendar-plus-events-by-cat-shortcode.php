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
			array(),
			calendarp_get_version()
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

		$display_location = ! empty( $atts['display_location'] ) && $atts['display_location'] !== 'false';
		$display_excerpt  = ! empty( $atts['display_excerpt'] ) && $atts['display_excerpt'] !== 'false';

		$sticky_ids = get_option( 'sticky_posts' );

		$from_date = false;
		$to_date   = false;
		if( isset( $atts['past_events'] ) ) {
			if( $atts['past_events'] !== false ) {
				$to_date = $current_time;
			} else {
				$from_date = $current_time;
			}
		} else {

			$from_date = $current_time;
		}

		if ( empty( $sticky_ids ) ) {

			$event_groups = array(
				calendarp_get_events_in_date_range( $from_date, $to_date, $args ),
			);

		} else {

			$event_groups = array(
				calendarp_get_events_in_date_range( $from_date, $to_date, $args + array( 'include_ids' => $sticky_ids ) ),
				calendarp_get_events_in_date_range( $from_date, $to_date, $args + array( 'exclude_ids' => $sticky_ids ) ),
			);
		}
		$template_data = array(
			'featured_image' => isset( $atts['featured_image'] ) && $atts['featured_image']
		);

		if ( ! function_exists( 'calendarp_locate_template' ) ) {
			require_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';
		}

		ob_start();

		include( calendarp_locate_template( 'shortcodes/events-list.php' ) );

		$content = ob_get_clean();

		if ( empty( $content ) && calendarp_is_rest_api_request() ) {
			$content = calendarp_block_error_msg(
				__( 'There are no events matching your query to show.', 'calendar-plus' )
			);
		}

		if ( $content ) {
			$class = 'calendarp-events-by-cat';
			if ( isset( $atts['class'] ) ) {
				$class = $atts['class'];
			}

			if ( isset( $atts['className'] ) ) {
				$class =  !empty( $class ) ? $class . ' ' . $atts['className'] :  $atts['className'];
			}

			if( $class ) {
				$content = '<div class="' . esc_attr( $class ) . '">' . $content . '</div>';
			}
		}

		return apply_filters( 'calendarp_events_list_shortcode_content', $content, $event_groups, $atts );
	}
	
	/**
	 * Register block for this shortcode
	 */
	public function register_block() {
		if(function_exists('register_block_type')) {
			register_block_type(
				'calendar-plus/events-list',
				array(
					'render_callback' => array( $this, 'blocks_content' ),
					'attributes'      => array(
						//Ref: https://github.com/WordPress/gutenberg/issues/16850#issuecomment-633203698
						'className' => array(
							'default' => '',
							'type'    => 'string'
						),
						'events' => array(
							'type' => 'number',
							'default' => 5,
						),
						'category' => array(
							'type' => 'string',
						),
						'past_events' => array(
							'type' => 'boolean',
							'default' => false
						),
						'display_location' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'display_excerpt' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'featured_image' => array(
							'type' => 'boolean',
							'default' => false
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
	public function blocks_content($atts) {
		return $this->render($atts);
	}
}
