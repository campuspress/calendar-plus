<?php

/**
 * Represents a calendar event post
 *
 * @property-read string $recurrence
 * @property-read string $status
 * @property-read array  $rules_errors
 * @property-read array  $recurring_rules
 * @property-read array  $standard_rules
 * @property-read string $color
 * @property-read bool   $dates_generated
 * @property-read int    $location_id
 */
class Calendar_Plus_Event {

	/**
	 * Event ID
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Associated post object
	 *
	 * @var WP_Post|bool
	 */
	public $post = false;


	/**
	 * @var bool|Calendar_Plus_Event_Rules_Formatter
	 */
	public $rules_formatter = false;

	/**
	 * Build an instance of this class
	 *
	 * @static
	 * @access public
	 *
	 * @param int|Calendar_Plus_Event|WP_Post $event Event ID|object.
	 *
	 * @return Calendar_Plus_Event|false Event object, false otherwise.
	 */
	public static function get_instance( $event ) {

		if ( is_numeric( $event ) ) {
			$_event = get_post( absint( $event ) );
			if ( ! $_event ) {
				return false;
			}

			if ( 'calendar_event' != $_event->post_type ) {
				return false;
			}

			return new self( $_event->ID );
		} elseif ( $event instanceof Calendar_Plus_Event ) {
			return $event;
		} elseif ( $event instanceof WP_Post ) {
			return new self( $event->ID );
		}

		return false;
	}

	/**
	 * Constructor.
	 *
	 * @param int $event_id Event ID.
	 */
	public function __construct( $event_id ) {
		$event      = get_post( $event_id );
		$this->post = $event;
		$this->ID   = $event->ID;
	}

	/**
	 * Retrieve the value of a class property
	 *
	 * @param string $key Key to get.
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get_meta( $key );
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get_meta( $key ) {
		$value = get_post_meta( $this->ID, '_' . $key, true );

		if ( 'recurrence' === $key ) {
			$value = empty( $value ) ? 'regular' : $value;
		} elseif ( 'status' === $key ) {
			$value = empty( $value ) ? 'cp-open' : $value;
		} elseif ( 'rules_errors' === $key ) {
			$value = empty( $value ) ? array() : $value;
		} elseif ( 'recurring_rules' === $key || 'standard_rules' === $key ) {
			if ( ! $value ) {
				$value = array();
			}
		} elseif ( 'color' === $key ) {
			$value = empty( $value ) ? '#DADADA' : $value;
		} elseif ( 'dates_generated' === $key ) {
			$value = (bool) $value;
		}

		return $value;
	}

	/**
	 * Check if the dates have been already generated for this event
	 *
	 * @return bool
	 */
	public function is_dates_generated() {
		return $this->dates_generated;
	}

	/**
	 * Check if the event is a recurring one
	 *
	 * @return bool
	 */
	public function is_recurring() {
		return 'recurrent' === $this->get_event_type();
	}

	/**
	 * Return the event type (recurrent or timespan)
	 */
	public function get_event_type() {
		if ( has_term( calendarp_get_event_type_term_id( 'recurrent' ), 'calendar_event_type', $this->ID ) ) {
			return 'recurrent';
		} elseif ( has_term( calendarp_get_event_type_term_id( 'datespan' ), 'calendar_event_type', $this->ID ) ) {
			return 'datespan';
		}

		return 'general';
	}

	/**
	 * Check if the event is an all day event
	 *
	 * @return bool
	 */
	public function is_all_day_event() {
		return $this->all_day;
	}

	/**
	 * Check if the event has custom dates
	 *
	 * @return bool
	 */
	public function has_custom_dates() {
		return (bool) $this->has_custom_dates;
	}


	/**
	 * Get the WP_Post object associated to this event
	 *
	 * @return array|bool|null|WP_Post
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 * Return the event status
	 *
	 * @return mixed
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Return the Event iCal UID if it has one
	 *
	 * @return string|false
	 */
	public function get_uid() {
		$uid = get_post_meta( $this->ID, '_event_uid', true );

		if ( $uid ) {
			return $uid;
		}

		$terms = wp_get_object_terms( $this->ID, 'calendar_event_uid' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			/** @var WP_Term $uid */
			$uid = $terms[0];

			return $uid->name;
		}

		return false;
	}

