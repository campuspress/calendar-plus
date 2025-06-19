<?php

use ICal\ICal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Calendar_Plus_iCal_Parser
 *
 * Acts as an abstract layer for \ICal\ICal
 */
class Calendar_Plus_iCal_Parser {
	/**
	 * iCal file contents
	 *
	 * @var array
	 */
	private $contents = array();

	/**
	 * @var \ICal\ICal
	 */
	public $ical;

	/**
	 * Holds next recurring instance index,
	 * keyed by event UID and recurring rule
	 *
	 * @var array
	 */
	public $_recurring_increments = array();

	/**
	 * Whether or not to import recurring events
	 *
	 * @var bool
	 */
	private $_import_recurring = false;

	/**
	 * Whether or not to exclude past events.
	 * For now only -1 is supported = exclude all events.
	 *
	 * @var int
	 */
	private $_exlude_past = false;

	/**
	 * Calendar_Plus_iCal_Sync constructor.
	 *
	 * @param string $ical_contents content of an iCal file
	 * @param bool $import_recurring Whether or not to import recurring event instances.
	 */
	public function __construct( $ical_contents, $import_recurring = false, $exlude_past = false ) {
		$this->contents = $ical_contents;
		$this->_import_recurring = ( bool ) $import_recurring;
		$this->_exlude_past = intval( $exlude_past );
	}

	/**
	 * Parse the content of the iCal file
	 */
	private function init() {
		$this->ical = new ICal( $this->contents, array(
			'defaultSpan'           => 2,     // Default value
			'defaultTimeZone'       => 'UTC',
			'defaultWeekStart'      => 'MO',  // Default value
			'skipRecurrence'        => true, // Default value
			'useTimeZoneWithRRules' => false, // Default value
		) );
	}

	/**
	 * Initializes the parser and get the parsed events
	 *
	 * @throws Exception
	 */
	public function parse() {
		$this->init();
		return $this->parse_events();
	}

	/**
	 * Extract only needed information from events
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	public function parse_events() {
		$_events = $this->ical->events();
		// This is the calendar timezone
		$calendar_tz = new DateTimeZone( $this->ical->calendarTimeZone() );

		if ( ! $calendar_tz ) {
			throw new Exception(
				__( 'iCal timezone cannot be parsed', 'calendarp' ),
				'wrong-timezone'
			);
		}

		// And this is WordPress timezone
		$local_tz = get_option('timezone_string');
		if( ! $local_tz ) {
			$local_tz = get_option( 'gmt_offset' );
			if ( ! $local_tz ) {
				$local_tz = '+0';
			} elseif ( $local_tz > 0 ) {
				$local_tz = '+' . $local_tz;
			}
		}

		$local_tz = new DateTimeZone( $local_tz );
		if ( ! $local_tz ) {
			throw new Exception( __( 'Local timezone cannot be parsed', 'calendarp' ), 'wrong-timezone' );
		}

		$recurring_single_events = array();
		foreach ( $_events as $event ) {
			if ( $event->recurrence_id ) {
				if ( ! isset( $recurring_single_events[ $event->uid ] ) ) {
					$recurring_single_events[ $event->uid ] = array();
				}
				$exclude_date = self::cast_date_timezones( $event->dtstart, $calendar_tz, $local_tz );
				$recurring_single_events[ $event->uid ][] = date( 'Y-m-d', $exclude_date );
			}
		}

		$events = array();
		foreach ( $_events as $_event ) {
			if ( $_event->rrule && ! $this->_import_recurring ) {
				continue;
			}
			/** @var \ICal\Event $_event */
			$content = '';
			if ( ! empty( $_event->x_alt_desc ) ) {
				$content = wp_kses( $_event->x_alt_desc, wp_kses_allowed_html( 'post' ) );
			} elseif ( ! empty( $_event->description ) ) {
				$content = wp_kses( $_event->description, wp_kses_allowed_html( 'post' ) );
			} elseif ( ! empty( $_event->summary ) ) {
				$content = wp_kses( $_event->summary, wp_kses_allowed_html( 'post' ) );
			}

			$content = html_entity_decode( $content );

			$cast_timezone = true;

			//dtstart has to be set but, dtend not always.
			$start_date_tz = $calendar_tz;
			if ( $_event->dtstart_array ) {
				$start_date_tz = $this->extract_timezone_from_ical_format( $_event->dtstart_array[3] );
				$start_date_tz = $start_date_tz ?: $calendar_tz;

				// dtstart can have only date instead of date-time
				if (
					isset( $_event->dtstart_array[0]['VALUE'] ) &&
					$_event->dtstart_array[0]['VALUE'] === 'DATE'
				) {
					// Set start time to begining
					$_event->dtstart .= 'T';
					$_event->all_day  = true;
					$cast_timezone    = false;
				}
			}

