<?php
/**
 * Calendar Plus: includes/calendar/class-calendar-plus-calendar.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


require_once calendarp_get_plugin_dir() . 'includes/calendar/calendar-templates.php';

/** Calendar component: Calendar_Plus_Timespan. */
class Calendar_Plus_Timespan {
	/**
	 * From date in Unix Timestamp
	 *
	 * @var Integer
	 */
	private $from;

	/**
	 * To date in Unix Timestamp
	 *
	 * @var Integer
	 */
	private $to;

	/**
	 * Initialize this calendar component.
	 *
	 * @param bool $from From.
	 * @param bool $to To.
	 */
	public function __construct( $from = false, $to = false ) {
		if ( ! $from || ! $to ) {
			// There's no from or to.
			// Let's grab the current month.
			$current_time = calendarp_wall_time();

			$days_in_month = str_pad( calendarp_get_total_days_in_a_month( gmdate( 'n', $current_time ), gmdate( 'Y', $current_time ) ), 2, '0', STR_PAD_LEFT );

			if ( ! $from ) {
				$from = strtotime( gmdate( 'Y-m', $current_time ) . '-01 00:00:00' );
			}

			if ( ! $to ) {
				$to = strtotime( gmdate( 'Y-m', $current_time ) . '-' . $days_in_month . ' 23:59:59' );
			}
		}

		$this->from = $from;
		$this->to   = $to;

		if ( $this->from > $this->to ) {
			$this->from = $to;
			$this->to   = $from;
		}
	}


	/**
	 * Retrieve duration.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_duration() {
		return $this->to - $this->from;
	}

	/**
	 * Retrieve from date.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_from_date() {
		return gmdate( 'Y-m-d', $this->from );
	}

	/**
	 * Retrieve to date.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_to_date() {
		if ( ! $this->to ) {
			return false;
		}
		return gmdate( 'Y-m-d', $this->to );
	}

	/**
	 * Retrieve from time.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_from_time() {
		return gmdate( 'H:i:s', $this->from );
	}

	/**
	 * Retrieve to time.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_to_time() {
		if ( ! $this->to ) {
			return false;
		}
		return gmdate( 'H:i:s', $this->to );
	}

	/**
	 * Retrieve from.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_from() {
		return $this->from;
	}

	/**
	 * Retrieve to.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_to() {
		return $this->to;
	}
}

/** Calendar component: Calendar_Plus_Calendar. */
class Calendar_Plus_Calendar {

	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	public $timespan;
	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	private $cells = null;

	/**
	 * Initialize this calendar component.
	 *
	 * @param int|bool $from Date in Unix Timestamp.
	 * @param int|bool $to Date in Unix Timestamp.
	 */
	public function __construct( $from = false, $to = false ) {
		$this->timespan = new Calendar_Plus_Timespan( $from, $to );
	}

	/**
	 * Retrieve cells.
	 *
	 * @param array $args Args.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_cells( $args = array() ) {
		$defaults = array(
			'category' => false,
		);
		$args     = wp_parse_args( $args, $defaults );

		if ( null === $this->cells ) {
			$grouped_events_by_date = calendarp_get_events_in_date_range( $this->timespan->get_from(), $this->timespan->get_to(), $args );

			$this->cells = array();
			foreach ( $grouped_events_by_date as $date => $events ) {
				$this->cells[ $date ] = new Calendar_Plus_Calendar_Cell( $events );
			}
		}

		return $this->cells;
	}

	/**
	 * Retrieve cells since.
	 *
	 * @param array $args Args.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_cells_since( $args = array() ) {
		if ( null === $this->cells ) {
			$grouped_events_by_date = calendarp_get_events_since( $this->timespan->get_from(), $args );

			$this->cells = array();
			foreach ( $grouped_events_by_date as $date => $events ) {
				$this->cells[ $date ] = new Calendar_Plus_Calendar_Cell( $events );
			}
		}

		return $this->cells;
	}
}

/** Calendar component: Calendar_Plus_Calendar_Cell. */
class Calendar_Plus_Calendar_Cell {

	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	private $events;

	/**
	 * Initialize this calendar component.
	 *
	 * @param mixed $events_list Events list.
	 */
	public function __construct( $events_list ) {
		$this->events = $events_list;
	}

