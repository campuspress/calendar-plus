<?php
/**
 * Calendar Plus: includes/class-calendar-plus-dates-generator.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Calendar component: Calendar_Plus_Dates_Generator. */
class Calendar_Plus_Dates_Generator {

	/**
	 * Delete generated dates for an event.
	 *
	 * @param int $event_id Event id.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function delete_event_dates( $event_id ) {
		global $wpdb;

		self::clear_cached_months();
		delete_post_meta( $event_id, '_dates_generated' );

		$table = $wpdb->calendarp_calendar;
		calendarp_schedule_changed();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Native schedule mutation uses the core typed delete API and invalidates its cache.
		$wpdb->delete(
			$table,
			array( 'event_id' => $event_id ),
			array( '%d' )
		);
	}

	/**
	 * Generate an event dates list.
	 *
	 * @param int $event_id Event id.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function generate_event_dates( $event_id ) {
		global $wpdb;

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return;
		}

		if ( self::is_event_dates_generated( $event_id ) ) {
			$this->delete_event_dates( $event_id );
			self::clear_cached_months();
		}

		$dates_list = $event->get_dates_list();

		if ( ! is_array( $dates_list ) || empty( $dates_list ) ) {
			update_post_meta( $event_id, '_dates_generated', true );

			return;
		}

		$values      = array();
		$event_dates = array();
		foreach ( $dates_list as $date ) {
			$until_time = isset( $date['until_time'] ) ? $date['until_time'] : '23:59';
			$until_date = isset( $date['until_date'] ) ? $date['until_date'] : $date['from_date'];

			$until_date_array = explode( '-', $until_date );
			if ( count( $until_date_array ) !== 3 ) {
				$until_date = $date['from_date'];
			} elseif ( ! checkdate( $until_date_array[1], $until_date_array[2], $until_date_array[0] ) ) {
					$until_date = $date['from_date'];
			}

			$from_date_array = explode( '-', $date['from_date'] );
			if ( count( $from_date_array ) !== 3 ) {
				continue;
			} elseif ( ! checkdate( $from_date_array[1], $from_date_array[2], $from_date_array[0] ) ) {
					continue;
			}

			$event_dates[] = array(
				$date['from_date'],
				$until_date,
				$date['from_time'],
				$until_time,
			);
		}

		$series_number = 0;
		foreach ( array_chunk( $event_dates, 100 ) as $batch ) {
			$arguments = array( $wpdb->calendarp_calendar );
			foreach ( $batch as $event_date ) {
				array_push( $arguments, $event_id, $event_date[0], $event_date[1], $event_date[2], $event_date[3], $series_number++ );
			}
			$placeholders = implode( ',', array_fill( 0, count( $batch ), '( %d, %s, %s, %s, %s, %d )' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Native schedule write; the interpolated fragment contains only generated placeholders, all identifiers/values are bound. Invalidation follows.
			$wpdb->query( $wpdb->prepare( "INSERT INTO %i (event_id, from_date, until_date, from_time, until_time, series_number) VALUES $placeholders", $arguments ) );
		}
		calendarp_schedule_changed();
		update_post_meta( $event_id, '_dates_generated', true );
	}

	/**
	 * Return the events for a given month.
	 *
	 * @param int   $month Month.
	 * @param int   $year Year.
	 * @param array $args See calendarp_get_events_in_date_range().
	 * @return array
	 */
	public function get_month_dates( $month, $year, $args = array() ) {
		$defaults = array(
			'grouped_by_day'       => false,
			'event_id'             => false,
			'ignore_sticky_events' => true,
		);
		$args     = wp_parse_args( $args, $defaults );
		$args     = apply_filters( 'calendarp_get_month_dates_args', $args );

		$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
		$year  = str_pad( $year, 4, '0', STR_PAD_LEFT );

		$from = strtotime( "$year-$month-01" );

		$days_in_month = str_pad( gmdate( 't', $from ), 2, '0', STR_PAD_LEFT );
		$to            = strtotime( "$year-$month-$days_in_month" );

		// Check that the date requested is actually generated.
		$first_date_generated = calendarp_get_first_date_generated();
		if ( false !== $first_date_generated && "$year-$month-$days_in_month" < $first_date_generated ) {
			if ( ! calendarp_keep_old_dates() ) {
				return array();
			}

			$ids = calendarp_get_event_ids_for_month( $month, $year );
			foreach ( $ids as $id ) {
				$event = calendarp_get_event( $id );
				$event->generate_dates();
			}
		}

		$month_dates = calendarp_get_events_in_date_range( $from, $to, $args );

		return $month_dates;
	}

