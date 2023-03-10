<?php

class Calendar_Plus_Calendar_Widget extends WP_Widget {

	public function __construct() {
		add_action( 'wp_ajax_nopriv_calendarp_widget_fetch_calendar', array( $this, 'fetch_calendar' ) );
		add_action( 'wp_ajax_calendarp_widget_fetch_calendar', array( $this, 'fetch_calendar' ) );
		$widget_ops = array( 'classname' => 'widget_calendar widget_calendar_plus', 'description' => __( 'A calendar of events.' ) );
		parent::__construct( 'calendar-plus', __( 'Calendar+ Calendar', 'calendar-plus' ), $widget_ops );
	}

	/**
	 * Called by AJAX
	 */
	public function fetch_calendar() {
		calendarp_get_calendar_widget();
		die();
	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		echo '<div id="calendar_wrap" class="calendar_wrap calendarp_calendar_wrap">';

		$category = ! empty( $args['category'] ) ? $args['category'] : array();
		calendarp_get_calendar_widget( true, true, false, array( 'category' => $category ) );

		echo '</div>';

		$gif_url = includes_url( 'images/spinner.gif', is_ssl() ? 'https' : 'http' );

		echo <<<EOT
	<style>
		.calendarp-backdrop {
			background-image: url( '$gif_url' );
			background-color: white;
			background-repeat: no-repeat;
			background-position: center;
			position:absolute;
			top:0;
			left:0;
			right:0;
			bottom:0;
			opacity:0.7;
			z-index:15000;
			display:none;
		}
		.calendarp_calendar_wrap {
			position:relative;
		}
	</style>
EOT;
		echo $args['after_widget'];

		wp_enqueue_script(
			'calendar-plus-widget',
			calendarp_get_plugin_url() . 'public/js/calendar-widget.js',
			[ 'jquery' ], calendarp_get_version(), true
		);

		$i10n = array(
			'ajaxurl' => admin_url( 'admin-ajax.php', is_ssl() ? 'https' : 'http' ),
		);

		wp_localize_script( 'calendar-plus-widget', 'CalendarPlusWidgeti18n', $i10n );
	}

