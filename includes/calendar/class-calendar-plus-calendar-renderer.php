<?php

use Mexitek\PHPColors\Color;

/**
 * @author Marosdee Uma, website:: www.danya-reload.com @ danya0365[at]live.com, danya0365[at]gmail.com, danya-planemo[at]live.com
 * @link  http://www.phpclasses.org/package/5434-PHP-Display-calendars-denoting-event-days.html
 * @copyright 2009
 * @version 1.5
 */
class Calendar_Plus_Calendar_Renderer {

	/**
	 * 0-6 date('w')
	 *
	 * @var integer
	 */
	public $today_day_of_week;

	/**
	 * 1-31 date('j')
	 *
	 * @var integer
	 */
	public $today_day;

	/**
	 * 1-6 getWeek()
	 *
	 * @var integer
	 */
	public $today_week;

	/**
	 * 1-12 date('n')
	 *
	 * @var integer
	 */
	public $today_month;

	/**
	 * 1970 or 2009 date('Y')
	 *
	 * @var integer
	 */
	public $today_year;


	/**
	 * 1-31 date('j')
	 *
	 * @var integer
	 */
	public $default_day;


	/**
	 * 1-12 date('n')
	 *
	 * @var integer
	 */
	public $default_month;

	/**
	 * 1970 or 2009 date('Y')
	 *
	 * @var integer
	 */
	public $default_year;

	/**
	 * 1-31
	 *
	 * @var integer
	 */
	public $total_day_in_month;

	/**
	 * 0-6
	 *
	 * @var integer
	 */
	public $first_day_of_week;

	/**
	 * 0-6
	 *
	 * @var integer
	 */
	public $last_day_of_week;

	public $category = false;


	/**
	 * array of Day of Week Title
	 *
	 * @var array
	 */
	private $day_of_week_title = array();

	private $events_calendar = array();

	public $cells_with_events = array();

	/**
	 * class constructor
	 *
	 * @return void
	 */
	public function __construct( $events_calendar = array() ) {
		global $wp_locale;

		$this->today_day_of_week = date_i18n( 'w', current_time( 'timestamp' ) );
		$this->today_day = date_i18n( 'j', current_time( 'timestamp' ) );
		$this->today_week = $this->get_week( date_i18n( 'j', current_time( 'timestamp' ) ), date_i18n( 'n', current_time( 'timestamp' ) ), date_i18n( 'Y', current_time( 'timestamp' ) ) );
		$this->today_month = date_i18n( 'n', current_time( 'timestamp' ) );
		$this->today_year = date_i18n( 'Y', current_time( 'timestamp' ) );
		$this->events_calendar = $events_calendar;
		$this->page = isset( $_REQUEST['calendar_page'] ) ? $_REQUEST['calendar_page'] : 1;
		$this->events_per_page = calendarp_get_events_per_page();

		foreach ( $wp_locale->weekday_abbrev as $dow ) {
			$this->day_of_week_title[] = $dow;
		}

		$this->mode = 'month';

	}


	public function set_mode( $mode ) {
		$this->mode = $mode;
	}

	public function set_category( $category ) {
		$this->category = $category;
	}

	public function set_events_per_page( $events_per_page ) {
		$this->events_per_page = $events_per_page;
	}

	/**
	 * calculate number of week
	 *
	 * @param integer $d (1-31)
	 * @param integer $m (1-12)
	 * @param integer $y (1970 or 2009)
	 *
	 * @return integer
	 */
	public function get_week( $d = 0, $m = 0, $y = 0 ) {
		$day = date( 'j' );
		$month = date( 'n' );
		$year = date( 'Y' );

		if ( is_numeric( $d ) && $d > 0 ) {
			$day = $d * 1;
		}
		if ( is_numeric( $m ) && $m > 0 ) {
			$month = $m * 1;
		}
		if ( is_numeric( $y ) && $y > 0 ) {
			$year = $y * 1;
		}

		$first_week = date( 'w', mktime( 0, 0, 0, $month, 1, $year ) );

		return ceil( ( $day + $first_week ) / 7 );
	}

	public function set_cells_with_events( $dates ) {
		$this->cells_with_events = $dates;
	}

