<?php
/**
 * Calendar Plus: includes/class-calendar-plus-sidebar.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Sidebar. */
class Calendar_Plus_Sidebar {

	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	private $plugin_name = '';

	/**
	 * Initialize this calendar component.
	 *
	 * @param mixed $plugin_name Plugin name.
	 */
	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
		add_action( 'widgets_init', array( $this, 'register_sidebar' ) );
		add_action( 'sidebars_widgets', array( $this, 'replace_widgets' ), 100 );
	}


	/**
	 * Register sidebar.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function register_sidebar() {
		$replace_sidebar = calendarp_get_setting( 'replace_sidebar' );
		if ( ! $replace_sidebar ) {
			return;
		}

		register_sidebar(
			array(
				'name'        => __( 'Calendar Plus Sidebar', 'calendar-plus' ),
				'id'          => 'calendarp',
				'description' => __( 'Widgets in this area will be displayed on all Calendar Plus pages.', 'calendar-plus' ),
			)
		);
	}

	/**
	 * Handle replace widgets.
	 *
	 * @param mixed $widgets Widgets.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function replace_widgets( $widgets ) {
		if ( is_admin() ) {
			return $widgets;
		}

		$replace_sidebar = calendarp_get_setting( 'replace_sidebar' );
		if ( empty( $replace_sidebar ) || ! isset( $widgets[ $replace_sidebar ] ) ) {
			return $widgets;
		}

		if ( ! calendarp_is_calendarp() ) {
			return $widgets;
		}

		$widgets[ $replace_sidebar ] = $widgets['calendarp'];

		return $widgets;
	}
}
