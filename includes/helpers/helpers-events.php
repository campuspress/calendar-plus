<?php
/**
 * Calendar Plus: includes/helpers/helpers-events.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Get a single event.
 *
 * @param int|Calendar_Plus_Event|WP_Post $event Event.
 * @return bool|Calendar_Plus_Event
 */
function calendarp_get_event( $event ) {
	return Calendar_Plus_Event::get_instance( $event );
}

/**
 * Get the available calendar event statuses.
 *
 * @since 0.1
 *
 * @return array Event statuses keyed by status slug.
 */
function calendarp_get_event_statuses() {
	return apply_filters(
		'calendarp_order_statuses',
		array(
			'cp-open'    => _x( 'Open', 'Order status', 'calendar-plus' ),
			'cp-close'   => _x( 'Closed', 'Order status', 'calendar-plus' ),
			'cp-expire'  => _x( 'Expired', 'Order status', 'calendar-plus' ),
			'cp-archive' => _x( 'Archived', 'Order status', 'calendar-plus' ),
		)
	);
}

/**
 * Delete generated calendar for an event.
 *
 * @param int|Calendar_Plus_Event|WP_Post $event_id Event id.
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
 * Format and update recurrence rules for an event.
 *
 * Updating rules regenerates the event dates.
 *
 * @param int   $event_id Event ID.
 * @param array $rules Unformatted recurrence rules.
 * @return void
 */
function calendarp_generate_event_rules_and_dates( $event_id, $rules ) {
	$event = calendarp_get_event( $event_id );
	if ( ! $event ) {
		return;
	}

	$formatted = $event->format_rules( $rules );

	// When rules are updated, dates are regenerated.
	$event->update_rules( $formatted );
}

/**
 * Return event IDs for a month, including events not present in the calendar table.
 *
 * Event IDs are calculated from the min/max dates table.
 *
 * @param int $month Month number.
 * @param int $year Four-digit year.
 * @return int[] Event IDs.
 */
function calendarp_get_event_ids_for_month( $month, $year ) {
	global $wpdb;

	$month             = zeroise( absint( $month ), 2 );
	$year              = zeroise( absint( $year ), 4 );
	$from_date         = "$year-$month-01";
	$last_day_of_month = gmdate( 't', strtotime( $from_date ) );
	$to_date           = "$year-$month-$last_day_of_month";

	$results = calendarp_schedule_read( 'SELECT event_id FROM %i WHERE NOT ( min_date > %s OR max_date < %s )', array( $wpdb->calendarp_min_max_dates, $to_date, $from_date ), 'col' );
	if ( ! $results ) {
		return array();
	}

	return array_unique( array_map( 'absint', $results ) );
}

/**
 * Get a set of calendar events.
 *
 * @param array $args Optional. Arguments passed to get_posts().
 * @return Calendar_Plus_Event[] Event objects.
 */
function calendarp_get_events( $args = array() ) {

	$args = array_merge( array( 'post_type' => 'calendar_event' ), $args );

	$events = get_posts( $args );

	$events = array_map( 'calendarp_get_event', $events );

	return $events;
}

/**
 * Get a human-readable date description for an event.
 *
 *                         Values separately. Default 'string'.
 *
 * @param int    $event_id Event ID.
 * @param string $format Optional. Return format. Use 'array' to return date, time, and recurrence.
 * @return string|array Human-readable date description, or separate date parts when $format is 'array'.
 */
