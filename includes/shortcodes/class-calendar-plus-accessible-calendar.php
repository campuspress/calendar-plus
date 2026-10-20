<?php
/**
 * Calendar Plus: includes/shortcodes/class-calendar-plus-accessible-calendar.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class for rendering an accessible calendar designed for screen readers
 */
class Calendar_Plus_Accessible_Calendar {


	/**
	 * Calendar component state.
	 *
	 * @var int
	 */
	public $month;

	/**
	 * Calendar component state.
	 *
	 * @var int
	 */
	public $year;

	/**
	 * Calendar component state.
	 *
	 * @var string
	 */
	public $search;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		$current_time = calendarp_wall_time();

		$this->month  = gmdate( 'n', $current_time );
		$this->year   = gmdate( 'Y', $current_time );
		$this->search = '';

		if ( null !== calendarp_form_input( 'acal_month', null, 'get' ) && is_numeric( calendarp_form_input( 'acal_month', '', 'get' ) ) && calendarp_form_input( 'acal_month', '', 'get' ) > 0 && calendarp_form_input( 'acal_month', '', 'get' ) <= 12 ) {
			$this->month = calendarp_form_input( 'acal_month', '', 'get' );
		}

		if ( null !== calendarp_form_input( 'acal_year', null, 'get' ) && false !== strtotime( calendarp_form_input( 'acal_year', '', 'get' ) ) ) {
			$this->year = calendarp_form_input( 'acal_year', '', 'get' );
		}

