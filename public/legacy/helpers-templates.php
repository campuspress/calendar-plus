<?php
/**
 * Template functions used only in legacy theme integration method.
 *
 * @package CalendarPlus
 */

/**
 * PLUGABLE FUNCTIONS
 */

if ( ! function_exists( 'calendarp_advanced_search_title' ) ) {
	function calendarp_advanced_search_title() {
		if ( isset( $_GET['calendarp_searchw'] ) ) {
			$list = array(
				'searching_by_string'   => get_search_query() ? true : false,
				'searching_by_category' => is_tax(),
				'searching_by_location' => ! empty( $_GET['location'] ),
				'searching_from'        => ! empty( $_GET['from'] ),
				'searching_to'          => ! empty( $_GET['to'] ),
				'show_past_events'      => ! empty( $_GET['show-past-events'] ),
			);

			?>
			<ul class="panel advanced-search-list no-bullet">
				<?php
				foreach ( $list as $searching => $value ) {
					if ( $value ) {
						switch ( $searching ) {
							case 'searching_by_string':
								echo '<li>' . sprintf( __( '<strong>Searching for</strong> %s', 'calendar-plus' ), get_search_query() ) . '</li>';
								break;

							case 'searching_by_category':
								echo '<li>' . sprintf( __( '<strong>Category</strong> %s', 'calendar-plus' ), single_term_title( '', false ) ) . '</li>';
								break;

							case 'searching_by_location':
								$location = calendarp_get_location( $_GET['location'] );
								if ( $location ) {
									echo '<li>' . sprintf( __( '<strong>Location</strong> %s', 'calendar-plus' ), get_the_title( $location->ID ) ) . '</li>';
								}
								break;

							case 'searching_from':
								$from = mysql2date( get_option( 'date_format' ), $_GET['from'] );
								echo '<li>' . sprintf( __( '<strong>From</strong> %s', 'calendar-plus' ), $from ) . '</li>';
								break;

							case 'searching_to':
								$to = mysql2date( get_option( 'date_format' ), $_GET['to'] );
								echo '<li>' . sprintf( __( '<strong>To</strong> %s', 'calendar-plus' ), $to ) . '</li>';
								break;

							case 'show_past_events':
								echo '<li>' . __( '<span>Displaying past events</span>', 'calendar-plus' ) . '</li>';
								break;

						}
					}
				}
				?>
			</ul>

			<?php
			if ( have_posts() ) {
				$args = array();
				if ( $list['searching_by_string'] ) {
					$args['s'] = get_search_query();
				}

				if ( $list['searching_by_category'] ) {
					$term = get_queried_object();
					if ( ! empty( $term->term_id ) ) {
						$args['category'] = $term->term_id;
					}
				}

				if ( $list['searching_by_location'] ) {
					$args['location'] = absint( $_GET['location'] );
				}

				$ical_url = calendarp_get_ical_file_url( $args );
				?>
				<div id="calendarp-add-to-calendar">
					<a class="button" href="<?php echo esc_url( $ical_url ); ?>" title="<?php esc_attr_e( 'Download iCal file for search results', 'calendar-plus' ); ?>"><span class="dashicons dashicons-calendar-alt"></span> <?php _e( 'iCal file', 'calendar-plus' ); ?>
					</a>
				</div>
				<?php
			}
		} elseif ( is_post_type_archive( 'calendar_event' ) && ! is_search() && ! is_tax() && have_posts() ) {
			$ical_url = calendarp_get_ical_file_url();
			?>
			<div id="calendarp-add-to-calendar">
				<a class="button" href="<?php echo esc_url( $ical_url ); ?>" title="<?php esc_attr_e( 'Download iCal file for search results', 'calendar-plus' ); ?>"><span class="dashicons dashicons-calendar-alt"></span> <?php _e( 'iCal file', 'calendar-plus' ); ?>
				</a>
			</div>
			<?php
		} elseif ( is_tax( 'calendar_event_category' ) && have_posts() ) {
			$args = array();
			$term = get_queried_object();
			if ( ! empty( $term->term_id ) ) {
				$args['category'] = $term->term_id;
			}
			$ical_url = calendarp_get_ical_file_url( $args );
			?>
			<div id="calendarp-add-to-calendar">
				<a class="button" href="<?php echo esc_url( $ical_url ); ?>" title="<?php esc_attr_e( 'Download iCal file for search results', 'calendar-plus' ); ?>"><span class="dashicons dashicons-calendar-alt"></span> <?php _e( 'iCal file', 'calendar-plus' ); ?>
				</a>
			</div>
			<?php
		}

	}
}

if ( ! function_exists( 'calendarp_pagination' ) ) {
	function calendarp_pagination() {
		calendarp_get_template( 'content/pagination.php' );
	}
}

if ( ! function_exists( 'calendarp_before_content' ) ) {
	function calendarp_before_content() {
		calendarp_get_template( 'content/before-content.php', array( 'template' => get_option( 'template' ) ) );
	}
}

if ( ! function_exists( 'calendarp_after_content' ) ) {
	function calendarp_after_content() {
		calendarp_get_template( 'content/after-content.php', array( 'template' => get_option( 'template' ) ) );
	}
}

if ( ! function_exists( 'calendarp_event_categories_list' ) ) {
	function calendarp_event_categories_list( $event_id = false ) {
		if ( ! $event_id ) {
			$event_id = get_the_ID();
		}

		echo get_the_term_list(
			$event_id,
			'calendar_event_category',
			'<div class="event-categories-list"><span class="dashicons dashicons-category"></span> <span class="event-category">',
			'</span> , <span class="event-category">',
			'</span></div>'
		);
	}
}

if ( ! function_exists( 'calendarp_event_ical_file_button' ) ) {
	function calendarp_event_ical_file_button( $event_id = false ) {
		if ( ! $event_id ) {
			$event_id = get_the_ID();
		}

		echo '<a class="button small" href="' . esc_url( calendarp_get_ical_file_url( array( 'event' => $event_id ) ) ) . '" title="' . esc_attr__( 'Download iCal file for this event', 'calendar-plus' ) . '"><span class="dashicons dashicons-calendar-alt"></span> ' . __( 'Download iCal file for this event', 'calendar-plus' ) . '</a>';
	}
}