function calendarp_get_human_read_dates( $event_id, $format = 'string' ) {
	$event = calendarp_get_event( $event_id );
	if ( ! $event ) {
		return '';
	}

	$calendar = $event->get_dates_list();

	$format_string = '';
	$format_array  = array(
		'date'       => '',
		'time'       => '',
		'recurrence' => '',
	);

	if ( empty( $calendar ) ) {
		$format_string        = __( 'No dates for this event', 'calendar-plus' );
		$format_array['date'] = __( 'No dates for this event', 'calendar-plus' );
	} elseif ( 'datespan' === $event->get_event_type() ) {
		if ( $event->is_all_day_event() ) {
			$from_date            = calendarp_get_formatted_date( $calendar[0]['from_date'] );
			$until_date           = calendarp_get_formatted_date( $calendar[0]['until_date'] );
			$format_string        = sprintf( /* translators: 1: formatted start date, 2: formatted end date. */
				_x( 'From %1$s to %2$s', 'Human read date for an all day datespan event', 'calendar-plus' ),
				$from_date,
				$until_date
			);
			$format_array['date'] = sprintf( /* translators: 1: formatted start date, 2: formatted end date. */
				_x( 'From %1$s to %2$s', 'Human read date for an all day datespan event', 'calendar-plus' ),
				$from_date,
				$until_date
			);
		} else {
			$from_date            = calendarp_get_formatted_date( $calendar[0]['from_date'] );
			$until_date           = calendarp_get_formatted_date( $calendar[0]['until_date'] );
			$from_time            = calendarp_get_formatted_time( $calendar[0]['from_time'] );
			$until_time           = calendarp_get_formatted_time( $calendar[0]['until_time'] );
			$format_string        = sprintf( /* translators: 1: formatted start date, 2: formatted end date. */
				_x( 'From %1$s to %2$s', 'Human read date for an all day datespan event', 'calendar-plus' ),
				$from_date . ' ' . $from_time,
				$until_date . ' ' . $until_time
			);
			$format_array['date'] = sprintf( /* translators: 1: formatted start date, 2: formatted end date. */
				_x( 'From %1$s to %2$s', 'Human read date for an all day datespan event', 'calendar-plus' ),
				$from_date,
				$until_date
			);
			$format_array['time'] = $from_time . ' - ' . $until_time;
		}
	} elseif ( count( $calendar ) === 1 ) {
		$date = calendarp_get_formatted_date( $calendar[0]['from_date'] );

		$format_string        = sprintf( /* translators: %s: formatted event date. */
			_x( 'On %s', 'Human read date for an event with only one date', 'calendar-plus' ),
			$date
		);
		$format_array['date'] = $date;
		if ( ! $event->is_all_day_event() ) {
			// Not all day event, let's show the time.
			$time                 = calendarp_get_formatted_time( $calendar[0]['from_time'] );
			$time_to              = calendarp_get_formatted_time( $calendar[0]['until_time'] );
			$format_string       .= ' ' . sprintf( /* translators: 1: formatted start time, 2: formatted end time. */
				_x( 'at %1$s till %2$s', 'Human read time for an event with only one date', 'calendar-plus' ),
				$time,
				$time_to
			);
			$format_array['time'] = $time . ' - ' . $time_to;
		}
	} elseif ( count( $calendar ) === 2 ) {
		$date_1 = calendarp_get_formatted_date( $calendar[0]['from_date'] );
		$date_2 = calendarp_get_formatted_date( $calendar[1]['from_date'] );

		$format_string        = sprintf( /* translators: 1: formatted start date, 2: formatted end date. */
			_x( 'On %1$s and %2$s', 'Human read date for an event with two dates', 'calendar-plus' ),
			$date_1,
			$date_2
		);
		$format_array['date'] = sprintf( /* translators: 1: formatted start date, 2: formatted end date. */
			_x( '%1$s and %2$s', 'Human read date for an event with two dates', 'calendar-plus' ),
			$date_1,
			$date_2
		);
		if ( ! $event->is_all_day_event() && $calendar[0]['from_time'] === $calendar[1]['from_time'] ) {
			// Not all day event and both dates match, let's show the time.
			$time                 = calendarp_get_formatted_time( $calendar[0]['from_time'] );
			$time_to              = calendarp_get_formatted_time( $calendar[0]['until_time'] );
			$format_string       .= ' ' . sprintf( /* translators: 1: formatted start time, 2: formatted end time. */
				_x( 'at %1$s till %2$s', 'Human read time for an event with two dates', 'calendar-plus' ),
				$time,
				$time_to
			);
			$format_array['time'] = $time . ' - ' . $time_to;
		}
	} elseif ( count( $calendar ) > 2 ) {

		$from_date = calendarp_get_formatted_date( $calendar[0]['from_date'] );
		end( $calendar );
		$to_date = calendarp_get_formatted_date( $calendar[ key( $calendar ) ]['from_date'] );
		reset( $calendar );

		$format_string        = sprintf( /* translators: 1: formatted start date, 2: formatted end date. */
			_x( 'From %1$s to %2$s', 'Human read date for an event with more than 2 dates', 'calendar-plus' ),
			$from_date,
			$to_date
		);
		$format_array['date'] = sprintf( /* translators: 1: formatted start date, 2: formatted end date. */
			_x( '%1$s to %2$s', 'Human read date for an event with more than 2 dates', 'calendar-plus' ),
			$from_date,
			$to_date
		);

		if ( 'recurrent' === $event->get_event_type() && ! $event->has_custom_dates() ) {
			// Every?
			$rules = $event->get_rules();

			if ( isset( $rules['every'] ) ) {
				$every          = $rules['every'][0]['every'];
				$format_string .= ', ';
				switch ( $rules['every'][0]['what'] ) {
					case 'day':
						$format_array['recurrence'] = sprintf(
							/* translators: %s: recurrence interval or weekday names. */
							__( 'Every %s', 'calendar-plus' ),
							sprintf( /* translators: %d: recurrence interval. */
								_n( '%d day', '%d days', $every, 'calendar-plus' ),
								$every
							)
						);

						$format_string .= $format_array['recurrence'];
						break;
					case 'dow':
						$dow_every = array_map(
							function ( $day_index ) {
								global $wp_locale;

								return $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( 7 === $day_index ? 0 : $day_index ) );
							},
							$every
						);

						$format_array['recurrence'] = sprintf(
							/* translators: %s: recurrence interval or weekday names. */
							__( 'Every %s', 'calendar-plus' ),
							implode( ', ', $dow_every )
						);

						$format_string .= $format_array['recurrence'];
						break;
					case 'week':
						$format_array['recurrence'] = sprintf(
							/* translators: %s: recurrence interval or weekday names. */
							__( 'Every %s', 'calendar-plus' ),
							sprintf( /* translators: %d: recurrence interval. */
								_n( '%d week', '%d weeks', $every, 'calendar-plus' ),
								$every
							)
						);

						$format_string .= $format_array['recurrence'];
						break;

					case 'month':
						$format_array['recurrence'] = sprintf(
							/* translators: %s: recurrence interval or weekday names. */
							__( 'Every %s', 'calendar-plus' ),
							sprintf( /* translators: %d: recurrence interval. */
								_n( '%d month', '%d months', $every, 'calendar-plus' ),
								$every
							)
						);

						$format_string .= $format_array['recurrence'];
						break;

					case 'year':
						$format_array['recurrence'] = sprintf(
							/* translators: %s: recurrence interval or weekday names. */
							__( 'Every %s', 'calendar-plus' ),
							sprintf( /* translators: %d: recurrence interval. */
								_n( '%d year', '%d years', $every, 'calendar-plus' ),
								$every
							)
						);

						$format_string .= $format_array['recurrence'];
						break;
				}
			}
		}

		$times_list   = wp_list_pluck( $calendar, 'from_time' );
		$times_unique = array_unique( $times_list );

		$until_times_list   = wp_list_pluck( $calendar, 'until_time' );
		$until_times_unique = array_unique( $until_times_list );

		$time_val = '';

		if ( ! $event->is_all_day_event() && count( $times_unique ) === 1 ) {
			// Same time for all events, let's show the time.
			$format_string .= ' ' . sprintf( /* translators: %s: formatted event time. */
				_x( 'at %s', 'Human read time for an event with more than 2 dates', 'calendar-plus' ),
				calendarp_get_formatted_time( $times_unique[0] )
			);
			$time_val       = calendarp_get_formatted_time( $times_unique[0] );
		}

		if ( ! $event->is_all_day_event() && count( $until_times_unique ) === 1 ) {
			// Same time for all events, let's show the time.
			$format_string .= ' ' . sprintf( /* translators: %s: formatted event time. */
				_x( 'till %s', 'Human read time for an event with more than 2 dates', 'calendar-plus' ),
				calendarp_get_formatted_time( $until_times_unique[0] )
			);

			if ( ! empty( $time_val ) ) {
				$format_array['time'] = $time_val . ' - ';
			}

			$format_array['time'] .= calendarp_get_formatted_time( $until_times_unique[0] );
		}
	}

	if ( 'array' === $format ) {
		return $format_array;
	}

	return $format_string;
}

