<?php
/**
 * Calendar Plus: includes/shortcodes/class-calendar-plus-calendar-shortcode-old.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Calendar_Shortcode_Old. */
class Calendar_Plus_Calendar_Shortcode_Old {

	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	public $enqueue_scripts = false;

	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {
		add_shortcode( 'calendarp-calendar-v1', array( $this, 'render' ) );
		add_action( 'wp_footer', array( $this, 'enqueue_scripts' ) );
	}


	/**
	 * Enqueue scripts.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function enqueue_scripts() {
		if ( ! $this->enqueue_scripts ) {
			return;
		}

		$url_base = calendarp_get_plugin_url() . 'public/';
		$version  = calendarp_get_version();

		wp_enqueue_style( 'calendarp-v1', $url_base . 'css/calendar-plus-v1.css', array(), $version, true );
		wp_enqueue_script( 'calendarp-v1', $url_base . 'js/calendar-plus-v1.js', array(), $version, true );
	}

	/**
	 * Handle render.
	 *
	 * @param array $atts Atts.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function render( $atts ) {
		$this->enqueue_scripts = true;

		$current_time = calendarp_wall_time();

		$default_mode = 'month';
		if ( isset( $atts['view'] ) ) {
			$default_mode = $atts['view'];
		}
		$args = array(
			'month'           => null !== calendarp_form_input( 'calendar_month', null, 'request' ) ? absint( calendarp_form_input( 'calendar_month', '', 'request' ) ) : gmdate( 'n', $current_time ),
			'year'            => null !== calendarp_form_input( 'calendar_year', null, 'request' ) ? absint( calendarp_form_input( 'calendar_year', '', 'request' ) ) : gmdate( 'Y', $current_time ),
			'day'             => null !== calendarp_form_input( 'calendar_day', null, 'request' ) ? absint( calendarp_form_input( 'calendar_day', '', 'request' ) ) : gmdate( 'd', $current_time ),
			'mode'            => null !== calendarp_form_input( 'calendar_mode', null, 'request' ) ? calendarp_form_input( 'calendar_mode', '', 'request' ) : $default_mode,
			'page'            => null !== calendarp_form_input( 'calendar_page', null, 'request' ) ? calendarp_form_input( 'calendar_page', '', 'request' ) : 1,
			'events_per_page' => ! empty( $atts['epp'] ) ? absint( $atts['epp'] ) : calendarp_get_events_per_page(),
		);

		if ( isset( $atts['category'] ) && term_exists( absint( $atts['category'] ), 'calendar_event_category' ) ) {
			$args['category'] = absint( $atts['category'] );
		}

		if ( 'agenda' !== $args['mode'] ) {
			$args['events_per_page'] = -1;
		}

		$cal_obj             = calendarp_get_the_calendar_object( $args );
		$the_calendar        = $cal_obj['the_calendar'] ?? '';
		$calendar_renderer   = $cal_obj['calendar_renderer'] ?? '';
		$calendar_cells_week = $cal_obj['calendar_cells_week'] ?? '';
		$calendar_cells_ids  = $cal_obj['calendar_cells_ids'] ?? '';

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

			echo wp_kses( $the_calendar, calendarp_get_allowed_calendar_html() );

			?>

			<?php $calendar_renderer->controls(); ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
