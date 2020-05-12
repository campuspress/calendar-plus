<?php

/**
 * Represents a calendar event location
 *
 * @property-read array  $gmaps_options
 * @property-read string $location_type
 * @property-read string $address
 * @property-read string $city
 * @property-read string $state
 * @property-read string $country
 * @property-read string $postcode
 */
class Calendar_Plus_Location {

	/**
	 * Location ID
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Associated post object
	 *
	 * @var WP_Post|bool
	 */
	public $post = false;

	/**
	 * Build an instance of this class
	 *
	 * @static
	 * @access public
	 *
	 * @param int|Calendar_Plus_Location|WP_Post $location Location ID|object.
	 *
	 * @return Calendar_Plus_Location|false Location object, false otherwise.
	 */
	public static function get_instance( $location ) {

		if ( is_numeric( $location ) ) {
			$_event = get_post( absint( $location ) );
			if ( ! $_event ) {
				return false;
			}

			if ( 'calendar_location' != $_event->post_type ) {
				return false;
			}

			return new self( $_event->ID );

		} elseif ( $location instanceof Calendar_Plus_Location ) {
			return $location;

		} elseif ( $location instanceof WP_Post ) {
			return new self( $location->ID );
		}

		return false;
	}

	/**
	 * Class constructor
	 *
	 * @param int $location_id Location ID.
	 */
	public function __construct( $location_id ) {
		$location = get_post( $location_id );
		$this->post = $location;
		$this->ID = $location->ID;
	}

	/**
	 * Retrieve the value of a class property
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		$value = get_post_meta( $this->ID, '_' . $key, true );

		if ( 'gmaps_options' === $key ) {
			$value = wp_parse_args( $value, array(
				'lat'         => 0,
				'long'        => 0,
				'marker-lat'  => false,
				'marker-long' => false,
				'zoom'        => 2,
				'address'     => '',
			) );
		}

		if ( 'location_type' === $key ) {
			$value = $value ? $value : 'standard';
		}

		return $value;
	}

	/**
	 * Retrieve the post object associated with this location
	 * @return bool|WP_Post
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 * Retrieve the address for this location
	 * @return string
	 */
	public function get_full_address() {
		$countries = calendarp_get_countries();
		$full_address = get_the_title( $this->ID );
		$settings = calendarp_get_settings();

		if ( $this->address ) {
			$full_address .= ', ' . $this->address;
		}

		if ( $this->city ) {
			$full_address .= ', ' . $this->city;
		}

		if ( $this->state ) {
			$full_address .= ", {$this->state}";
		}

		if ( $this->postcode ) {
			$full_address .= ' ' . $this->postcode;
		}

		if ( isset( $countries[ $this->country ] ) && $settings['display_location_country'] ) {
			$full_address .= ' (' . $countries[ $this->country ] . ')';
		}

		return $full_address;
	}

	/**
	 * Determine whether this location has an associated map
	 * @return bool
	 */
	public function has_map() {
		$map_options = $this->gmaps_options;

		return 'gmaps' === $this->location_type && isset( $map_options['marker-lat'] ) && isset( $map_options['marker-long'] );
	}
}
