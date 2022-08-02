<?php

/**
 * Get a single event
 *
 * @param int|Calendar_Plus_Event|WP_Post $event
 *
 * @return bool|Calendar_Plus_Event
 */
function calendarp_get_event( $event ) {
	return Calendar_Plus_Event::get_instance( $event );
}

/**
 * Get event statuses
 *
 * @since 0.1
 *
 * @return array
 */
function calendarp_get_event_statuses() {
	return apply_filters( 'calendarp_order_statuses', array(
		'cp-open'    => _x( 'Open', 'Order status', 'calendar-plus' ),
		'cp-close'   => _x( 'Closed', 'Order status', 'calendar-plus' ),
		'cp-expire'  => _x( 'Expired', 'Order status', 'calendar-plus' ),
		'cp-archive' => _x( 'Archived', 'Order status', 'calendar-plus' ),
	) );
}

/**
 * Delete generated calendar for an event
 *
 * @param int|Calendar_Plus_Event|WP_Post $event_id
 *
 * @return bool False if the event does not exist
 */
function calendarp_delete_event_dates( $event_id ) {
	$event = calendarp_get_event( $event_id );
	if ( ! $event ) {
		return false;
	}

	$event->delete_dates();

	return true;
}

/**
 * Format the rules, update them and delete and generate the dates for an event
 *
 * @param  Integer $event_id Event ID
 * @param  array   $rules    Unformatted rules
 */
function calendarp_generate_event_rules_and_dates( $event_id, $rules ) {
	$event = calendarp_get_event( $event_id );
	if ( ! $event ) {
		return;
	}

	$formatted = $event->format_rules( $rules );

	// When rules are updated, dates are regenerated
	$event->update_rules( $formatted );
}

/**
 * Return event IDs list for a month, even if they are not
 * in the calendar
 *
 * This function calculates event IDs based on min_max_dates table
 *
 * @param $month
 *
 * @return array
 */
function calendarp_get_event_ids_for_month( $month, $year ) {
	global $wpdb;

	$month = zeroise( absint( $month ), 2 );
	$year = zeroise( absint( $year ), 4 );
	$from_date = "$year-$month-01";
	$last_day_of_month = date( 't', strtotime( $from_date ) );
	$to_date = "$year-$month-$last_day_of_month";

	$results = $wpdb->get_col( $wpdb->prepare( "SELECT event_id FROM $wpdb->calendarp_min_max_dates WHERE NOT ( min_date > %s OR max_date < %s )", $to_date, $from_date ) );
	if ( ! $results ) {
		return array();
	}

	return array_unique( array_map( 'absint', $results ) );
}

/**
 * Get a set of events
 *
 * @param array $args See get_posts()
 *
 * @return array
 */
function calendarp_get_events( $args = array() ) {

	$args = array_merge( array( 'post_type' => 'calendar_event' ), $args );

	$events = get_posts( $args );

	$events = array_map( 'calendarp_get_event', $events );

	return $events;
}

/**
 * Get a description of the dates for an event in a more human format
 *
 * @param int    $event_id   Event ID
 * @param string $format     The format returned.
 *                           If format == 'array':
 *                           array( 'date' => Date/s formatted to human read, 'time' => Time formatted )
 *
 * @return string|array Explanation of the dates
 */
