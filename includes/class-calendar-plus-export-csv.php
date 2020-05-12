<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for exporting multiple events in CSV format
 */
class Calendar_Plus_Export_CSV {

	/**
	 * List of events to export
	 * @var array
	 */
	private $events;

	/**
	 * Columns to include in the export file
	 * @var array
	 */
	private $columns;

	/**
	 * Class constructor
	 *
	 * @param array $events  List of Calendar_Event instances to export
	 * @param array $columns List of columns to include in the export file
	 */
	public function __construct( $events = array(), $columns = array() ) {

		$this->events = empty( $events ) ? calendarp_get_events( array( 'posts_per_page' => - 1 ) ) : $events;
		$this->columns = empty( $columns ) ? $this->get_supported_columns() : $columns;
	}

	/**
	 * Retrieve a list of columns supported by this class
	 * @return array
	 */
	public static function get_supported_columns() {
		return array(
			'id',
			'type',
			'from_date',
			'until_date',
			'from_time',
			'until_time',
			'recurring_every',
			'title',
			'categories',
			'tags',
			'location',
			'description',
		);
	}

	/**
	 * Retrieve the list of columns
	 * @return array
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * Retrieve the list of events
	 * @return array
	 */
	public function get_events() {
		return $this->events;
	}

	/**
	 * Construct the value for a specific column and an event
	 *
	 * @param string              $column
	 * @param Calendar_Plus_Event $event
	 *
	 * @return string
	 */
	private function get_column_data( $column, $event ) {

		$event_type = $event->get_event_type();
		$event_type = ( 'datespan' === $event_type || 'recurrent' === $event_type ) ? $event_type : 'standard';

		switch ( $column ) {

			case 'id':
				return $event->ID;

			case 'title':
				return $event->post->post_title;

			case 'description':
				return $event->post->post_content;

			case 'type':
				return $event_type;

			case 'location':
				$location = $event->get_location();

				return $location ? $location->post->post_title : '';

			case 'tags':
			case 'categories':
				$valid_taxonomies = array( 'tags' => 'calendar_event_tag', 'categories' => 'calendar_event_category' );
				$taxonomy = $valid_taxonomies[ $column ];

				$terms = wp_get_object_terms( $event->ID, $taxonomy, array( 'fields' => 'names' ) );

				return join( ', ', $terms );

			case 'from_date':
			case 'until_date':
			case 'from_time':
			case 'until_time':
				$parts = explode( '_', $column );
				$rules = $event->get_rules();

				if ( 'standard' === $event_type || 'datespan' === $event_type ) {
					return isset( $rules[ $event_type ][0][ $column ] ) ?
						$rules[ $event_type ][0][ $column ] : '';
				}

				if ( 'recurrent' === $event_type ) {
					return isset( $rules[ $parts[1] . 's' ][0][ $parts[0] ] ) ?
						$rules[ $parts[1] . 's' ][0][ $parts[0] ] : '';
				}

				break;

			case 'recurring_every':
				if ( 'recurrent' !== $event->get_event_type() ) {
					break;
				}

				/** @var WP_Locale $wp_locale */
				global $wp_locale;

				$rules = $event->get_rules();
				$every = $rules['every'][0]['every'];
				$what = $rules['every'][0]['what'];

				// Deal with simple recurrence formats first

				$formats = array(
					'week'  => _n( '%d week', '%d weeks', $every, 'calendar-plus' ),
					'month' => _n( '%d month', '%d months', $every, 'calendar-plus' ),
					'year'  => _n( '%d year', '%d years', $every, 'calendar-plus' ),
				);

				if ( isset( $formats[ $what ] ) ) {
					return sprintf( $formats[ $what ], number_format_i18n( $every ) );
				}

				if ( 'dom' !== $what && 'dow' !== $what ) {
					break;
				}

				// Otherwise, more work is required to construct a string

				if ( 'dom' === $what ) {
					$ord_nums = array(
						'',
						__( '1st', 'calendar-plus' ),
						__( '2nd', 'calendar-plus' ),
						__( '3rd', 'calendar-plus' ),
						__( '4th', 'calendar-plus' ),
						__( '5th', 'calendar-plus' ),
					);

				} else {
					$every = array( $every );
				}

				$days = array();
				foreach ( $every as $nth => $dows ) {
					foreach ( $dows as $dow ) {
						$weekday = $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( $dow % 7 ) );
						$days[] = isset( $ord_nums ) ? ( $ord_nums[ $nth ] . ' ' . $weekday ) : $weekday;
					}
				}

				return join( ', ', $days );
		}

		return '';
	}

	/**
	 * Build a row of data for an event
	 *
	 * @param Calendar_Plus_Event $event
	 *
	 * @return array
	 */
	private function generate_row( $event ) {
		$row = array();

		foreach ( $this->columns as $column ) {
			$row[ $column ] = $this->get_column_data( $column, $event );
		}

		return $row;
	}

	/**
	 * Retrieve the filename of the export file
	 * @return string
	 */
	public function get_filename() {
		$filename = sprintf( 'calendar_events.%s.csv', date( 'Y-m-d' ) );
		return sanitize_file_name( apply_filters( 'calendar_plus_export_filename', $filename, $this ) );
	}

	/**
	 * Generate the export file
	 */
	public function export() {
		header( 'Content-Type: application/csv' );
		header( sprintf( 'Content-Disposition: attachment; filename="%s"', $this->get_filename() ) );

		$handle = fopen( 'php://output', 'w' );
		fputcsv( $handle, $this->columns );

		foreach ( $this->get_events() as $event ) {
			fputcsv( $handle, $this->generate_row( $event ) );
		}

		fclose( $handle );
		exit;
	}
}