	/**
	 * @param Calendar_Plus_Event $event
	 * @param string              $date_id
	 * @param bool                $show_time
	 * @param array               $event_template
	 * @param array               $event_popup_template
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function get_event_content(
		 Calendar_Plus_Event $event, $date_id = '', $show_time = true, $event_template = array(), $event_popup_template = array() ) {

		$event_color = $event->color;
		$color = new Color( $event_color );

		if ( $color->isLight() ) {
			$text_color = '#' . $color->darken( 80 );
		} elseif ( $color->isDark() ) {
			$text_color = '#' . $color->lighten( 80 );
		} else {
			$text_color = '#' . $color->complementary();
		}

		$event_content = array();
		$event_content[] = str_replace(
			array( '{event_color}', '{calendar_cell_id}', '{event_id}', '{date_id}', '{text_color}' ),
			array( $event->color, $event->dates_data['calendar_cell_id'], $event->ID, $date_id, $text_color ),
			$event_template['title_start']
		);

		$time = calendarp_24h_to_timestamp( $event->dates_data['from_time'] );
		$format = calendarp_get_setting( 'time_format' );
		if ( 'AM/PM' == $format ) {
			$time = date( 'h:i A', $time );
		} else {
			$time = date( 'H:i', $time );
		}
		$event_content[] = str_replace(
			array( '{event_time}', '{event_time_class}' ),
			array( $event->is_all_day_event() || ! $show_time ? '' : $time, $event->is_all_day_event() || ! $show_time ? 'all-day-event' : '' ),
			$event_template['time']
		);

		// Now the content.
		$event_content[] = str_replace(
			array( '{event_content}', '{event_permalink}' ),
			array( get_the_title( $event->ID ), '<a class="view-event" target="_blank" style="color:' . $text_color . ';" href="' . esc_url( get_permalink( $event->ID ) ) . '" title="' . esc_attr__( 'View Event', 'calendar-plus' ) . '">' . __( 'View Event', 'calendar-plus' ) . '</a><div style="clear:both"></div>' ),
			$event_template['event_content']
		);
		$event_content[] = $event_template['title_end'];

		$popup_content = array();
		if ( $event_popup_template ) {
			// Open the popup
			$popup_content[] = str_replace( '{calendar_cell_id}', $event->dates_data['calendar_cell_id'], $event_popup_template['popup_start'] );

			// The popup header
			$popup_content[] = str_replace(
				array( '{event_color}', '{event_title}', '{text_color}' ),
				array( $event->color, get_the_title( $event->ID ), $text_color ),
				$event_popup_template['popup_content_header']
			);

			// The popup content
			$from_date = mysql2date( get_option( 'date_format' ), $event->dates_data['from_date'] );
			if ( $event->is_all_day_event() ) {
				$from_time = __( 'All day event', 'calendar-plus' );
				$to_time = '';
			} else {
				$from_time = mysql2date( get_option( 'time_format' ), $event->dates_data['from_date'] . ' ' . $event->dates_data['from_time'] );
				$to_time = '- ' . mysql2date( get_option( 'time_format' ), $event->dates_data['from_date'] . ' ' . $event->dates_data['until_time'] );
			}

			$from_time_selector = calendarp_time_selector(
				array(
					'selected'     => $event->dates_data['from_time'],
					'echo'         => false,
					'hours_name'   => 'from-time-hour-' . $event->dates_data['calendar_cell_id'],
					'minutes_name' => 'from-time-minute-' . $event->dates_data['calendar_cell_id'],
					'am_pm_name'   => 'from-time-am-pm-' . $event->dates_data['calendar_cell_id'],
				)
			);

			$to_time_selector = calendarp_time_selector(
				array(
					'selected'     => $event->dates_data['until_time'],
					'echo'         => false,
					'hours_name'   => 'to-time-hour-' . $event->dates_data['calendar_cell_id'],
					'minutes_name' => 'to-time-minute-' . $event->dates_data['calendar_cell_id'],
					'am_pm_name'   => 'to-time-am-pm-' . $event->dates_data['calendar_cell_id'],
				)
			);

			$popup_content[] = $event_popup_template['popup_content_inner_start'];
			$popup_content[] = str_replace( '{event_date}', $from_date, $event_popup_template['popup_content_inner_date'] );
			$popup_content[] = str_replace( array( '{event_time_start}', '{event_time_end}' ), array( $from_time, $to_time ), $event_popup_template['popup_content_inner_time'] );
			$popup_content[] = $event_popup_template['popup_content_inner_end'];

			$popup_content[] = $event_popup_template['popup_content_inner_editable_start'];
			$popup_content[] = str_replace( '{event_date}', $from_date, $event_popup_template['popup_content_inner_editable_date'] );
			$popup_content[] = str_replace( array( '{event_time_start}', '{event_time_end}', '{event_starts_at_title}', '{event_finishes_at_title}' ), array( $from_time_selector, $to_time_selector, __( 'Starts at', 'calendar-plus' ), __( 'Finishes at', 'calendar-plus' ) ), $event_popup_template['popup_content_inner_editable_time'] );
			$popup_content[] = $event_popup_template['popup_content_inner_editable_end'];

			// The popup footer
			$edit_button = '';
			if ( ! $event->is_all_day_event() ) {
				$edit_button = '<a class="button-primary edit-calendar-cell" data-cell-id="' . $event->dates_data['calendar_cell_id'] . '">' . __( 'Edit', 'calendar-plus' ) . '</a>';
			}

			$delete_button = '<a class="button-secondary delete-calendar-cell" data-cell-id="' . $event->dates_data['calendar_cell_id'] . '">' . __( 'Delete', 'calendar-plus' ) . '</a>';

			$popup_content[] = $event_popup_template['popup_content_footer_start'];
			$popup_content[] = $event_popup_template['popup_content_inner_footer_content_start'];
			$popup_content[] = str_replace(
				array( '{edit_button}', '{delete_button}', '{edit_event_link}', '{edit_even_link_title}' ),
				array( $edit_button, $delete_button, esc_url( get_edit_post_link( $event->ID ) ), esc_attr( 'Edit Event', 'calendar-plus' ), __( 'Edit Event', 'calendar-plus' ) ),
				$event_popup_template['popup_content_inner_footer_content']
			);
			$popup_content[] = $event_popup_template['popup_content_inner_footer_content_end'];

			if ( ! $event->is_all_day_event() ) {
				$popup_content[] = $event_popup_template['popup_content_inner_footer_editable_content_start'];
				$popup_content[] = str_replace(
					array( '{calendar_cell_id}', '{save_cell_text}' ),
					array( $event->dates_data['calendar_cell_id'], __( 'Save', 'calendar-plus' ) ),
					$event_popup_template['popup_content_inner_footer_editable_content']
				);
				$popup_content[] = $event_popup_template['popup_content_inner_footer_editable_content_end'];
			}

			$popup_content[] = $event_popup_template['popup_content_footer_end'];

			// Close the popup
			$popup_content[] = $event_popup_template['popup_end'];
		}

		$event_content[] = str_replace( '{event_popup}', implode( "\n", $popup_content ), $event_template['event_popup'] );

		return implode( "\n", $event_content );

	}

	public function calendar_mode_selector() {
		?>
		<form action="" method="get" class="custom">
			<label for="calendar_mode"><?php _e( 'View', 'calendar-plus' ); ?>
				<select name="calendar_mode" id="calendar_mode">
					<option value="month" <?php selected( $this->mode, 'month' ); ?>><?php _e( 'Month', 'calendar-plus' ); ?></option>
					<option value="week" <?php selected( $this->mode, 'week' ); ?>><?php _e( 'Week', 'calendar-plus' ); ?></option>
					<option value="day" <?php selected( $this->mode, 'day' ); ?>><?php _e( 'Day', 'calendar-plus' ); ?></option>
					<option value="agenda" <?php selected( $this->mode, 'agenda' ); ?>><?php _e( 'Agenda', 'calendar-plus' ); ?></option>
				</select>
			</label>
		</form>
		<?php
	}

	public function controls() {
		global $wp_locale;

		if ( 'month' === $this->mode ) {
			$month = $this->today_month;
			$year = $this->today_year;
			$current_time = current_time( 'timestamp' );
			$current_month_link = add_query_arg(
				array(
					'calendar_year'  => date( 'Y', $current_time ),
					'calendar_month' => date( 'm', $current_time ),
				)
			);
			?>
			<form action="" id="calendarp-controls-form" class="columns large-9" method="get">
				<div class="panel">
					<div class="row">
						<div class="large-4 columns">
							<select name="calendar_month" id="calendar-month-selector">
								<?php foreach ( $wp_locale->month as $month_number => $month_name ) : ?>
									<option value="<?php echo absint( $month_number ); ?>" <?php selected( absint( $month_number ), absint( $month ) ); ?>><?php echo $month_name; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="large-4 columns">
							<select name="calendar_year" id="calendar-year-selector">
								<?php for ( $i = 2015; $i < 2030; $i++ ) : ?>
									<option value="<?php echo absint( $i ); ?>" <?php selected( absint( $i ), absint( $year ) ); ?>><?php echo $i; ?></option>
								<?php endfor; ?>
							</select>
						</div>

						<input type="hidden" name="calendar_mode" value="<?php echo $this->mode; ?>">
						<p class="submit large-4 columns">
							<input type="submit" name="submit" id="submit" class="button small" value="<?php esc_attr_e( 'View month', 'calendar-plus' ); ?>">
						</p>
					</div>
				</div>
			</form>
			<?php
		} elseif ( 'agenda' === $this->mode ) {

			$args = array(
				'page'            => $this->page + 1,
				'events_per_page' => 1,
			);

			if ( $this->category ) {
				$args['category'] = $this->category;
			}

			if ( $this->events_per_page ) {
				$args['events_per_page'] = $this->events_per_page;
			}

			$next_batch = calendarp_get_events_since(
				$this->events_calendar->timespan->get_from(),
				$args
			);

			$next_url = false;
			if ( ! empty( $next_batch ) ) {
				$next_url = add_query_arg(
					array(
						'calendar_page' => $this->page + 1,
					)
				);
			}

			$prev_url = false;
			if ( $this->page > 1 ) {
				$prev_url = add_query_arg(
					array(
						'calendar_page' => $this->page - 1,
					)
				);
			}

			if ( $prev_url || $next_url ) :
				?>
				<form action="" id="calendarp-controls-form" class="row" method="get">
					<div class="large-12 columns">
						<div class="panel clearfix">
							<?php if ( $prev_url ) : ?>
								<a href="<?php echo esc_url( $prev_url ); ?>" class="button small left" title="<?php esc_attr_e( 'Previous page', 'calendar-plus' ); ?>"><?php _e( '&laquo; Previous page ', 'calendar-plus' ); ?></a>
							<?php endif; ?>

							<?php if ( $next_url ) : ?>
								<a href="<?php echo esc_url( $next_url ); ?>" class="button small right" title="<?php esc_attr_e( 'Next page', 'calendar-plus' ); ?>"><?php _e( 'Next page &raquo;', 'calendar-plus' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				</form>
			<?php
			endif;

		} elseif ( 'week' === $this->mode ) {
			$next_week_url = add_query_arg(
				array(
					'calendar_day'   => $this->next_day,
					'calendar_month' => $this->next_month,
					'calendar_year'  => $this->next_year,
				)
			);

			$prev_week_url = add_query_arg(
				array(
					'calendar_day'   => $this->back_day,
					'calendar_month' => $this->back_month,
					'calendar_year'  => $this->back_year,
				)
			);

			$current_week_url = remove_query_arg(
				array(
					'calendar_day',
					'calendar_month',
					'calendar_year',
				)
			);

			?>
			<form action="" id="calendarp-controls-form" class="columns large-7" method="get">
				<div class="panel clearfix">
					<a href="<?php echo esc_url( $prev_week_url ); ?>" class="button small left" title="<?php esc_attr_e( 'Previous week', 'calendar-plus' ); ?>"><?php _e( '&laquo; Prev week ', 'calendar-plus' ); ?></a>
					<a href="<?php echo esc_url( $next_week_url ); ?>" class="button small right" title="<?php esc_attr_e( 'Next week', 'calendar-plus' ); ?>"><?php _e( 'Next week &raquo;', 'calendar-plus' ); ?></a>
				</div>
			</form>
			<?php
		} elseif ( 'day' === $this->mode ) {
			$next_day_url = add_query_arg(
				array(
					'calendar_day'   => $this->next_day,
					'calendar_month' => $this->next_month,
					'calendar_year'  => $this->next_year,
				)
			);

			$prev_day_url = add_query_arg(
				array(
					'calendar_day'   => $this->back_day,
					'calendar_month' => $this->back_month,
					'calendar_year'  => $this->back_year,
				)
			);

			$current_day_url = remove_query_arg(
				array(
					'calendar_day',
					'calendar_month',
					'calendar_year',
				)
			);

			?>
			<form action="" id="calendarp-controls-form" class="columns large-6" method="get">
				<div class="panel clearfix">
					<a href="<?php echo esc_url( $prev_day_url ); ?>" class="button small left" title="<?php esc_attr_e( 'Previous day', 'calendar-plus' ); ?>"><?php _e( '&laquo; Prev day ', 'calendar-plus' ); ?></a>
					<a href="<?php echo esc_url( $next_day_url ); ?>" class="button small right" title="<?php esc_attr_e( 'Next day', 'calendar-plus' ); ?>"><?php _e( 'Next day &raquo;', 'calendar-plus' ); ?></a>
				</div>
			</form>
			<?php
		}

	}

	public function calendar_slots( $calendar_cells, $mode, $event_template = false, $popup_template = false ) {
		if ( 'week' === $mode || 'day' === $mode ) :
			?>
			<div id="calendar-slots">
				<?php foreach ( $calendar_cells as $cell ) : ?>
					<?php foreach ( $cell as $event ) : ?>
						<?php foreach ( $event['times'] as $time ) : ?>
							<?php
							$date = $event['dates_data']['from_date'] . ' ' . $time . ':00';
							$date = mysql2date( 'YmdHi', $date, false );
							?>
							<?php echo self::get_event_content( $event['event'], $date, false, $event_template, $popup_template ); ?>
						<?php endforeach; ?>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</div>
		<?php elseif ( 'month' === $mode ) : ?>
			<div id="calendar-slots">
				<?php foreach ( $calendar_cells as $cell ) : ?>
					<?php foreach ( $cell as $event ) : ?>
						<?php
						$date = $event['dates_data']['from_date'];
						$date = mysql2date( 'Ymd', $date, false );
						?>
						<?php echo self::get_event_content( $event['event'], $date, true, $event_template, $popup_template ); ?>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</div>
		<?php
		endif;

	}

	/**
	 * get Calendar Month
	 *
	 * @param integer $m (1-2)
	 * @param integer $y (1970 or 2009)
	 *
	 * @return string output
	 */
	public function calendar_month( $m = 0, $y = 0, $in_year_calendar = false ) {

		if ( is_numeric( $m ) && $m > 0 && $m < 13 ) {
			$this->today_month = $m;
		}
		if ( is_numeric( $y ) && $y > 0 ) {
			$this->today_year = $y;
		}

		$this->total_day_in_month = date( 't', mktime( 0, 0, 0, $this->today_month, 1, $this->today_year ) );
		$this->first_day_of_week = $this->_get_first_day_of_week();
		$this->last_day_of_week = $this->_get_last_day_of_week();

		$back_month = $this->today_month - 1;
		$back_year = $this->today_year;

		if ( $back_month < 1 ) {
			$back_month = 12;
			$back_year = $this->today_year - 1;
		}

		$total_day_in_back_month = date( 't', mktime( 0, 0, 0, $back_month, 1, $back_year ) );
		$start_day_in_back_month = ( $total_day_in_back_month - $this->first_day_of_week ) + 1;

		// Days from previous month that will be present at the start of our current month
		$days_from_back_month = $total_day_in_back_month - $start_day_in_back_month;

		// Day from we start (can be negative if the previous month does not ends on saturday)
		$day = -$days_from_back_month;

		$template = is_admin() ? calendarp_get_calendar_admin_month_template() : calendarp_get_calendar_front_month_template();

		$return = array();

		$heading = $this->get_month_name( $this->today_month ) . '&nbsp;' . $this->today_year;

		// Wrapper start
		$return[] = $template['calendar_wrapper_start'];
		$return[] = str_replace( '{heading}', $heading, $template['heading'] );

		// Week days row
		$return[] = $template['week_days_table_wrapper_start'];
		$return[] = $template['week_row_start'];
		foreach ( $this->day_of_week_title as $dow ) {
			$return[] = str_replace( '{day_of_week}', $dow, $template['week_row_cell'] );
		}
		$return[] = $template['week_row_end'];
		$return[] = $template['week_days_table_wrapper_end'];

		// The calendar itself
		// Calendar wrap start
		$return[] = $template['cal_rows_wrapper_start'];

		// Calendar Cells
		$calendar_cells = $this->events_calendar->get_cells();
		while ( $day <= $this->total_day_in_month ) {
			// Row start
			$return[] = $template['cal_row_start'];

			for ( $i = 0; $i < 7; $i++ ) {

				if ( $day > 0 && $day <= $this->total_day_in_month ) {

					// Day of the current month

					$current_datetime = mktime( 0, 0, 0, $this->today_month, $day, $this->today_year );
					$current_date = date( 'Ymd', $current_datetime );

					$current_month = absint( date( 'm', current_time( 'timestamp' ) ) );

					$class = array();

					if ( in_array( date( 'Y-m-d', $current_datetime ), $this->cells_with_events ) ) {
						$class[] = 'with-events';
					}

					$daytime_id = str_replace( array( '-' ), array( '', '' ), $current_date );

					if ( absint( $day ) == absint( $this->today_day ) && absint( $this->today_month ) === $current_month ) {
						// Today cell
						$class[] = 'today';
					}

					// Open the cell
					$return[] = str_replace(
						array( '{daytime_id}', '{cell_class}' ),
						array( $daytime_id, implode( ' ', $class ) ),
						$template['cal_cell_start']
					);

					// Cell content
					$return[] = str_replace( array( '{day}', '{content}' ), array( $day, '' ), $template['cal_cell_content'] );

					// Close the cell
					$return[] = $template['cal_cell_end'];
				} else {
					// Day on the previous or next month
					$return[] = str_replace(
						array( '{daytime_id}', '{cell_class}' ),
						array( '', '' ),
						$template['cal_cell_start']
					);
					$return[] = $template['cal_cell_blank'];
					$return[] = $template['cal_cell_end'];
				}

				$day++;
			}

			// Row end
			$return[] = $template['cal_row_end'];
		}

		// Calendar wrap end
		$return[] = $template['cal_rows_wrapper_end'];

		// Wrapper End
		$return[] = $template['calendar_wrapper_end'];

		return implode( "\n", $return );

	}

