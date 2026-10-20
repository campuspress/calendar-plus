<?php
/**
 * Calendar Plus: admin/pages/class-calendar-page.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Admin_Calendar_Page. */
class Calendar_Plus_Admin_Calendar_Page {

	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	public $slug;

	/**
	 * Initialize this calendar component.
	 *
	 * @param mixed $plugin_name Plugin name.
	 */
	public function __construct( $plugin_name ) {
		$this->slug = $plugin_name . '-calendar';
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Add menu.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function add_menu() {
		$page_id = add_submenu_page( 'edit.php?post_type=calendar_event', __( 'Calendar', 'calendar-plus' ), __( 'Calendar', 'calendar-plus' ), 'manage_calendar_plus', $this->slug, array( $this, 'render' ) );
		add_action( 'load-' . $page_id, array( $this, 'redirect_on_submit_form' ) );
	}

	/**
	 * Retrieve tabs.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_tabs() {
		return array(
			'month' => __( 'Month', 'calendar-plus' ),
		);
	}

	/**
	 * Retrieve current tab.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function get_current_tab() {
		$tabs = $this->get_tabs();

		if ( ! ( null !== calendarp_form_input( 'tab', null, 'get' ) ) ) {
			return key( $tabs );
		}

		return isset( $tabs[ calendarp_form_input( 'tab', '', 'get' ) ] ) ? calendarp_form_input( 'tab', '', 'get' ) : key( $tabs );
	}

	/**
	 * Redirect to a better friendly URL when month selector is submitted.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function redirect_on_submit_form() {
		if ( null !== calendarp_form_input( 'submit', null, 'get' ) && null !== calendarp_form_input( 'calendar_month', null, 'get' ) && null !== calendarp_form_input( 'calendar_year', null, 'get' ) ) {
			$url = add_query_arg(
				array(
					'calendar_month' => absint( calendarp_form_input( 'calendar_month', '', 'get' ) ),
					'calendar_year'  => absint( calendarp_form_input( 'calendar_year', '', 'get' ) ),
				)
			);

			wp_safe_redirect( $url );
			die();
		}

		if ( null !== calendarp_form_input( 'go-to-current-month', null, 'get' ) ) {
			$url = add_query_arg(
				array(
					'calendar_month' => absint( gmdate( 'n', calendarp_wall_time() ) ),
					'calendar_year'  => absint( gmdate( 'Y', calendarp_wall_time() ) ),
				)
			);

			wp_safe_redirect( $url );
			die();
		}
	}


	/**
	 * Handle render.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function render() {
		global $wp_locale;

		$current_time = calendarp_wall_time();

		$tabs        = $this->get_tabs();
		$current_tab = $this->get_current_tab();

		$args = array(
			'month'           => null !== calendarp_form_input( 'calendar_month', null, 'request' ) ? absint( calendarp_form_input( 'calendar_month', '', 'request' ) ) : gmdate( 'n', $current_time ),
			'year'            => null !== calendarp_form_input( 'calendar_year', null, 'request' ) ? absint( calendarp_form_input( 'calendar_year', '', 'request' ) ) : gmdate( 'Y', $current_time ),
			'day'             => null !== calendarp_form_input( 'calendar_day', null, 'request' ) ? absint( calendarp_form_input( 'calendar_day', '', 'request' ) ) : gmdate( 'd', $current_time ),
			'mode'            => $current_tab,
			'events_per_page' => -1,
		);

		$cal_obj = calendarp_get_the_calendar_object( $args );

		$the_calendar        = $cal_obj['the_calendar'] ?? '';
		$calendar_renderer   = $cal_obj['calendar_renderer'] ?? '';
		$calendar_cells_week = $cal_obj['calendar_cells_week'] ?? '';
		$calendar_cells_ids  = $cal_obj['calendar_cells_ids'] ?? '';

		include_once calendarp_get_plugin_dir() . 'admin/views/admin-calendar.php';
	}
}
