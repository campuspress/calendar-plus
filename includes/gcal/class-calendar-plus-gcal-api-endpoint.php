<?php
/**
 * Calendar Plus: includes/gcal/class-calendar-plus-gcal-api-endpoint.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Calendar_Plus_Google_Calendar_API_Endpoint' ) ) {
	/** Calendar component: Calendar_Plus_Google_Calendar_API_Endpoint. */
	class Calendar_Plus_Google_Calendar_API_Endpoint {

		/**
		 * API manager.
		 *
		 * @var Calendar_Plus_Google_Calendar_API_Manager
		 */
		public $api;

		/**
		 * Calendar_Plus_Google_Calendar_API_Endpoint constructor.
		 *
		 * @param Calendar_Plus_Google_Calendar_API_Manager $api_manager Api manager.
		 */
		public function __construct( $api_manager ) {
			$this->api = $api_manager;
		}
	}
}
