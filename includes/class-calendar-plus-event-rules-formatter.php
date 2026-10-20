<?php
/**
 * Calendar Plus: includes/class-calendar-plus-event-rules-formatter.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Calendar_Plus_Event_Rules_Formatter_Interface {
	/**
	 * Handle format.
	 *
	 * @param mixed $rule Rule.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function format( $rule );
}

/** Calendar component: Calendar_Plus_Event_Rules_Formatter. */
class Calendar_Plus_Event_Rules_Formatter {
	/**
	 * Native times formatting state.
	 *
	 * @var Calendar_Plus_Event_Rules_Times_Formatter
	 */
	public $times;

	/**
	 * Native dates formatting state.
	 *
	 * @var Calendar_Plus_Event_Rules_Dates_Formatter
	 */
	public $dates;

	/**
	 * Native every formatting state.
	 *
	 * @var Calendar_Plus_Event_Rules_Every_Formatter
	 */
	public $every;

	/**
	 * Native exclusions formatting state.
	 *
	 * @var Calendar_Plus_Event_Rules_Exclusions_Formatter
	 */
	public $exclusions;

	/**
	 * Native standard formatting state.
	 *
	 * @var Calendar_Plus_Event_Rules_Standard_Formatter
	 */
	public $standard;

	/**
	 * Native datespan formatting state.
	 *
	 * @var Calendar_Plus_Event_Rules_Datespan_Formatter
	 */
	public $datespan;

	/**
	 * Native formatted date formatting state.
	 *
	 * @var array
	 */
	public $formatted_date = array();

	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {
		$this->times      = new Calendar_Plus_Event_Rules_Times_Formatter();
		$this->dates      = new Calendar_Plus_Event_Rules_Dates_Formatter();
		$this->every      = new Calendar_Plus_Event_Rules_Every_Formatter();
		$this->exclusions = new Calendar_Plus_Event_Rules_Exclusions_Formatter();
		$this->standard   = new Calendar_Plus_Event_Rules_Standard_Formatter();
		$this->datespan   = new Calendar_Plus_Event_Rules_Datespan_Formatter();

		$this->formatted_date = array();

		do_action_ref_array( 'calendarp_event_rules_formatter', array( &$this ) );
	}

	/**
	 * Format all.
	 *
	 * @param array $rules Rules.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function format_all( $rules ) {
		$formatted = array();
		foreach ( $rules as $rule ) {
			if ( ! isset( $rule['rule_type'] ) ) {
				continue;
			}

			if ( ! isset( $formatted[ $rule['rule_type'] ] ) ) {
				$formatted[ $rule['rule_type'] ] = array();
			}

			if ( isset( $formatted['dates'] ) && empty( $this->formatted_date ) ) {
				if ( isset( $formatted['dates'][0] ) ) {
					$this->formatted_date['from']  = new \DateTime( $formatted['dates'][0]['from'] );
					$this->formatted_date['until'] = new \DateTime( $formatted['dates'][0]['until'] );
				}
			}

			$formatted[ $rule['rule_type'] ][] = $this->format( $rule );
		}

		return $formatted;
	}

	/**
	 * Handle format.
	 *
	 * @param mixed $rule Rule.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function format( $rule ) {
		if ( ! isset( $rule['rule_type'] ) ) {
			return false;
		}

		if ( property_exists( $this, $rule['rule_type'] ) ) {
			$classname = 'Calendar_Plus_Event_Rules_' . ucfirst( strtolower( $rule['rule_type'] ) ) . '_Formatter';
			$rule_type = $rule['rule_type'];

			if ( $this->$rule_type instanceof $classname ) {
				return 'times' === $rule_type ? $this->$rule_type->format( $rule, $this->formatted_date ) : $this->$rule_type->format( $rule );
			}
		}

		return false;
	}
}


/** Calendar component: Calendar_Plus_Event_Rules_Times_Formatter. */
class Calendar_Plus_Event_Rules_Times_Formatter implements Calendar_Plus_Event_Rules_Formatter_Interface {