			if ( $_event->all_day && ! $cast_timezone ) {
				$from = self::cast_date_without_timezones( $_event->dtstart );
			} else {
				$from = self::cast_date_timezones( $_event->dtstart, $start_date_tz, $local_tz );
			}

			$end_date_tz = $calendar_tz;
			if( isset( $_event->dtend ) && $_event->dtend ) {
				$end_date_tz = null;
				if ( $_event->dtend_array ) {
					$end_date_tz = $this->extract_timezone_from_ical_format( $_event->dtend_array[3] );

					// DTEND property can have date instead of time
					if (
						isset( $_event->dtend_array[0]['VALUE'] ) &&
						$_event->dtend_array[0]['VALUE'] === 'DATE'
					) {
						// Set end time to 23:59:59.
						$_event->dtend .= 'T235959';
					}
				}
				$end_date_tz = $end_date_tz ?: $calendar_tz;

				if ( $_event->all_day && ! $cast_timezone ) {
					$to = self::cast_previous_date_without_timezones( $_event->dtend );
				} else {
					$to = self::cast_date_timezones( $_event->dtend, $end_date_tz, $local_tz );
				}
			}
			else {
				$to = $from;
			}

			if( $this->_exlude_past === -1 ) {

				if( $to < time() && ! $_event->rrule ) {
					continue;
				}
			}

			$event_data = array(
				'post_status'   => strtoupper( $_event->status ) === 'CANCELLED' ? 'trash' : 'publish',
				'post_content'  => $content,
				'post_title'    => wp_kses( $_event->summary, wp_kses_allowed_html( 'post' ) ),
				'from'          => $from,
				'to'            => $to,
				'last_updated'  => self::cast_date_timezones( $_event->lastmodified, $calendar_tz, $local_tz ),
				'uid'           => $this->get_event_uid( $_event ),
				'location'      => $_event->location,
				'categories'    => isset( $_event->categories ) ? array_map( 'trim', explode( ',', $_event->categories ) ) : array(),
				'all_day'       => ! empty( $_event->all_day ),
			);

