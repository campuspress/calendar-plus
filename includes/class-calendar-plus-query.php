<?php

class Calendar_Plus_Query {

	public $query_vars;

	public function __construct() {

		$this->query_vars = array( 'from', 'to', 'location', 'calendarp_searchw', 'order' );

		if ( ! is_admin() ) {
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		}
	}


	/**
	 * @param WP_Query $query
	 */
	public function pre_get_posts( $query ) {
		if( ! empty( $query->get( 'post__in' ) ) || $query->is_single() ) {
			return;
		}
		if( $query->get( 'post_type' ) != 'calendar_event' && ! $query->is_tax( get_object_taxonomies( 'calendar_event' ) ) ) {
			return;
		}
		
		if( ! wp_is_block_theme() ) {
			$legacy_integration = calendarp_get_setting( 'legacy_theme_integration' );
			if( ! empty( $legacy_integration ) ) {
				$events_page_id = absint( calendarp_get_setting( 'events_page_id' ) );
				if ( isset( $query->queried_object_id ) && $query->queried_object_id === $events_page_id ) {
					//TODO we don't get here
					$query->set( 'post_type', 'calendar_event' );
					$query->set( 'page', '' );
					$query->set( 'pagename', '' );
	
					$query->is_post_type_archive = true;
					$query->is_singular = false;
					$query->is_page = false;
					$query->is_archive = true;
				}
			}
		}

		if ( ! $query->get( 'order' ) ) {
			$query->set( 'order', 'ASC' );
		}		

		$this->parse_query( $query );

		// Meta query
		$meta_query = is_array( $query->get( 'meta_query' ) ) ? $query->get( 'meta_query' ) : array();

		if ( isset( $_GET['location'] ) && absint( $_GET['location'] ) && $location = calendarp_get_location( absint( $_GET['location'] ) ) ) {
			$meta_query[] = array(
				'key'     => '_location_id',
				'value'   => $location->ID,
				'compare' => '=',
			);
		}

		$query->set( 'meta_query', $meta_query );

		add_filter( 'posts_clauses', array( $this, 'clauses' ), 10, 2 );
		add_filter( 'posts_fields', array( $this, 'fields' ), 10, 2 );

		do_action( 'calendarp_query', $query, $this );
	}

	public function fields( $fields, $query ) {
		remove_filter( 'posts_fields', array( $this, 'fields' ) );

		$fields .= ', cal.from_date, cal.until_date, cal.from_time, cal.until_time';
		return $fields;
	}

	public function clauses( $clauses, $query ) {
		global $wpdb;

		remove_filter( 'posts_clauses', array( $this, 'clauses' ) );

		$clauses['join'] .= " RIGHT JOIN $wpdb->calendarp_calendar cal ON $wpdb->posts.ID = cal.event_id ";
		$clauses['groupby'] = ' cal.event_id';

		// Generate all months between the dates
		$from = explode( '-', $query->get( 'from' ) );
		$from_is_date = is_array( $from ) && count( $from ) === 3 && checkdate( $from[1], $from[2], $from[0] );

		// Makes ordering based on event date
		if (
			( $order = $query->get('order') ) &&
			'desc' === strtolower( $order )
		) {
			$clauses['orderby'] = 'cal.from_date DESC';

			// For DESC order if no to date is set, set to today.
			if ( ! $query->get( 'to' ) ) {
				$to = date( 'Y-m-d', strtotime("yesterday") );
				$to = explode( '-', $to );
			}
		} else {
			$clauses['orderby'] = 'cal.from_date ASC';
		}
		
		if ( $query->get( 'to' ) ) {
			if ( 'today' === $query->get( 'to' ) ) {
				$to = date( 'Y-m-d', strtotime("yesterday") );
				$to = explode( '-', $to );
			} else {
				$to = explode( '-', $query->get( 'to' ) );
			}
		}
		$to_is_date = !empty( $to ) && is_array( $to ) && count( $to ) === 3 && checkdate( $to[1], $to[2], $to[0] );

		$where_not = array();
		if ( $from_is_date ) {
			$where_not[] = $wpdb->prepare( 'cal.until_date < %s', implode( '-', $from ) );
		}

		if ( $to_is_date ) {
			$where_not[] = $wpdb->prepare( 'cal.from_date > %s', implode( '-', $to ) );
		}

		if ( ! $from_is_date && ! $to_is_date ) {
			$date = date( 'Y-m-d', strtotime("yesterday") );
			$where_not[] = $wpdb->prepare( 'cal.until_date < %s', $date );
		}

		$where_not = implode( ' OR ', $where_not );
		$clauses['where'] .= " AND NOT ( $where_not )";

		return $clauses;
	}

	/*
	public function remove_clauses_query( $posts ) {
		if ( has_filter( 'posts_clauses', array( $this, 'clauses' ) ) ) {
			remove_filter( 'posts_clauses', array( $this, 'clauses' ) );
		}
		if ( has_filter( 'posts_fields', array( $this, 'fields' ) ) ) {
			remove_filter( 'posts_fields', array( $this, 'fields' ) );
		}

		return $posts;
	}
	*/

	public function parse_query( $query ) {
		foreach ( $this->query_vars as $key ) {
			if ( isset( $_REQUEST[ $key ] ) ) {
				$query->query_vars[ $key ] = $_REQUEST[ $key ];
            }
		}
	}

}
