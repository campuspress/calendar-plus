<?php

class Calendar_Plus_Google_Calendar {

	/**
	 * Google Calendar API abstraction class
	 * @var Calendar_Plus_Google_Calendar_API_Manager
	 */
	private $api;

	public function __construct() {
		//add_action( 'save_post', array( $this, 'save_event' ), 10, 3 );
	}

	public function get_api_manager() {
		if ( ! $this->api ) {
			$options = calendarp_get_settings();

			include_once( 'gcal/class-calendar-plus-gcal-api-manager.php' );
			$this->api = Calendar_Plus_Google_Calendar_API_Manager::get_instance();

			if ( ! empty( $options['gcal_client_id'] ) && ! empty( $options['gcal_client_id'] ) ) {
				$this->api->set_client_id_and_secret( $options['gcal_client_id'], $options['gcal_client_secret'] );
			}

			if ( ! empty( $options['gcal_token'] ) ) {
				$this->api->set_access_token( $options['gcal_token'] );
				add_action( 'shutdown', array( $this, 'save_new_token' ) );
			}
		}
		return $this->api;
	}

	public function save_event( $post_id, $post, $update ) {
		if ( get_post_type( $post_id ) != 'calendar_event' ) {
			return;
		}
	}

	/**
	 * Sometimes Google will refresh the token.
	 * If so, we'll save it
	 */
	public function save_new_token() {
		$current_token = $this->api->get_access_token();
		if ( ! $current_token ) {
			return;
		}

		$options = calendarp_get_settings();
		if ( $options['gcal_token'] != $current_token ) {
			$options['gcal_token'] = $current_token;
			calendarp_update_settings( $options );
		}
	}
}
