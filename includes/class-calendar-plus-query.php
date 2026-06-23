<?php

class Calendar_Plus_Query {

	public $query_vars;

	public function __construct() {

		$this->query_vars = array( 'location' );

		// "to" and "from" query vars can lead to heavy queries, lets only allow them if total dates is below the limit
		$last_known_total_dates = (int) get_option( 'calendarp_last_known_total_dates', 0 );
		if ( $last_known_total_dates < $this->get_total_dates_limit() ) {
			$this->query_vars[] = 'from';
			$this->query_vars[] = 'to';
		}

		if ( ! is_admin() || wp_doing_ajax() ) {
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		}
	}


	/**
	 * @param WP_Query $query
	 */
	public function pre_get_posts( $query ) {
		if ( ! empty( $query->get( 'post__in' ) ) || $query->is_single() ) {
			return;
		}

		if ( $query->get( 'post_type' ) != 'calendar_event' && ! $query->is_tax( get_object_taxonomies( 'calendar_event' ) ) ) {
			return;
		}

		// If this is a taxonomy archive and the post type isn't explicitly calendar_event.
		if ( $query->is_tax( get_object_taxonomies( 'calendar_event' ) ) ) {
			// if taxonomy is assigned to multiple post types, we are not forcing query filters
			$assigned_to_multiple = calendarp_is_taxonomy_assigned_to_multiple( $query->get_queried_object() );

			if ( $assigned_to_multiple ) {
				return;
			}
		}

		if ( ! wp_is_block_theme() ) {
			$events_page_id = absint( calendarp_get_setting( 'events_page_id' ) );
			if ( isset( $query->queried_object_id ) && $query->queried_object_id === $events_page_id ) {
				$query->set( 'post_type', 'calendar_event' );
				$query->set( 'page', '' );
				$query->set( 'pagename', '' );

				$query->is_post_type_archive = true;
				$query->is_singular          = false;
				$query->is_page              = false;
				$query->is_archive           = true;
			}
		}

		if ( ! $query->get( 'order' ) ) {
			$query->set( 'order', 'ASC' );
		}

		$this->parse_query( $query );

		if ( ! empty( $query->get( 'cat' ) ) && $term = get_term( $query->get( 'cat' ), 'calendar_event_category' ) ) {
			// Redirect to taxonomy archive
			$vars        = array( 'from', 'to', 's', 'location', 'post_type', 'calendarp_searchw', 'order' );
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

		add_filter( 'posts_clauses', array( $this, 'clauses' ), 10, 2 );
		add_filter( 'posts_request', array( $this, 'maybe_strip_calc_found_rows' ), 10, 2 );
		add_filter( 'found_posts', array( $this, 'cap_found_posts' ), 10, 2 );

		do_action( 'calendarp_query', $query, $this );
	}

	public function clauses( $clauses, $query ) {
		global $wpdb;

		remove_filter( 'posts_clauses', array( $this, 'clauses' ) );

		$clauses['join']   .= " RIGHT JOIN $wpdb->calendarp_calendar cal ON $wpdb->posts.ID = cal.event_id ";
		$clauses['groupby'] = ' cal.event_id';

		// Cap LIMIT offset to prevent bots from deep-paginating past the total-dates threshold.
		if ( $clauses['limits'] ) {
			$max_offset     = $this->get_total_dates_limit();
			$posts_per_page = (int) $query->get( 'posts_per_page' );
			$offset         = ( (int) $query->get( 'paged' ) - 1 ) * $posts_per_page;

			if ( $offset > $max_offset ) {
				$clauses['limits'] = $wpdb->prepare( 'LIMIT %d, %d', $max_offset, $posts_per_page );
			}
		}

		// Generate all months between the dates
		$from         = explode( '-', $query->get( 'from' ) );
		$from_is_date = is_array( $from ) && count( $from ) === 3 && checkdate( $from[1], $from[2], $from[0] );

		// Makes ordering based on event date
		if (
			( $order = $query->get( 'order' ) ) &&
			'desc' === strtolower( $order )
		) {
			$clauses['orderby'] = 'cal.from_date DESC';

			// For DESC order if no to date is set, set to yesterday, so only past events are included.
			if ( ! $query->get( 'to' ) ) {
				$to = date( 'Y-m-d', strtotime( 'yesterday' ) );
				$to = explode( '-', $to );
			}
		} else {
			$clauses['orderby'] = 'cal.from_date ASC';
		}

		if ( $query->get( 'to' ) ) {
			if ( 'today' === $query->get( 'to' ) ) {
				$to = date( 'Y-m-d', strtotime( 'today' ) );
				$to = explode( '-', $to );
			} else {
				$to = explode( '-', $query->get( 'to' ) );
			}
		}
		$to_is_date = ! empty( $to ) && is_array( $to ) && count( $to ) === 3 && checkdate( $to[1], $to[2], $to[0] );

		$where_not = array();
		if ( $from_is_date ) {
			$where_not[] = $wpdb->prepare( 'cal.until_date < %s', implode( '-', $from ) );
		}

		if ( $to_is_date ) {
			$where_not[] = $wpdb->prepare( 'cal.from_date > %s', implode( '-', $to ) );
		}

		if ( ! $from_is_date && ! $to_is_date ) {
			$date        = date( 'Y-m-d', strtotime( 'today' ) );
			$where_not[] = $wpdb->prepare( 'cal.until_date < %s', $date );
		}

		$where_not         = implode( ' OR ', $where_not );
		$clauses['where'] .= " AND NOT ( $where_not )";

		return $clauses;
	}

	public function parse_query( $query ) {
		foreach ( $this->query_vars as $key ) {
			if ( isset( $_REQUEST[ $key ] ) ) {
				$query->query_vars[ $key ] = $_REQUEST[ $key ];
			}
		}
	}

	public function maybe_strip_calc_found_rows( $sql, $query ) {
		remove_filter( 'posts_request', array( $this, 'maybe_strip_calc_found_rows' ), 10 );

		if ( false === strpos( $sql, 'SQL_CALC_FOUND_ROWS' ) ) {
			return $sql;
		}

		$last_changed = wp_cache_get_last_changed( 'calendarp:events' );
		if ( false !== wp_cache_get_salted( $this->found_posts_cache_key( $query ), 'calendarp:events', $last_changed ) ) {
			add_filter( 'found_posts_query', array( $this, 'found_posts_query' ), 10, 2 );
			return str_replace( 'SQL_CALC_FOUND_ROWS ', '', $sql );
		}

		return $sql;
	}

	public function found_posts_query( $sql, $query ) {
		remove_filter( 'found_posts_query', array( $this, 'found_posts_query' ), 10 );

		$last_changed   = wp_cache_get_last_changed( 'calendarp:events' );
		$cached = wp_cache_get_salted( $this->found_posts_cache_key( $query ), 'calendarp:events', $last_changed );

		if ( false !== $cached ) {
			return 'SELECT ' . (int) $cached;
		}

		return $sql;
	}

	public function cap_found_posts( $found_posts, $query ) {
		remove_filter( 'found_posts', array( $this, 'cap_found_posts' ) );

		$last_changed   = wp_cache_get_last_changed( 'calendarp:events' );
		$cached = wp_cache_get_salted( $this->found_posts_cache_key( $query ), 'calendarp:events', $last_changed );
		if ( false !== $cached ) {
			$found_posts = $cached;
		} else {
			wp_cache_set_salted( $this->found_posts_cache_key( $query ), $found_posts, 'calendarp:events', $last_changed );
		}

		return min( (int) $found_posts, $this->get_total_dates_limit() );
	}

	private function found_posts_cache_key( $query ) {
		$vars = $query->query_vars;
		unset( $vars['paged'], $vars['offset'], $vars['no_found_rows'] );

		return 'calendarp_found_' . md5( serialize( $vars ) );
	}

	private function get_total_dates_limit() {
		return (int) apply_filters( 'calendarp_heavy_query_vars_total_dates_limit', 500 );
	}
}