/**
 * Set whether an event is recurrent.
 *
 * @param int  $event_id Event ID.
 * @param bool $recurrent Whether the event should be recurrent.
 * @return void
 */
function calendarp_update_event_type_recurrence( $event_id, $recurrent ) {
	if ( $recurrent ) {
		calendarp_update_event_type( $event_id, 'recurrent' );
	} else {
		calendarp_update_event_type( $event_id, '' );
	}
}

/**
 * Set an event type taxonomy term.
 *
 * @param int    $event_id Event ID.
 * @param string $type Event type slug, such as 'recurrent' or 'datespan'.
 * @return void
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
 * Get an event iCal UID.
 *
 * @param int $event_id Event ID.
 * @return string|false Event UID, or false when the event cannot be loaded.
 */
function calendarp_get_event_uid( $event_id ) {
	$event = calendarp_get_event( $event_id );
	if ( ! $event ) {
		return false;
	}

	return $event->get_uid();
}

/**
 * Update an event iCal UID.
 *
 * @param int    $event_id Event ID.
 * @param string $uid Event UID.
 * @return void
 */
function calendarp_update_event_uid( $event_id, $uid ) {
	$event = calendarp_get_event( $event_id );
	if ( ! $event ) {
		return;
	}
	$event->set_uid( $uid );
}

