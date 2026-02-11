<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Calendar_Plus_Dates_Generator {

	/**
	 * Delete generated dates for an event
	 *
	 * @param $event_id
	 */
	public function delete_event_dates( $event_id ) {
		global $wpdb;

		self::clear_cached_months();
		delete_post_meta( $event_id, '_dates_generated' );

		$table = $wpdb->calendarp_calendar;
		$wpdb->delete(
			$table,
			array( 'event_id' => $event_id ),
			array( '%d' )
		);
	}

	/**
	 * Generate an event dates list
	 *
	 * @param int $event_id
	 */
	public function generate_event_dates( $event_id ) {
		global $wpdb;

		if ( ! $event = calendarp_get_event( $event_id ) ) {
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

		$sql = "INSERT INTO $wpdb->calendarp_calendar ( event_id, from_date, until_date, from_time, until_time, series_number ) VALUES ";

		$values = array();
		$event_dates = array();
		foreach ( $dates_list as $date ) {
			$until_time = isset( $date['until_time'] ) ? $date['until_time'] : '23:59';
			$until_date = isset( $date['until_date'] ) ? $date['until_date'] : $date['from_date'];

			$until_date_array = explode( '-', $until_date );
			if ( count( $until_date_array ) != 3 ) {
				$until_date = $date['from_date'];
			} else {
				if ( ! checkdate( $until_date_array[1], $until_date_array[2], $until_date_array[0] ) ) {
					$until_date = $date['from_date'];
				}
			}

			$from_date_array = explode( '-', $date['from_date'] );
			if ( count( $from_date_array ) != 3 ) {
				continue;
			} else {
				if ( ! checkdate( $from_date_array[1], $from_date_array[2], $from_date_array[0] ) ) {
					continue;
				}
			}

			$event_dates[] = array(
				$date['from_date'],
				$until_date,
				$date['from_time'],
				$until_time,
			);
		}

		$series_number = 0;
		foreach ( $event_dates as $event_date ) {
			$values[] = $wpdb->prepare( '( %d, %s, %s, %s, %s, %d )', $event_id, $event_date[0], $event_date[1], $event_date[2], $event_date[3], $series_number );
			$series_number++;
		}

		$values = implode( ',', $values );
		$sql .= $values;

		$wpdb->query( $sql );
		update_post_meta( $event_id, '_dates_generated', true );
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
	public function get_month_dates( $month, $year, $args = array() ) {
		$defaults = array(
			'grouped_by_day'       => false,
			'event_id'             => false,
			'ignore_sticky_events' => true,
		);
		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters( 'calendarp_get_month_dates_args', $args );

		$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
		$year = str_pad( $year, 4, '0', STR_PAD_LEFT );

		// Check that the date requested is actually generated
		$first_date_generated = self::get_first_date_generated();
		if ( false !== $first_date_generated ) {
			if ( "$year-$month-01" <= $first_date_generated ) {
				// This month has been erased previously, let's regenerate
				$ids = calendarp_get_event_ids_for_month( $month, $year );
				foreach ( $ids as $id ) {
					$event = calendarp_get_event( $id );
					$event->generate_dates();
				}
			}
		}

		if ( function_exists( 'wp_cache_get_last_changed' ) ) {
			$last_changed = wp_cache_get_last_changed( 'calendarp:events' );
		}

		$cache_args = $args;
		$cache_args['month'] = $month;
		$cache_args['year'] = $year;
		$cache_key = wp_hash( maybe_serialize( $cache_args ) );

		if ( function_exists( 'wp_cache_get_salted' ) ) {
			$cache_group = 'calendarp_cache';

			$month_dates = wp_cache_get_salted( $cache_key, $cache_group, $last_changed );

			if ( false !== $month_dates ) {
				return $month_dates;
			}
		}

		$from = strtotime( "$year-$month-01" );

		$days_in_month = str_pad( date( 't', $from ), 2, '0', STR_PAD_LEFT );
		$to = strtotime( "$year-$month-$days_in_month" );
		$month_dates = calendarp_get_events_in_date_range( $from, $to, $args );

		/*
		|--------------------------------------------------------------------------
		| Store Salted Cache
		|--------------------------------------------------------------------------
		*/
		if ( function_exists( 'wp_cache_set_salted' ) ) {
			wp_cache_set_salted(
				$cache_key,
				$month_dates,
				$cache_group,
				$last_changed
			);
		}

		return $month_dates;
	}

	/**
	 * Delete all dates
	 */
	public function delete_dates() {
		global $wpdb;
		$table = $wpdb->calendarp_calendar;
		$wpdb->query( "DELETE FROM $table" );
		self::clear_cached_months();
	}

	/**
	 * Delete a single date for an event
	 *
	 * @param int $date_id
	 */
	public function delete_single_event_date( $date_id ) {
		global $wpdb;

		$cell = calendarp_get_event_cell( $date_id );
		if ( ! $cell ) {
			return;
		}

		$event_id = absint( $cell->event_id );

		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->calendarp_calendar WHERE ID = %d", $date_id ) );

		self::clear_cached_months();
		calendarp_sort_calendar_event_cells( $event_id );
		calendarp_delete_events_in_range_cache();
		calendarp_delete_events_since_cache();
		calendarp_delete_calendar_cache( $event_id );
		update_post_meta( $event_id, '_has_custom_dates', true );
	}

	public function get_single_event_date( $date_id ) {
		global $wpdb;

		$cell = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->calendarp_calendar WHERE ID = %d", $date_id ) );

		if ( empty( $cell ) ) {
			return false;
		}

		return $cell;
	}

	/**
	 * Get the complete list of dates for an event
	 *
	 * @param $event_id
	 *
	 * @return mixed
	 */
	public function get_all_event_dates( $event_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->calendarp_calendar WHERE event_id = %d", $event_id ) );
	}

	/**
	 * Clear all cached months
	 */
	private static function clear_cached_months() {
		Calendar_Plus_Cache::delete_cache_group( 'calendarp_months_dates' );
		wp_cache_delete( 'get_calendar_plus_widget', 'calendar' );
	}

	/**
	 * Check if an event dates have been generated already
	 *
	 * @param int $event_id
	 *
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
	 * Get first date generated in calendar table
	 *
	 * @return bool|false|string
	 */
	public static function get_first_date_generated() {
		$date = get_option( 'calendarp_first_date_generated' );
		if ( ! $date ) {
			return false;
		}

		return $date;
	}

	/**
	 * Delete old dates in calendar table
	 *
	 * @param string|bool $delete_from Delete from this date. If set to false, a span will be applied automatically based on current time
	 */
	public static function delete_old_dates( $delete_from = false ) {
		$keep_old_dates = apply_filters( 'calendarp_keep_old_dates', false );
		if ( $keep_old_dates ) {
			return;
		}

		global $wpdb;

		$table = $wpdb->calendarp_calendar;

		if ( ! $delete_from ) {
			$span = apply_filters( 'calendarp_old_dates_span', 365 * 24 * 3600 ); // 1 year span
			$current_time = current_time( 'timestamp' );
			$delete_from = date( 'Y-m-d', $current_time - $span );
		}

		$total_old_dates = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM $table WHERE until_date < '$delete_from'" );
		$max_old_dates   = apply_filters( 'calendarp_max_old_dates_count', 100 );
		if ( $total_old_dates >= $max_old_dates ) {
			$wpdb->query( "DELETE FROM $table WHERE until_date < '$delete_from'" );
			update_option( 'calendarp_first_date_generated', $delete_from );
			self::clear_cached_months();
		}

		// Lets use this opportunity to store the last known total dates
		$total_old_dates = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM $table" );
		update_option( 'calendarp_last_known_total_dates', $delete_from );
	}


	/**
	 * Get the minimum date for a given event
	 *
	 * @param int|Calendar_Plus_Event|WP_Post $event_id
	 *
	 * @return bool|string
	 */
	public function get_event_min_date( $event_id ) {
		global $wpdb;

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return false;
		}

		$result = $wpdb->get_var( $wpdb->prepare( "SELECT min_date FROM $wpdb->calendarp_min_max_dates WHERE event_id = %d LIMIT 1", $event_id ) );
		if ( ! $result ) {
			return false;
		}

		return $result;
	}

	/**
	 * Get the maximum date for a given event
	 *
	 * @param int|Calendar_Plus_Event|WP_Post $event_id
	 *
	 * @return bool|string
	 */
	public function get_event_max_date( $event_id ) {
		global $wpdb;

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return false;
		}

		$result = $wpdb->get_var( $wpdb->prepare( "SELECT max_date FROM $wpdb->calendarp_min_max_dates WHERE event_id = %d LIMIT 1", $event_id ) );
		if ( ! $result ) {
			return false;
		}

		return $result;
	}

	/**
	 * Update the min and max dates for a given event
	 *
	 * @param $event_id
	 */
	public static function refresh_event_min_max_dates( $event_id ) {
		global $wpdb;

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return;
		}

		$dates_list = $event->get_dates_list();

		$from_dates = wp_list_pluck( $dates_list, 'from_date' );
		$to_dates = wp_list_pluck( $dates_list, 'until_date' );
		$dates = array_merge( $from_dates, $to_dates );

		self::delete_event_min_max_dates( $event_id );
		if ( empty( $dates ) ) {
			return;
		}

		$wpdb->insert(
			$wpdb->calendarp_min_max_dates,
			array( 'event_id' => $event_id, 'min_date' => min( $dates ), 'max_date' => max( $dates ) ),
			array( '%d', '%s', '%s' )
		);
	}

	/**
	 * Delete the min/max dates for a given event
	 *
	 * @param $event_id
	 */
	public static function delete_event_min_max_dates( $event_id ) {
		global $wpdb;

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return;
		}

		if ( function_exists( 'wp_cache_set_last_changed' ) ) {
			// Sets last changed date for calendarp:events cache group to now.
			// This invalidates all cached queries for this group.
			wp_cache_set_last_changed( 'calendarp:events' );
		}

		$wpdb->delete(
			$wpdb->calendarp_min_max_dates,
			array( 'event_id' => $event_id ),
			array( '%d' )
		);
	}

}