	public function calendar_agenda( $d = '', $m = '', $y = '' ) {

		if ( is_numeric( $d ) && $d > 0 && $m < 31 ) {
			$this->today_day = $d;
		}
		if ( is_numeric( $m ) && $m > 0 && $m < 13 ) {
			$this->today_month = $m;
		}
		if ( is_numeric( $y ) && $y > 0 ) {
			$this->today_year = $y;
		}

		$day = str_pad( $this->today_day, 2, '0', STR_PAD_LEFT );
		$month = str_pad( $this->today_month, 2, '0', STR_PAD_LEFT );

		$start_date = date_i18n( get_option( 'date_format' ), strtotime( "$y-$month-$day" ) );
		$end_date = date_i18n( get_option( 'date_format' ), strtotime( '+1 month -1 day', strtotime( "$y-$month-$day" ) ) );

		$heading = sprintf( __( '%1$s to %2$s', 'calendar-plus' ), $start_date, $end_date );

		$return = array();
		$template = calendarp_get_calendar_agenda_template();

		// Wrapper start
		$return[] = $template['calendar_wrapper_start'] . "\n";
		$return[] = str_replace( '{heading}', '', $template['heading'] ) . "\n";

		$all_cells = $this->events_calendar->get_cells();
		ob_start();
		foreach ( $all_cells as $date => $cell ) {
			$month_name = date_i18n( 'M', strtotime( $date ) );
			$day = date_i18n( 'd', strtotime( $date ) );
			$dow = date_i18n( 'D', strtotime( $date ) );

			$cell_events = $cell->get_events();
			if ( empty( $cell_events ) ) {
				continue;
			}

			?>
			<div class="calendarp-date-item row">
				<div class="calendarp-date large-3 columns text-center">
					<div class="calendarp-date-month"><?php echo $month_name; ?></div>
					<div class="calendarp-date-day"><?php echo $day; ?></div>
					<div class="calendarp-date-dow"><?php echo $dow; ?></div>
				</div>
				<div class="calendarp-events large-9 columns">
					<?php foreach ( $cell_events as $event ) : ?>
						<div class="calendar-event">
							<h3>
								<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>"><?php echo get_the_title( $event->ID ); ?></a>
							</h3>
							<div class="calendarp-event-meta">
								<?php echo calendarp_get_human_read_dates( $event->ID ); ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>


			<?php
		}

		$return[] = ob_get_clean();

		// Wrapper end
		$return[] = $template['calendar_wrapper_end'] . "\n";

		return implode( "\n", $return );
	}