	/**
	 * Set a new iCal UID for the event
	 *
	 * @param string|false $uid Set to false to remove UID from event
	 */
	public function set_uid( $uid ) {
		if ( $uid ) {
			update_post_meta( $this->ID, '_event_uid', $uid );
		} else {
			delete_post_meta( $this->ID, '_event_uid' );
		}
	}

	/**
	 * Return the event location object
	 *
	 * @return bool|Calendar_Plus_Location
	 */
	public function get_location() {
		$location_id = $this->get_location_id();
		if ( ! $location_id ) {
			return false;
		}

		$location = calendarp_get_location( $location_id );
		if ( ! $location ) {
			return false;
		}

		return $location;
	}

	/**
	 * Return the event location ID
	 *
	 * @return int
	 */
	public function get_location_id() {
		return absint( $this->location_id );
	}

	/**
	 * Return the event rules formatter object
	 *
	 * @return Calendar_Plus_Event_Rules_Formatter
	 */
	public function get_rules_formatter() {
		if ( $this->rules_formatter ) {
			return $this->rules_formatter;
		}

		$this->rules_formatter = new Calendar_Plus_Event_Rules_Formatter();

		return $this->rules_formatter;
	}

	/**
	 * Return the list of the event rules
	 *
	 * @return array
	 */
	public function get_rules() {
		if ( 'recurrent' === $this->get_event_type() ) {
			$rules = $this->recurring_rules;
		} elseif ( 'datespan' === $this->get_event_type() ) {
			$rules = $this->datespan_rules;
		} else {
			$rules = $this->standard_rules;
		}

		if ( ! is_array( $rules ) ) {
			return array();
		}

		return $rules;
	}

	/**
	 * Update a set of formatted rules
	 *
	 * @param array $formatted_rules
	 */
	public function update_rules( $formatted_rules ) {
		$save_rules = array();

		if ( 'recurrent' === $this->get_event_type() ) {
			$allowed_rules = array( 'dates', 'dows', 'every', 'exclusions', 'times' );

			foreach ( $allowed_rules as $allowed_rule ) {
				if ( empty( $formatted_rules[ $allowed_rule ] ) ) {
					continue;
				}

				foreach ( $formatted_rules[ $allowed_rule ] as $formatted_rule ) {
					if ( ! $formatted_rule ) {
						continue;
					}

					if ( ! isset( $save_rules[ $allowed_rule ] ) ) {
						$save_rules[ $allowed_rule ] = array();
					}

					$save_rules[ $allowed_rule ][] = $formatted_rule;
				}
			}

			// We cannot have 'every' and 'dows' types
			if ( isset( $save_rules['dows'] ) && isset( $save_rules['every'] ) ) {
				unset( $save_rules['every'] );
			}

			$meta_key = '_recurring_rules';
		} elseif ( 'datespan' === $this->get_event_type() ) {
			// Just one dates rule
			if ( isset( $formatted_rules['datespan'] ) ) {
				$dates = $formatted_rules['datespan'][0];
				if ( $dates ) {
					$save_rules['datespan'][] = $dates;
				}
			}
			$meta_key = '_datespan_rules';
		} else {
			if ( isset( $formatted_rules['standard'] ) && is_array( $formatted_rules['standard'] ) ) {

				foreach ( $formatted_rules['standard'] as $key => $rule ) {
					if ( ! $rule ) {
						continue;
					}

					$save_rules['standard'][] = $rule;
				}
			}

			if ( isset( $formatted_rules['exclusions'] ) && is_array( $formatted_rules['exclusions'] ) ) {
				$save_rules['exclusions'] = $formatted_rules['exclusions'];
			}

			$meta_key = '_standard_rules';
		}

		update_post_meta( $this->ID, $meta_key, $save_rules );

		do_action( 'calendarp_update_event_rules', $this->ID, $formatted_rules );
	}

	/**
	 * Format a set of event rules
	 *
	 * @param array $rules
	 *
	 * @return array
	 */
	public function format_rules( $rules ) {
		$formatter = $this->get_rules_formatter();

		return $formatter->format_all( $rules );
	}