/**
 * Get an event linked to an iCal UID.
 *
 * @param string $uid Event UID.
 * @return Calendar_Plus_Event|false Event instance, or false when no matching event exists.
 */
function calendarp_get_event_by_uid( $uid ) {

	$args = array(
		'post_type'      => 'calendar_event',
		'posts_per_page' => 1,
		'post_status'    => get_post_stati(
			array(
				'public'                 => true,
				'protected'              => true,
				'show_in_admin_all_list' => true,
			),
			'names',
			'or'
		),
		// phpcs:ignore WordPress.DB.SlowDBQuery -- Native cached WordPress query for this exact metadata/taxonomy mapping; preserve object visibility and compatibility.
		'meta_query'     => array(
			array(
				'key'   => '_event_uid',
				'value' => $uid,
			),
		),
	);

	$posts = get_posts( $args );
	if ( $posts ) {
		return calendarp_get_event( $posts[0]->ID );
	}

	unset( $args['meta_query'] );
	// phpcs:ignore WordPress.DB.SlowDBQuery -- Native cached WordPress query for this exact metadata/taxonomy mapping; preserve object visibility and compatibility.
	$args['tax_query'] = array(
		array(
			'taxonomy' => 'calendar_event_uid',
			'field'    => 'name',
			'terms'    => $uid,
		),
	);

	$posts = get_posts( $args );
	if ( $posts ) {
		return calendarp_get_event( $posts[0]->ID );
	}

	return false;
}

/**
 * Return events since a given timestamp grouped by day.
 *
 * @param int   $from Unix timestamp.
 * @param array $args Optional. List of arguments {.
 * @type int $page            Current page to retrieve.
 * @type int $events_per_page Number of events per page.
 * @type int $category        Category ID to filter by.
 * }
 *
 * @return array Events grouped by date.
 */
