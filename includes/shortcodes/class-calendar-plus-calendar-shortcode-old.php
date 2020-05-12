<?php

class Calendar_Plus_Calendar_Shortcode_Old {

	public $enqueue_scripts = false;

	public function __construct() {
		add_shortcode( 'calendarp-calendar-v1', array( $this, 'render' ) );
		add_action( 'wp_footer', array( $this, 'enqueue_scripts' ) );
	}


	public function enqueue_scripts() {
		if ( ! $this->enqueue_scripts ) {
			return;
		}

		$url_base = calendarp_get_plugin_url() . 'public/';
		$version = calendarp_get_version();

		wp_enqueue_style( 'calendarp-v1', $url_base . 'css/calendar-plus-v1.css', [], $version );
		wp_enqueue_script( 'calendarp-v1', $url_base . 'js/calendar-plus-v1.js', [], $version );
	}

	public function render( $atts ) {
		$this->enqueue_scripts = true;

		$current_time = current_time( 'timestamp' );

		$default_mode = 'month';
		if ( isset( $atts['view'] ) ) {
			$default_mode = $atts['view'];
		}
		$args = array(
			'month'           => isset( $_REQUEST['calendar_month'] ) ? absint( $_REQUEST['calendar_month'] ) : date( 'n', $current_time ),
			'year'            => isset( $_REQUEST['calendar_year'] ) ? absint( $_REQUEST['calendar_year'] ) : date( 'Y', $current_time ),
			'day'             => isset( $_REQUEST['calendar_day'] ) ? absint( $_REQUEST['calendar_day'] ) : date( 'd', $current_time ),
			'mode'            => isset( $_REQUEST['calendar_mode'] ) ? $_REQUEST['calendar_mode'] : $default_mode,
			'page'            => isset( $_REQUEST['calendar_page'] ) ? $_REQUEST['calendar_page'] : 1,
			'events_per_page' => ! empty( $atts['epp'] ) ? absint( $atts['epp'] ) : calendarp_get_events_per_page(),
		);

		if ( isset( $atts['category'] ) && term_exists( absint( $atts['category'] ), 'calendar_event_category' ) ) {
			$args['category'] = absint( $atts['category'] );
		}

		if ( 'agenda' != $args['mode'] ) {
			$args['events_per_page'] = -1;
		}

		$cal_obj = calendarp_get_the_calendar_object( $args );
		extract( $cal_obj );

		ob_start();

		$calendar_renderer->calendar_slots( $calendar_cells_week, $args['mode'], calendarp_get_calendar_event_shortcode_template() );
		?>
		<div class="calendarp">
			<div class="calendar-mode row">
				<div class="large-12 columns">
					<?php $calendar_renderer->calendar_mode_selector(); ?>
				</div>
			</div>

			<?php

			echo $the_calendar;

			?>

			<?php echo $calendar_renderer->controls(); ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
