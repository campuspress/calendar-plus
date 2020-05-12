<?php

class Calendar_Plus_Sidebar {

	private $plugin_name = '';

	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
		add_action( 'widgets_init', array( $this, 'register_sidebar' ) );
		add_action( 'sidebars_widgets', array( $this, 'replace_widgets' ), 100 );
	}


	public function register_sidebar() {
		$replace_sidebar = calendarp_get_setting( 'replace_sidebar' );
		if ( ! $replace_sidebar ) {
			return;
		}

		register_sidebar( array(
			'name'        => __( 'Calendar Plus Sidebar', 'calendar-plus' ),
			'id'          => 'calendarp',
			'description' => __( 'Widgets in this area will be displayed on all Calendar Plus pages.', 'calendar-plus' ),
		) );
	}

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
