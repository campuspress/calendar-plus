<?php
/**
 * Calendar Plus: includes/class-calendar-plus-query.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Query. */
class Calendar_Plus_Query {

	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	public $query_vars;

	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {

		$this->query_vars = array( 'location' );

		// "to" and "from" query vars can lead to heavy queries, lets only allow them if total dates is below the limit.
		$last_known_total_dates = calendarp_get_last_known_total_dates();
		if ( $last_known_total_dates < calendarp_get_total_dates_limit() ) {
			$this->query_vars[] = 'from';
			$this->query_vars[] = 'to';
		}

		if ( ! is_admin() || wp_doing_ajax() ) {
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		}
	}


	/**
	 * Handle pre get posts.
	 *
	 * @param WP_Query $query Query.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function pre_get_posts( $query ) {
		if ( $query->get( 'calendarp_native_posts' ) || ! empty( $query->get( 'post__in' ) ) || $query->is_single() ) {
			return;
		}

		// Only filter queries for the calendar_event post type or its dedicated taxonomies.
		if ( $query->get( 'post_type' ) !== 'calendar_event' ) {
			if ( ! $query->is_tax( get_object_taxonomies( 'calendar_event' ) ) ) {
				return;
			} else {
				// if taxonomy is assigned to multiple post types, we are not forcing query filters.
				$assigned_to_multiple = calendarp_is_taxonomy_assigned_to_multiple( $query->get_queried_object() );

				if ( $assigned_to_multiple ) {
					return;
				}
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

		$term = ! empty( $query->get( 'cat' ) ) ? get_term( $query->get( 'cat' ), 'calendar_event_category' ) : false;
		if ( $term ) {
			// Redirect to taxonomy archive.
			$vars        = array( 'from', 'to', 's', 'location', 'post_type', 'calendarp_searchw', 'order' );
			$redirect_to = get_term_link( $term->term_id, 'calendar_event_category' );
			foreach ( $vars as $var ) {
				$value = get_query_var( $var );
				if ( ! empty( $value ) ) {
					$redirect_to = add_query_arg( $var, $value, $redirect_to );
				}
			}

			wp_safe_redirect( esc_url_raw( $redirect_to ) );
			die();
		}

		// Meta query.
		$meta_query = is_array( $query->get( 'meta_query' ) ) ? $query->get( 'meta_query' ) : array();

		$location_id = absint( calendarp_form_input( 'location', '', 'get' ) );
		$location    = $location_id ? calendarp_get_location( $location_id ) : false;
		if ( $location ) {
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

	/**
	 * Handle clauses.
	 *
	 * @param mixed $clauses Clauses.
	 * @param mixed $query Query.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function clauses( $clauses, $query ) {
		global $wpdb;

		remove_filter( 'posts_clauses', array( $this, 'clauses' ) );

		$clauses['join']   .= " RIGHT JOIN $wpdb->calendarp_calendar cal ON $wpdb->posts.ID = cal.event_id ";
		$clauses['groupby'] = ' cal.event_id';

		// Cap LIMIT offset to prevent bots from deep-paginating past the total-dates threshold.
		if ( $clauses['limits'] ) {
			$max_offset     = calendarp_get_total_dates_limit();
			$posts_per_page = (int) $query->get( 'posts_per_page' );
			$offset         = ( (int) $query->get( 'paged' ) - 1 ) * $posts_per_page;

			if ( $offset > $max_offset ) {
				$clauses['limits'] = $wpdb->prepare( 'LIMIT %d, %d', $max_offset, $posts_per_page );
			}
		}

		$from         = explode( '-', $query->get( 'from' ) );
		$from_is_date = is_array( $from ) && count( $from ) === 3 && checkdate( $from[1], $from[2], $from[0] );

		$first_date_generated = calendarp_get_first_date_generated();

		// Don't check older 'from' than first date we have.
		if ( $from_is_date && false !== $first_date_generated && strtotime( $first_date_generated ) > strtotime( implode( '-', $from ) ) ) {
			$from = explode( '-', $first_date_generated );
		}

		// Makes ordering based on event date.
		$order = $query->get( 'order' );
		if (
			$order &&
			'desc' === strtolower( $order )
		) {
			$clauses['orderby'] = 'cal.from_date DESC';

			// For DESC order if no to date is set, set to yesterday, so only past events are included.
			if ( ! $query->get( 'to' ) ) {
				$to = gmdate( 'Y-m-d', strtotime( 'yesterday' ) );
				$to = explode( '-', $to );
			}
		} else {
			$clauses['orderby'] = 'cal.from_date ASC';
		}

		if ( $query->get( 'to' ) ) {
			if ( 'today' === $query->get( 'to' ) ) {
				$to = gmdate( 'Y-m-d', strtotime( 'today' ) );
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
			$date        = gmdate( 'Y-m-d', strtotime( 'today' ) );
			$where_not[] = $wpdb->prepare( 'cal.until_date < %s', $date );
		}

		$where_not         = implode( ' OR ', $where_not );
		$clauses['where'] .= " AND NOT ( $where_not )";

		return $clauses;
	}

	/**
	 * Parse query.
	 *
	 * @param mixed $query Query.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function parse_query( $query ) {
		foreach ( $this->query_vars as $key ) {
			if ( null !== calendarp_form_input( $key, null, 'request' ) ) {
				$query->query_vars[ $key ] = calendarp_form_input( $key, '', 'request' );
			}
		}
	}

	/**
	 * Conditionally strip calc found rows.
	 *
	 * @param mixed $sql Sql.
	 * @param mixed $query Query.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function maybe_strip_calc_found_rows( $sql, $query ) {
		remove_filter( 'posts_request', array( $this, 'maybe_strip_calc_found_rows' ), 10 );

		if ( false === strpos( $sql, 'SQL_CALC_FOUND_ROWS' ) ) {
			return $sql;
		}

		$last_changed = wp_cache_get_last_changed( 'calendarp:events' );
		if ( false !== calendarp_cache_get_salted( $this->found_posts_cache_key( $query ), 'calendarp:events', $last_changed ) ) {
			add_filter( 'found_posts_query', array( $this, 'found_posts_query' ), 10, 2 );
			return str_replace( 'SQL_CALC_FOUND_ROWS ', '', $sql );
		}

		return $sql;
	}

	/**
	 * Handle found posts query.
	 *
	 * @param mixed $sql Sql.
	 * @param mixed $query Query.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function found_posts_query( $sql, $query ) {
		remove_filter( 'found_posts_query', array( $this, 'found_posts_query' ), 10 );

		$last_changed = wp_cache_get_last_changed( 'calendarp:events' );
		$cached       = calendarp_cache_get_salted( $this->found_posts_cache_key( $query ), 'calendarp:events', $last_changed );

		if ( false !== $cached ) {
			return 'SELECT ' . (int) $cached;
		}

		return $sql;
	}

	/**
	 * Handle cap found posts.
	 *
	 * @param mixed $found_posts Found posts.
	 * @param mixed $query Query.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function cap_found_posts( $found_posts, $query ) {
		remove_filter( 'found_posts', array( $this, 'cap_found_posts' ) );

		$last_changed = wp_cache_get_last_changed( 'calendarp:events' );
		$cached       = calendarp_cache_get_salted( $this->found_posts_cache_key( $query ), 'calendarp:events', $last_changed );
		if ( false !== $cached ) {
			$found_posts = $cached;
		} else {
			calendarp_cache_set_salted( $this->found_posts_cache_key( $query ), $found_posts, 'calendarp:events', $last_changed );
		}

		return min( (int) $found_posts, calendarp_get_total_dates_limit() );
	}

	/**
	 * Handle found posts cache key.
	 *
	 * @param mixed $query Query.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function found_posts_cache_key( $query ) {
		$vars = $query->query_vars;
		unset( $vars['paged'], $vars['offset'], $vars['no_found_rows'] );

		return 'calendarp_found_' . md5( wp_json_encode( $vars ) );
	}
}