	/**
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	/**
	 * @param array $instance
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = strip_tags( $instance['title'] );

		$events_page_id = calendarp_get_setting( 'events_page_id' );
		$error = false;
		if ( ! get_post( $events_page_id ) ) {
			$settings_page_url = menu_page_url( 'calendar-plus-settings', false );
			?>
			<p style="color:red"><?php printf( __( 'Events page has not been set. Links in calendar will not work property. Please set an Events page <a href="%s">here</a>', 'calendar-plus' ), $settings_page_url ); ?></p>
			<?php
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

}

add_action( 'widgets_init', 'calendarp_register_calendar_widget' );
function calendarp_register_calendar_widget() {
	register_widget( 'Calendar_Plus_Calendar_Widget' );
}

function calendarp_get_calendar_widget( $initial = true, $echo = true, $event_id = false, $args = array() ) {
	global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;

	$event_archives = get_permalink( calendarp_get_setting( 'events_page_id' ) );
	$event_archives = apply_filters( 'calendarp_widget_event_archive_link', $event_archives );

	$event = calendarp_get_event( $event_id );
	$monthnum = isset( $_GET['calendar_month'] ) ? absint( $_GET['calendar_month'] ) : $monthnum;
	$year = isset( $_GET['calendar_year'] ) ? absint( $_GET['calendar_year'] ) : $year;
	$category = ! empty( $args['category'] ) ? $args['category'] : '';

	if ( empty( $category ) && isset( $_GET['category'] ) ) {
		$category = sanitize_text_field( $_GET['category'] );
		$category = explode( ',', $category );
	}

    $category_str = is_array( $category ) ? implode(',', $category) : $category;

	$key = $monthnum . $year . $category_str;
	if ( $event ) {
		$key .= $event_id;
	}
	$key = md5( $key );

	if ( $cache = wp_cache_get( 'get_calendar_plus_widget', 'calendar' ) ) {
		if ( is_array( $cache ) && isset( $cache[ $key ] ) ) {
			if ( $echo ) {
				echo $cache[ $key ];

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

	if ( isset( $_GET['w'] ) ) {
		$w = '' . intval( $_GET['w'] );
	}

	// week_begins = 0 stands for Sunday
	$week_begins = intval( get_option( 'start_of_week' ) );

	// Let's figure out when we are
	if ( ! empty( $monthnum ) && ! empty( $year ) ) {
		$thismonth = '' . zeroise( intval( $monthnum ), 2 );
		$thisyear = '' . intval( $year );
	} elseif ( ! empty( $w ) ) {
		// We need to get the month from MySQL
		$thisyear = '' . intval( substr( $m, 0, 4 ) );
		$d = ( ( $w - 1 ) * 7 ) + 6; //it seems MySQL's weeks disagree with PHP's
		$thismonth = $wpdb->get_var( "SELECT DATE_FORMAT((DATE_ADD('{$thisyear}0101', INTERVAL $d DAY) ), '%m')" );
	} elseif ( ! empty( $m ) ) {
		$thisyear = '' . intval( substr( $m, 0, 4 ) );
		if ( strlen( $m ) < 6 ) {
			$thismonth = '01';
		} else {
			$thismonth = '' . zeroise( intval( substr( $m, 4, 2 ) ), 2 );
		}
	} else {
		$thisyear = gmdate( 'Y', current_time( 'timestamp' ) );
		$thismonth = gmdate( 'm', current_time( 'timestamp' ) );
	}

	$unixmonth = mktime( 0, 0, 0, $thismonth, 1, $thisyear );
	$last_day = date( 't', $unixmonth );

	// Get the next and previous month and year with at least one post
	$q = "SELECT MONTH(c.from_date) AS month, YEAR(c.from_date) AS year
		FROM $wpdb->posts p
		INNER JOIN $wpdb->calendarp_calendar c ON c.event_id = p.ID
		WHERE c.from_date < '$thisyear-$thismonth-01'";

	if ( $event ) {
		$q .= $wpdb->prepare( ' AND p.ID = %d', $event->ID );
	}

	$q .= " AND p.post_type = 'calendar_event' AND p.post_status = 'publish'
			ORDER BY c.from_date DESC
			LIMIT 1";

	$previous = $wpdb->get_row( $q );

	// Its possible for event to end in previous month but start even earlier. 
	// TODO fix it for events that span across 2+ months.
	if( !$previous ) {
		$q = "SELECT MONTH(c.until_date) AS month, YEAR(c.until_date) AS year
			FROM $wpdb->posts p
			INNER JOIN $wpdb->calendarp_calendar c ON c.event_id = p.ID
			WHERE c.until_date < '$thisyear-$thismonth-01'";

		if ( $event ) {
			$q .= $wpdb->prepare( ' AND p.ID = %d', $event->ID );
		}

		$q .= " AND p.post_type = 'calendar_event' AND p.post_status = 'publish'
				ORDER BY c.until_date DESC
				LIMIT 1";

		$previous = $wpdb->get_row( $q );
	}

	$q = "SELECT MONTH(c.from_date) AS month, YEAR(c.from_date) AS year
		FROM $wpdb->posts p
		INNER JOIN $wpdb->calendarp_calendar c ON c.event_id = p.ID
		WHERE c.from_date > '$thisyear-$thismonth-{$last_day}'";

	if ( $event ) {
		$q .= $wpdb->prepare( ' AND p.ID = %d', $event->ID );
	}

	$q .= " AND p.post_type = 'calendar_event' AND p.post_status = 'publish'
			ORDER BY c.from_date ASC
			LIMIT 1";

	$next = $wpdb->get_row( $q );
	
	// Its possible for event to start in current month but end in next.
	// TODO fix it for events that span across 2+ months.
	if( !$next ) {
		$q = "SELECT MONTH(c.until_date) AS month, YEAR(c.until_date) AS year
		FROM $wpdb->posts p
		INNER JOIN $wpdb->calendarp_calendar c ON c.event_id = p.ID
		WHERE c.until_date > '$thisyear-$thismonth-{$last_day}'";

		if ( $event ) {
			$q .= $wpdb->prepare( ' AND p.ID = %d', $event->ID );
		}

		$q .= " AND p.post_type = 'calendar_event' AND p.post_status = 'publish'
				ORDER BY c.until_date ASC
				LIMIT 1";

		$next = $wpdb->get_row( $q );
	}


	// Backdrop
	$calendar_output = '<div class="calendarp-backdrop"></div>';

	/* translators: Calendar caption: 1: month name, 2: 4-digit year */
	$calendar_caption = _x( '%1$s %2$s', 'calendar caption' );
	$calendar_output .= '<table id="wp-calendar" class="wp-calendar-plus">
	<caption>' . sprintf( $calendar_caption, $wp_locale->get_month( $thismonth ), date( 'Y', $unixmonth ) ) . '</caption>
	<thead>
	<tr>';

