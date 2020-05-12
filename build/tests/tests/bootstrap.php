<?php

class Calendar_Plus_UnitTestCase extends CampusPress_UnitTestCase {
	public function setUp() {
		parent::setUp();
		//activate_calendarp();
	}
}

class Calendar_Plus_REST_API_UnitTestCase extends Calendar_Plus_UnitTestCase {

	protected $server;

	public function setUp() {
		parent::setUp();

		add_filter( 'rest_url', array( $this, 'filter_rest_url_for_leading_slash' ), 10, 2 );
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new Spy_REST_Server;
		do_action( 'rest_api_init' );
	}

	public function tearDown() {
		parent::tearDown();
		remove_filter( 'rest_url', array( $this, 'test_rest_url_for_leading_slash' ), 10 );
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = null;
	}

	public function filter_rest_url_for_leading_slash( $url, $path ) {
		if ( is_multisite() ) {
			return $url;
		}

		// Make sure path for rest_url has a leading slash for proper resolution.
		$this->assertTrue( 0 === strpos( $path, '/' ), 'REST API URL should have a leading slash.' );

		return $url;
	}
}

if ( ! class_exists( 'Spy_REST_Server' ) ) {
	class Spy_REST_Server extends WP_REST_Server {

		public $sent_headers = array();
		public $sent_body = '';
		public $last_request = null;
		public $override_by_default = false;

		/**
		 * Get the raw $endpoints data from the server
		 *
		 * @return array
		 */
		public function get_raw_endpoint_data() {
			return $this->endpoints;
		}

		/**
		 * Allow calling protected methods from tests
		 *
		 * @param string $method Method to call
		 * @param array  $args Arguments to pass to the method
		 *
		 * @return mixed
		 */
		public function __call( $method, $args ) {
			return call_user_func_array( array( $this, $method ), $args );
		}

		public function send_header( $header, $value ) {
			$this->sent_headers[ $header ] = $value;
		}

		/**
		 * Override the dispatch method so we can get a handle on the request object.
		 *
		 * @param  WP_REST_Request $request
		 *
		 * @return WP_REST_Response Response returned by the callback.
		 */
		public function dispatch( $request ) {
			$this->last_request = $request;

			return parent::dispatch( $request );
		}

		/**
		 * Override the register_route method so we can re-register routes internally if needed.
		 *
		 * @param string $namespace Namespace.
		 * @param string $route The REST route.
		 * @param array  $route_args Route arguments.
		 * @param bool   $override Optional. Whether the route should be overridden if it already exists.
		 *                           Default false. Also set $GLOBALS['wp_rest_server']->override_by_default = true
		 *                           to set overrides when you don't have access to the caller context.
		 */
		public function register_route( $namespace, $route, $route_args, $override = false ) {
			parent::register_route( $namespace, $route, $route_args, $override || $this->override_by_default );
		}

		public function serve_request( $path = null ) {

			ob_start();
			$result = parent::serve_request( $path );
			$this->sent_body = ob_get_clean();

			return $result;
		}
	}

}

if ( ! function_exists( 'xd' ) ) {
	function xd() { die( var_export( func_get_args() ) ); }
}