function calendarp_get_events_since( $from, $args = array() ) {
	global $wpdb;

	$defaults = array(
		'page'            => 1,
		'events_per_page' => calendarp_get_events_per_page(),
		'category'        => false,
	);
	$args     = wp_parse_args( $args, $defaults );

	$timespan  = new Calendar_Plus_Timespan( $from, false );
	$from_date = $timespan->get_from_date();
	$from_time = $timespan->get_from_time();

	$query     = 'SELECT cal.* FROM %i cal INNER JOIN %i p ON p.ID = cal.event_id';
	$arguments = array( $wpdb->calendarp_calendar, $wpdb->posts );
	if ( ! empty( $args['category'] ) && term_exists( absint( $args['category'] ), 'calendar_event_category' ) ) {
		$query .= " INNER JOIN %i tt ON tt.term_id = %d AND tt.taxonomy = 'calendar_event_category' INNER JOIN %i tr ON tr.object_id = cal.event_id AND tr.term_taxonomy_id = tt.term_taxonomy_id";
		array_push( $arguments, $wpdb->term_taxonomy, absint( $args['category'] ), $wpdb->term_relationships );
	}
	$query          .= " WHERE p.post_type = 'calendar_event' AND p.post_status = 'publish' AND p.post_password = '' AND (cal.from_date > %s OR (cal.from_date = %s AND cal.from_time >= %s)) ORDER BY cal.from_date ASC, cal.from_time ASC, cal.event_id ASC LIMIT %d, %d";
	$page            = max( 1, absint( $args['page'] ) );
	$events_per_page = max( 1, min( 1000, absint( $args['events_per_page'] ) ) );
	array_push( $arguments, $from_date, $from_date, $from_time, ( $page - 1 ) * $events_per_page, $events_per_page );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Internal finite clauses; table identifiers and every value are prepared in one call.
	$query = $wpdb->prepare( $query, $arguments );

	$cache_key      = md5( $query );
	$cached_results = get_transient( 'calendarp_events_since' );
	if ( false === $cached_results ) {
		$cached_results = array();
	}

	if ( ! isset( $cached_results[ $cache_key ] ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Native schedule read is prepared above and cached in the bounded transient below.
		$results = $wpdb->get_results( $query );
		$results = apply_filters( 'calendarp_events_data', $results );
		if ( count( $cached_results ) >= 20 ) {
			$cached_results = array();
		}
		$cached_results[ $cache_key ] = $results;
		set_transient( 'calendarp_events_since', $cached_results, 86400 ); // Save the data for one day.
	} else {
		$results = $cached_results[ $cache_key ];
	}

	return calendarp_group_events_by_date( $results );
}

/**
 * Get events in a date range.
 *
 * @param int|bool $from Unix timestamp. Default false.
 * @param int|bool $to Unix timestamp. Default false.
 * @param array    $args Optional. List of arguments {.
 * @type int|array $category        Category term ID or IDs to filter by.
 * @type int|array $tag             Tag term ID or IDs to filter by.
 * @type int       $events_per_page Limit results to a given number of events.
 * @type bool      $grouped_by_day  Whether to group results by day.
 * @type string    $search          Search string used to filter events.
 * @type int|bool  $event_id        Event ID to filter by.
 * @type int[]     $include_ids     Event IDs to include.
 * @type int[]     $exclude_ids     Event IDs to exclude.
 * @type string    $order           Sort direction. Accepts 'ASC' or 'DESC'.
 * }
 *
 * @return array Events in the requested range, optionally grouped by date.
 */
function calendarp_get_events_in_date_range( $from = false, $to = false, $args = array() ) {
	global $wpdb;

	$args = wp_parse_args(
		$args,
		array(
			'category'        => array(),
			'tag'             => array(),
			'events_per_page' => false,
			'grouped_by_day'  => true,
			'search'          => '',
			'event_id'        => false,
			'include_ids'     => array(),
			'exclude_ids'     => array(),
			'order'           => 'ASC',
		)
	);

	// Normalise timestamps to Y-m-d date strings before building the cache key.
	if ( $from ) {
		$from = gmdate( 'Y-m-d', $from );
	}
	if ( $to ) {
		$to = gmdate( 'Y-m-d', $to );
	}

	// Normalise category/tag before building the cache key so callers passing.
	// a scalar, a string-int, or an array all produce the same key.
	$term_ids  = array();
	$tax_names = array();

	foreach ( array( 'category', 'tag' ) as $tax ) {

		if ( ! empty( $args[ $tax ] ) && ! is_array( $args[ $tax ] ) ) {
			$args[ $tax ] = array( $args[ $tax ] );
		}

		if ( ! is_array( $args[ $tax ] ) ) {
			$args[ $tax ] = array();
		}

		$args[ $tax ] = array_map( 'absint', $args[ $tax ] );

		if ( ! empty( $args[ $tax ] ) ) {
			$term_ids    = array_merge( $term_ids, $args[ $tax ] );
			$tax_names[] = 'calendar_event_' . $tax;
		}
	}

	// Return early if a valid cache entry exists for this query.
	$last_changed = wp_cache_get_last_changed( 'calendarp:events' );
	$cache_key    = 'calendarp_date_range_' . md5( wp_json_encode( compact( 'from', 'to', 'args' ) ) );
	$cached       = calendarp_cache_get_salted( $cache_key, 'calendarp:events', $last_changed );

	if ( false !== $cached ) {
		return $cached;
	}

	$select    = 'SELECT cal.* FROM %i cal';
	$arguments = array( $wpdb->calendarp_calendar, $wpdb->posts );
	$join      = 'INNER JOIN %i p ON p.ID = cal.event_id';

	// Conditions use until_date/from_date to catch events that overlap the requested range.
	$where = array( "p.post_status = 'publish'" );
	if ( $from ) {
		$where[]     = 'cal.until_date >= %s';
		$arguments[] = $from;
	}

	if ( $to ) {
		$where[]     = 'cal.from_date <= %s';
		$arguments[] = $to;
	}

	if ( ! empty( $term_ids ) ) {
		$placeholders_terms = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
		$placeholders_tax   = implode( ',', array_fill( 0, count( $tax_names ), '%s' ) );

		$exists_sql = "
			EXISTS (
				SELECT 1
				FROM %i tr
				INNER JOIN %i tt
					ON tt.term_taxonomy_id = tr.term_taxonomy_id
				WHERE tr.object_id = cal.event_id
				AND tt.term_id IN ($placeholders_terms)
				AND tt.taxonomy IN ($placeholders_tax)
			)
		";

		$where[]   = $exists_sql;
		$arguments = array_merge( $arguments, array( $wpdb->term_relationships, $wpdb->term_taxonomy ), $term_ids, $tax_names );
	}

	// Merge explicitly included IDs with any keyword-search matches.
	$event_ids = $args['include_ids'];

	if ( $args['search'] ) {
		$search_results = get_posts(
			array(
				'post_type'      => 'calendar_event',
				's'              => $args['search'],
				'fields'         => 'ids',
				// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Bounded native keyword candidate scan, independent of the result page size; never an unbounded -1 query.
				'posts_per_page' => 500,
				'orderby'        => 'none',
			)
		);

		if ( empty( $search_results ) ) {
			$search_results = array( 0 );
		}

		$event_ids = array_unique( array_merge( $event_ids, $search_results ) );
	}

	if ( ! empty( $event_ids ) ) {
		$where[]   = sprintf(
			'cal.event_id IN (%s)',
			implode( ',', array_fill( 0, count( $event_ids ), '%d' ) )
		);
		$arguments = array_merge( $arguments, array_map( 'absint', $event_ids ) );
	}

	if ( ! empty( $args['exclude_ids'] ) ) {
		$where[]   = sprintf(
			'cal.event_id NOT IN (%s)',
			implode( ',', array_fill( 0, count( $args['exclude_ids'] ), '%d' ) )
		);
		$arguments = array_merge( $arguments, array_map( 'absint', $args['exclude_ids'] ) );
	}

	if ( $args['event_id'] ) {
		$where[]     = 'cal.event_id = %d';
		$arguments[] = absint( $args['event_id'] );
	}

	$where_sql = 'WHERE ' . implode( ' AND ', $where );

	// Validate the sort direction to prevent unexpected SQL behaviour.
	$sort_type = strtoupper( $args['order'] ) ?? 'ASC';

	if ( ! in_array( $sort_type, array( 'ASC', 'DESC' ), true ) ) {
		$sort_type = 'ASC';
	}

	$order = "ORDER BY cal.from_date $sort_type, cal.from_time $sort_type, cal.event_id $sort_type";

	$limit    = '';
	$per_page = intval( $args['events_per_page'] );
	if ( $per_page > 0 ) {
		$limit       = 'LIMIT %d';
		$arguments[] = min( 1001, $per_page );
	}

	$query = "$select $join $where_sql $order $limit";

	$results = calendarp_schedule_read( $query, $arguments );
	$results = apply_filters( 'calendarp_events_data', $results );

	if ( $args['grouped_by_day'] ) {
		$data = calendarp_group_events_by_date( $results );
	} else {
		$data = $results;
	}

	calendarp_cache_set_salted( $cache_key, $data, 'calendarp:events', $last_changed );

	return $data;
}

/**
 * Return events for a given month.
 *
 * @param int   $month Month number.
 * @param int   $year Four-digit year.
 * @param array $args Optional. Arguments passed to calendarp_get_events_in_date_range().
 * @return array Events for the requested month.
 */
function calendarp_get_events_in_month( $month, $year, $args = array() ) {
	return calendar_plus()->generator->get_month_dates( $month, $year, $args );
}

/**
 * Group raw calendar rows by date.
 *
 * @param array $results Raw calendar rows.
 * @internal
 *
 * @return array Events grouped by from_date.
 */
function calendarp_group_events_by_date( $results ) {

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
			$cloned_event                        = clone $event;
			$events_for_date[ $key ]             = $cloned_event;
			$events_for_date[ $key ]->dates_data = $dates_data;
		}

		$grouped_events[ $result->from_date ] = array_merge( $grouped_events[ $result->from_date ], $events_for_date );

	}

	return $grouped_events;
}