function calendarp_get_human_read_dates( $event_id, $format = 'string' ) {
	$event = calendarp_get_event( $event_id );
	if ( ! $event ) {
		return '';
	}

	$calendar = $event->get_dates_list();

	$format_string = '';
	$format_array = array( 'date' => '', 'time' => '', 'recurrence' => '' );

	if ( empty( $calendar ) ) {
		$format_string = __( 'No dates for this event', 'calendar-plus' );
		$format_array['date'] = __( 'No dates for this event', 'calendar-plus' );
	} elseif ( 'datespan' === $event->get_event_type() ) {
		if ( $event->is_all_day_event() ) {
			$from_date = calendarp_get_formatted_date( $calendar[0]['from_date'] );
			$until_date = calendarp_get_formatted_date( $calendar[0]['until_date'] );
			$format_string = sprintf( _x( 'From %1$s to %2$s', 'Human read date for an all day datespan event', 'calendar-plus' ), $from_date, $until_date );
			$format_array['date'] = sprintf( _x( 'From %1$s to %2$s', 'Human read date for an all day datespan event', 'calendar-plus' ), $from_date, $until_date );
		} else {
			$from_date = calendarp_get_formatted_date( $calendar[0]['from_date'] );
			$until_date = calendarp_get_formatted_date( $calendar[0]['until_date'] );
			$from_time = calendarp_get_formatted_time( $calendar[0]['from_time'] );
			$until_time = calendarp_get_formatted_time( $calendar[0]['until_time'] );
			$format_string = sprintf( _x( 'From %1$s to %2$s', 'Human read date for an all day datespan event', 'calendar-plus' ), $from_date . ' ' . $from_time, $until_date . ' ' . $until_time );
			$format_array['date'] = sprintf( _x( 'From %1$s to %2$s', 'Human read date for an all day datespan event', 'calendar-plus' ), $from_date, $until_date );
		}
	} elseif ( count( $calendar ) === 1 ) {
		$date = calendarp_get_formatted_date( $calendar[0]['from_date'] );

		$format_string = sprintf( _x( 'On %s', 'Human read date for an event with only one date', 'calendar-plus' ), $date );
		$format_array['date'] = $date;
		if ( ! $event->is_all_day_event() ) {
			// Not all day event, let's show the time
			$time = calendarp_get_formatted_time( $calendar[0]['from_time'] );
			$time_to = calendarp_get_formatted_time( $calendar[0]['until_time'] );
			$format_string .= ' ' . sprintf( _x( 'at %s', 'Human read time for an event with only one date', 'calendar-plus' ), $time );
			$format_array['time'] = $time . ' - ' . $time_to;
		}
	} elseif ( count( $calendar ) === 2 ) {
		$date_1 = calendarp_get_formatted_date( $calendar[0]['from_date'] );
		$date_2 = calendarp_get_formatted_date( $calendar[1]['from_date'] );

		$format_string = sprintf( _x( 'On %1$s and %2$s', 'Human read date for an event with two dates', 'calendar-plus' ), $date_1, $date_2 );
		$format_array['date'] = sprintf( _x( '%1$s and %2$s', 'Human read date for an event with two dates', 'calendar-plus' ), $date_1, $date_2 );
		if ( ! $event->is_all_day_event() && $calendar[0]['from_time'] === $calendar[1]['from_time'] ) {
			// Not all day event and both dates match, let's show the time
			$time = calendarp_get_formatted_time( $calendar[0]['from_time'] );
			$format_string .= ' ' . sprintf( _x( 'at %s', 'Human read time for an event with two dates', 'calendar-plus' ), $time );
			$format_array['time'] = $time;
		}
	} elseif ( count( $calendar ) > 2 ) {

		$from_date = calendarp_get_formatted_date( $calendar[0]['from_date'] );
		end( $calendar );
		$to_date = calendarp_get_formatted_date( $calendar[ key( $calendar ) ]['from_date'] );
		reset( $calendar );

		$format_string = sprintf( _x( 'From %1$s to %2$s', 'Human read date for an event with more than 2 dates', 'calendar-plus' ), $from_date, $to_date );
		$format_array['date'] = sprintf( _x( '%1$s to %2$s', 'Human read date for an event with more than 2 dates', 'calendar-plus' ), $from_date, $to_date );

		if ( 'recurrent' === $event->get_event_type() && ! $event->has_custom_dates() ) {
			// Every?
			$rules = $event->get_rules();

			if ( isset( $rules['every'] ) ) {
				$every = $rules['every'][0]['every'];
				$format_string .= ', ';
				switch ( $rules['every'][0]['what'] ) {
					case 'day':
						$format_array['recurrence'] = sprintf(
							__( 'Every %s', 'calendar-plus' ),
							sprintf( _n( 'day', '%d days', $every, 'calendar-plus' ), $every )
						);

						$format_string .= $format_array['recurrence'];
						break;

					case 'week':
						$format_array['recurrence'] = sprintf(
							__( 'Every %s', 'calendar-plus' ),
							sprintf( _n( 'week', '%d weeks', $every, 'calendar-plus' ), $every )
						);

						$format_string .= $format_array['recurrence'];
						break;

					case 'month':
						$format_array['recurrence'] = sprintf(
							__( 'Every %s', 'calendar-plus' ),
							sprintf( _n( 'month', '%d months', $every, 'calendar-plus' ), $every )
						);

						$format_string .= $format_array['recurrence'];
						break;

					case 'year':
						$format_array['recurrence'] = sprintf(
							__( 'Every %s', 'calendar-plus' ),
							sprintf( _n( 'year', '%d years', $every, 'calendar-plus' ), $every )
						);

						$format_string .= $format_array['recurrence'];
						break;
				}
			}
		}

		$times_list = wp_list_pluck( $calendar, 'from_time' );
		$times_unique = array_unique( $times_list );

		if ( ! $event->is_all_day_event() && count( $times_unique ) === 1 ) {
			// Same time for all events, let's show the time
			$format_string .= ' ' . sprintf( _x( 'at %s', 'Human read time for an event with more than 2 dates', 'calendar-plus' ), calendarp_get_formatted_time( $times_unique[0] ) );
			$format_array['time'] = calendarp_get_formatted_time( $times_unique[0] );
		}
	}

	if ( 'array' === $format ) {
		return $format_array;
	}

	return $format_string;
}