		if ( ! empty( calendarp_form_input( 'acal_s', '', 'get' ) ) ) {
			$this->search = calendarp_form_input( 'acal_s', '', 'get' );
		}
	}

	/**
	 * Retrieve the range of years when events take place.
	 *
	 * @return array
	 */
	private function get_year_range() {
		global $wpdb;

		$results = calendarp_schedule_read( 'SELECT MIN(min_date) AS min, MAX(max_date) AS max FROM %i', array( $wpdb->calendarp_min_max_dates ), 'row' );

		$current_time = calendarp_wall_time();

		$min = strtotime( $results->min );
		$max = strtotime( $results->max );

		if ( ! $min ) {
			$min = $current_time;
		}

		if ( ! $max ) {
			if ( $min <= $current_time ) {
				$max = $current_time;
			} else {
				$max = $min;
				$min = $current_time;
			}
		}

		return array( gmdate( 'Y', $min ), gmdate( 'Y', $max ) );
	}

	/**
	 * Render the calendar navigation form.
	 *
	 * @param string $title Title.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function render_navigation( $title ) {
		/** Calendar component state. @var WP_Locale $wp_locale */
		global $wp_locale;

		$year_range = $this->get_year_range();

		?>

		<form>
			<fieldset>
				<input type="hidden" name="acal" value="1">

				<label for="acal_s"><?php esc_html_e( 'Search Calendar Events', 'calendar-plus' ); ?></label>
				<br />
				<input type="search" id="acal_s_label" name="acal_s" value="<?php echo esc_attr( $this->search ); ?>">
				<br />

				<label for="acal_month_label"><?php esc_html_e( 'Month', 'calendar-plus' ); ?></label>
				<br />
				<select id="acal_month_label" name="acal_month">
					<?php
					for ( $m = 1; $m < 12; $m++ ) {
						printf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $m ),
							selected( $m, $this->month, false ),
							esc_html( $wp_locale->get_month( $m ) )
						);
					}
					?>
				</select>
				<br /><br />

				<label for="acal_year_label"><?php esc_html_e( 'Year', 'calendar-plus' ); ?></label>
				<br />
				<select id="acal_year_label" name="acal_year">
					<?php
					for ( $y = $year_range[1]; $y >= $year_range[0]; $y-- ) {
						printf( '<option value="%s"%s>%s</option>', esc_attr( $y ), selected( $y, $this->year, false ), esc_html( $y ) );
					}
					?>
				</select>

				<br /><br />

				<input type="submit" value="<?php esc_attr_e( 'Go', 'calendar-plus' ); ?>">
			</fieldset>
		</form>

		<?php
	}

	/**
	 * Build a list of information for a given event.
	 *
	 * @param Calendar_Plus_Event $event Event.
	 * @return array
	 */
	private function get_event_info( Calendar_Plus_Event $event ) {
		$info = array();

		$info['date'] = calendarp_get_human_read_dates( $event->ID, 'string' );

		$info['categories'] = get_the_term_list( $event->ID, 'calendar_event_category' );
		if ( $info['categories'] ) {
			$info['categories'] = sprintf(
				/* translators: %s: event category names. */
				__( 'Posted in %s', 'calendar-plus' ),
				$info['categories']
			);
		}

		$info['description'] = $event->post->post_excerpt
			? get_the_excerpt( $event->post )
			: $event->post->post_content;
		$info['description'] = wp_trim_words( $info['description'], 20 );

		return $info;
	}

	/**
	 * Render the search results screen.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function render_search_results() {
		/** Calendar component state. @var WP_Locale $wp_locale */
		global $wp_locale;

		echo wp_kses_post( '<p>' ),
		esc_html(
			sprintf(
			/* translators: 1: search phrase, 2: month name, 3: four-digit year. */
				esc_html__( 'Search results for “%1$s” in %2$s %3$d.', 'calendar-plus' ),
				esc_html( $this->search ),
				$wp_locale->get_month( $this->month ),
				$this->year
			)
		),
		wp_kses_post(
			sprintf(
				' <a href="%s">%s</a>',
				esc_url( remove_query_arg( 'acal_s' ) ),
				esc_html__( 'Return to Calendar', 'calendar-plus' )
			)
		),
		wp_kses_post( '</p>' );

		$this->render_navigation( __( 'Refine Query', 'calendar-plus' ) );

		$results = calendar_plus()->generator->get_month_dates(
			$this->month,
			$this->year,
			array(
				'search'         => $this->search,
				'grouped_by_day' => false,
			)
		);

		$events = array();

		foreach ( $results as $result ) {
			$id = $result->event_id;

			if ( ! isset( $events[ $id ] ) ) {
				$events[ $id ] = array();
			}

			$events[ $id ][] = $result;
		}

		foreach ( $events as $result ) {
			$event = calendarp_get_event( $result[0]->event_id );
			$info  = $this->get_event_info( $event );

			echo '<hr><section>';

			echo wp_kses_post( '<strong>' ), esc_html( get_the_title( $event->ID ) ), wp_kses_post( '</strong>' );

			if ( $info['date'] ) {
				echo wp_kses_post( '<br>' ), esc_html( $info['date'] );
			}

			if ( 'recurrent' === $event->get_event_type() ) {
				echo wp_kses_post( '<br>' ), esc_html(
					sprintf(
					/* translators: 1: occurrence count, 2: month name, 3: four-digit year. */
						_n( 'Occurs %1$d time in %2$s %3$d: ', 'Occurs %1$d times in %2$s %3$d: ', 'calendar-plus', 'calendar-plus' ),
						count( $result ),
						$wp_locale->get_month( $this->month ),
						$this->year
					)
				);

				$dates = array();

				foreach ( $result as $occurrence ) {
					$dates[] = date_i18n( _x( 'jS', 'day format', 'calendar-plus' ), strtotime( $occurrence->from_date ) );
				}

				echo esc_html( implode( _x( ', ', 'event day separator', 'calendar-plus' ), $dates ) );
			}

			printf(
				'<br><a href="%s">%s</a>',
				esc_url( get_the_permalink( $event->ID ) ),
				esc_html(
					sprintf( /* translators: %s: event title. */
						esc_html__( 'Continue Reading %s', 'calendar-plus' ),
						get_the_title( $event->ID )
					)
				)
			);

			echo '</section>';
		}
	}

	/**
	 * Render a single event within the calendar.
	 *
	 * @param Calendar_Plus_Event $event Event.
	 * @param string              $formatted_date Formatted date.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function render_calendar_event( Calendar_Plus_Event $event, string $formatted_date ) {
		?>
		<section class="event collapsed">
			<header>
				<h2 
					class="event-title">
					<?php echo esc_html( get_the_title( $event->post ) ); ?> (<time><?php echo esc_html( $formatted_date ); ?></time>)</h2>
			</header>
			<button class="toggle-event-details" aria-expanded="false">
				<?php
				echo wp_kses_post(
					sprintf( /* translators: %s: event title. */
						esc_html__( 'Show Details for %s', 'calendar-plus' ) . ' (<time>%s</time>)',
						get_the_title( $event->post ),
						esc_html( $formatted_date )
					)
				);
				?>
			</button>

			<div class="event-details" aria-hidden="true">
				<?php

				foreach ( $this->get_event_info( $event ) as $type => $info ) {
					if ( empty( $type ) || empty( $info ) ) {
						continue;
					}
					echo '<div class="event-section ' . esc_attr( sanitize_html_class( $type ) ) . '">' .
						wp_kses_post( $info ) .
					'</div>';
				}

				?>
				<div class="event-section event-details-link">
					<a href="<?php echo esc_url( get_the_permalink( $event->post ) ); ?>">
						<?php
							echo esc_html(
								sprintf( /* translators: %s: event title. */
									esc_html__( 'View Full Details for %s', 'calendar-plus' ),
									get_the_title( $event->post )
								)
							);
						?>
					</a>
				</div>
			</div>
		</section><hr>
		<?php
	}

	/**
	 * Render the calendar view.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function render_calendar() {
		/** Calendar component state. @var WP_Locale $wp_locale */
		global $wp_locale;

		$total_days = gmdate( 't', mktime( 0, 0, 0, $this->month, 1, $this->year ) );
		$first_dow  = gmdate( 'w', mktime( 0, 0, 0, $this->month, 1, $this->year ) );

		$previous_month = $this->month - 1;
		$previous_year  = $this->year;

		if ( $previous_month < 1 ) {
			$previous_month = 12;
			--$previous_year;
		}

		$prev_month_total_days = gmdate( 't', mktime( 0, 0, 0, $previous_month, 1, $previous_year ) );
		$prev_month_start_day  = ( $prev_month_total_days - $first_dow ) + 1;

		// Days from previous month that will be present at the start of our current month.
		$days_from_back_month = $prev_month_total_days - $prev_month_start_day;

		// Day from we start (can be negative if the previous month does not ends on saturday).
		$day = -$days_from_back_month - 1;

		$events = array();

		foreach ( calendarp_get_events_in_month( $this->month, $this->year ) as $event_info ) {
			$event = calendarp_get_event( $event_info->event_id );

			if ( 'publish' === $event->post->post_status ) {
				$events[] = array(
					'event'  => $event,
					'start'  => strtotime( $event_info->from_date ),
					'finish' => strtotime( $event_info->until_date ),
				);
			}
		}

		?>

		<div class="calendar-plus-accessible-calendar"
			data-title-fallback="<?php esc_attr_e( 'this event', 'calendar-plus' ); ?>"
			data-show-details-text="
			<?php
			/* translators: %EVENT%: literal token replaced with the event title by the details toggle. */
			esc_attr_e( 'Show Details for %EVENT%', 'calendar-plus' );
			?>
	"
			data-hide-details-text="
			<?php
			/* translators: %EVENT%: literal token replaced with the event title by the details toggle. */
			esc_attr_e( 'Hide Details for %EVENT%', 'calendar-plus' );
			?>
	">

			<nav role="navigation" aria-label="<?php esc_html_e( 'Calendar Navigation', 'calendar-plus' ); ?>">

				<p>
					<?php
					printf(
						/* translators: 1: month name, 2: four-digit year. */
						esc_html__( 'Showing events from %1$s %2$d.', 'calendar-plus' ),
						esc_html( $wp_locale->get_month( $this->month ) ),
						esc_html( $this->year )
					);
					?>
				</p>

				<?php $this->render_navigation( __( 'Calendar Navigation', 'calendar-plus' ) ); ?>

			</nav>

			<table
				aria-label="<?php esc_html_e( 'Calendar Dates', 'calendar-plus' ); ?>"
			>
				<caption>
				<?php
					printf(
						/* translators: 1: month name, 2: four-digit year. */
						esc_html__( 'Events for %1$s %2$d', 'calendar-plus' ),
						esc_html( $wp_locale->get_month( $this->month ) ),
						esc_html( $this->year )
					);
				?>
				</caption>
				<thead>
				<tr>
					<?php

					for ( $dow = 0; $dow < 7; $dow++ ) {
						echo wp_kses_post( '<th>' ), esc_html( $wp_locale->get_weekday( $dow ) ), "</th>\n";
					}

					?>
				</tr>
				</thead>
				<tbody>
				<?php

				while ( $day < $total_days ) {
					echo '<tr>';

					for ( $dow = 0; $dow < 7; $dow++ ) {
						++$day;
						$current_datetime = mktime( 0, 0, 0, $this->month, $day, $this->year );
						$ymd              = gmdate( 'Y-m-d', $current_datetime );

						printf( '<td data-date="%s">', esc_attr( $ymd ) );
						echo '<strong>' .
							'<time datetime="' . esc_attr( $ymd ) . '" title="' .
								esc_attr( $ymd ) .
							'">' .
							esc_html( gmdate( 'j', $current_datetime ) ) .
							'</time>' .
						'</strong>';

						foreach ( $events as $event ) {
							if ( $event['start'] <= $current_datetime && $event['finish'] >= $current_datetime ) {
								$formatted_time = gmdate( 'Y-m-d', $current_datetime );
								$this->render_calendar_event( $event['event'], $formatted_time );
							}
						}

						echo '</td>';
					}

					echo '</tr>';
				}

				?>
				</tbody>
			</table>

		</div>
		<?php
	}

	/**
	 * Render the accessible calendar.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function render() {

		if ( $this->search ) {
			$this->render_search_results();
		} else {
			$this->render_calendar();

			printf(
				'<a href="%s">%s</a>',
				esc_url( remove_query_arg( 'acal' ) ),
				esc_html__( 'View Interactive Version', 'calendar-plus' )
			);
		}
	}
}
