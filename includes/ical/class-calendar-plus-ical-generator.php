<?php

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
	 * Class constructor
	 *
	 * @param array $event_ids
	 * @param bool  $future_events
	 */
	public function __construct( $event_ids, $future_events = false ) {
		$this->event_ids = array_map( 'absint', $event_ids );
		$this->future_events = $future_events;
	}

	/**
	 * Fetch the required event data from the database
	 *
	 * @return array
	 */
	protected function fetch_data() {
		global $wpdb;

		$current_datetime = current_time( 'timestamp' );

		$where = array();

		if ( $this->future_events ) {
			$from_date = date( 'Y-m-d', $current_datetime );
			$where[] = $wpdb->prepare( 'from_date >= %s', $from_date );
		}

		$where[] = 'event_id IN (' . implode( ',', $this->event_ids ) . ')';
		$where = implode( ' AND ', $where );

		return $wpdb->get_results( "
			SELECT event_id, from_date, from_time, until_date, until_time, series_number
			FROM $wpdb->calendarp_calendar
			WHERE $where
			ORDER BY event_id ASC, series_number ASC"
		);
	}

	/**
	 * Convert a GMT datetime string into iCal format
	 *
	 * @param string $datetime
	 *
	 * @return string
	 */
	public function convert_gmt_datetime( $datetime ) {
		return gmdate( self::ICAL_DATE_FORMAT, strtotime( $datetime ) );
	}

	/**
	 * Convert a local datetime string into iCal format
	 *
	 * @param string $datetime
	 *
	 * @return string
	 */
	public function convert_datetime( $datetime ) {
		return gmdate( self::ICAL_DATE_FORMAT, strtotime( gmdate( $datetime ) ) );
	}

	/**
	 * Convert a weekday number into its associated code
	 *
	 * @param int $weekday
	 *
	 * @return string
	 */
	public function convert_weekday( $weekday ) {
		return self::WEEKDAY_CODES[ absint( $weekday ) % 7 ];
	}

	/**
	 * Format an associative array of iCal parameters into the accepted format
	 *
	 * @param array $params
	 *
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
	 * Convert rich HTML content into properly formatted plain text
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	protected function convert_plaintext( $text ) {
		$text = strip_tags( html_entity_decode( $text ) );

		$text = str_replace( '\\', '\\\\', $text );
		$text = str_replace( "\n", '\n', $text );
		$text = str_replace( "\r", '', $text );
		$text = str_replace( ',', '\,', $text );
		$text = str_replace( ';', '\;', $text );

		return $text;
	}

	/**
	 * Generate the iCal section for an event
	 *
	 * @param Calendar_Plus_Event $event
	 * @param stdClass            $row
	 *
	 * @return string
	 */
	protected function generate_event( Calendar_Plus_Event $event, $row ) {
		$result['BEGIN'] = 'VEVENT';

		$from_date_string = $this->convert_datetime( $row->from_date . ' ' . $row->from_time );
		$until_date_string = $this->convert_datetime( $row->until_date . ' ' . $row->until_time );

		$publish_date_string = $this->convert_gmt_datetime( $event->post->post_date_gmt );
		$update_date_string = $this->convert_gmt_datetime( $event->post->post_modified_gmt );

		$result['UID'] = $event->ID . '-' . $row->series_number;
		$result['SUMMARY'] = $this->convert_plaintext( get_the_title( $event->ID ) );
		$result['DTSTART'] = $from_date_string;
		$result['DTEND'] = $until_date_string;

		$result['DTSTAMP'] = $publish_date_string;
		$result['LAST-MODIFIED'] = $update_date_string;
		$result['SEQUENCE'] = $row->series_number;

		if ( $location = $event->get_location() ) {
			$result['LOCATION'] = $location->get_full_address();
		}

		if ( 'recurrent' === $event->get_event_type() ) {
			$rules = $event->get_rules();
			$every = $rules['every'][0]['every'];
			$what = $rules['every'][0]['what'];

			if ( in_array( $what, array( 'dow', 'dom', 'week', 'day', 'month', 'year' ) ) ) {

				$from_date_string = $this->convert_datetime( $rules['dates'][0]['from'] . ' ' . $rules['times'][0]['from'] );
				$until_date_string = $this->convert_datetime( $rules['dates'][0]['until'] . ' ' . $rules['times'][0]['until'] );

				$result['UID'] = $event->ID;
				$result['DTSTART'] = $from_date_string;
				unset( $result['DTEND'] );

				$this->skip[] = $event->ID;

				if ( 'dow' === $what ) {
					$days = array();

					foreach ( $every as $weekday ) {
						if ( $code = $this->convert_weekday( $weekday ) ) {
							$days[] = $code;
						}
					}

					$result['RRULE'] = sprintf(
						'FREQ=WEEKLY;UNTIL=%s;WKST=SU;BYDAY=%s',
						$until_date_string, implode( ',', $days )
					);

				} elseif ( 'dom' === $what ) {
					$days = array();

					foreach ( $every as $week => $weekdays ) {
						foreach ( $weekdays as $weekday ) {
							if ( $code = $this->convert_weekday( $weekday ) ) {
								$days[] = $week . $code;
							}
						}
					}

					$result['RRULE'] = sprintf(
						'FREQ=MONTHLY;UNTIL=%s;WKST=SU;BYDAY=%s',
						$until_date_string, implode( ',', $days )
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
						$frequencies[ $what ], $every, $until_date_string
					);
				}
			}
		}

		$desc = $event->post->post_content;
		$desc = apply_filters( 'wptexturize', $desc );
		$desc = apply_filters( 'convert_smilies', $desc );
		$result['DESCRIPTION'] = $this->convert_plaintext( $desc );

		$result['END'] = 'VEVENT';
		return $this->format_params( $result );
	}

	/**
	 * Generate the iCal file contents
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

		// Core fields
		$output[] = 'BEGIN:VCALENDAR';
		$output[] = 'VERSION:2.0';
		$output[] = "PRODID:-//$site_name//NONSGML v1.0//EN";
		$output[] = 'CALSCALE:GREGORIAN';
		$output[] = 'METHOD:PUBLISH';

		foreach ( $results as $row ) {
			$event = calendarp_get_event( $row->event_id );

			if ( ! $event || in_array( $event->ID, $this->skip ) ) {
				continue;
			}

			$output[] = $this->generate_event( $event, $row );
		}

		$output[] = 'END:VCALENDAR';

		return implode( "\r\n", $output );
	}

	public function download_file( $filename = 'calendar' ) {
		$filename = sanitize_file_name( $filename . '.ics' );

		header( 'Content-type: text/calendar; charset=UTF-8' );
		header( sprintf( 'Content-Disposition: attachment; filename="%s"', $filename ) );

		echo $this->get();
		exit;
	}
}
