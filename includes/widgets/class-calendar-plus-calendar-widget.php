<?php
/**
 * Calendar Plus: includes/widgets/class-calendar-plus-calendar-widget.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Calendar_Widget. */
class Calendar_Plus_Calendar_Widget extends WP_Widget {


	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {
		add_action( 'wp_ajax_nopriv_calendarp_widget_fetch_calendar', array( $this, 'fetch_calendar' ) );
		add_action( 'wp_ajax_calendarp_widget_fetch_calendar', array( $this, 'fetch_calendar' ) );
		$widget_ops = array(
			'classname'   => 'widget_calendar widget_calendar_plus',
			'description' => __( 'A calendar of events.', 'calendar-plus' ),
		);
		parent::__construct( 'calendar-plus', __( 'Calendar+ Calendar', 'calendar-plus' ), $widget_ops );
	}

	/**
	 * Called by AJAX.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function fetch_calendar() {
		calendarp_get_calendar_widget();
		die();
	}

	/**
	 * Handle widget.
	 *
	 * @param array $args Args.
	 * @param array $instance Instance.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo wp_kses_post( $args['before_widget'] );
		if ( $title ) {
			echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );
		}
		echo '<div id="calendar_wrap" class="calendar_wrap calendarp_calendar_wrap">';

		$category = ! empty( $args['category'] ) ? $args['category'] : array();
		calendarp_get_calendar_widget( true, true, false, array( 'category' => $category ) );

		echo '</div>';

		echo wp_kses_post( $args['after_widget'] );
		wp_enqueue_style( 'calendarp-calendar-widget', calendarp_get_plugin_url() . 'public/css/calendar-widget.css', array(), calendarp_get_version() );

		wp_enqueue_script(
			'calendar-plus-widget',
			calendarp_get_plugin_url() . 'public/js/calendar-widget.js',
			array( 'jquery' ),
			calendarp_get_version(),
			true
		);

		$i10n = array(
			'ajaxurl' => admin_url( 'admin-ajax.php', is_ssl() ? 'https' : 'http' ),
		);

		wp_localize_script( 'calendar-plus-widget', 'CalendarPlusWidgeti18n', $i10n );
	}

	/**
	 * Handle update.
	 *
	 * @param array $new_instance New instance.
	 * @param array $old_instance Old instance.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = wp_strip_all_tags( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Handle form.
	 *
	 * @param array $instance Instance.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title    = wp_strip_all_tags( $instance['title'] );

		$events_page_id = calendarp_get_setting( 'events_page_id' );
		$error          = false;
		if ( ! get_post( $events_page_id ) ) {
			$settings_page_url = menu_page_url( 'calendar-plus-settings', false );
			?>
			<p style="color:red">
			<?php
			printf( /* translators: %s: URL of the Calendar Plus settings page. */
				wp_kses_post( __( 'Events page has not been set. Links in calendar will not work properly. Please set an Events page <a href="%s">here</a>', 'calendar-plus' ) ),
				esc_url( $settings_page_url )
			);
			?>
	</p>
			<?php
		}
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'calendar-plus' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}
}

