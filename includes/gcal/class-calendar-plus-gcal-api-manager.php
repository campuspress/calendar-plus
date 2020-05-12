<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'eduhosting_load_vendor' ) ) {
	function eduhosting_load_vendor() {
		include_once calendarp_get_plugin_dir() . 'vendor/autoload.php';
	}
}

class Calendar_Plus_Google_Calendar_API_Manager {

	/**
	 * @var bool|Google_Client
	 */
	private $client = false;

	/**
	 * @var bool|Google_Service_Calendar
	 */
	private $service = false;

	/**
	 * Calendar ID
	 *
	 * @var string
	 */
	private $calendar = '';

	/**
	 * Unique instance of this class
	 *
	 * @var null|Calendar_Plus_Google_Calendar_API_Manager
	 */
	private static $instance = null;

	/**
	 * Get the unique instance for this class
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			$calendar_id = calendarp_get_setting( 'google-calendar' );
			self::$instance = new self( $calendar_id );
		}
		return self::$instance;
	}

	public function __construct( $calendar_id ) {
		eduhosting_load_vendor();
		include_once( 'class-calendar-plus-gcal-logger.php' );
		include_once( 'class-calendar-plus-gcal-api-endpoint.php' );
		include_once( 'endpoints/class-calendar-plus-gcal-api-events-endpoint.php' );
		include_once( 'endpoints/class-calendar-plus-gcal-api-calendars-endpoint.php' );
		$this->calendar = $calendar_id;

		$this->calendars_endpoint = new Calendar_Plus_Google_Calendar_API_Calendars_Endpoint( $this );
		$this->events_endpoint = new Calendar_Plus_Google_Calendar_API_Events_Endpoint( $this );
	}

	/**
	 * Check if there's a connection with GCal
	 *
	 * @return bool
	 */
	public function is_connected() {
		$access_token = (object) $this->get_access_token();
		if ( ! $access_token ) {
			return false;
		}

		$options = calendarp_get_settings();

		if ( empty( $options['gcal_access_code'] ) ) {
			return false;
		}

		if ( empty( $options['gcal_client_id'] ) || empty( $options['gcal_client_secret'] ) ) {
			// No client secret and no client ID, why do we have a token then?
			$this->set_access_token( '{"access_token":0}' );
			$options['gcal_token'] = '';
			$options['gcal_client_id'] = '';
			$options['gcal_client_secret'] = '';
			$options['gcal_access_code'] = '';
			calendarp_update_settings( $options );
			return false;
		}

		if ( ( ! isset( $access_token->access_token ) ) || ( isset( $access_token->access_token ) && ! $access_token->access_token ) ) {
			return false;
		}

		return true;
	}

	public function connect() {

	}

	/**
	 * Return the Google Client Instance
	 *
	 * @return Google_Client
	 */
	public function get_client() {
		if ( ! $this->client ) {
			$this->client = new Google_Client();
			$this->client->setApplicationName( 'Calendar+' );
			$this->client->setScopes( 'https://www.googleapis.com/auth/calendar' );
			$this->client->setAccessType( 'offline' );
			$this->client->setRedirectUri( 'urn:ietf:wg:oauth:2.0:oob' );
			//          $this->client->setLogger( new Calendar_Plus_Google_Calendar_Logger( $this->client ) );
		}
		return $this->client;
	}

	/**
	 * Return the Google Client Instance
	 *
	 * @return Google_Service_Calendar
	 */
	public function get_service() {
		if ( ! $this->service ) {
			$this->service = new Google_Service_Calendar( $this->get_client() );
		}
		return $this->service;
	}

	/**
	 * Return the selected Calendar ID
	 *
	 * @return string
	 */
	public function get_calendar() {
		return $this->calendar;
	}

	/**
	 * Generate an authorization URL where the user can allow Calendar+ to access Google API
	 *
	 * @return string|WP_Error
	 */
	public function create_auth_url() {
		$client = $this->get_client();
		return $client->createAuthUrl();
	}

	/**
	 * Sets the Client ID and Client Secret for this session
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 */
	public function set_client_id_and_secret( $client_id, $client_secret ) {
		$this->get_client();
		$this->client->setClientId( $client_id );
		$this->client->setClientSecret( $client_secret );
	}

	/**
	 * Sets the access token for this session
	 *
	 * @param string $token JSON string
	 *
	 * @return bool|WP_Error
	 */
	public function set_access_token( $token ) {
		$client = $this->get_client();
		try {
			$client->setAccessToken( $token );
		} catch ( Exception $e ) {
			return new WP_Error( 'calendarp-gcal-set-token', $e->getMessage() );
		}

		return true;
	}

	/**
	 * Get the current session token
	 *
	 * @return string JSON string
	 */
	public function get_access_token() {
		$client = $this->get_client();
		return $client->getAccessToken();
	}


	/**
	 * Revoke the current session token
	 *
	 * @return bool|WP_Error
	 */
	public function revoke_token() {
		$client = $this->get_client();
		if ( $client->getAccessToken() ) {
			try {
				$client->revokeToken();
			} catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}
		}

		return true;
	}

	/**
	 * Check if the session token is expired
	 *
	 * @return bool
	 */
	public function is_token_expired() {
		$client = $this->get_client();
		return $client->isAccessTokenExpired();
	}

	/**
	 * Try to authenticate into Google by passing an access code
	 *
	 * @param string $access_code
	 *
	 * @return bool|WP_Error
	 */
	public function authenticate( $access_code ) {
		$client = $this->get_client();
		try {
			$client->authenticate( $access_code );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}

		return true;
	}
}
