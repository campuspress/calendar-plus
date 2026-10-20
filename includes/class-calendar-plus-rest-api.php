<?php
/**
 * Calendar Plus: includes/class-calendar-plus-rest-api.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Calendar component: Calendar_Plus_REST_API. */
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
	 *
	 * @return mixed Calendar operation result.
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
	 *
	 * @return mixed Calendar operation result.
	 */
	private function rest_api_includes() {
		// Exception handler.
		include_once calendarp_get_plugin_dir() . 'includes/rest-api/class-calendar-plus-rest-exception.php';

		// Abstract controllers.
		include_once calendarp_get_plugin_dir() . 'includes/rest-api/abstract-calendar-plus-rest-controller.php';

		// REST API controllers.
		include_once calendarp_get_plugin_dir() . 'includes/rest-api/endpoints/class-calendar-plus-rest-endpoints-event.php';
	}

	/**
	 * Register REST API routes.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function register_rest_routes() {
		$controllers = array(
			'Calendar_Plus_REST_Endpoints_Events',
		);

		foreach ( $controllers as $controller ) {
			$controller_instance = new $controller();
			$controller_instance->register_routes();
		}
	}
}