add_action( 'widgets_init', 'calendarp_register_calendar_widget' );
/**
 * Handle calendarp register calendar widget.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_register_calendar_widget() {
	register_widget( 'Calendar_Plus_Calendar_Widget' );
}

/**
 * Handle calendarp get calendar widget.
 *
 * @param bool  $initial Initial.
 * @param bool  $echo Echo.
 * @param bool  $event_id Event id.
 * @param array $args Args.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_get_calendar_widget( $initial = true, $echo = true, $event_id = false, $args = array() ) {
	global $wpdb, $m, $wp_locale, $posts;
	$calendarp_widget_month = get_query_var( 'monthnum' );
	$calendarp_widget_year  = get_query_var( 'year' );

	$event_archives = get_permalink( calendarp_get_setting( 'events_page_id' ) );

	/**
	 * Filters the event archive URL
	 *
	 * @since 2.2.7.1
	 *
	 * @param string $event_archives Existing archive page link.
	 */
	$event_archives = apply_filters( 'calendarp_widget_event_archive_link', $event_archives );

	$event                  = calendarp_get_event( $event_id );
	$calendarp_widget_month = null !== calendarp_form_input( 'calendar_month', null, 'get' ) ? absint( calendarp_form_input( 'calendar_month', '', 'get' ) ) : $calendarp_widget_month;
	$calendarp_widget_year  = null !== calendarp_form_input( 'calendar_year', null, 'get' ) ? absint( calendarp_form_input( 'calendar_year', '', 'get' ) ) : $calendarp_widget_year;
	$category               = ! empty( $args['category'] ) ? $args['category'] : '';

	if ( empty( $category ) && ! empty( calendarp_form_input( 'category', '', 'get' ) ) ) {
		$category = sanitize_text_field( calendarp_form_input( 'category', '', 'get' ) );
		$category = explode( ',', $category );
	}

	$category_str = is_array( $category ) ? implode( ',', $category ) : $category;

	$key = $calendarp_widget_month . $calendarp_widget_year . $category_str;
	if ( $event ) {
		$key .= $event_id;
	}
	$key = md5( $key );

	$cache = wp_cache_get( 'get_calendar_plus_widget', 'calendar' );
	if ( $cache ) {
		if ( is_array( $cache ) && isset( $cache[ $key ] ) ) {
			if ( $echo ) {
				echo wp_kses_post( $cache[ $key ] );

				return '';
			} else {
				return $cache[ $key ];
			}
		}
	}

	if ( ! is_array( $cache ) ) {
		$cache = array();
	}

	// Quick check. If we have no posts at all, abort!
	if ( ! $posts ) {
		$gotsome = get_posts( array( 'post_type' => 'calendar_event' ) );
		if ( ! $gotsome ) {
			$cache[ $key ] = '';
			wp_cache_set( 'get_calendar_plus_widget', $cache, 'calendar' );

			return '';
		}
	}

	$first_date_generated = calendarp_get_first_date_generated();

	// If year + month is older than first generated date, set based on first generated date.
	if ( $calendarp_widget_year && $calendarp_widget_month ) {
		if ( $first_date_generated ) {
			$first_date_generated_time = strtotime( $first_date_generated );
			if ( $first_date_generated_time > strtotime( $calendarp_widget_year . '-' . $calendarp_widget_month . '-01' ) ) {
				$calendarp_widget_month = gmdate( 'm', $first_date_generated_time );
				$calendarp_widget_year  = gmdate( 'Y', $first_date_generated_time );
			}
		}
	}

	if ( null !== calendarp_form_input( 'w', null, 'get' ) ) {
		$w = '' . intval( calendarp_form_input( 'w', '', 'get' ) );
	}

	// week_begins = 0 stands for Sunday.
	$week_begins = intval( get_option( 'start_of_week' ) );

	// Let's figure out when we are.
	if ( ! empty( $calendarp_widget_month ) && ! empty( $calendarp_widget_year ) ) {
		$thismonth = '' . zeroise( intval( $calendarp_widget_month ), 2 );
		$thisyear  = '' . intval( $calendarp_widget_year );
	} elseif ( ! empty( $w ) ) {
		// We need to get the month from MySQL.
		$thisyear  = '' . intval( substr( $m, 0, 4 ) );
		$d         = ( ( $w - 1 ) * 7 ) + 6; // it seems MySQL's weeks disagree with PHP's.
		$thismonth = gmdate( 'm', strtotime( sprintf( '%04d-01-01 +%d days', (int) $thisyear, (int) $d ) ) );
	} elseif ( ! empty( $m ) ) {
		$thisyear = '' . intval( substr( $m, 0, 4 ) );
		if ( strlen( $m ) < 6 ) {
			$thismonth = '01';
		} else {
			$thismonth = '' . zeroise( intval( substr( $m, 4, 2 ) ), 2 );
		}
	} else {
		$thisyear  = gmdate( 'Y', calendarp_wall_time() );
		$thismonth = gmdate( 'm', calendarp_wall_time() );
	}

	$thismonth = max( 1, min( 12, (int) $thismonth ) );
	$thisyear  = max( 1, min( 9999, (int) $thisyear ) );
	$thismonth = zeroise( $thismonth, 2 );
	$unixmonth = mktime( 0, 0, 0, $thismonth, 1, $thisyear );
	$last_day  = gmdate( 't', $unixmonth );

	// Get the next and previous month and year with at least one post.
	$current_month_start = "$thisyear-$thismonth-01";
	$previous_month_end  = gmdate( 'Y-m-t', strtotime( "$current_month_start -1 day" ) );
	$previous            = false;

	if ( ! $first_date_generated || $previous_month_end >= $first_date_generated ) {
		$previous = calendarp_widget_adjacent_month( $current_month_start, $event ? $event->ID : 0, false, 'from_date' );
		if ( ! $previous ) {
			$previous = calendarp_widget_adjacent_month( $current_month_start, $event ? $event->ID : 0, false, 'until_date' );
		}
	}
	$month_end = "$thisyear-$thismonth-$last_day";
	$next      = calendarp_widget_adjacent_month( $month_end, $event ? $event->ID : 0, true, 'from_date' );
	if ( ! $next ) {
		$next = calendarp_widget_adjacent_month( $month_end, $event ? $event->ID : 0, true, 'until_date' );
	}

	// Backdrop.
	$calendar_output = '<div class="calendarp-backdrop"></div>';

	/* translators: Calendar caption: 1: month name, 2: 4-digit year */
	$calendar_caption = _x( '%1$s %2$s', 'calendar caption', 'calendar-plus' );
	$calendar_output .= '<table id="wp-calendar" class="wp-calendar-plus">
	<caption>' . sprintf( $calendar_caption, $wp_locale->get_month( $thismonth ), gmdate( 'Y', $unixmonth ) ) . '</caption>
	<thead>
	<tr>';

	$myweek = array();

	for ( $wdcount = 0; $wdcount <= 6; $wdcount++ ) {
		$myweek[] = $wp_locale->get_weekday( ( $wdcount + $week_begins ) % 7 );
	}

	/**
	 * Enable/Disable week initial in Calendar widget
	 *
	 * @since 2.2.7
	 *
	 * @param bool $initial True|False.
	 */
	$initial = apply_filters( 'calendarp_widget_week_initial', $initial );

	foreach ( $myweek as $wd ) {
		$day_name         = ( true === $initial ) ? $wp_locale->get_weekday_initial( $wd ) : $wp_locale->get_weekday_abbrev( $wd );
		$wd               = esc_attr( $wd );
		$calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
	}

	$calendar_output .= '
	</tr>
	</thead>

	<tfoot>
	<tr>';

	if ( $previous ) {
		$month_link       = add_query_arg(
			array(
				'calendar_month' => $previous->month,
				'calendar_year'  => $previous->year,
				'category'       => $category_str,
			)
		);
		$calendar_output .= "\n\t\t" . '<td colspan="3" class="calendar-plus-prev calendar-plus-nav" id="prev"><a data-month="' . esc_attr( $previous->month ) . '" data-year="' . esc_attr( $previous->year ) . '" data-category ="' . esc_attr( $category_str ) . '" href="' . esc_url( $month_link ) . '">&laquo; ' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $previous->month ) ) . '</a></td>';
	} else {
		$calendar_output .= "\n\t\t" . '<td colspan="3" class="calendar-plus-prev calendar-plus-nav" id="prev" class="pad">&nbsp;</td>';
	}

	$calendar_output .= "\n\t\t" . '<td class="pad">&nbsp;</td>';

	if ( $next ) {
		$month_link       = add_query_arg(
			array(
				'calendar_month' => $next->month,
				'calendar_year'  => $next->year,
				'category'       => $category_str,
			)
		);
		$calendar_output .= "\n\t\t" . '<td colspan="3" class="calendar-plus-next calendar-plus-nav" id="next"><a data-month="' . esc_attr( $next->month ) . '" data-year="' . esc_attr( $next->year ) . '" data-category ="' . esc_attr( $category_str ) . '" href="' . esc_url( $month_link ) . '">' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $next->month ) ) . ' &raquo;</a></td>';
	} else {
		$calendar_output .= "\n\t\t" . '<td colspan="3" class="calendar-plus-next calendar-plus-nav" id="next" class="pad">&nbsp;</td>';
	}

	$calendar_output .= '
	</tr>
	</tfoot>

	<tbody>
	<tr>';

	$events_for_month = calendarp_get_events_in_month( $thismonth, $thisyear, array( 'category' => $category ) );
	$daywithpost      = array();
	$ak_post_titles   = array();
	foreach ( $events_for_month as $event ) {
		$event_id = $event->event_id;
		if ( $event->from_date !== $event->until_date && $event->until_date > $event->from_date ) {
			// Event spanned in several days.
			$_from_date = $event->from_date . ' ' . $event->from_time;

			$selected_start_date = $thisyear . '-' . $thismonth . '-01 00:00:00';
			$selected_start_time = strtotime( $thisyear . '-' . $thismonth . '-01 00:00:00' );
			$start_time          = strtotime( $_from_date );

			if ( $start_time < $selected_start_time ) {
				$_from_date = $selected_start_date;
			}
			$_from_date_obj = date_create( $_from_date );

			$selected_end_date = strtotime( $thisyear . '-' . $thismonth . '-' . $last_day . ' 23:59:00' );
			$end_time          = strtotime( $event->until_date . ' ' . $event->until_time );
			if ( $end_time > $selected_end_date ) {
				$end_time = $selected_end_date;
			}

			while ( $_from_date_obj->getTimestamp() <= $end_time ) {
				$dom              = $_from_date_obj->format( 'j' );
				$daywithpost[]    = $dom;
				$ak_post_titles[] = (object) array(
					'ID'         => $event_id,
					'post_title' => get_the_title( $event_id ),
					'dom'        => $dom,
				);
				$_from_date_obj   = $_from_date_obj->add( new DateInterval( 'P1D' ) );
			}
		} else {
			$dom              = gmdate( 'j', strtotime( $event->from_date ) );
			$daywithpost[]    = $dom;
			$ak_post_titles[] = (object) array(
				'ID'         => $event_id,
				'post_title' => get_the_title( $event_id ),
				'dom'        => $dom,
			);
		}
	}

	$daywithpost = array_unique( $daywithpost );

	if ( strpos( ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ), 'MSIE' ) !== false || stripos( ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ), 'camino' ) !== false || stripos( ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ), 'safari' ) !== false ) {
		$ak_title_separator = "\n";
	} else {
		$ak_title_separator = ', ';
	}

	$ak_titles_for_day = array();

	$times = array();
	if ( $event ) {
		$_times = calendarp_schedule_read( 'SELECT from_time, until_time, DAYOFMONTH(from_date) AS dom FROM %i WHERE event_id = %d AND from_date <= %s AND from_date >= %s', array( $wpdb->calendarp_calendar, $event->ID, "$thisyear-$thismonth-$last_day", "$thisyear-$thismonth-01" ) );

		foreach ( $_times as $row ) {
			if ( $event->is_all_day_event() ) {
				$times[ $row->dom ] = __( 'All day event', 'calendar-plus' );
			} else {
				$times[ $row->dom ] = sprintf( /* translators: 1: formatted start date, 2: formatted end date. */
					_x( 'From %1$s to %2$s', 'Event times', 'calendar-plus' ),
					calendarp_get_formatted_time( $row->from_time ),
					calendarp_get_formatted_time( $row->until_time )
				);
			}
		}
	}

	if ( $ak_post_titles ) {
		foreach ( (array) $ak_post_titles as $ak_post_title ) {

			$post_title = esc_attr( $ak_post_title->post_title );

			if ( empty( $ak_titles_for_day[ 'day_' . $ak_post_title->dom ] ) ) {
				$ak_titles_for_day[ 'day_' . $ak_post_title->dom ] = '';
			}
			if ( empty( $ak_titles_for_day[ "$ak_post_title->dom" ] ) ) { // first one.
				$ak_titles_for_day[ "$ak_post_title->dom" ] = $post_title;
			} else {
				$ak_titles_for_day[ "$ak_post_title->dom" ] .= $ak_title_separator . $post_title;
			}

			if ( ! empty( $times ) && isset( $times[ "$ak_post_title->dom" ] ) ) {
				$ak_titles_for_day[ "$ak_post_title->dom" ] = $times[ "$ak_post_title->dom" ];
			}
		}
	}

	// See how much we should pad in the beginning.
	$pad = calendar_week_mod( gmdate( 'w', $unixmonth ) - $week_begins );
	if ( 0 !== $pad ) {
		$calendar_output .= "\n\t\t" . '<td colspan="' . esc_attr( $pad ) . '" class="pad">&nbsp;</td>';
	}

	$daysinmonth = intval( gmdate( 't', $unixmonth ) );
	for ( $day = 1; $day <= $daysinmonth; ++$day ) {
		if ( isset( $newrow ) && $newrow ) {
			$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
		}
		$newrow = false;

		if ( (int) gmdate( 'j', calendarp_wall_time() ) === (int) $day &&
			gmdate( 'm', calendarp_wall_time() ) === $thismonth &&
			(int) gmdate( 'Y', calendarp_wall_time() ) === (int) $thisyear ) {
			$calendar_output .= '<td id="today">';
		} else {
			$calendar_output .= '<td>';
		}

		if ( in_array( $day, $daywithpost, true ) ) {// any posts today?
			$day_link = add_query_arg(
				array(
					'from'           => "$thisyear-$thismonth-$day",
					'to'             => "$thisyear-$thismonth-$day",
					'calendar_month' => $thismonth,
					'calendar_year'  => $thisyear,
					'catgeory'       => $category_str,
				),
				$event_archives
			);

			$calendar_output .= '<a class="caledarp-calendar-day-link" href="' . esc_url( $day_link ) . '" title="' . esc_attr( $ak_titles_for_day[ $day ] ) . "\">$day</a>";
		} else {
			$calendar_output .= $day;
		}
		$calendar_output .= '</td>';

		if ( 6 === calendar_week_mod( gmdate( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins ) ) {
			$newrow = true;
		}
	}

	$pad = 7 - calendar_week_mod( gmdate( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins );
	if ( 0 !== $pad && 7 !== $pad ) {
		$calendar_output .= "\n\t\t" . '<td class="pad" colspan="' . esc_attr( $pad ) . '">&nbsp;</td>';
	}

	if ( ! function_exists( 'calendarp_events_permalink' ) ) {
		include_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';
	}

	$calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table>";
	$calendar_output .= '<br/>';
	$calendar_output .= calendarp_events_permalink( false );

	$cache[ $key ] = $calendar_output;
	wp_cache_set( 'get_calendar_plus_widget', $cache, 'calendar' );

	if ( $echo ) {
		/**
		 * Filter the HTML calendar output.
		 *
		 * @param string $calendar_output HTML output of the calendar.
		 *
		 * @since 3.0.0
		 */
		echo wp_kses_post( $calendar_output );
	} else {
		/** This filter is documented in wp-includes/general-template.php */
		return $calendar_output;
	}
}

/**
 * Handle calendarp widget adjacent month.
 *
 * @param mixed $date Date.
 * @param int   $event_id Event id.
 * @param mixed $next Next.
 * @param mixed $column Column.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_widget_adjacent_month( $date, $event_id, $next, $column ) {
	global $wpdb;
	$column   = 'until_date' === $column ? 'until_date' : 'from_date';
	$operator = $next ? '>' : '<';
	$order    = $next ? 'ASC' : 'DESC';
	$sql      = "SELECT MONTH(c.$column) AS month, YEAR(c.$column) AS year FROM %i p INNER JOIN %i c ON c.event_id = p.ID WHERE c.$column $operator %s AND p.post_type = 'calendar_event' AND p.post_status = 'publish' AND p.post_password = ''";
	$args     = array( $wpdb->posts, $wpdb->calendarp_calendar, $date );
	if ( $event_id ) {
		$sql   .= ' AND p.ID = %d';
		$args[] = absint( $event_id );
	}
	$sql .= " ORDER BY c.$column $order LIMIT 1";
	return calendarp_schedule_read( $sql, $args, 'row' );
}