/**
 * Set an event as a recurring one or not
 *
 * @param int  $event_id
 * @param bool $recurrent
 */
function calendarp_update_event_type_recurrence( $event_id, $recurrent ) {
	if ( $recurrent ) {
		calendarp_update_event_type( $event_id, 'recurrent' );
	} else {
		calendarp_update_event_type( $event_id, '' );
	}
}

/**
 * Set an event type
 *
 * @param        $event_id
 * @param string $type recurrent|timespan
 */
function calendarp_update_event_type( $event_id, $type ) {
	$term_id = calendarp_get_event_type_term_id( $type );
	if ( $term_id ) {
		wp_set_object_terms( $event_id, array( $term_id ), 'calendar_event_type' );
	} else {
		wp_set_object_terms( $event_id, array(), 'calendar_event_type' );
	}
}

/**
 * Get an event iCal UID
 *
 * @param $event_id
 *
 * @return bool|false|string
 */
function calendarp_get_event_uid( $event_id ) {
	$event = calendarp_get_event( $event_id );
	if ( ! $event ) {
		return false;
	}

	return $event->get_uid();
}

/**
 * Get an event iCal UID
 *
 * @param        $event_id
 * @param string $uid
 */
function calendarp_update_event_uid( $event_id, $uid ) {
	$event = calendarp_get_event( $event_id );
	if ( ! $event ) {
		return;
	}
	$event->set_uid( $uid );
}

/**
 * Get an event linked to a iCal UID
 *
 * @param $uid
 *
 * @return bool|Calendar_Plus_Event
 */
function calendarp_get_event_by_uid( $uid ) {

	$args = array(
		'post_type'      => 'calendar_event',
		'posts_per_page' => 1,
		'post_status'    => get_post_stati( array( 'public' => true, 'protected' => true, 'show_in_admin_all_list' => true ), 'names', 'or' ),
		'meta_query'     => array(
			array(
				'key'   => '_event_uid',
				'value' => $uid,
			),
		),
	);

	if ( $posts = get_posts( $args ) ) {
		return calendarp_get_event( $posts[0]->ID );
	}

	unset( $args['meta_query'] );
	$args['tax_query'] = array(
		array(
			'taxonomy' => 'calendar_event_uid',
			'field'    => 'name',
			'terms'    => $uid,
		),
	);

	if ( $posts = get_posts( $args ) ) {
		return calendarp_get_event( $posts[0]->ID );
	}

	return false;
}

/**
 * Return a list of events since a given date grouped by day
 *
 * @param int   $from Unix timestamp
 * @param array $args List of arguments {
 *
 * @type int $page            Current page to retrieve
 * @type int $events_per_page Show number of events per page
 * @type int $category        Filter by category ID
 * }
 *
 * @return array
 */