	/**
	 * Handle format.
	 *
	 * @param mixed $rule Rule.
	 * @param array $formatted_date Formatted date.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function format( $rule, $formatted_date = array() ) {
		if ( ! isset( $rule['from'] ) ) {
			return false;
		}

		$from_time = self::format_single_time( $rule['from'] );
		if ( ! $from_time ) {
			return false;
		}

		if ( isset( $rule['until'] ) ) {
			$until_time = self::format_single_time( $rule['until'] );
		} else {
			$until_time = false;
		}

		if ( $from_time === $until_time ) {
			$until_time = false;
		}

		if ( $until_time < $from_time ) {
			if ( $formatted_date['from'] === $formatted_date['until'] ) {
				$_until_time = $until_time;
				$until_time  = $from_time;
				$from_time   = $_until_time;
			}
		}

		return array(
			'from'  => $from_time,
			'until' => $until_time,
		);
	}

	/**
	 * Format single time.
	 *
	 * @param mixed $time Time.
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function format_single_time( $time ) {
		if ( ! is_scalar( $time ) || ! preg_match( '/^(?:[0-9]{4}|[0-9]{1,2}:[0-5][0-9](?::[0-5][0-9])?)$/D', (string) $time ) ) {
			return false;
		}
		$time = explode( ':', $time );

		if ( ! $time ) {
			return false;
		}

		if ( count( $time ) === 1 ) {
			$time_hour    = substr( $time[0], 0, 2 );
			$time_minutes = substr( $time[0], 2, 2 );
			$time         = array( $time_hour, $time_minutes );
		}

		$time = array_map( 'absint', $time );
		if ( count( $time ) < 2 ) {
			return false;
		}

		if ( $time[0] > 23 || $time[1] > 59 ) {
			return false;
		}

		$time[0] = str_pad( $time[0], 2, '0', STR_PAD_LEFT );
		$time[1] = str_pad( $time[1], 2, '0', STR_PAD_LEFT );
		return implode( ':', $time );
	}
}

/** Calendar component: Calendar_Plus_Event_Rules_Dates_Formatter. */
class Calendar_Plus_Event_Rules_Dates_Formatter implements Calendar_Plus_Event_Rules_Formatter_Interface {
	/**
	 * Handle format.
	 *
	 * @param mixed $rule Rule.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function format( $rule ) {
		if ( ! isset( $rule['from'] ) || ! isset( $rule['until'] ) ) {
			return false;
		}

		$from_date  = explode( '-', $rule['from'] );
		$until_date = explode( '-', $rule['until'] );
		if ( ! $from_date || ! is_array( $from_date ) || ! $until_date || ! is_array( $until_date ) ) {
			return false;
		}

		if ( count( $from_date ) !== 3 || count( $until_date ) !== 3 ) {
			return false;
		}

		$check_from  = checkdate( $from_date[1], $from_date[2], $from_date[0] );
		$check_until = checkdate( $until_date[1], $until_date[2], $until_date[0] );

		if ( ! $check_from || ! $check_until ) {
			return false;
		}

		if ( $from_date > $until_date ) {
			return false;
		}

		return array(
			'from'  => $rule['from'],
			'until' => $rule['until'],
		);
	}
}

/** Calendar component: Calendar_Plus_Event_Rules_Every_Formatter. */
class Calendar_Plus_Event_Rules_Every_Formatter implements Calendar_Plus_Event_Rules_Formatter_Interface {
	/**
	 * Handle format.
	 *
	 * @param mixed $rule Rule.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function format( $rule ) {
		if ( ! isset( $rule['every'] ) || ! isset( $rule['what'] ) ) {
			return false;
		}

		$what = $rule['what'];
		if ( ! in_array( $what, array( 'day', 'week', 'month', 'year', 'dow', 'dom' ), true ) ) {
			return false;
		}

		if ( 'dow' === $what ) {
			$every = $rule['every'];

			if ( ! is_array( $every ) ) {
				return false;
			}
			$every = array_filter( $every, array( $this, 'filter_day_of_week' ) );
			sort( $every );

		} elseif ( 'dom' === $what ) {

			if ( ! is_array( $rule['every'] ) ) {
				return false;
			}

			$every = array();

			foreach ( $rule['every'] as $week => $days ) {
				$week = intval( $week );

				if ( $week < 1 || $week > 5 ) {
					continue;
				}

				$every[ $week ] = array_filter( $days, array( $this, 'filter_day_of_week' ) );
			}
		} else {
			$every = absint( $rule['every'] );
			if ( ! $every ) {
				return false;
			}
		}

		$formatted = array(
			'every' => $every,
			'what'  => $what,
		);

		return $formatted;
	}

	/**
	 * Handle filter day of week.
	 *
	 * @param mixed $dow Dow.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function filter_day_of_week( $dow ) {
		$dow = intval( $dow );
		return ! ( $dow > 7 || $dow < 1 );
	}
}

/** Calendar component: Calendar_Plus_Event_Rules_Exclusions_Formatter. */
class Calendar_Plus_Event_Rules_Exclusions_Formatter implements Calendar_Plus_Event_Rules_Formatter_Interface {
	/**
	 * Handle format.
	 *
	 * @param mixed $rule Rule.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function format( $rule ) {
		if ( empty( $rule['date'] ) ) {
			return false;
		}

		$date = explode( '-', $rule['date'] );

		if ( ! $date ) {
			return false;
		}

		if ( count( $date ) !== 3 ) {
			return false;
		}

		$check = checkdate( $date[1], $date[2], $date[0] );

		if ( ! $check ) {
			return false;
		}

		return array( 'date' => $rule['date'] );
	}
}

/** Calendar component: Calendar_Plus_Event_Rules_Standard_Formatter. */
class Calendar_Plus_Event_Rules_Standard_Formatter implements Calendar_Plus_Event_Rules_Formatter_Interface {
	/**
	 * Handle format.
	 *
	 * @param mixed $rule Rule.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function format( $rule ) {
		if ( ! isset( $rule['from_date'] ) || ! isset( $rule['until_date'] ) || ! isset( $rule['from_time'] ) ) {
			return false;
		}

		$from_date  = explode( '-', $rule['from_date'] );
		$until_date = explode( '-', $rule['until_date'] );
		if ( ! $from_date || ! is_array( $from_date ) || ! $until_date || ! is_array( $until_date ) ) {
			return false;
		}

		if ( count( $from_date ) !== 3 || count( $until_date ) !== 3 ) {
			return false;
		}

		$check_from  = checkdate( $from_date[1], $from_date[2], $from_date[0] );
		$check_until = checkdate( $until_date[1], $until_date[2], $until_date[0] );

		if ( ! $check_from || ! $check_until ) {
			return false;
		}

		if ( $from_date > $until_date ) {
			return false;
		}

		if ( ! isset( $rule['until_time'] ) ) {
			$until_time = '23:59';
		} else {
			$until_time = $rule['until_time'];
		}

		$from_time = Calendar_Plus_Event_Rules_Times_Formatter::format_single_time( $rule['from_time'] );
		if ( ! $from_time ) {
			return false;
		}

		$until_time = Calendar_Plus_Event_Rules_Times_Formatter::format_single_time( $until_time );
		if ( ! $until_time ) {
			$until_time = '23:59';
		}

		// switch times if event is set to finish before it begins.
		if ( $until_time < $from_time && $rule['from_date'] === $rule['until_date'] ) {
			$_until_time = $until_time;
			$until_time  = $from_time;
			$from_time   = $_until_time;
		}

		return array(
			'from_date'  => $rule['from_date'],
			'until_date' => $rule['until_date'],
			'from_time'  => $from_time,
			'until_time' => $until_time,
		);
	}
}

/** Calendar component: Calendar_Plus_Event_Rules_Datespan_Formatter. */
class Calendar_Plus_Event_Rules_Datespan_Formatter extends Calendar_Plus_Event_Rules_Standard_Formatter {
	/**
	 * Handle format.
	 *
	 * @param mixed $rule Rule.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function format( $rule ) {
		if ( ! isset( $rule['until_date'] ) ) {
			$rule['until_date'] = $rule['from_date'];
		}

		$until_date = explode( '-', $rule['until_date'] );
		if ( ! $until_date || ! is_array( $until_date ) ) {
			$rule['until_date'] = $rule['from_date'];
		}

		if ( count( $until_date ) !== 3 ) {
			$rule['until_date'] = $rule['from_date'];
		}

		return parent::format( $rule );
	}
}
