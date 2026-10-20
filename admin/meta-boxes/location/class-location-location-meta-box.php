<?php
/**
 * Calendar Plus: admin/meta-boxes/location/class-location-location-meta-box.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Location_Location_Metabox. */
class Calendar_Plus_Location_Location_Metabox extends Calendar_Plus_Meta_Box {

	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {
		$this->meta_box_slug     = 'calendar-location-location';
		$this->meta_box_label    = __( 'Select a Location', 'calendar-plus' );
		$this->meta_box_context  = 'normal';
		$this->meta_box_priority = 'high';
		$this->post_type         = 'calendar_location';

		parent::__construct();
	}

	/**
	 * Handle render.
	 *
	 * @param mixed $post Post.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function render( $post ) {
		$location      = calendarp_get_location( $post );
		$meta_box_slug = $this->meta_box_slug;

		$map_options = $location->gmaps_options;
		include_once calendarp_get_plugin_dir() . 'admin/views/location-location-meta-box.php';
	}

	/**
	 * Save data.
	 *
	 * @param int $location_id Location id.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function save_data( $location_id ) {
		if ( ! $this->can_save( $location_id ) ) {
			return;
		}

		$location = calendarp_get_location( $location_id );
		if ( ! $location ) {
			return;
		}

		$type = 'gmaps' === calendarp_form_input( 'location-type' ) ? 'gmaps' : 'standard';

		$input = calendarp_form_input( $type, array() );
		if ( array_filter( $input, 'is_array' ) ) {
			return;
		}

		update_post_meta( $location_id, '_location_type', $type );
		update_post_meta( $location_id, '_address', sanitize_text_field( ( $input['location-address'] ?? '' ) ) );
		update_post_meta( $location_id, '_city', sanitize_text_field( ( $input['location-city'] ?? '' ) ) );
		update_post_meta( $location_id, '_state', sanitize_text_field( ( $input['location-state'] ?? '' ) ) );
		update_post_meta( $location_id, '_postcode', sanitize_text_field( ( $input['location-postcode'] ?? '' ) ) );

		if ( array_key_exists( ( $input['location-country'] ?? '' ), calendarp_get_countries() ) ) {
			update_post_meta( $location_id, '_country', ( $input['location-country'] ?? '' ) );
		} else {
			update_post_meta( $location_id, '_country', '' );
		}

		if ( 'gmaps' === $type ) {
			$map_options = array(
				'lat'         => max( -90, min( 90, (float) ( $input['location-gmaps-lat'] ?? 0 ) ) ),
				'long'        => max( -180, min( 180, (float) ( $input['location-gmaps-long'] ?? 0 ) ) ),
				'marker-lat'  => max( -90, min( 90, (float) ( $input['location-gmaps-marker-lat'] ?? 0 ) ) ),
				'marker-long' => max( -180, min( 180, (float) ( $input['location-gmaps-marker-long'] ?? 0 ) ) ),
				'zoom'        => max( 0, min( 21, (float) ( $input['location-gmaps-zoom'] ?? 0 ) ) ),
			);

			update_post_meta( $location_id, '_gmaps_options', $map_options );
		}
	}
}
