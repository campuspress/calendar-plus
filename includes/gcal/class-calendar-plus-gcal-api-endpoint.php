<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Calendar_Plus_Google_Calendar_API_Endpoint' ) ) {
	class Calendar_Plus_Google_Calendar_API_Endpoint {

		/**
		 * Calendar_Plus_Google_Calendar_API_Endpoint constructor.
		 *
		 * @param Calendar_Plus_Google_Calendar_API_Manager $api_manager
		 */
		public function __construct( $api_manager ) {
			$this->api = $api_manager;
		}
	}
}
