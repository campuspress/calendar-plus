<?php

class Calendar_Plus_Admin_Calendar_Page {

	public $slug;

	public function __construct( $plugin_name ) {
		$this->slug = $plugin_name . '-calendar';
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	public function add_menu() {
		$page_id = add_submenu_page( 'edit.php?post_type=calendar_event', __( 'Calendar', 'calendar-plus' ), __( 'Calendar', 'calendar-plus' ), 'manage_calendar_plus', $this->slug, array( $this, 'render' ) );
		add_action( 'load-' . $page_id, array( $this, 'redirect_on_submit_form' ) );
	}

	public function get_tabs() {
		return array(
			'month' => __( 'Month', 'calendar-plus' ),
			//'week' => __( 'Week', 'calendar-plus' ),
			//'day' => __( 'Day', 'calendar-plus' )
		);
	}

	private function get_current_tab() {
		$tabs = $this->get_tabs();

		if ( ! isset( $_GET['tab'] ) ) {
			return key( $tabs );
		}

		return isset( $tabs[ $_GET['tab'] ] ) ? $_GET['tab'] : key( $tabs );
	}

	/**
	 * Redirect to a better friendly URL when month selector is submitted
	 */
	public function redirect_on_submit_form() {
		if ( isset( $_POST['submit'] ) && isset( $_POST['calendar_month'] ) && isset( $_POST['calendar_year'] ) ) {
			$url = add_query_arg(
				array(
					'calendar_month' => absint( $_POST['calendar_month'] ),
					'calendar_year'  => absint( $_POST['calendar_year'] ),
				)
			);

			wp_redirect( $url );
			die();
		}

		if ( isset( $_POST['go-to-current-month'] ) ) {
			$url = add_query_arg(
				array(
					'calendar_month' => absint( date( 'n', current_time( 'timestamp' ) ) ),
					'calendar_year'  => absint( date( 'Y', current_time( 'timestamp' ) ) ),
				)
			);

			wp_redirect( $url );
			die();
		}
	}


	public function render() {
		global $wp_locale;

		$current_time = current_time( 'timestamp' );

		$tabs = $this->get_tabs();
		$current_tab = $this->get_current_tab();

		$args = array(
			'month'           => isset( $_REQUEST['calendar_month'] ) ? absint( $_REQUEST['calendar_month'] ) : date( 'n', $current_time ),
			'year'            => isset( $_REQUEST['calendar_year'] ) ? absint( $_REQUEST['calendar_year'] ) : date( 'Y', $current_time ),
			'day'             => isset( $_REQUEST['calendar_day'] ) ? absint( $_REQUEST['calendar_day'] ) : date( 'd', $current_time ),
			'mode'            => $current_tab,
			'events_per_page' => -1,
		);

		$cal_obj = calendarp_get_the_calendar_object( $args );

		extract( $cal_obj );

		include_once( calendarp_get_plugin_dir() . 'admin/views/admin-calendar.php' );
	}
}