	/**
	 * get Calendar Week
	 *
	 * @param integer $d (1-6)
	 * @param integer $m (1-12)
	 * @param integer $y (1970 or 2009)
	 *
	 * @return string output
	 */
	public function calendar_week( $d = 0, $m = 0, $y = 0 ) {
		if ( is_numeric( $m ) && $m > 0 && $m < 13 ) {
			$this->today_month = $m * 1;
		}
		if ( is_numeric( $y ) && $y > 0 ) {
			$this->today_year = $y * 1;
		}
		if ( is_numeric( $d ) && $d > 0 ) {
			$this->today_day = $d * 1;
		}

		$current_datetime = mktime( 0, 0, 0, $this->today_month, $this->today_day, $this->today_year );
		$current_dow = date( 'w', $current_datetime );
		if ( 0 != $current_dow ) {
			$start_datetime = strtotime( 'last Sunday', $current_datetime );
			$end_datetime = strtotime( '+6 days', $start_datetime );
		} else {
			$start_datetime = $current_datetime;
			$end_datetime = strtotime( '+6 days', $start_datetime );
		}

		$start_date = mysql2date( get_option( 'date_format' ), date( 'Y-m-d', $start_datetime ) );
		$end_date = mysql2date( get_option( 'date_format' ), date( 'Y-m-d', $end_datetime ) );

		$days_list = array();
		$current_datetime = $start_datetime;
		while ( $current_datetime <= $end_datetime ) {
			$days_list[] = date( 'd', $current_datetime );
			$current_datetime = strtotime( '+1 day', $current_datetime );
		}

		$next_datetime = strtotime( '+1 day', $end_datetime );
		$prev_datetime = strtotime( '-1 day', $start_datetime );

		$this->next_day = date( 'd', $next_datetime );
		$this->next_month = date( 'm', $next_datetime );
		$this->next_year = date( 'Y', $next_datetime );

		$this->back_day = date( 'd', $prev_datetime );
		$this->back_month = date( 'm', $prev_datetime );
		$this->back_year = date( 'Y', $prev_datetime );

		$template = is_admin() ? calendarp_get_calendar_admin_week_template() : calendarp_get_calendar_front_week_template();
		$event_template = calendarp_get_calendar_event_template();
		$calendar_cells = $this->events_calendar->get_cells();
		$times = calendarp_get_times_list();

		$return = array();

		// Wrapper start
		$return[] = str_replace( '{mode}', 'week', $template['calendar_wrapper_start'] );
		$return[] = str_replace( '{heading}', $start_date . ' - ' . $end_date, $template['heading'] );

		// Week days row
		$return[] = $template['week_days_table_wrapper_start'];
		$return[] = $template['week_row_start'];
		$return[] = '<th class="times-col"></th>';
		foreach ( $this->day_of_week_title as $key => $dow ) {
			$return[] = str_replace( '{day_of_week}', $dow . ' ' . $days_list[ $key ], $template['week_row_cell'] );
		}
		$return[] = $template['week_row_end'];
		$return[] = $template['week_days_table_wrapper_end'];

		// The Calendar itself
		// Calendar Wrap start
		$return[] = $template['cal_rows_wrapper_start'];

		// Calendar row start (only one for week calendar)
		$return[] = $template['cal_row_start'];

		// The times column
		$return[] = $template['cal_time_col_start'];
		foreach ( $times as $time ) {
			$return[] = str_replace( '{time}', $time, $template['cal_time_content'] );
		}
		$return[] = $template['cal_time_col_end'];

		$current_datetime = $start_datetime;

		// The days columns
		foreach ( $this->day_of_week_title as $dow ) {
			$class = '';
			$today = date( 'Y-m-d', current_time( 'timestamp' ) );
			if ( date( 'Y-m-d', $current_datetime ) === $today ) {
				$class = 'today';
			}

			$return[] = str_replace( '{class}', $class, $template['cal_day_col_start'] );

			foreach ( $times as $time ) {
				$cell_id = str_replace( array( '-', ':' ), array( '', '' ), date( 'Y-m-d', $current_datetime ) . $time );
				$return[] = $template['cal_day_col_time_inner_start'];

				// Cell content
				$return[] = str_replace( '{date_time_id}', $cell_id, $template['cal_day_col_events_wrap_start'] );
				$return[] = str_replace( '{content}', date_i18n( get_option( 'date_format' ), $current_datetime ) . ' ' . $time, $template['cal_day_col_events_content'] );
				$return[] = $template['cal_day_col_events_wrap_end'];

				$return[] = $template['cal_day_col_time_inner_end'];
			}

			$return[] = $template['cal_day_col_end'];

			$current_datetime = strtotime( '+1 day', $current_datetime );
		}

		// Calendar row end
		$return[] = $template['cal_row_end'];

		// Calendar Wrap end
		$return[] = $template['cal_rows_wrapper_end'];

		// Wrapper End
		$return[] = $template['calendar_wrapper_end'];

		return implode( "\n", $return );

	}