function calendarp_get_events_since( $from, $args = array() ) {
	global $wpdb;

	$defaults = array(
		'page'            => 1,
		'events_per_page' => calendarp_get_events_per_page(),
		'category'        => false,
	);
	$args = wp_parse_args( $args, $defaults );

	$timespan = new Calendar_Plus_Timespan( $from, false );
	$from_date = $timespan->get_from_date();
	$from_time = $timespan->get_from_time();

	$select = "SELECT cal.* FROM $wpdb->calendarp_calendar cal";

	$join = '';
	if ( isset( $args['category'] ) && term_exists( absint( $args['category'] ), 'calendar_event_category' ) ) {
		$join = $wpdb->prepare(
			"JOIN $wpdb->term_taxonomy tt ON tt.term_id = %d and tt.taxonomy = 'calendar_event_category'
			JOIN $wpdb->term_relationships tr ON tr.object_id = cal.event_id AND tr.term_taxonomy_id = tt.term_taxonomy_id",
			$args['category']
		);
	}

	$where = array();
	$where[] = $wpdb->prepare( 'cal.from_date >= %s', $from_date );
	$where[] = $wpdb->prepare( 'cal.from_time >= %s', $from_time );

	$where = 'WHERE ' . implode( ' AND ', $where );

	$order = 'ORDER BY cal.from_date ASC, cal.from_time ASC, cal.event_id ASC';

	$page = absint( $args['page'] );
	$events_per_page = absint( $args['events_per_page'] );
	$limit = $wpdb->prepare( 'LIMIT %d, %d', ( $page - 1 ) * $events_per_page, $events_per_page );

	$query = "$select $join $where $order $limit";

	$cache_key = md5( $query );
	$cached_results = get_transient( 'calendarp_events_since' );
	if ( false === $cached_results ) {
		$cached_results = array();
	}

	if ( ! isset( $cached_results[ $cache_key ] ) ) {
		$results = $wpdb->get_results( $query );
		$cached_results[ $cache_key ] = $results;
		set_transient( 'calendarp_events_since', $cached_results, 86400 ); // Save the data for one day
	} else {
		$results = $cached_results[ $cache_key ];
	}

	return _calendarp_group_events_by_date( $results );

}

/**
 * Get events in a date range
 *
 * @param int      $from            Unix Timestamp
 * @param int|bool $to              Unix Timestamp. Default false
 * @param array    $args            List of arguments {
 *
 * @type int|array $category        Filter events by Term ID
 * @type int       $events_per_page Limit results to a given number of events
 * @type int       $grouped_by_day  Group results by day
 * @type string    $search          Filter events with a search string
 * }
 *
 * @return array
 */
function calendarp_get_events_in_date_range( $from, $to = false, $args = array() ) {
	global $wpdb;

	$args = wp_parse_args( $args, array(
		'category'        => array(),
		'tag'             => array(),
		'events_per_page' => false,
		'grouped_by_day'  => true,
		'search'          => '',
		'event_id'        => false,
		'include_ids'     => array(),
		'exclude_ids'     => array(),
	) );

	$timespan = new Calendar_Plus_Timespan( $from, $to );
	$from_date = $timespan->get_from_date();
	$to_date = $timespan->get_to_date();

	$select = "SELECT DISTINCT cal.* FROM $wpdb->calendarp_calendar cal";

	$join = "INNER JOIN $wpdb->posts p ON p.ID = cal.event_id ";

	$term_ids = array();
	$tax_names = array();

	foreach ( array( 'category', 'tag' ) as $tax ) {

		if ( ! empty( $args[ $tax ] ) && ! is_array( $args[ $tax ] ) ) {
			$args[ $tax ] = array( $args[ $tax ] );
		}

		if ( ! is_array( $args[ $tax ] ) ) {
			$args[ $tax ] = array();
		}

		$args[ $tax ] = array_map( 'absint', $args[ $tax ] );

		$term_ids = array_merge( $term_ids, $args[ $tax ] );
		$tax_names[] = 'calendar_event_' . $tax;
	}

	if ( ! empty( $term_ids ) ) {

		$terms_join = sprintf(
			"JOIN $wpdb->term_taxonomy tt ON tt.term_id IN (%s) AND tt.taxonomy IN (%s) ",
			implode( ',', array_fill( 0, count( $term_ids ), '%d' ) ),
			implode( ',', array_fill( 0, count( $tax_names ), '%s' ) )
		);

		$terms_join = $wpdb->prepare( $terms_join, array_merge( $term_ids, $tax_names ) );
		$terms_join .= "JOIN $wpdb->term_relationships tr ON tr.object_id = cal.event_id AND tr.term_taxonomy_id = tt.term_taxonomy_id ";

		$join .= $terms_join;
	}

	$where_not = array();
	$where_not[] = $wpdb->prepare( 'cal.until_date < %s', $from_date );

	if ( $to ) {
		$where_not[] = $wpdb->prepare( 'cal.from_date > %s', $to_date );
	}

	$event_ids = $args['include_ids'];

	$where = array("p.post_status = 'publish'");
	if ( $args['search'] ) {
		$search_results = get_posts( array(
			'post_type' => 'calendar_event',
			's'         => $args['search'],
			'fields'    => 'ids',
			'posts_per_page' => 500,
			'orderby' => 'none'
		) );

		if ( empty( $search_results ) ) {
			$search_results = array( 0 );
		}

		$event_ids += $search_results;
	}

	if ( $event_ids ) {
		$where[] = sprintf( 'cal.event_id IN (%s)', implode( ',', $event_ids ) );
	}

	if ( $args['exclude_ids'] ) {
		$where[] = sprintf( 'cal.event_id NOT IN (%s)', implode( ',', $args['exclude_ids'] ) );
	}

	if ( $args['event_id'] ) {
		$where[] = $wpdb->prepare( 'cal.event_id = %d', $args['event_id'] );
	}

	if ( $where ) {
		$where = ' AND ' . implode( ' AND ', $where );
	} else {
		$where = '';
	}

	$where_not = 'WHERE NOT (' . implode( ' OR ', $where_not ) . ')';

	$order = 'ORDER BY cal.from_date ASC, cal.from_time ASC, cal.event_id ASC';

	$limit = '';
	$per_page = intval( $args['events_per_page'] );
	if ( $per_page > 0 ) {
		$limit = $wpdb->prepare( 'LIMIT %d', $per_page );
	}

	$query = "$select $join $where_not $where $order $limit";
	$results = $wpdb->get_results( $query );

	if ( $args['grouped_by_day'] ) {
		$data = _calendarp_group_events_by_date( $results );
	} else {
		$data = $results;
	}

	return $data;
}

