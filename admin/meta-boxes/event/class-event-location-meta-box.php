<?php

class Calendar_Plus_Event_Location_Metabox extends Calendar_Plus_Meta_Box {

	public function __construct() {
		$this->meta_box_slug = 'calendar-event-location';
		$this->meta_box_label = __( 'Event Location', 'calendar-plus' );
		$this->meta_box_context = 'side';
		$this->meta_box_priority = 'high';
		$this->post_type = 'calendar_event';

		add_action( 'wp_ajax_search_event_location', array( $this, 'search_location' ) );
		parent::__construct();
	}

	public function render( $post ) {
		$event = calendarp_get_event( $post );
		$meta_box_slug = $this->meta_box_slug;

		$location_id = $event->location_id;
		$_current_location = calendarp_get_location( $location_id );

		$current_location = new stdClass();
		if ( $_current_location ) {
			$query = new WP_Query(
				array(
					'post_type'				=> 'calendar_location',
					'supress_filters'		=> true,
					'ignore_sticky_posts'	=> true,
					'posts_per_page'		=> 1,
					'post__in'				=> array( $_current_location->ID )
				)
			);

			$results			= $this->get_javascript_location( $query );
			$current_location	= $results[0];
		}

		$current_location = json_encode( $current_location );

		include_once calendarp_get_plugin_dir() . 'admin/views/event-location-meta-box.php';
	}

	public function save_data( $event_id ) {
		if ( ! $event = calendarp_get_event( $event_id ) ) {
			return;
		}

		$input = $_POST['event_location'];

		$value = isset( $input['_location_id'] ) && calendarp_is_location( absint( $input['_location_id'] ) ) ?
			absint( $input['_location_id'] ) : '';

		update_post_meta( $event->ID, '_location_id', $value );
	}

	public function search_location() {

		$query = new WP_Query( array(
			's'						=> $_REQUEST['s'],
			'post_type'				=> 'calendar_location',
			'supress_filters'		=> true,
			'ignore_sticky_posts'	=> true,
			'orderby'				=> 'title',
			'order'					=> 'ASC',
		) );

		$results = $this->get_javascript_location( $query );

		wp_send_json( $results );
		die();
	}

	/**
	 * Sets the structure of a location query to work with it under JS
	 *
	 * @param WP_Query $query
	 *
	 * @return array
	 */
	private function get_javascript_location( $query ) {
		add_filter( 'excerpt_more', array( $this, 'set_excerpt_more_on_locations_search' ), 999 );
		$results = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$post = $query->next_post();

				$results[] = array(
					'id'        => $post->ID,
					'title'     => apply_filters( 'the_title', $post->post_title, $post->ID ),
					'slug'      => $post->post_name,
				);
			}
		}

		return $results;
	}

	public function set_excerpt_more_on_locations_search( $text ) {
		return '&hellip;';
	}
}


