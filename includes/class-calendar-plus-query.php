<?php

class Calendar_Plus_Query {

	public $query_vars;

	public function __construct() {

		$this->query_vars = array( 'from', 'to', 'location', 'calendarp_searchw' );

		if ( ! is_admin() ) {
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			//add_action( 'wp', array( $this, 'remove_clauses_query' ) );
		}
	}


	/**
	 * @param WP_Query $query
	 */
	public function pre_get_posts( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}

		$events_page_id = absint( calendarp_get_setting( 'events_page_id' ) );
		if ( isset( $query->queried_object_id ) && $query->queried_object_id === $events_page_id ) {
			$query->set( 'post_type', 'calendar_event' );
			$query->set( 'page', '' );
			$query->set( 'pagename', '' );

			$query->is_post_type_archive = true;
			$query->is_singular = false;
			$query->is_page = false;
			$query->is_archive = true;

		}

		if ( ! $query->is_post_type_archive( 'calendar_event' ) && ! $query->is_tax( get_object_taxonomies( 'calendar_event' ) ) ) {
			return;
		}

		$this->parse_query( $query );

		if ( ! empty( $query->get( 'cat' ) ) && $term = get_term( $query->get( 'cat' ), 'calendar_event_category' ) ) {
			// Redirect to taxonomy archive
			$vars = array( 'from', 'to', 's', 'location', 'post_type', 'calendarp_searchw' );
			$redirect_to = get_term_link( $term->term_id, 'calendar_event_category' );
			foreach ( $vars as $var ) {
				$value = get_query_var( $var );
				if ( ! empty( $value ) ) {
					$redirect_to = add_query_arg( $var, $value, $redirect_to );
                }
			}

			wp_redirect( esc_url_raw( $redirect_to ) );
			die();
		}

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

		add_filter( 'posts_clauses', array( $this, 'clauses' ) );
		add_filter( 'posts_fields', array( $this, 'fields' ) );

		do_action( 'calendarp_query', $query, $this );

		remove_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		remove_filter( 'posts_clauses', array( $this, 'clauses' ) );
		remove_filter( 'posts_fields', array( $this, 'fields' ) );
	}

	public function fields( $fields ) {
		$fields .= ', cal.from_date, cal.until_date, cal.from_time, cal.until_time';
		return $fields;
	}

	public function clauses( $clauses ) {
		global $wpdb, $wp_query;
		$clauses['join'] .= " RIGHT JOIN $wpdb->calendarp_calendar cal ON $wpdb->posts.ID = cal.event_id ";
		$clauses['groupby'] = ' cal.event_id';

		if ( is_search() || is_post_type_archive( 'calendar_event' ) || is_tax( get_object_taxonomies( 'calendar_event' ) ) ) {
			// Generate all months between the dates
			$from = explode( '-', $wp_query->get( 'from' ) );
			$from_is_date = is_array( $from ) && count( $from ) === 3 && checkdate( $from[1], $from[2], $from[0] );
			$to = explode( '-', $wp_query->get( 'to' ) );
			$to_is_date = is_array( $to ) && count( $to ) === 3 && checkdate( $to[1], $to[2], $to[0] );

			$where_not = array();
			if ( $from_is_date ) {
				$where_not[] = $wpdb->prepare( 'cal.until_date < %s', implode( '-', $from ) );
			}

			if ( $to_is_date ) {
				$where_not[] = $wpdb->prepare( 'cal.from_date > %s', implode( '-', $to ) );
			}

			if ( ! $from_is_date && ! $to_is_date ) {
				$date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$where_not[] = $wpdb->prepare( 'cal.until_date < %s', $date );
			}

			$where_not = implode( ' OR ', $where_not );
			$clauses['where'] .= " AND NOT ( $where_not )";
		}

		$clauses['orderby'] = 'cal.from_date ASC';
		return $clauses;
	}

	public function remove_clauses_query() {
		remove_filter( 'posts_clauses', array( $this, 'clauses' ) );
	}

	public function parse_query( $query ) {
		foreach ( $this->query_vars as $key ) {
			if ( isset( $_REQUEST[ $key ] ) ) {
				$query->query_vars[ $key ] = $_REQUEST[ $key ];
            }
		}
	}

}
