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
	 * Gets iCal calendar timezone
	 *
	 * Extracted because:
	 * 1) UTC fallbacks might not be correct
	 * 2) Microsoft Outlook doesn't use the TZs PHP understands.
	 *
	 * @return object DateTimeZone instance
	 */
	public function get_ical_tz() {
		$defaultTz = $this->ical->defaultTimeZone;
		$this->ical->defaultTimeZone = -1;

		$raw = $this->ical->calendarTimeZone();
		$tz = $raw !== -1
			? $raw
			: $this->get_ical_ms_tz();

		$this->ical->defaultTimeZone = $defaultTz;
		return empty( $tz )
			? new DateTimeZone( $defaultTz )
			: new DateTimeZone( $tz );
	}

	/**
	 * Low-level iCal timezone parsing
	 *
	 * Used as fallback when normal iCal parser would fail.
	 * This is so that we can support more timezone offsets.
	 *
	 * See https://github.com/u01jmg3/ics-parser/issues/244
	 *
	 * @return string
	 */
	public function get_ical_ms_tz() {
		if (isset($this->ical->cal['VCALENDAR']['X-WR-TIMEZONE'])) {
			$timeZone = $this->ical->cal['VCALENDAR']['X-WR-TIMEZONE'];
		} elseif (isset($this->ical->cal['VTIMEZONE']['TZID'])) {
			$timeZone = $this->ical->cal['VTIMEZONE']['TZID'];
		} else {
			$timeZone = $this->ical->defaultTimeZone;
		}
		if ( stristr( $timeZone, 'UTC' ) !== false ) {
			return $this->ical->calendarTimeZone();
		}
		return $this->get_ms_timezone( $timeZone );
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
		// This is the *actual* calendar timezone
		$mod_calendar_tz = $this->get_ical_tz();
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

			$now = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
			$event_tz = $calendar_tz->getOffset( $now ) !== $mod_calendar_tz->getOffset( $now )
				? $mod_calendar_tz
				: $calendar_tz;

			//dtstart has to be set but, dtend not always.
			$from = self::cast_date_timezones( $_event->dtstart, $event_tz, $local_tz );
			if( isset( $_event->dtend ) && $_event->dtend ) {
				$to = self::cast_date_timezones( $_event->dtend, $event_tz, $local_tz );
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
				'last_updated' => self::cast_date_timezones( $_event->lastmodified, $event_tz, $local_tz ),
				'uid'           => $this->get_event_uid( $_event ),
				'location'      => $_event->location,
				'categories'    => isset( $_event->categories ) ? array_map( 'trim', explode( ',', $_event->categories ) ) : array(),
			);
			if ( $_event->rrule ) {
				$rules   = array();
				$rrule   = $this->parse_rrule( $_event->rrule );

				$start_date = null;
				if ( ! empty( $_event->dtstart ) ) {
					$start_date = $this->create_date_from_ical_format( $_event->dtstart, $event_tz, $local_tz );
				}

				$end_date = null;
				if ( ! empty( $rrule['UNTIL'] ) ) {
					$end_date = $this->create_date_from_ical_format( $rrule['UNTIL'], $event_tz, $local_tz );
				} else {
					$end_date = $start_date;
				}

				if ( $this->_exlude_past === -1 && $end_date?->getTimestamp() < time() ) {
					continue;
				}

				$rules[] = $this->build_date_rules( $start_date, $end_date );

				$end_time = null;
				if ( ! empty( $_event->dtend ) ) {
					$end_time = $this->create_date_from_ical_format( $_event->dtend, $event_tz, $local_tz );
				}
				$rules[] = $this->build_time_rules( $start_date, $end_time );

				// Shift selected day of week when
				// user time is day ahead of server time or before it
				$day_offset = 0;
				if ( isset( $start_date ) ) {
					$from       = date_create( $_event->dtstart, $event_tz );
					$day_offset = (int) $start_date->format( 'd' ) - (int) $from->format( 'd' );
				}
				$rules[] = $this->build_rrule( $rrule, $day_offset );

				$event_data['rrules'] = $rules;
			}
			$events[] = $event_data;
		}

		return $events;
	}

	private function parse_rrule( string $rule ) {
		$options = explode( ';', $rule );
		foreach ( $options as $key => $option ) {
			$option_data = mb_split( '=', $option );
			$options[ $option_data[0] ] = $option_data[1];

			unset( $options[ $key ] );
		}
		return $options;
	}

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

	private function build_date_rules( $start_date, $end_date ): array {
		$rule = array( 'rule_type' => 'dates' );
		if ( $start_date instanceof DateTime ) {
			$rule['from'] = $start_date->format( 'Y-m-d' );
		}
		if ( $end_date instanceof DateTime ) {
			$rule['until'] = $end_date->format( 'Y-m-d' );
		}

		return $rule;
	}

	private function build_time_rules( $start_date, $end_date ): array {
		$rule = array( 'rule_type' => 'times' );

		if ( $start_date instanceof DateTime ) {
			$rule['from'] = $start_date->format( 'H:i:s' );
		}
		if ( $end_date instanceof DateTime ) {
			$rule['until'] = $end_date->format( 'H:i:s' );
		}

		return $rule;
	}

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

	private function convert_weekdays( $days_str, $day_offset ) {
		$days_of_week = mb_split( ',', $days_str );
		return array_map( function ( $day ) use ( $day_offset ) {
			$idx = array_search( $day, Calendar_Plus_iCal_Generator::WEEKDAY_CODES );
			$idx += $day_offset;
			if ( $idx === 0 ) {
				$idx = 7;
			} else {
				$idx = $idx % 7;
			}
			return $idx;
		}, $days_of_week );
	}

	private function convert_multiple_weeks_days( $weekdays, $day_offset ) {
		$every    = array();
		$match  = array();
		if ( preg_match( '/(\d+)([A-Z]+)/', $weekdays, $match ) ) {
			$week = (int) $match[1];
			$every[ $week ] = $this->convert_weekdays( $match[2], $day_offset );
		}
		return $every;
	}

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
	 * Static list on Microsoft timezones with their conversion counterparts.
	 *
	 * @param string $tz (Optional) timezone.
	 *
	 * @return array|string Map of all timezones if no specific timezone, otherwise a timezone string which can be empty.
	 * ref: https://github.com/fruux/sabre-vobject/blob/master/lib/timezonedata/windowszones.php#L60
	 */
	public function get_ms_timezone( $tz = false ) {
		$mstz = [
			'AUS Central Standard Time' => 'Australia/Darwin',
			'AUS Eastern Standard Time' => 'Australia/Sydney',
			'Afghanistan Standard Time' => 'Asia/Kabul',
			'Alaskan Standard Time' => 'America/Anchorage',
			'Aleutian Standard Time' => 'America/Adak',
			'Altai Standard Time' => 'Asia/Barnaul',
			'Arab Standard Time' => 'Asia/Riyadh',
			'Arabian Standard Time' => 'Asia/Dubai',
			'Arabic Standard Time' => 'Asia/Baghdad',
			'Argentina Standard Time' => 'America/Buenos_Aires',
			'Astrakhan Standard Time' => 'Europe/Astrakhan',
			'Atlantic Standard Time' => 'America/Halifax',
			'Aus Central W. Standard Time' => 'Australia/Eucla',
			'Azerbaijan Standard Time' => 'Asia/Baku',
			'Azores Standard Time' => 'Atlantic/Azores',
			'Bahia Standard Time' => 'America/Bahia',
			'Bangladesh Standard Time' => 'Asia/Dhaka',
			'Belarus Standard Time' => 'Europe/Minsk',
			'Bougainville Standard Time' => 'Pacific/Bougainville',
			'Canada Central Standard Time' => 'America/Regina',
			'Cape Verde Standard Time' => 'Atlantic/Cape_Verde',
			'Caucasus Standard Time' => 'Asia/Yerevan',
			'Cen. Australia Standard Time' => 'Australia/Adelaide',
			'Central America Standard Time' => 'America/Guatemala',
			'Central Asia Standard Time' => 'Asia/Almaty',
			'Central Brazilian Standard Time' => 'America/Cuiaba',
			'Central Europe Standard Time' => 'Europe/Budapest',
			'Central European Standard Time' => 'Europe/Warsaw',
			'Central Pacific Standard Time' => 'Pacific/Guadalcanal',
			'Central Standard Time' => 'America/Chicago',
			'Central Standard Time (Mexico)' => 'America/Mexico_City',
			'Chatham Islands Standard Time' => 'Pacific/Chatham',
			'China Standard Time' => 'Asia/Shanghai',
			'Cuba Standard Time' => 'America/Havana',
			'Dateline Standard Time' => 'Etc/GMT+12',
			'E. Africa Standard Time' => 'Africa/Nairobi',
			'E. Australia Standard Time' => 'Australia/Brisbane',
			'E. Europe Standard Time' => 'Europe/Chisinau',
			'E. South America Standard Time' => 'America/Sao_Paulo',
			'Easter Island Standard Time' => 'Pacific/Easter',
			'Eastern Standard Time' => 'America/New_York',
			'Eastern Standard Time (Mexico)' => 'America/Cancun',
			'Egypt Standard Time' => 'Africa/Cairo',
			'Ekaterinburg Standard Time' => 'Asia/Yekaterinburg',
			'FLE Standard Time' => 'Europe/Kiev',
			'Fiji Standard Time' => 'Pacific/Fiji',
			'GMT Standard Time' => 'Europe/London',
			'GTB Standard Time' => 'Europe/Bucharest',
			'Georgian Standard Time' => 'Asia/Tbilisi',
			'Greenland Standard Time' => 'America/Godthab',
			'Greenwich Standard Time' => 'Atlantic/Reykjavik',
			'Haiti Standard Time' => 'America/Port-au-Prince',
			'Hawaiian Standard Time' => 'Pacific/Honolulu',
			'India Standard Time' => 'Asia/Calcutta',
			'Iran Standard Time' => 'Asia/Tehran',
			'Israel Standard Time' => 'Asia/Jerusalem',
			'Jordan Standard Time' => 'Asia/Amman',
			'Kaliningrad Standard Time' => 'Europe/Kaliningrad',
			'Korea Standard Time' => 'Asia/Seoul',
			'Libya Standard Time' => 'Africa/Tripoli',
			'Line Islands Standard Time' => 'Pacific/Kiritimati',
			'Lord Howe Standard Time' => 'Australia/Lord_Howe',
			'Magadan Standard Time' => 'Asia/Magadan',
			'Magallanes Standard Time' => 'America/Punta_Arenas',
			'Marquesas Standard Time' => 'Pacific/Marquesas',
			'Mauritius Standard Time' => 'Indian/Mauritius',
			'Middle East Standard Time' => 'Asia/Beirut',
			'Montevideo Standard Time' => 'America/Montevideo',
			'Morocco Standard Time' => 'Africa/Casablanca',
			'Mountain Standard Time' => 'America/Denver',
			'Mountain Standard Time (Mexico)' => 'America/Chihuahua',
			'Myanmar Standard Time' => 'Asia/Rangoon',
			'N. Central Asia Standard Time' => 'Asia/Novosibirsk',
			'Namibia Standard Time' => 'Africa/Windhoek',
			'Nepal Standard Time' => 'Asia/Katmandu',
			'New Zealand Standard Time' => 'Pacific/Auckland',
			'Newfoundland Standard Time' => 'America/St_Johns',
			'Norfolk Standard Time' => 'Pacific/Norfolk',
			'North Asia East Standard Time' => 'Asia/Irkutsk',
			'North Asia Standard Time' => 'Asia/Krasnoyarsk',
			'North Korea Standard Time' => 'Asia/Pyongyang',
			'Omsk Standard Time' => 'Asia/Omsk',
			'Pacific SA Standard Time' => 'America/Santiago',
			'Pacific Standard Time' => 'America/Los_Angeles',
			'Pacific Standard Time (Mexico)' => 'America/Tijuana',
			'Pakistan Standard Time' => 'Asia/Karachi',
			'Paraguay Standard Time' => 'America/Asuncion',
			'Qyzylorda Standard Time' => 'Asia/Qyzylorda',
			'Romance Standard Time' => 'Europe/Paris',
			'Russia Time Zone 10' => 'Asia/Srednekolymsk',
			'Russia Time Zone 11' => 'Asia/Kamchatka',
			'Russia Time Zone 3' => 'Europe/Samara',
			'Russian Standard Time' => 'Europe/Moscow',
			'SA Eastern Standard Time' => 'America/Cayenne',
			'SA Pacific Standard Time' => 'America/Bogota',
			'SA Western Standard Time' => 'America/La_Paz',
			'SE Asia Standard Time' => 'Asia/Bangkok',
			'Saint Pierre Standard Time' => 'America/Miquelon',
			'Sakhalin Standard Time' => 'Asia/Sakhalin',
			'Samoa Standard Time' => 'Pacific/Apia',
			'Sao Tome Standard Time' => 'Africa/Sao_Tome',
			'Saratov Standard Time' => 'Europe/Saratov',
			'Singapore Standard Time' => 'Asia/Singapore',
			'South Africa Standard Time' => 'Africa/Johannesburg',
			'Sri Lanka Standard Time' => 'Asia/Colombo',
			'Sudan Standard Time' => 'Africa/Khartoum',
			'Syria Standard Time' => 'Asia/Damascus',
			'Taipei Standard Time' => 'Asia/Taipei',
			'Tasmania Standard Time' => 'Australia/Hobart',
			'Tocantins Standard Time' => 'America/Araguaina',
			'Tokyo Standard Time' => 'Asia/Tokyo',
			'Tomsk Standard Time' => 'Asia/Tomsk',
			'Tonga Standard Time' => 'Pacific/Tongatapu',
			'Transbaikal Standard Time' => 'Asia/Chita',
			'Turkey Standard Time' => 'Europe/Istanbul',
			'Turks And Caicos Standard Time' => 'America/Grand_Turk',
			'US Eastern Standard Time' => 'America/Indianapolis',
			'US Mountain Standard Time' => 'America/Phoenix',
			'UTC' => 'Etc/GMT',
			'UTC+12' => 'Etc/GMT-12',
			'UTC+13' => 'Etc/GMT-13',
			'UTC-02' => 'Etc/GMT+2',
			'UTC-08' => 'Etc/GMT+8',
			'UTC-09' => 'Etc/GMT+9',
			'UTC-11' => 'Etc/GMT+11',
			'Ulaanbaatar Standard Time' => 'Asia/Ulaanbaatar',
			'Venezuela Standard Time' => 'America/Caracas',
			'Vladivostok Standard Time' => 'Asia/Vladivostok',
			'Volgograd Standard Time' => 'Europe/Volgograd',
			'W. Australia Standard Time' => 'Australia/Perth',
			'W. Central Africa Standard Time' => 'Africa/Lagos',
			'W. Europe Standard Time' => 'Europe/Berlin',
			'W. Mongolia Standard Time' => 'Asia/Hovd',
			'West Asia Standard Time' => 'Asia/Tashkent',
			'West Bank Standard Time' => 'Asia/Hebron',
			'West Pacific Standard Time' => 'Pacific/Port_Moresby',
			'Yakutsk Standard Time' => 'Asia/Yakutsk',
			'Yukon Standard Time' => 'America/Whitehorse',
		];
		if ( empty( $tz ) ) {
			return $mstz;
		}

		return array_key_exists( $tz, $mstz ) ? $mstz[ $tz ] : '';
	}
}
