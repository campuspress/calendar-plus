<?php

/**
 * Class Calendar_Plus_iCal_Sync
 *
 * Sync iCal files for Calendar+
 */
class Calendar_Plus_iCal_Sync {

	/**
	 * @var array
	 */
	protected $events;

	/**
	 * List of event locations
	 * @var array
	 */
	protected $locations;

	/**
	 * User ID of author to use when creating or updating posts
	 * @var string
	 */
	protected $event_author;

	/**
	 * Term ID of category to place new posts into
	 * @var string
	 */
	protected $event_category;

	/**
	 * Default status to use when creating posts if not specified in the feed
	 * @var string
	 */
	protected $default_status;

	/**
	 * Time of previous synchronization to check if post was modified
	 * @var int
	 */
	protected $prev_sync_time;

	/**
	 * Calendar_Plus_iCal_Sync constructor.
	 *
	 * @param array $events List of parsed events
	 * @param array $args
	 */
	public function __construct( $events, $args = [] ) {
		$this->events = $events;
		$this->locations = calendarp_get_locations();

		$args = wp_parse_args( $args, [
			'author'         => 0,
			'category'       => 0,
			'status'         => 'publish',
			'prev_sync_time' => time(),
		] );

		$this->event_author = $args['author'] ? $args['author'] : get_current_user_id();
		$this->event_category = $args['category'];
		$this->default_status = $args['status'];
		$this->prev_sync_time = $args['prev_sync_time'];
	}

	/**
	 * Sync Calendar+ events
	 *
	 * @return array List of processed post IDs
	 */
	public function sync() {
		$synced = array();

		foreach ( $this->events as $event_data ) {
			$post_id = $this->sync_event( $event_data );

			if ( $post_id ) {
				$synced[] = $post_id;
			}
		}

		return $synced;
	}

	/**
	 * Synchronise an event
	 *
	 * @param array $event_data
	 *
	 * @return int|bool
	 */
	protected function sync_event( $event_data ) {

		$event_data = wp_parse_args( $event_data, [
			'uid'          => '',
			'post_title'   => '',
			'post_content' => '',
			'post_status'  => $this->default_status,
			'location'     => '',
			'last_updated' => '',
			'categories'   => [],
		] );
		$event_data['hash'] = md5( serialize( $event_data ) );

		if ( 'publish' === $event_data['post_status'] ) {
			$event_data['post_status'] = $this->default_status;
		}

		$event = calendarp_get_event_by_uid( $event_data['uid'] );

		$post_args = array(
			'post_type'    => 'calendar_event',
			'post_status'  => $event_data['post_status'],
			'post_title'   => $event_data['post_title'],
			'post_content' => $event_data['post_content'],
		);

		if ( $event ) {
			if ( 'trash' === $event_data['post_status'] ) {
				// The feed event has been deleted, delete locally
				wp_delete_post( $event->ID, true );
				return $event->ID;
			}

			$event_post  = $event->get_post();
			// Skip if there are no changes
			if (
				! empty( $event_data['last_updated'] ) &&
				$event_data['last_updated'] === $event->get_meta( 'ical_last_updated' )
			) {
				return false;
			}
			// Skip if there are no changes
			if ( $event_data['hash'] === $event->get_meta( 'ical_hash' ) ) {
				return false;
			}

			if ( $event_post->post_modified !== $event_post->post_date ) {
				return false;
			}

			$post_args['ID'] = $event->ID;

			if ( $this->event_author ) {
				$post_args['post_author'] = $this->event_author;
			}

			wp_update_post( $post_args );
			$post_id = $event->ID;

		} else {
			if ( 'trash' === $event_data['post_status'] ) {
				return false;
			}

			$post_id = wp_insert_post( $post_args );
		}

		update_post_meta( $post_id, '_event_uid', $event_data['uid'] );

		if ( $event_location = $this->get_location( $event_data['location'], $event_data['post_status'] ) ) {
			update_post_meta( $post_id, '_location_id', $event_location->ID );
		}

		if ( $this->event_category ) {
			$event_data['categories'][] = $this->event_category;
		}

		if ( $event_data['categories'] ) {
			wp_set_object_terms( $post_id, $event_data['categories'], 'calendar_event_category', true );
		}

		$rules = array(
			'from_date'  => date( 'Y-m-d', $event_data['from'] ),
			'until_date' => date( 'Y-m-d', $event_data['to'] ),
			'from_time'  => date( 'H:i', $event_data['from'] ),
			'until_time' => date( 'H:i', $event_data['to'] ),
		);

		$type = $rules['from_date'] === $rules['until_date'] ? '' : 'datespan';
		calendarp_update_event_type( $post_id, $type );

		$rules['rule_type'] = 'datespan' === $type ? 'datespan' : 'standard';

		calendarp_generate_event_rules_and_dates( $post_id, [ $rules ] );

		if ( $event_data['last_updated'] ) {
			update_post_meta( $post_id, '_ical_last_updated', $event_data['last_updated'] );
		}

		update_post_meta( $post_id, '_ical_hash', $event_data['hash'] );

		return $post_id;
	}

	/**
	 * Retrieve the location post for a given location name, using an existing location if available or creating a new one if necessary
	 *
	 * @param string $location_name Name of location to retrieve
	 * @param string Optional status to use if creating a location post. Defaults to default status
	 *
	 * @return Calendar_Plus_Location|null
	 */
	protected function get_location( $location_name, $post_status = '' ) {
		if ( ! $location_name ) {
			return null;
		}

		/** @var Calendar_Plus_Location $location */
		foreach ( $this->locations as $location ) {
			if ( $location->get_post()->post_title === $location_name ) {
				return $location;
			}
		}

		$location_id = wp_insert_post( [
			'post_title'  => $location_name,
			'post_status' => $post_status ? $post_status : $this->default_status,
			'post_type'   => 'calendar_location',
			'post_author' => $this->event_author,
		] );

		if ( is_wp_error( $location_id ) ) {
			return null;
		}

		$location = calendarp_get_location( $location_id );
		$this->locations[] = $location;
		return $location;
	}
}