/**
 * Return the events for a given month
 *
 * @param int   $month
 * @param int   $year
 * @param array $args See calendarp_get_events_in_date_range()
 *
 * @return array
 */
function calendarp_get_events_in_month( $month, $year, $args = array() ) {
	return calendar_plus()->generator->get_month_dates( $month, $year, $args );
}

/**
 * Group events by date
 *
 * @internal
 *
 * @param array $results list of events
 *
 * @return array
 */
function _calendarp_group_events_by_date( $results ) {

	if ( empty( $results ) ) {
		return array();
	}

	$events_ids = array_unique( wp_list_pluck( $results, 'event_id' ) );

	$args = array(
		'post__in'       => $events_ids,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	);

	$events = calendarp_get_events( $args );

	$grouped_events = array();
	foreach ( $results as $result ) {
		if ( ! isset( $grouped_events[ $result->from_date ] ) ) {
			$grouped_events[ $result->from_date ] = array();
		}

		$dates_data = array(
			'from_date'        => $result->from_date,
			'until_date'       => $result->until_date,
			'from_time'        => $result->from_time,
			'until_time'       => $result->until_time,
			'calendar_cell_id' => $result->ID,
		);

		$events_for_date = wp_list_filter( $events, array( 'ID' => absint( $result->event_id ) ) );
		foreach ( $events_for_date as $key => $event ) {
			$cloned_event = clone $event;
			$events_for_date[ $key ] = $cloned_event;
			$events_for_date[ $key ]->dates_data = $dates_data;
		}

		$grouped_events[ $result->from_date ] = array_merge( $grouped_events[ $result->from_date ], $events_for_date );

	}

	return $grouped_events;
}

function calendarp_get_event_type_term_ids() {
	$defaults = array(
		'recurrent' => 0,
		'datespan'  => 0,
	);

	$term_ids = get_option( 'calendarp_event_type_term_ids', array() );
	$term_ids = wp_parse_args( $term_ids, $defaults );

	$updated = false;
	foreach ( $term_ids as $term_name => $term_id ) {

		$term = get_term( $term_id );
		if (
			! $term ||
			'calendar_event_type' !== $term->taxonomy &&
			$term_name !== $term->slug
		) {
			$updated = true;
			$term = wp_insert_term( $term_name, 'calendar_event_type' );
			if ( is_array( $term ) ) {
				$term_ids[ $term_name ] = $term['term_id'];
			}
		}
	}

	if ( $updated ) {
		update_option( 'calendarp_event_type_term_ids', $term_ids );
	}

	return $term_ids;
}

function calendarp_get_event_type_term_id( $type ) {
	$term_ids = calendarp_get_event_type_term_ids();

	return isset( $term_ids[ $type ] ) ? $term_ids[ $type ] : false;
}
