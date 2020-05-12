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
	 * Calendar_Plus_iCal_Sync constructor.
	 *
	 * @param string $ical_contents content of an iCal file
	 * @param bool $import_recurring Whether or not to import recurring event instances.
	 */
	public function __construct( $ical_contents, $import_recurring = false ) {
		$this->contents = $ical_contents;
		$this->_import_recurring = ( bool ) $import_recurring;
	}

	/**
	 * Parse the content of the iCal file
	 */
	private function init() {
		$this->ical = new ICal( $this->contents, array(
			'defaultSpan'           => 2,     // Default value
			'defaultTimeZone'       => 'UTC',
			'defaultWeekStart'      => 'MO',  // Default value
			'skipRecurrence'        => false, // Default value
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

			$events[] = array(
				'post_status'   => strtoupper( $_event->status ) === 'CANCELLED' ? 'trash' : 'publish',
				'post_content'  => $content,
				'post_title'    => wp_kses( $_event->summary, wp_kses_allowed_html( 'post' ) ),
				'from'          => self::cast_date_timezones( $_event->dtstart, $event_tz, $local_tz ),
				'to'            => self::cast_date_timezones( $_event->dtend, $event_tz, $local_tz ),
				'last_updated' => self::cast_date_timezones( $_event->lastmodified, $event_tz, $local_tz ),
				'uid'           => $this->get_event_uid( $_event ),
				'location'      => $_event->location,
				'categories'    => isset( $_event->categories ) ? array_map( 'trim', explode( ',', $_event->categories ) ) : array(),
			);
		}

		return $events;
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

		if ( empty( $event->rrule ) ) {
			// Not a recurring event, return normal UID.
			return $uid;
		}

		$recurrence = $uid . md5( $event->rrule );
		$idx = ! empty( $this->_recurring_increments[ $recurrence ] )
			? (int) $this->_recurring_increments[ $recurrence ]
			: 1;
		$uid .= "-{$idx}";

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
	 */
	public function get_ms_timezone( $tz = false ) {
		$mstz = [
			'Dateline Standard Time' => '(GMT-12:00) International Date Line West',
			'Samoa Standard Time' => '(GMT-11:00) Midway Island, Samoa',
			'Hawaiian Standard Time' => '(GMT-10:00) Hawaii',
			'Alaskan Standard Time' => '(GMT-09:00) Alaska',
			'Pacific Standard Time' => '(GMT-08:00) Pacific Time (US and Canada); Tijuana',
			'Mountain Standard Time' => '(GMT-07:00) Mountain Time (US and Canada)',
			'Mexico Standard Time 2' => '(GMT-07:00) Chihuahua, La Paz, Mazatlan',
			'U.S. Mountain Standard Time' => '(GMT-07:00) Arizona',
			'Central Standard Time' => '(GMT-06:00) Central Time (US and Canada)',
			'Canada Central Standard Time' => '(GMT-06:00) Saskatchewan',
			'Mexico Standard Time' => '(GMT-06:00) Guadalajara, Mexico City, Monterrey',
			'Central America Standard Time' => '(GMT-06:00) Central America',
			'Eastern Standard Time' => '(GMT-05:00) Eastern Time (US and Canada)',
			'U.S. Eastern Standard Time' => '(GMT-05:00) Indiana (East)',
			'S.A. Pacific Standard Time' => '(GMT-05:00) Bogota, Lima, Quito',
			'Atlantic Standard Time' => '(GMT-04:00) Atlantic Time (Canada)',
			'S.A. Western Standard Time' => '(GMT-04:00) Georgetown, La Paz, San Juan',
			'Pacific S.A. Standard Time' => '(GMT-04:00) Santiago',
			'Newfoundland and Labrador Standard Time' => '(GMT-03:30) Newfoundland',
			'E. South America Standard Time' => '(GMT-03:00) Brasilia',
			'S.A. Eastern Standard Time' => '(GMT-03:00) Georgetown',
			'Greenland Standard Time' => '(GMT-03:00) Greenland',
			'Mid-Atlantic Standard Time' => '(GMT-02:00) Mid-Atlantic',
			'Azores Standard Time' => '(GMT-01:00) Azores',
			'Cape Verde Standard Time' => '(GMT-01:00) Cape Verde Islands',
			'GMT Standard Time' => '(GMT) Greenwich Mean Time: Dublin, Edinburgh, Lisbon, London',
			'Greenwich Standard Time' => '(GMT) Monrovia, Reykjavik',
			'Central Europe Standard Time' => '(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague',
			'Central European Standard Time' => '(GMT+01:00) Sarajevo, Skopje, Warsaw, Zagreb',
			'Romance Standard Time' => '(GMT+01:00) Brussels, Copenhagen, Madrid, Paris',
			'W. Europe Standard Time' => '(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna',
			'W. Central Africa Standard Time' => '(GMT+01:00) West Central Africa',
			'E. Europe Standard Time' => '(GMT+02:00) Minsk',
			'Egypt Standard Time' => '(GMT+02:00) Cairo',
			'FLE Standard Time' => '(GMT+02:00) Helsinki, Kiev, Riga, Sofia, Tallinn, Vilnius',
			'GTB Standard Time' => '(GMT+02:00) Athens, Bucharest, Istanbul',
			'Israel Standard Time' => '(GMT+02:00) Jerusalem',
			'South Africa Standard Time' => '(GMT+02:00) Harare, Pretoria',
			'Russian Standard Time' => '(GMT+03:00) Moscow, St. Petersburg, Volgograd',
			'Arab Standard Time' => '(GMT+03:00) Kuwait, Riyadh',
			'E. Africa Standard Time' => '(GMT+03:00) Nairobi',
			'Arabic Standard Time' => '(GMT+03:00) Baghdad',
			'Iran Standard Time' => '(GMT+03:30) Tehran',
			'Arabian Standard Time' => '(GMT+04:00) Abu Dhabi, Muscat',
			'Caucasus Standard Time' => '(GMT+04:00) Baku, Tbilisi, Yerevan',
			'Transitional Islamic State of Afghanistan Standard Time' => '(GMT+04:30) Kabul',
			'Ekaterinburg Standard Time' => '(GMT+05:00) Ekaterinburg',
			'West Asia Standard Time' => '(GMT+05:00) Tashkent',
			'India Standard Time' => '(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi',
			'Nepal Standard Time' => '(GMT+05:45) Kathmandu',
			'Central Asia Standard Time' => '(GMT+06:00) Astana, Dhaka',
			'Sri Lanka Standard Time' => '(GMT+06:00) Sri Jayawardenepura',
			'N. Central Asia Standard Time' => '(GMT+06:00) Almaty, Novosibirsk',
			'Myanmar Standard Time' => '(GMT+06:30) Yangon (Rangoon)',
			'S.E. Asia Standard Time' => '(GMT+07:00) Bangkok, Hanoi, Jakarta',
			'North Asia Standard Time' => '(GMT+07:00) Krasnoyarsk',
			'China Standard Time' => '(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi',
			'Singapore Standard Time' => '(GMT+08:00) Kuala Lumpur, Singapore',
			'Taipei Standard Time' => '(GMT+08:00) Taipei',
			'W. Australia Standard Time' => '(GMT+08:00) Perth',
			'North Asia East Standard Time' => '(GMT+08:00) Irkutsk, Ulaanbaatar',
			'Korea Standard Time' => '(GMT+09:00) Seoul',
			'Tokyo Standard Time' => '(GMT+09:00) Osaka, Sapporo, Tokyo',
			'Yakutsk Standard Time' => '(GMT+09:00) Yakutsk',
			'A.U.S. Central Standard Time' => '(GMT+09:30) Darwin',
			'Cen. Australia Standard Time' => '(GMT+09:30) Adelaide',
			'A.U.S. Eastern Standard Time' => '(GMT+10:00) Canberra, Melbourne, Sydney',
			'E. Australia Standard Time' => '(GMT+10:00) Brisbane',
			'Tasmania Standard Time' => '(GMT+10:00) Hobart',
			'Vladivostok Standard Time' => '(GMT+10:00) Vladivostok',
			'West Pacific Standard Time' => '(GMT+10:00) Guam, Port Moresby',
			'Central Pacific Standard Time' => '(GMT+11:00) Magadan, Solomon Islands, New Caledonia',
			'Fiji Islands Standard Time' => '(GMT+12:00) Fiji, Kamchatka, Marshall Is.',
			'New Zealand Standard Time' => '(GMT+12:00) Auckland, Wellington',
			'Tonga Standard Time' => '(GMT+13:00) Nuku\'alofa',
			'Azerbaijan Standard Time ' => '(GMT-03:00) Buenos Aires',
			'Middle East Standard Time' => '(GMT+02:00) Beirut',
			'Jordan Standard Time' => '(GMT+02:00) Amman',
			'Central Standard Time (Mexico)' => '(GMT-06:00) Guadalajara, Mexico City, Monterrey - New',
			'Mountain Standard Time (Mexico)' => '(GMT-07:00) Chihuahua, La Paz, Mazatlan - New',
			'Pacific Standard Time (Mexico)' => '(GMT-08:00) Tijuana, Baja California',
			'Namibia Standard Time' => '(GMT+02:00) Windhoek',
			'Georgian Standard Time' => '(GMT+03:00) Tbilisi',
			'Central Brazilian Standard Time' => '(GMT-04:00) Manaus',
			'Montevideo Standard Time' => '(GMT-03:00) Montevideo',
			'Armenian Standard Time' => '(GMT+04:00) Yerevan',
			'Venezuela Standard Time' => '(GMT-04:30) Caracas',
			'Argentina Standard Time' => '(GMT-03:00) Buenos Aires',
			'Morocco Standard Time' => '(GMT) Casablanca',
			'Pakistan Standard Time' => '(GMT+05:00) Islamabad, Karachi',
			'Mauritius Standard Time' => '(GMT+04:00) Port Louis',
			'UTC' => '(GMT) Coordinated Universal Time',
			'Paraguay Standard Time' => '(GMT-04:00) Asuncion',
			'Kamchatka Standard Time' => '(GMT+12:00) Petropavlovsk-Kamchatsky',
		];
		if ( empty( $tz ) ) {
			return $mstz;
		}

		return array_key_exists( $tz, $mstz ) ? $mstz[ $tz ] : '';
	}
}
