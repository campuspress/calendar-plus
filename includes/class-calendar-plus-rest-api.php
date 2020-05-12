<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Calendar_Plus_REST_API {

	/**
	 * Setup class.
	 */
	public function __construct() {
		// WP REST API.
		$this->rest_api_init();
	}

	/**
	 * Init WP REST API.
	 */
	private function rest_api_init() {
		// REST API was included starting WordPress 4.4.
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		$this->rest_api_includes();

		// Init REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Include REST API classes.
	 */
	private function rest_api_includes() {
		// Exception handler.
		include_once( calendarp_get_plugin_dir() . 'includes/rest-api/class-calendar-plus-rest-exception.php' );

		// Abstract controllers.
		include_once( calendarp_get_plugin_dir() . 'includes/rest-api/abstract-calendar-plus-rest-controller.php' );

		// REST API controllers.
		include_once( calendarp_get_plugin_dir() . 'includes/rest-api/endpoints/class-calendar-plus-rest-endpoints-event.php' );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		$controllers = array(
			'Calendar_Plus_REST_Endpoints_Events',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}
}
