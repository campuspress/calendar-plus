<?php

class Calendar_Plus_Settings {

	private $settings_slug;

	private $settings = false;

	public function __construct( $plugin_name ) {
		$this->settings_slug = str_replace( '-', '_', $plugin_name . '_settings' );

		add_filter( 'calendarp_event_slug', array( $this, 'set_event_slug' ) );
		add_filter( 'calendarp_location_slug', array( $this, 'set_location_slug' ) );
		add_filter( 'calendarp_load_modules', array( $this, 'set_modules' ) );
	}

	public function get_settings() {
		if ( false === $this->settings ) {
			$this->settings = wp_parse_args( get_option( $this->settings_slug ), $this->get_default_settings() );
		}

		if ( defined( 'CALENDARP_GOOGLE_MAPS_API_KEY' ) ) {
			$this->settings['gmaps_api_key'] = CALENDARP_GOOGLE_MAPS_API_KEY;
		}

		return $this->settings;
	}

	public function get_setting( $name ) {
		$settings = $this->get_settings();

		if ( isset( $settings[ $name ] ) ) {
			return $settings[ $name ];
        }

		return false;
	}

	public function update_settings( $new_settings ) {
		$new_settings = wp_parse_args( $new_settings, $this->get_default_settings() );
		update_option( $this->settings_slug, $new_settings );
		$this->settings = $new_settings;
	}

	private function get_default_settings() {
		$defaults = array(
			'event_slug'               => 'event',
			'location_slug'            => 'location',
			'fb_twitter_login'         => false,
			'default_styles'           => true,
			'modules'                  => array( 'bp-activity-auto-updates' ),
			'country'                  => '',
			'time_format'              => preg_match( '/^(G|H)/', get_option( 'time_format' ) ) ? '24h' : 'AM/PM',
			'events_page_id'           => 0,
			'replace_sidebar'          => '',
			'event_thumbnail_width'    => 200,
			'event_thumbnail_height'   => 200,
			'event_thumbnail_crop'     => true,
			'event_single_map_height'  => 300,
			'display_location_country' => false,
			'gcal_client_id'           => '',
			'gcal_client_secret'       => '',
			'gcal_token'               => '',
			'gcal_access_code'         => '',
			'gcal_calendar_id'         => '',
			'gmaps_api_key'            => '',
			'ical_feed'                => '',
			'legacy_theme_integration' => '',
		);

		return apply_filters( 'calendarp_default_settings', $defaults );
	}


	public function set_event_slug( $slug ) {
		return $this->get_setting( 'event_slug' );
	}

	public function set_location_slug( $slug ) {
		return $this->get_setting( 'location_slug' );
	}

	public function set_modules( $modules ) {
		return $this->get_setting( 'modules' );
	}

	public function get_settings_slug() {
		return $this->settings_slug;
	}

}