			if ( $_event->rrule ) {
				$rules   = array();
				$rrule   = $this->parse_rrule( $_event->rrule );

				$start_date = null;
				if ( ! empty( $_event->dtstart ) ) {
					$start_date = $this->create_date_from_ical_format( $_event->dtstart, $start_date_tz, $local_tz );
				}

				$end_date = null;
				if ( ! empty( $rrule['UNTIL'] ) ) {
					$end_date = $this->create_date_from_ical_format( $rrule['UNTIL'], $end_date_tz, $local_tz );
				} else {
					$end_date = $start_date;
				}

				if ( $this->_exlude_past === -1 && $end_date?->getTimestamp() < time() ) {
					continue;
				}

				$rules[] = $this->build_date_rule( $start_date, $end_date );

				$end_time = null;
				if ( ! empty( $_event->dtend ) ) {
					$end_time = $this->create_date_from_ical_format( $_event->dtend, $end_date_tz, $local_tz );
				}
				$rules[] = $this->build_time_rule( $start_date, $end_time );

				// Shift selected day of week when
				// user time is day ahead of server time or before it
				$day_offset = 0;
				if ( isset( $start_date ) ) {
					$from       = date_create( $_event->dtstart, $start_date_tz );
					$day_offset = (int) $start_date->format( 'd' ) - (int) $from->format( 'd' );
				}

				$rules[] = $this->build_rrule( $rrule, $day_offset );
				if ( $_event->exdate_array ) {
					$excluded_dates = $this->parse_excluded_dates( $_event->exdate_array, $start_date_tz, $local_tz );
					if ( isset( $recurring_single_events[ $_event->uid ] ) ) {
						$recurring_single_events[ $_event->uid ] = array_merge( $recurring_single_events[ $_event->uid ], $excluded_dates );
					} else {
						$recurring_single_events[ $_event->uid ] = $excluded_dates;
					}
				}
				if ( isset( $recurring_single_events[ $_event->uid ] ) ) {
					foreach ( $recurring_single_events[ $_event->uid ] as $exclusion ) {
						$rules[] = array(
							'rule_type' => 'exclusions',
							'date'      => $exclusion
						);
					}
				}
				$event_data['rrules'] = $rules;
			}
			$events[] = $event_data;
		}

		return $events;
	}

	/**
	 * Extract timezone from date-time property.
	 * @param string $date_str Input format: TZID=America/New_York:20230101T000000
	 *
	 * @return DateTimeZone|null
	 */
	private function extract_timezone_from_ical_format( string $date_str ): ?DateTimeZone {
		$datetime = $this->ical->iCalDateToDateTime( $date_str );

		return $datetime->getTimezone() ?: new \DateTimeZone('UTC');
	}


	/**
	 * Parse RRULE string
	 * @param string $rule
	 *
	 * @return string[]
	 */
	private function parse_rrule( string $rule ) {
		$options = explode( ';', $rule );
		foreach ( $options as $key => $option ) {
			$option_data = mb_split( '=', $option );
			$options[ $option_data[0] ] = trim( $option_data[1] );

			unset( $options[ $key ] );
		}
		return $options;
	}

	/**
	 * Create DateTime from ical format and changes timezone from event to local
	 * @param string $date_str
	 * @param DateTimeZone $event_tz
	 * @param DateTimeZone $local_tz
	 *
	 * @return DateTime|null
	 */
	private function create_date_from_ical_format( string $date_str, DateTimeZone $event_tz, DateTimeZone $local_tz ): ?DateTime {
		if ( ! str_ends_with( $date_str, 'Z' ) ) {
			$date_str .= 'Z';
		}

		$date = date_create_from_format(
			Calendar_Plus_iCal_Generator::ICAL_DATE_FORMAT,
			$date_str,
			$event_tz
		);
		if ( ! $date ) {
			return null;
		}

		$date->setTimezone( $local_tz );
		return $date;
	}

	/**
	 * Build date rule according to Calendar_Plus_Event_Rules_Dates_Formatter format
	 * @param ?DateTime $start_date
	 * @param ?DateTime $end_date
	 *
	 * @return string[]
	 */
	private function build_date_rule( ?DateTime $start_date, ?DateTime $end_date ): array {
		$rule = array( 'rule_type' => 'dates' );
		if ( $start_date ) {
			$rule['from'] = $start_date->format( 'Y-m-d' );
		}
		if ( $end_date ) {
			$rule['until'] = $end_date->format( 'Y-m-d' );
		}

		return $rule;
	}

	/**
	 * Build time rule according to Calendar_Plus_Event_Rules_Times_Formatter format
	 * @param ?DateTime $start_date
	 * @param ?DateTime $end_date
	 *
	 * @return string[]
	 */
	private function build_time_rule( ?DateTime $start_date, ?DateTime $end_date ): array {
		$rule = array( 'rule_type' => 'times' );

		if ( $start_date instanceof DateTime ) {
			$rule['from'] = $start_date->format( 'H:i:s' );
		}
		if ( $end_date instanceof DateTime ) {
			$rule['until'] = $end_date->format( 'H:i:s' );
		}

		return $rule;
	}

	/**
	 * Build RRULE array according to Calendar_Plus_Event_Rules_Every_Formatter format
	 * @param array $options - RRULE options
	 * @param int $day_offset - number of days to shift weekday. That's due to timezone difference.
	 *                          value can be -1, 0 or 1
	 *
	 * @return string[]
	 */
	private function build_rrule( array $options, int $day_offset = 0 ) {

		$every = array( 'rule_type' => 'every' );

		if ( isset( $options['BYDAY'] ) ) {
			if ( $options['FREQ'] === 'WEEKLY' ) {
				if ( isset( $options['INTERVAL'] ) ) {
					$every['every'] = (int) $options['INTERVAL'];
					$every['what']  = 'week';
				} else {
					$every['what']  = 'dow';
					$every['every'] = $this->convert_weekdays( $options['BYDAY'], $day_offset );
				}
			} elseif ( $options['FREQ'] === 'MONTHLY' ) {
				$every['what'] = 'dom';
				$every['every'] = $this->convert_multiple_weeks_days( $options['BYDAY'], $day_offset );
			}
		} else {
			$every['what']  = $this->convert_period( $options['FREQ'] );
			if ( isset( $options['INTERVAL'] ) ) {
				$every['every'] = (int) $options['INTERVAL'];
			} else {
				$every['every'] = 1;
			}
		}

		return $every;
	}

	/**
	 * Convert weekdays string into array of numeric representation of days of week
	 * @param string $days_str
	 * @param int $day_offset - number of days to shift weekday
	 *
	 * @return int[]
	 */
	private function convert_weekdays( string $days_str, int $day_offset ) {
		$days_of_week = mb_split( ',', $days_str );
		return array_map( function ( $day ) use ( $day_offset ) {
			$idx = array_search( $day, Calendar_Plus_iCal_Generator::WEEKDAY_CODES );
			$idx += $day_offset;

			$idx = ( $idx + 7 ) % 7;
			if ( $idx === 0 ) {
				$idx = 7;
			}
			return $idx;
		}, $days_of_week );
	}

	/**
	 * Convert multiple weeks weekdays string into array of numeric
	 * representation of days of week
	 * @param $weekdays
	 * @param $day_offset
	 *
	 * @return array
	 */
	private function convert_multiple_weeks_days( $weekdays, $day_offset ) {
		$every    = array();
		$match  = array();
		if ( preg_match( '/(\d+)([A-Z]+)/', $weekdays, $match ) ) {
			$week = (int) $match[1];
			$every[ $week ] = $this->convert_weekdays( $match[2], $day_offset );
		}
		return $every;
	}

	/**
	 * Convert RRULE period into Calendar_Plus_Event_Rules_Every_Formatter format value
	 * @param string $period
	 *
	 * @return string
	 */
	private function convert_period( string $period ) {
		$frequencies = array(
			'WEEKLY'  => 'week',
			'DAILY'   => 'day',
			'MONTHLY' => 'month',
			'YEARLY'  => 'year',
		);
		return $frequencies[ $period ];
	}

	/**
	 * Parse exdate_array (parsed EXDATE property) values
	 * @param array $dates
	 * @param $local_tz
	 *
	 * @return array
	 */
	private function parse_excluded_dates( array $dates, $event_tz, $local_tz ) {
		$parsed = array();
		for( $i = 0; $i < count( $dates ); $i += 2 ) {
			$date = self::cast_date_timezones(
				$dates[ $i + 1 ][0],
				$event_tz,
				$local_tz
			);
			$parsed[] = date( 'Y-m-d', $date );
		}
		return $parsed;
	}

	/**
	 * Gets event iCal object UID
	 *
	 * Used for recurring event instances importing
	 *
	 * @throws Exception
	 *
	 * @param object $event iCal event object instance.
	 *
	 * @return string Event UID
	 */
	public function get_event_uid( $event ) {
		if ( ! is_object( $event ) ) {
			throw new Exception(
				__( 'Not an event object', 'calendar-plus' )
			);
		}

		$uid = $event->uid;
		if ( empty( $this->_import_recurring ) ) {
			// We are set to not import recurring instances.
			// UIDs remain constant.
			return $uid;
		}

		if ( ! $event->rrule && ! $event->recurrence_id ) {
			// Not a recurring event, return normal UID.
			return $uid;
		}

		$recurrence = $uid . $event->sequence;
		$idx = ! empty( $this->_recurring_increments[ $recurrence ] )
			? (int) $this->_recurring_increments[ $recurrence ]
			: 1;
		$uid .= '-' . $event->sequence . '-' . $idx;

		$this->_recurring_increments[ $recurrence ] = $idx + 1;
		return $uid;
	}

	/**
	 * Transform a date from one timezone to another
	 *
	 * @param string $date
	 * @param DateTimeZone $from_tz The $date timezone
	 * @param DateTimeZone $to_tz The timezone that we want to cast to
	 *
	 * @return int New timestamp
	 */
	private static function cast_date_timezones( $date, $from_tz, $to_tz ) {
		// Set first the date with the iCal timezone
		$date = date_create( $date, $from_tz );

		// Now cast to local timezone
		$date->setTimezone( $to_tz );

		//Its not easy to get time zone based timestamp. Hack was needed.
		return strtotime( $date->format( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Transform a date to given format without changing timezones.
	 *
	 * @param string $date
	 *
	 * @return int New timestamp
	 */
	private static function cast_date_without_timezones( $date ) {
		$date = date_create( $date );

		if ( ! $date ) {
			return '';
		}

		//Its not easy to get time zone based timestamp. Hack was needed.
		return strtotime( $date->format( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Casts a date to previous day without changing timezones.
	 *
	 * @param string $date
	 *
	 * @return int New timestamp
	 */
	private static function cast_previous_date_without_timezones( $date ) {
		$date = date_create( $date );

		if ( ! $date ) {
			return '';
		}

		//Its not easy to get time zone based timestamp. Hack was needed.
		return strtotime( $date->format( 'Y-m-d H:i:s' ) . ' -1 day' );
	}
}