	/**
	 * Format a single event rule
	 *
	 * @param $rule
	 *
	 * @return bool
	 */
	public function format_rule( $rule ) {
		$formatter = $this->get_rules_formatter();

		return $formatter->format( $rule );
	}

	/**
	 * Generates the event timetable
	 */
	public function generate_dates() {
		calendar_plus()->generator->generate_event_dates( $this->ID );
	}

	/**
	 * Delete the event timetable
	 */
	public function delete_dates() {
		calendar_plus()->generator->delete_event_dates( $this->ID );
		delete_transient( 'calendarp_events_in_range' );
		calendarp_delete_events_since_cache();
		calendarp_delete_calendar_cache( $this->ID );

		delete_post_meta( $this->ID, '_has_custom_dates' );
	}

	/**
	 * Return the list of dates for this event for a given month
	 *
	 * @param int $month
	 * @param int $year
	 *
	 * @return array
	 */
	public function get_month_dates( $month, $year ) {
		return calendar_plus()->generator->get_month_dates( $month, $year, array( 'event_id' => $this->ID ) );
	}


	/**
	 * Get the dates list for this event
	 *
	 * These are the dates generated by the rules but not inserted /yet) in the calendar table
	 *
	 * @return array|mixed
	 */
	public function get_dates_list() {
		$dates_list = array();
		if ( 'general' === $this->get_event_type() ) {
			// A regular event, formatted rules are the same than the dates list
			$formatted_rules = $this->get_rules();
			$dates_list      = isset( $formatted_rules['standard'] ) ? $formatted_rules['standard'] : array();
		} elseif ( 'recurrent' === $this->get_event_type() ) {
			$formatted_rules = $this->get_rules();
			$dates_list      = $this->get_recurrent_dates_list( $formatted_rules );
		} elseif ( 'datespan' === $this->get_event_type() ) {
			$formatted_rules = $this->get_rules();
			$dates_list      = isset( $formatted_rules['datespan'] ) ? $formatted_rules['datespan'] : array();
		}

		if ( ! empty( $formatted_rules['exclusions'] ) ) {
			foreach ( $formatted_rules['exclusions'] as $exclusion_rule ) {
				$excluded   = wp_list_filter( $dates_list, array(
					'from_date'  => $exclusion_rule['date'],
					'until_date' => $exclusion_rule['date'],
				), 'OR' );
				$dates_list = array_diff_key( $dates_list, $excluded );
			}
		}

		usort( $dates_list, array( $this, 'compare_standard_dates' ) );

		return $dates_list;
	}