	$myweek = array();

	for ( $wdcount = 0; $wdcount <= 6; $wdcount++ ) {
		$myweek[] = $wp_locale->get_weekday( ( $wdcount + $week_begins ) % 7 );
	}

	$initial = apply_filters( 'calendarp_widget_week_initial', $initial );

	foreach ( $myweek as $wd ) {
		$day_name = ( true == $initial ) ? $wp_locale->get_weekday_initial( $wd ) : $wp_locale->get_weekday_abbrev( $wd );
		$wd = esc_attr( $wd );
		$calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
	}

	$calendar_output .= '
	</tr>
	</thead>

	<tfoot>
	<tr>';

	if ( $previous ) {
		$month_link = add_query_arg(
			array(
				'calendar_month' => $previous->month,
				'calendar_year'  => $previous->year,
				'category'       => $category_str
			)
		);
		$calendar_output .= "\n\t\t" . '<td colspan="3" class="calendar-plus-prev calendar-plus-nav" id="prev"><a data-month="' . esc_attr( $previous->month ) . '" data-year="' . esc_attr( $previous->year ) . '" data-category ="'. esc_attr( $category_str ) .'" href="' . esc_url( $month_link ) . '">&laquo; ' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $previous->month ) ) . '</a></td>';
	} else {
		$calendar_output .= "\n\t\t" . '<td colspan="3" class="calendar-plus-prev calendar-plus-nav" id="prev" class="pad">&nbsp;</td>';
	}

	$calendar_output .= "\n\t\t" . '<td class="pad">&nbsp;</td>';

	if ( $next ) {
		$month_link = add_query_arg(
			array(
				'calendar_month' => $next->month,
				'calendar_year'  => $next->year,
				'category'       => $category_str
			)
		);
		$calendar_output .= "\n\t\t" . '<td colspan="3" class="calendar-plus-next calendar-plus-nav" id="next"><a data-month="' . esc_attr( $next->month ) . '" data-year="' . esc_attr( $next->year ) . '" data-category ="'. esc_attr( $category_str ) . '" href="' . esc_url( $month_link ) . '">' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $next->month ) ) . ' &raquo;</a></td>';
	} else {
		$calendar_output .= "\n\t\t" . '<td colspan="3" class="calendar-plus-next calendar-plus-nav" id="next" class="pad">&nbsp;</td>';
	}

	$calendar_output .= '
	</tr>
	</tfoot>

	<tbody>
	<tr>';

	$events_for_month = calendarp_get_events_in_month( $thismonth, $thisyear, array( 'category' => $category ) );
	$daywithpost = array();
	$ak_post_titles = array();
	foreach ( $events_for_month as $event ) {
		$event_id = $event->event_id;
		if ( $event->from_date != $event->until_date && $event->until_date > $event->from_date ) {
			// Event spanned in several days
			$_from_date = $event->from_date  . ' ' . $event->from_time;

			$selected_start_date = $thisyear . '-' . $thismonth . '-01 00:00:00';
			$selected_start_time = strtotime( $thisyear . '-' . $thismonth . '-01 00:00:00' );
			$start_time = strtotime( $_from_date );

			if( $start_time < $selected_start_time ) {
				$_from_date = $selected_start_date;
			}
			$_from_date_obj = date_create( $_from_date );

			$selected_end_date = strtotime( $thisyear . '-' . $thismonth . '-' . $last_day . ' 23:59:00' );
			$end_time          = strtotime( $event->until_date . ' ' . $event->until_time );
			if( $end_time > $selected_end_date ) {
				$end_time = $selected_end_date;
			}

			while (  $_from_date_obj->getTimestamp() <= $end_time ) {
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
			$dom              = date( 'j', strtotime( $event->from_date ) );
			$daywithpost[]    = $dom;
			$ak_post_titles[] = (object) array(
				'ID'         => $event_id,
				'post_title' => get_the_title( $event_id ),
				'dom'        => $dom,
			);
		}
	}

	$daywithpost = array_unique( $daywithpost );

	if ( strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE' ) !== false || stripos( $_SERVER['HTTP_USER_AGENT'], 'camino' ) !== false || stripos( $_SERVER['HTTP_USER_AGENT'], 'safari' ) !== false ) {
		$ak_title_separator = "\n";
	} else {
		$ak_title_separator = ', ';
	}

	$ak_titles_for_day = array();

	$times = array();
	if ( $event ) {
		$q = $wpdb->prepare( "SELECT from_time, until_time, DAYOFMONTH(from_date) as dom FROM $wpdb->calendarp_calendar
			WHERE event_id = %d
			AND from_date <= '{$thisyear}-{$thismonth}-{$last_day}'
			AND from_date >= '{$thisyear}-{$thismonth}-01'",
			$event->ID
		);

		$_times = $wpdb->get_results( $q );
		foreach ( $_times as $row ) {
			if ( $event->is_all_day_event() ) {
				$times[ $row->dom ] = __( 'All day event', 'calendar-plus' );
			} else {
				$times[ $row->dom ] = sprintf( _x( 'From %1$s to %2$s', 'Event times', 'calendar-plus' ), calendarp_get_formatted_time( $row->from_time ), calendarp_get_formatted_time( $row->until_time ) );
			}
		}
	}

	if ( $ak_post_titles ) {
		foreach ( (array) $ak_post_titles as $ak_post_title ) {

			$post_title = esc_attr( $ak_post_title->post_title, $ak_post_title->ID );

			if ( empty( $ak_titles_for_day[ 'day_' . $ak_post_title->dom ] ) ) {
				$ak_titles_for_day[ 'day_' . $ak_post_title->dom ] = '';
			}
			if ( empty( $ak_titles_for_day["$ak_post_title->dom"] ) ) { // first one
				$ak_titles_for_day["$ak_post_title->dom"] = $post_title;
			} else {
				$ak_titles_for_day["$ak_post_title->dom"] .= $ak_title_separator . $post_title;
			}

			if ( ! empty( $times ) && isset( $times["$ak_post_title->dom"] ) ) {
				$ak_titles_for_day["$ak_post_title->dom"] = $times["$ak_post_title->dom"];
			}
		}
	}

	// See how much we should pad in the beginning
	$pad = calendar_week_mod( date( 'w', $unixmonth ) - $week_begins );
	if ( 0 != $pad ) {
		$calendar_output .= "\n\t\t" . '<td colspan="' . esc_attr( $pad ) . '" class="pad">&nbsp;</td>';
	}

	$daysinmonth = intval( date( 't', $unixmonth ) );
	for ( $day = 1; $day <= $daysinmonth; ++$day ) {
		if ( isset( $newrow ) && $newrow ) {
			$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
		}
		$newrow = false;

		if ( gmdate( 'j', current_time( 'timestamp' ) ) == $day &&
			 gmdate( 'm', current_time( 'timestamp' ) ) == $thismonth &&
			 gmdate( 'Y', current_time( 'timestamp' ) ) == $thisyear ) {
			$calendar_output .= '<td id="today">';
		} else {
			$calendar_output .= '<td>';
		}

		if ( in_array( $day, $daywithpost ) ) {// any posts today?
			$day_link = add_query_arg(
				array(
					'from'           => "$thisyear-$thismonth-$day",
					'to'             => "$thisyear-$thismonth-$day",
					'calendar_month' => $thismonth,
					'calendar_year'  => $thisyear,
					'catgeory'       => $category_str
				),
				$event_archives
			);

			$calendar_output .= '<a class="caledarp-calendar-day-link" href="' . esc_url( $day_link ) . '" title="' . esc_attr( $ak_titles_for_day[ $day ] ) . "\">$day</a>";
		} else {
			$calendar_output .= $day;
		}
		$calendar_output .= '</td>';

		if ( 6 == calendar_week_mod( date( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins ) ) {
			$newrow = true;
		}
	}

	$pad = 7 - calendar_week_mod( date( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins );
	if ( 0 != $pad && 7 != $pad ) {
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
		 *
		 */
		echo $calendar_output;
	} else {
		/** This filter is documented in wp-includes/general-template.php */
		return $calendar_output;
	}
}