	/**
	 * get Calendar Day
	 *
	 * @param integer $d (1-6)
	 * @param integer $m (1-12)
	 * @param integer $y (1970 or 2009)
	 *
	 * @return string output
	 */
	public function calendar_day( $d = 0, $m = 0, $y = 0 ) {
		if ( is_numeric( $m ) && $m > 0 && $m < 13 ) {
			$this->today_month = $m * 1;
		}
		if ( is_numeric( $y ) && $y > 0 ) {
			$this->today_year = $y * 1;
		}
		if ( is_numeric( $d ) && $d > 0 ) {
			$this->today_day = $d * 1;
		}

		$current_datetime = mktime( 0, 0, 0, $this->today_month, $this->today_day, $this->today_year );
		$current_dow = date( 'w', $current_datetime );

		$next_datetime = strtotime( '+1 day', $current_datetime );
		$prev_datetime = strtotime( '-1 day', $current_datetime );

		$this->next_day = date( 'd', $next_datetime );
		$this->next_month = date( 'm', $next_datetime );
		$this->next_year = date( 'Y', $next_datetime );

		$this->back_day = date( 'd', $prev_datetime );
		$this->back_month = date( 'm', $prev_datetime );
		$this->back_year = date( 'Y', $prev_datetime );

		$template = is_admin() ? calendarp_get_calendar_admin_week_template() : calendarp_get_calendar_front_week_template();
		$times = calendarp_get_times_list();

		$return = array();

		// Wrapper start
		$return[] = str_replace( '{mode}', 'day', $template['calendar_wrapper_start'] );
		$return[] = str_replace( '{heading}', mysql2date( get_option( 'date_format' ), date( 'Y-m-d', $current_datetime ) ), $template['heading'] );

		// The Calendar itself
		// Calendar Wrap start
		$return[] = $template['cal_rows_wrapper_start'];

		// Calendar row start (only one for day calendar)
		$return[] = $template['cal_row_start'];

		// The times column
		$return[] = $template['cal_time_col_start'];
		foreach ( $times as $time ) {
			$return[] = str_replace( '{time}', $time, $template['cal_time_content'] );
		}
		$return[] = $template['cal_time_col_end'];

		$return[] = str_replace( '{class}', '', $template['cal_day_col_start'] );

		foreach ( $times as $time ) {
			$cell_id = str_replace( array( '-', ':' ), array( '', '' ), date( 'Y-m-d', $current_datetime ) . $time );
			$return[] = $template['cal_day_col_time_inner_start'];

			// Cell content
			$return[] = str_replace( '{date_time_id}', $cell_id, $template['cal_day_col_events_wrap_start'] );
			$return[] = str_replace( '{content}', date_i18n( get_option( 'date_format' ), $current_datetime ) . ' ' . $time, $template['cal_day_col_events_content'] );
			$return[] = $template['cal_day_col_events_wrap_end'];

			$return[] = $template['cal_day_col_time_inner_end'];
		}

		$return[] = $template['cal_day_col_end'];

		// Calendar row end
		$return[] = $template['cal_row_end'];

		// Calendar Wrap end
		$return[] = $template['cal_rows_wrapper_end'];

		return implode( "\n", $return );

		// Header weekdays row
		$return[] = $template['week_row_start'];

		// The time column
		$return[] = str_replace( '{day_of_week}', '', $template['cal_cell_time_start'] );
		$return[] = str_replace( array( '{time}' ), array( 'Time column' ), $template['cal_cell_time_content'] );
		$return[] = str_replace( '{day_of_week}', '', $template['cal_cell_time_end'] );

		// The days of week column
		$return[] = str_replace( '{day_of_week}', $this->day_of_week_title[ $current_dow ] . ' ' . $this->today_day, $template['week_row_cell'] ) . "\n";
		$return[] = $template['week_row_end'] . "\n";

		// Fill the times list
		$times = calendarp_get_times_list();

		foreach ( $times as $time ) {
			$return[] = $template['cal_row_start'];
			$return[] = $template['cal_cell_time_start'];
			$return[] = str_replace( array( '{time}' ), array( $time ), $template['cal_cell_time_content'] );
			$return[] = $template['cal_cell_time_end'];

			$current_date = date( 'Y-m-d', $current_datetime );
			$return[] = str_replace( '{daytime_id}', str_replace( array( '-', ':' ), array( '', '' ), $current_date . $time ), $template['cal_cell_start'] );
			$return[] = $template['cal_cell_end'];

			$return[] = $template['cal_row_end'];
		}

		// Close the wrapper
		$return[] = $template['calendar_wrapper_end'];

		return implode( "\n", $return );

	}

	/**
	 * calculate First Day Of Week
	 *
	 * @return   integer
	 */
	private function _get_first_day_of_week() {
		return date( 'w', mktime( 0, 0, 0, $this->today_month, 1, $this->today_year ) );
	}

	/**
	 * calculate Last Day Of Week
	 *
	 * @return   integer
	 */
	private function _get_last_day_of_week() {
		return date( 'w', mktime( 0, 0, 0, $this->today_month, date( 't', mktime( 0, 0, 0, $this->today_month, 1, $this->today_year ) ), $this->today_year ) );
	}


	public function get_month_name( $month ) {
		global $wp_locale;

		$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
		$month_names = $wp_locale->month;

		return ucfirst( $month_names[ $month ] );
	}

	public function get_short_month_name( $month ) {
		global $wp_locale;

		$month = absint( $month ) - 1;
		$month_names = array_values( $wp_locale->month_abbrev );

		return $month_names[ $month ];
	}
}