	/**
	 * Generate a list of dates
	 *
	 * @param  array $formatted_rules Formatted rules
	 *
	 * @return array List of dates with the following format
	 *
	 * array(
	 * array(
	 * 'from_date' =>
	 * 'until_date' =>
	 * 'from_time' =>
	 * 'until_time' => [optional]
	 * ),
	 * array(
	 * 'from_date' =>
	 * 'until_date' =>
	 * 'from_time' =>
	 * 'until_time' => [optional]
	 * ),
	 * ...
	 * )
	 */
	public static function get_recurrent_dates_list( $formatted_rules ) {
		$dates_list = array();

		// We need at least the from/until dates
		if ( empty( $formatted_rules['dates'] ) ) {
			return array();
		}

		// We need at least one type of frequency
		if ( ! isset( $formatted_rules['dows'] ) && ! isset( $formatted_rules['every'] ) ) {
			return array();
		}

		// We need at least one type of times
		if ( ! isset( $formatted_rules['times'] ) ) {
			return array();
		}

		$dates_rules = $formatted_rules['dates'];

		// Only one please
		$dates_rules = $dates_rules[0];

		$from_datetime    = strtotime( $dates_rules['from'] );
		$until_datetime   = strtotime( $dates_rules['until'] );
		$current_datetime = $from_datetime;
		$all_dates        = array();
		while ( $current_datetime <= $until_datetime ) {
			$all_dates[]      = date( 'Y-m-d', $current_datetime );
			$current_datetime = strtotime( '+1 day', $current_datetime );
		}

		if ( empty( $all_dates ) ) {
			return array();
		}

		$frequency_type  = isset( $formatted_rules['dows'] ) ? 'dows' : 'every';
		$frequency_rules = $formatted_rules[ $frequency_type ][0];

		// Only one of these rules is accepted, with preference to DOWs
		if ( 'dows' === $frequency_type ) {
			// Remove all dates that are not included in those days of the week
			foreach ( $all_dates as $key => $date ) {
				$dow = date( 'N', strtotime( $date ) );
				if ( ! array_key_exists( $dow, $frequency_rules ) ) {
					unset( $all_dates[ $key ] );
				}
			}
		} elseif ( 'every' === $frequency_type ) {
			if ( ! isset( $frequency_rules['every'] ) || ! isset( $frequency_rules['what'] ) ) {
				return array();
			}

			$every = is_array( $frequency_rules['every'] ) ? $frequency_rules['every'] : absint( $frequency_rules['every'] );
			$what  = in_array( $frequency_rules['what'], array( 'week', 'day', 'month', 'year', 'dow', 'dom' ) ) ? $frequency_rules['what'] : false;

			if ( ! $every || ! $what ) {
				return array();
			}

			if ( 'day' === $what ) {
				// We start on the first day of the list
				$counter = 0;

				foreach ( $all_dates as $key => $date ) {
					if ( 0 !== $counter % $every ) {
						unset( $all_dates[ $key ] );
					}

					$counter ++;
				}
			} elseif ( 'week' === $what ) {

				$on = absint( date( 'N', $from_datetime ) );

				$current_datetime = strtotime( current( $all_dates ) );
				$end_datetime     = strtotime( end( $all_dates ) );
				reset( $all_dates );

				$every_dates_list = array();
				while ( $current_datetime <= $end_datetime ) {
					$every_dates_list[] = date( 'Y-m-d', $current_datetime );
					$current_datetime   = strtotime( '+' . $every . ' week', $current_datetime );
				}

				$all_dates = array_intersect( $all_dates, $every_dates_list );

			} elseif ( 'month' === $what ) {
				$current_datetime = strtotime( current( $all_dates ) );
				$end_datetime     = strtotime( end( $all_dates ) );
				reset( $all_dates );

				// We'll try to set this day of month for every date when possible
				$day_of_month = date( 'j', $current_datetime );

				$every_dates_list = array();

				while ( $current_datetime <= $end_datetime ) {
					$current_month = date( 'n', $current_datetime );
					$current_year  = date( 'Y', $current_datetime );

					$days_in_month = calendarp_get_total_days_in_a_month( $current_month, $current_year );

					if ( absint( $days_in_month ) >= absint( $day_of_month ) ) {
						$day = $day_of_month;
					} else {
						$day = $days_in_month;
					}

					$current_month = str_pad( $current_month, 2, '0', STR_PAD_LEFT );
					$day           = str_pad( $day, 2, '0', STR_PAD_LEFT );

					$add_date = "$current_year-$current_month-$day";

					$every_dates_list[] = $add_date;

					// Add a month
					for ( $i = 1; $i <= $every; $i ++ ) {
						$current_datetime = strtotime( 'first day of next month', $current_datetime );
					}

					$current_month = date( 'n', $current_datetime );
					$current_year  = date( 'Y', $current_datetime );

					$days_in_month = calendarp_get_total_days_in_a_month( $current_month, $current_year );

					if ( $days_in_month >= $day_of_month ) {
						$day = $day_of_month;
					} else {
						$day = $days_in_month;
					}

					$current_datetime = strtotime( "$current_year-$current_month-$day" );
				}

				$all_dates = array_intersect( $all_dates, $every_dates_list );

			} elseif ( 'year' === $what ) {

				$every_dates_list = array();

				$current_datetime = strtotime( current( $all_dates ) );
				$end_datetime     = strtotime( end( $all_dates ) );
				reset( $all_dates );

				while ( $current_datetime <= $end_datetime ) {
					$every_dates_list[] = date( 'Y-m-d', $current_datetime );
					$current_datetime   = strtotime( '+ ' . $every . ' year', $current_datetime );
				}

				$all_dates = array_intersect( $all_dates, $every_dates_list );

			} elseif ( 'dow' === $what ) {
				$current_datetime = strtotime( current( $all_dates ) );
				$end_datetime     = strtotime( end( $all_dates ) );

				$every_dates_list = array();

				while ( $current_datetime <= $end_datetime ) {
					if ( in_array( date( 'N', $current_datetime ), $every ) ) {
						$every_dates_list[] = date( 'Y-m-d', $current_datetime );
					}
					$current_datetime = strtotime( '+1 day', $current_datetime );
				}

				$all_dates = array_intersect( $all_dates, $every_dates_list );

			} elseif ( 'dom' === $what ) {

				$current_datetime = strtotime( current( $all_dates ) );
				$end_datetime     = strtotime( end( $all_dates ) );

				$every_dates_list = array();

				while ( $current_datetime <= $end_datetime ) {
					$day_of_week = date( 'N', $current_datetime );

					// determine how many times this day of week has already occurred this month
					$i = floor( ( date( 'd', $current_datetime ) - 1 ) / 7 ) + 1;

					if ( isset( $every[ $i ] ) && in_array( $day_of_week, $every[ $i ] ) ) {
						$every_dates_list[] = date( 'Y-m-d', $current_datetime );
					}

					$current_datetime = strtotime( '+1 day', $current_datetime );
				}

				$all_dates = array_intersect( $all_dates, $every_dates_list );
			}
		} else {
			return array();
		}

		if ( empty( $all_dates ) ) {
			return array();
		}

		$times_rules = $formatted_rules['times'][0];
		if ( ! empty( $all_dates ) ) {
			// Now add the time to every date
			$from_times  = array( $times_rules['from'] );
			$until_times = array( $times_rules['until'] );

			$new_dates = array();
			foreach ( $all_dates as $date_key => $date ) {
				foreach ( $from_times as $time_key => $from_time ) {
					$new_date = array(
						'from_date'  => $date,
						'until_date' => $date,
						'from_time'  => $from_time,
					);

					if ( $until_times[ $time_key ] ) {
						$until_time             = $until_times[ $time_key ];
						$new_date['until_time'] = $until_time;
						// If until time is lower than from time that means that
						// The end date is the next day
						if ( $until_time < $from_time ) {
							$until_datetime         = strtotime( $new_date['until_date'] );
							$next_day               = date( 'Y-m-d', strtotime( '+1 day', $until_datetime ) );
							$new_date['until_date'] = $next_day;
						}
					}

					$new_dates[] = $new_date;
				}
			}

			$all_dates = $new_dates;

		}

		return $all_dates;
	}