/**
 * Retrieves the term ID for a specific calendar event type.
 *
 * @param string $type Event type slug.
 * @return int|false Term ID if found or created, or false on failure.
 */
function calendarp_get_event_type_term_id( $type ) {
	$term = get_term_by( 'slug', $type, 'calendar_event_type' );

	if ( $term && ! is_wp_error( $term ) ) {
		return $term->term_id;
	}

	$term = wp_insert_term( $type, 'calendar_event_type' );

	if ( ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
		return $term['term_id'];
	}

	return false;
}

/**
 * Get the first date generated in the calendar table.
 *
 * @return string|false First generated date, or false when not set.
 */
function calendarp_get_first_date_generated() {
	$date = get_option( 'calendarp_first_date_generated' );
	if ( ! $date ) {
		return false;
	}

	return $date;
}

/**
 * Determine whether old generated calendar dates should be retained.
 *
 * @return bool True to keep old dates, false to remove them.
 */
function calendarp_keep_old_dates() {
	return apply_filters( 'calendarp_keep_old_dates', false );
}

/**
 * Get the retention span for old generated calendar dates.
 *
 * @return int Retention span in seconds.
 */
function calendarp_get_old_dates_span() {
	$old_dates_span = apply_filters( 'calendarp_old_dates_span', YEAR_IN_SECONDS );

	if ( $old_dates_span <= 0 ) {
		return YEAR_IN_SECONDS;
	}

	return $old_dates_span;
}

/**
 * Get the last known total number of generated dates.
 *
 * @return int Last known total generated dates.
 */
function calendarp_get_last_known_total_dates() {
	return (int) get_option( 'calendarp_last_known_total_dates' );
}

/**
 * Get the generated dates count threshold used to detect heavy queries.
 *
 * @return int Total dates limit.
 */
function calendarp_get_total_dates_limit() {
	return (int) apply_filters( 'calendarp_heavy_query_vars_total_dates_limit', 250 );
}
