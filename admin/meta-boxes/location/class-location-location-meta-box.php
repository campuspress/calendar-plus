<?php

class Calendar_Plus_Location_Location_Metabox extends Calendar_Plus_Meta_Box {

	public function __construct() {
		$this->meta_box_slug = 'calendar-location-location';
		$this->meta_box_label = __( 'Select a Location', 'calendar-plus' );
		$this->meta_box_context = 'normal';
		$this->meta_box_priority = 'high';
		$this->post_type = 'calendar_location';

		parent::__construct();
	}

	public function render( $post ) {
		$location = calendarp_get_location( $post );
		$meta_box_slug = $this->meta_box_slug;

		$map_options = $location->gmaps_options;
		include_once( calendarp_get_plugin_dir() . 'admin/views/location-location-meta-box.php' );
	}

	public function save_data( $location_id ) {
		$location = calendarp_get_location( $location_id );
		if ( ! $location ) {
			return;
		}

		$type = in_array( $_POST['location-type'], array( 'standard', 'gmaps' ) ) ? $_POST['location-type'] : 'standard';
		update_post_meta( $location_id, '_location_type', $type );

		$input = $_POST[ $type ];

		update_post_meta( $location_id, '_address', sanitize_text_field( $input['location-address'] ) );
		update_post_meta( $location_id, '_city', sanitize_text_field( $input['location-city'] ) );
		update_post_meta( $location_id, '_state', sanitize_text_field( $input['location-state'] ) );
		update_post_meta( $location_id, '_postcode', sanitize_text_field( $input['location-postcode'] ) );

		if ( array_key_exists( $input['location-country'], calendarp_get_countries() ) ) {
			update_post_meta( $location_id, '_country', $input['location-country'] );
		} else {
			update_post_meta( $location_id, '_country', '' );
		}

		if ( 'gmaps' === $type ) {
			$map_options = array(
				'lat'         => $input['location-gmaps-lat'],
				'long'        => $input['location-gmaps-long'],
				'marker-lat'  => $input['location-gmaps-marker-lat'],
				'marker-long' => $input['location-gmaps-marker-long'],
				'zoom'        => $input['location-gmaps-zoom'],
			);

			update_post_meta( $location_id, '_gmaps_options', $map_options );
		}
	}

}
