<?php
/**
 * Calendar Plus: includes/ical/class-calendar-plus-ical-generator.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class for generating iCal files from events
 *
 * @see https://tools.ietf.org/html/rfc5545
 */
class Calendar_Plus_iCal_Generator {


	/**
	 * List of event IDs to include in the generated iCal file
	 *
	 * @var array
	 */
	protected $event_ids;

	/**
	 * Whether to exclude
	 *
	 * @var bool
	 */
	protected $future_events;

	/**
	 * PHP date() format used for iCal date parameters
	 *
	 * @var string
	 */
	const ICAL_DATE_FORMAT = 'Ymd\THis\Z';

	/**
	 * PHP date() format used for iCal date-only parameters
	 *
	 * @var string
	 */
	const ICAL_DATE_ONLY_FORMAT = 'Ymd';

	/**
	 * List of iCal weekday codes
	 *
	 * @var array
	 */
	const WEEKDAY_CODES = array( 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' );

	/**
	 * List of event IDs to skip when generating sections
	 *
	 * @var array
	 */
	protected $skip = array();

	/**
	 * Class constructor.
	 *
	 * @param array $event_ids Event ids.
	 * @param bool  $future_events Future events.
	 */
	public function __construct( $event_ids, $future_events = false ) {
		$this->event_ids     = array_map( 'absint', $event_ids );
		$this->future_events = $future_events;
	}

	/**
	 * Fetch the required event data from the database.
	 *
	 * @return array
	 */
	protected function fetch_data() {
		global $wpdb;

		$current_datetime = calendarp_wall_time();

		if ( empty( $this->event_ids ) ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $this->event_ids ), '%d' ) );
		$sql          = "SELECT event_id, from_date, from_time, until_date, until_time, series_number FROM %i WHERE event_id IN ($placeholders)";
		$args         = array_merge( array( $wpdb->calendarp_calendar ), $this->event_ids );
		if ( $this->future_events ) {
			$sql   .= ' AND from_date >= %s';
			$args[] = gmdate( 'Y-m-d', $current_datetime );
		}
		$sql .= ' ORDER BY event_id ASC, series_number ASC';
		return calendarp_schedule_read( $sql, $args );
	}

	/**
	 * Convert a GMT datetime string into iCal format.
	 *
	 * @param string $datetime Datetime.
	 * @return string
	 */
	public function convert_gmt_datetime( $datetime ) {
		return gmdate( self::ICAL_DATE_FORMAT, strtotime( $datetime ) );
	}

	/**
	 * Convert a local datetime string into iCal format.
	 *
	 * @param string $datetime Datetime.
	 * @return string
	 */
	public function convert_datetime( $datetime ) {
		return gmdate( self::ICAL_DATE_FORMAT, strtotime( get_gmt_from_date( $datetime ) ) );
	}

	/**
	 * Convert a local date string into iCal date-only format, optionally adding a number of days to the date.
	 *
	 * @param string $date Date.
	 * @param int    $days_to_add Number of days to add to the date (used for end dates to make them inclusive).
	 * @return string
	 */
	public function convert_date( $date, $days_to_add = 0 ) {
		$converted_date = DateTimeImmutable::createFromFormat( '!Y-m-d', $date );

		if ( false === $converted_date ) {
			return $date;
		}

		if ( 0 !== (int) $days_to_add ) {
			$converted_date = $converted_date->modify( sprintf( '%+d day', (int) $days_to_add ) );
		}

		return $converted_date->format( self::ICAL_DATE_ONLY_FORMAT );
	}

	/**
	 * Convert a weekday number into its associated code.
	 *
	 * @param int $weekday Weekday.
	 * @return string
	 */
	public function convert_weekday( $weekday ) {
		return self::WEEKDAY_CODES[ absint( $weekday ) % 7 ];
	}

	/**
	 * Format an associative array of iCal parameters into the accepted format.
	 *
	 * @param array $params Params.
	 * @return string
	 */
	protected function format_params( $params ) {
		$result = array();

		foreach ( $params as $name => $param ) {
			$result[] = $name . ':' . $param;
		}

		return implode( "\r\n", $result );
	}

	/**
	 * Convert rich HTML content into properly formatted plain text.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	protected function convert_plaintext( $text ) {
		$text = wp_strip_all_tags( html_entity_decode( $text ) );

		$text = str_replace( '\\', '\\\\', $text );
		$text = str_replace( "\n", '\n', $text );
		$text = str_replace( "\r", '', $text );
		$text = str_replace( ',', '\,', $text );
		$text = str_replace( ';', '\;', $text );

		return $text;
	}

	/**
	 * Generate the iCal section for an event.
	 *
	 * @param Calendar_Plus_Event $event Event.
	 * @param stdClass            $row Row.
	 * @return string
	 */
	protected function generate_event( Calendar_Plus_Event $event, $row ) {
		$result['BEGIN'] = 'VEVENT';

		$from_date_string  = $this->convert_datetime( $row->from_date . ' ' . $row->from_time );
		$until_date_string = $this->convert_datetime( $row->until_date . ' ' . $row->until_time );

		$publish_date_string = $this->convert_gmt_datetime( $event->post->post_date_gmt );
		$update_date_string  = $this->convert_gmt_datetime( $event->post->post_modified_gmt );

		$result['UID']     = $event->ID . '-' . $row->series_number;
		$result['SUMMARY'] = $this->convert_plaintext( get_the_title( $event->ID ) );

		if ( $event->is_all_day_event() && 'recurrent' !== $event->get_event_type() ) {
			$result['DTSTART;VALUE=DATE'] = $this->convert_date( $row->from_date );
			$result['DTEND;VALUE=DATE']   = $this->convert_date( $row->until_date, 1 );
		} else {
			$result['DTSTART'] = $from_date_string;
			$result['DTEND']   = $until_date_string;
		}

		$result['DTSTAMP']       = $publish_date_string;
		$result['LAST-MODIFIED'] = $update_date_string;
		$result['SEQUENCE']      = $row->series_number;

		$location = $event->get_location();
		if ( $location ) {
			$result['LOCATION'] = $location->get_full_address();
		}

		if ( 'recurrent' === $event->get_event_type() ) {
			$rules = $event->get_rules();
			$every = $rules['every'][0]['every'];
			$what  = $rules['every'][0]['what'];

			if ( in_array( $what, array( 'dow', 'dom', 'week', 'day', 'month', 'year' ), true ) ) {

				$from_date_string  = $this->convert_datetime( $rules['dates'][0]['from'] . ' ' . $rules['times'][0]['from'] );
				$until_date_string = $this->convert_datetime( $rules['dates'][0]['until'] . ' ' . $rules['times'][0]['until'] );

				$result['UID'] = $event->ID;
				if ( $event->is_all_day_event() ) {
					unset( $result['DTSTART'] );
					$result['DTSTART;VALUE=DATE'] = $this->convert_date( $rules['dates'][0]['from'] );
				} else {
					$result['DTSTART'] = $from_date_string;
				}
				unset( $result['DTEND'] );

				$this->skip[] = $event->ID;

				if ( 'dow' === $what ) {
					$days = array();

					foreach ( $every as $weekday ) {
						$code = $this->convert_weekday( $weekday );
						if ( $code ) {
							$days[] = $code;
						}
					}

					$result['RRULE'] = sprintf(
						'FREQ=WEEKLY;UNTIL=%s;WKST=SU;BYDAY=%s',
						$until_date_string,
						implode( ',', $days )
					);

				} elseif ( 'dom' === $what ) {
					$days = array();

					foreach ( $every as $week => $weekdays ) {
						foreach ( $weekdays as $weekday ) {
							$code = $this->convert_weekday( $weekday );
							if ( $code ) {
								$days[] = $week . $code;
							}
						}
					}

					$result['RRULE'] = sprintf(
						'FREQ=MONTHLY;UNTIL=%s;WKST=SU;BYDAY=%s',
						$until_date_string,
						implode( ',', $days )
					);

				} else {

					$frequencies = array(
						'week'  => 'WEEKLY',
						'day'   => 'DAILY',
						'month' => 'MONTHLY',
						'year'  => 'YEARLY',
					);

					$result['RRULE'] = sprintf(
						'FREQ=%s;INTERVAL=%d;UNTIL=%s',
						$frequencies[ $what ],
						$every,
						$until_date_string
					);
				}
			}
		}

		$desc                  = $event->post->post_content;
		$desc                  = wptexturize( $desc );
		$desc                  = convert_smilies( $desc );
		$result['DESCRIPTION'] = $this->convert_plaintext( $desc );

		$result['END'] = 'VEVENT';
		return $this->format_params( $result );
	}

	/**
	 * Generate the iCal file contents.
	 *
	 * @return string
	 */
	public function get() {

		if ( empty( $this->event_ids ) ) {
			return '';
		}

		$results = $this->fetch_data();

		$site_name = get_bloginfo( 'name' );

		$output = array();

		// Core fields.
		$output[] = 'BEGIN:VCALENDAR';
		$output[] = 'VERSION:2.0';
		$output[] = "PRODID:-//$site_name//NONSGML v1.0//EN";
		$output[] = 'CALSCALE:GREGORIAN';
		$output[] = 'METHOD:PUBLISH';

		foreach ( $results as $row ) {
			$event = calendarp_get_event( $row->event_id );

			if ( ! $event || in_array( $event->ID, $this->skip, true ) ) {
				continue;
			}

			$output[] = $this->generate_event( $event, $row );
		}

		$output[] = 'END:VCALENDAR';

		return implode( "\r\n", $output );
	}

	/**
	 * Handle download file.
	 *
	 * @param string $filename Filename.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function download_file( $filename = 'calendar' ) {
		$filename = sanitize_file_name( $filename . '.ics' );

		nocache_headers();
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-type: text/calendar; charset=UTF-8' );
		header( sprintf( 'Content-Disposition: attachment; filename="%s"', $filename ) );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Validated and escaped iCalendar property values in a text/calendar download; HTML escaping would corrupt the file.
		echo $this->get();
		exit;
	}
}