	/**
	 * Delete all dates.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function delete_dates() {
		global $wpdb;
		$table = $wpdb->calendarp_calendar;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Native schedule deletion; cache invalidation follows.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table ) );
		self::clear_cached_months();
	}

	/**
	 * Delete a single date for an event.
	 *
	 * @param int $date_id Date id.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function delete_single_event_date( $date_id ) {
		global $wpdb;

		$cell = calendarp_get_event_cell( $date_id );
		if ( ! $cell ) {
			return;
		}

		$event_id = absint( $cell->event_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Native schedule deletion; cache invalidation follows.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE ID = %d', $wpdb->calendarp_calendar, $date_id ) );

		self::clear_cached_months();
		calendarp_sort_calendar_event_cells( $event_id );
		calendarp_delete_events_since_cache();
		calendarp_delete_calendar_cache( $event_id );
		update_post_meta( $event_id, '_has_custom_dates', true );
	}

	/**
	 * Retrieve single event date.
	 *
	 * @param int $date_id Date id.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_single_event_date( $date_id ) {
		global $wpdb;

		$cell = calendarp_schedule_read( 'SELECT * FROM %i WHERE ID = %d', array( $wpdb->calendarp_calendar, $date_id ), 'row' );

		if ( empty( $cell ) ) {
			return false;
		}

		return $cell;
	}

	/**
	 * Get the complete list of dates for an event.
	 *
	 * @param int $event_id Event id.
	 * @return mixed
	 */
	public function get_all_event_dates( $event_id ) {
		global $wpdb;

		return calendarp_schedule_read( 'SELECT * FROM %i WHERE event_id = %d', array( $wpdb->calendarp_calendar, $event_id ) );
	}

	/**
	 * Clear all cached months.
	 *
	 * @return mixed Calendar operation result.
	 */
	private static function clear_cached_months() {
		calendarp_schedule_changed();
	}

	/**
	 * Check if an event dates have been generated already.
	 *
	 * @param int $event_id Event id.
	 * @return bool
	 */
	private static function is_event_dates_generated( $event_id ) {
		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return false;
		}

		return $event->is_dates_generated();
	}

	/**
	 * Delete old dates in calendar table.
	 *
	 * @param string|bool $delete_from Delete from this date. If set to false, a span will be applied automatically based on current time.
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function delete_old_dates( $delete_from = false ) {
		global $wpdb;

		$keep_old_dates = calendarp_keep_old_dates();
		if ( $keep_old_dates ) {
			return;
		}

		$table = $wpdb->calendarp_calendar;

		if ( ! $delete_from ) {
			$span         = calendarp_get_old_dates_span();
			$current_time = calendarp_wall_time();
			$delete_from  = gmdate( 'Y-m-d', $current_time - $span );
		}

		$total_old_dates = (int) calendarp_schedule_read( 'SELECT COUNT(ID) FROM %i WHERE until_date < %s', array( $table, $delete_from ), 'var' );
		$max_old_dates   = apply_filters( 'calendarp_max_old_dates_count', 100 );
		if ( $total_old_dates >= $max_old_dates ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Native schedule deletion; cache invalidation follows.
			$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE until_date < %s', $table, $delete_from ) );
			self::clear_cached_months();
		}

		update_option( 'calendarp_first_date_generated', calendarp_schedule_read( 'SELECT MIN(from_date) FROM %i', array( $table ), 'var' ) );

		// Lets use this opportunity to store the last known total dates.
		$total_dates = (int) calendarp_schedule_read( 'SELECT COUNT(ID) FROM %i', array( $table ), 'var' );
		update_option( 'calendarp_last_known_total_dates', $total_dates );
	}


	/**
	 * Get the minimum date for a given event.
	 *
	 * @param int|Calendar_Plus_Event|WP_Post $event_id Event id.
	 * @return bool|string
	 */
	public function get_event_min_date( $event_id ) {
		global $wpdb;

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return false;
		}

		$result = calendarp_schedule_read( 'SELECT min_date FROM %i WHERE event_id = %d LIMIT 1', array( $wpdb->calendarp_min_max_dates, $event_id ), 'var' );
		if ( ! $result ) {
			return false;
		}

		return $result;
	}

	/**
	 * Get the maximum date for a given event.
	 *
	 * @param int|Calendar_Plus_Event|WP_Post $event_id Event id.
	 * @return bool|string
	 */
	public function get_event_max_date( $event_id ) {
		global $wpdb;

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return false;
		}

		$result = calendarp_schedule_read( 'SELECT max_date FROM %i WHERE event_id = %d LIMIT 1', array( $wpdb->calendarp_min_max_dates, $event_id ), 'var' );
		if ( ! $result ) {
			return false;
		}

		return $result;
	}

	/**
	 * Update the min and max dates for a given event.
	 *
	 * @param int $event_id Event id.
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function refresh_event_min_max_dates( $event_id ) {
		global $wpdb;

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return;
		}

		$dates_list = $event->get_dates_list();

		$from_dates = wp_list_pluck( $dates_list, 'from_date' );
		$to_dates   = wp_list_pluck( $dates_list, 'until_date' );
		$dates      = array_merge( $from_dates, $to_dates );

		self::delete_event_min_max_dates( $event_id );
		if ( empty( $dates ) ) {
			return;
		}

		calendarp_schedule_changed();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Native schedule mutation uses the core typed insert API and invalidates its cache.
		$wpdb->insert(
			$wpdb->calendarp_min_max_dates,
			array(
				'event_id' => $event_id,
				'min_date' => min( $dates ),
				'max_date' => max( $dates ),
			),
			array( '%d', '%s', '%s' )
		);
	}

	/**
	 * Delete the min/max dates for a given event.
	 *
	 * @param int $event_id Event id.
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function delete_event_min_max_dates( $event_id ) {
		global $wpdb;

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return;
		}

		calendarp_schedule_changed();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Native schedule mutation uses the core typed delete API and invalidates its cache.
		$wpdb->delete(
			$wpdb->calendarp_min_max_dates,
			array( 'event_id' => $event_id ),
			array( '%d' )
		);
	}
}