	/**
	 * Retrieve events.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_events() {
		return $this->events;
	}
}

/**
 * Handle calendarp get the calendar object.
 *
 * @param array $args Args.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_get_the_calendar_object( $args = array() ) {
	$defaults = array(
		'year'            => '',
		'month'           => '',
		'day'             => '',
		'mode'            => 'month',
		'page'            => 1,
		'events_per_page' => calendarp_get_events_per_page(),
		'category'        => false,
	);

	$args = wp_parse_args( $args, $defaults );

	$cells_ids = array();

	$accepted_modes = array( 'month', 'day', 'week', 'agenda' );
	if ( ! in_array( $args['mode'], $accepted_modes, true ) ) {
		$args['mode'] = $defaults['mode'];
	}

	$year            = $args['year'] ?? '';
	$month           = $args['month'] ?? '';
	$day             = $args['day'] ?? '';
	$mode            = $args['mode'] ?? '';
	$page            = $args['page'] ?? '';
	$events_per_page = $args['events_per_page'] ?? '';
	$category        = $args['category'] ?? '';

	$current_time = calendarp_wall_time();
	$current_day  = gmdate( 'd', $current_time );
	$month        = ! empty( $month ) ? absint( $month ) : gmdate( 'n', $current_time );
	$year         = ! empty( $year ) ? absint( $year ) : gmdate( 'Y', $current_time );
	$day          = ! empty( $day ) ? absint( $day ) : gmdate( 'd', $current_time );

	if ( $month > 12 ) {
		$month = 1;
		++$year;
	}

	if ( $month < 1 ) {
		$month = 12;
		--$year;
	}

	if ( 'week' === $mode ) {
		$from = strtotime( "$year-$month-$day 00:00:00" );
		$to   = strtotime( gmdate( 'Y-m-d', strtotime( '+6 days', $from ) ) . ' 23:59:59' );
		$from = strtotime( '-6 days', $from );
	} elseif ( 'day' === $mode ) {
		$from = strtotime( "$year-$month-$day 00:00:00" );
		$from = strtotime( '-1 day', $from );
		$to   = strtotime( gmdate( 'Y-m-d', strtotime( '+1 day', $from ) ) . ' 23:59:59' );
	} elseif ( 'agenda' === $mode ) {
		$from = strtotime( "$year-$month-$day 00:00:00" );
		$to   = false;
	} else {
		$from = strtotime( "$year-$month-01 00:00:00" );
		$to   = strtotime( gmdate( 'Y-m-d', strtotime( '+1 month', $from ) ) . ' 23:59:59' );
	}

	$calendar = new Calendar_Plus_Calendar( $from, $to );

	if ( 'agenda' === $mode ) {
		$calendar_cells = $calendar->get_cells_since( $args );
	} else {
		$calendar_cells = $calendar->get_cells( $args );
	}

	$calendar_renderer = new Calendar_Plus_Calendar_Renderer( $calendar );

	if ( $args['category'] ) {
		$calendar_renderer->set_category( $args['category'] );
	}

	$calendar_cells_week = calendarp_add_week_fields( $calendar_cells );

	$with_events = array();
	foreach ( $calendar_cells_week as $cell_key => $cell ) {
		foreach ( $cell as $inner_cell ) {
			$cells_ids[] = $inner_cell['id'];
		}
		if ( ! empty( $cell ) ) {
			$with_events[] = $cell_key;
		}
	}

	$calendar_renderer->set_cells_with_events( $with_events );

	if ( 'day' === $mode ) {
		$calendar_renderer->set_mode( 'day' );
		$the_calendar = $calendar_renderer->calendar_day( $day, $month, $year );
	} elseif ( 'week' === $mode ) {
		$calendar_renderer->set_mode( 'week' );
		$the_calendar = $calendar_renderer->calendar_week( $day, $month, $year );
	} elseif ( 'month' === $mode ) {
		$calendar_renderer->set_mode( 'month' );
		$the_calendar = $calendar_renderer->calendar_month( $month, $year );
	} elseif ( 'agenda' === $mode ) {
		$calendar_renderer->set_mode( 'agenda' );
		$the_calendar = $calendar_renderer->calendar_agenda( $day, $month, $year );
	}

	return array(
		'the_calendar'        => $the_calendar,
		'calendar_cells_week' => $calendar_cells_week,
		'calendar_renderer'   => $calendar_renderer,
		'calendar_cells_ids'  => array_unique( $cells_ids ),
	);
}


// Better names for these functions please.

/**
 * Handle calendarp calendar to array.
 *
 * @param mixed $calendar Calendar.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_calendar_to_array( $calendar ) {
	$array = array();
	foreach ( $calendar as $date => $cell ) {
		$events         = $cell->get_events();
		$array[ $date ] = array();
		foreach ( $events as $event ) {
			$array[ $date ][] = array(
				'title'      => get_the_title( $event->ID ),
				'id'         => $event->ID,
				'dates_data' => $event->dates_data,
				'all_day'    => $event->is_all_day_event(),
				'color'      => $event->color,
				'event'      => $event,
			);
		}
	}

	return $array;
}

/**
 * Handle calendarp add week fields.
 *
 * @param mixed $calendar Calendar.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_add_week_fields( $calendar ) {
	$array      = calendarp_calendar_to_array( $calendar );
	$week_array = array();

	$days  = array_keys( $array );
	$times = calendarp_get_times_list();

	foreach ( $days as $day ) {
		$week_array[ $day ] = array();
		foreach ( $array[ $day ] as $event_key => $event ) {

			// Get the times array for this event.
			$event_from = $event['dates_data']['from_time'];
			$event_to   = $event['dates_data']['until_time'];

			$event['times'] = array();
			if ( ! $event['all_day'] ) {

				$finished = false;
				foreach ( $times as $key => $time ) {
					if ( isset( $times[ $key + 1 ] ) ) {
						$next_time = $times[ $key + 1 ];
					} else {
						$next_time = '23:59';
					}

					if ( ! ( $time > $event_from ) && ! ( $next_time > $event_from ) ) {
						continue;
					} elseif ( $time . ':00' >= $event_to ) {
						continue;
					} elseif ( ! ( $time > $event_from ) && ( $next_time > $event_from ) ) {
						$event['times'][] = $time;
					} elseif ( ( $time > $event_from ) && ( $next_time > $event_from ) ) {
						if ( ( $time <= $event_to ) && ( $next_time <= $event_to ) ) {
							$event['times'][] = $time;
						} else {
							$event['times'][] = $time;
							$finished         = true;
						}
					}

					if ( $finished ) {
						break;
					}
				}
			} else {
				$event['times'] = $times;
			}
			$week_array[ $day ][] = $event;
		}
	}

	return $week_array;
}