	public function compare_standard_dates( $a, $b ) {
		if ( $a['from_date'] . ' ' . $a['from_time'] == $b['from_date'] . ' ' . $b['from_time'] ) {
			return 0;
		}

		return ( $a['from_date'] . ' ' . $a['from_time'] < $b['from_date'] . ' ' . $b['from_time'] ) ? - 1 : 1;
	}


	/**
	 * Get the minimum date for a given event
	 *
	 * @return bool|string
	 */
	public function get_min_date() {
		$calendar_plus = calendar_plus();

		return $calendar_plus->generator->get_event_min_date( $this->ID );
	}

	/**
	 * Get the maximum date for a given event
	 *
	 * @return bool|string
	 */
	public function get_max_date() {
		$calendar_plus = calendar_plus();

		return $calendar_plus->generator->get_event_max_date( $this->ID );
	}

	/**
	 * Checks if the event can be read by the current user
	 *
	 * Correctly handles posts with the inherit status
	 *
	 * @return bool Whether the post can be read.
	 */
	public function current_user_can_read() {
		$post = $this->post;
		$post_type = get_post_type_object( $post->post_type );

		// Is the post readable?
		if ( 'publish' === $post->post_status || current_user_can( $post_type->cap->read_post, $post->ID ) ) {
			return true;
		}

		$post_status_obj = get_post_status_object( $post->post_status );
		if ( $post_status_obj && $post_status_obj->public ) {
			return true;
		}

		// Can we read the parent if we're inheriting?
		if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
			$parent = calendarp_get_event( $post->post_parent );
			return $parent->current_user_can_read();
		}

		/*
		 * If there isn't a parent, but the status is set to inherit, assume
		 * it's published (as per get_post_status()).
		 */
		if ( 'inherit' === $post->post_status ) {
			return true;
		}

		return false;
	}
}
