<?php

/**
 * Return the templates folder inside the theme root folder
 *
 * @since  0.1
 * @return string The templates folder
 */
function calendarp_get_template_dir() {
    return apply_filters( 'calendarp_template_dir', 'calendar-plus/' );
}

function calendarp_get_template_part( $slug, $name = '' ) {
    $template = '';

    // theme_root_folder/slug-name.php
    // and theme_root_folder/calendar-plus/slug-name.php
    if ( $name ) {
        $template = locate_template( array( $slug . '-' . $name . '.php', calendarp_get_template_dir() . $slug . '-' . $name . '.php' ) );
    }

    // calendar-plus/public/templates/slug-name.php
    if ( ! $template ) {
        if( $name ) {
            $file = calendarp_get_plugin_dir() . 'public/templates/' . $slug . '-' . $name . '.php';
        }
        else {
            $file = calendarp_get_plugin_dir() . 'public/templates/' . $slug . '.php';
        }
        if ( file_exists( $file ) ) {
            $template = $file;
        }
    }

    // theme_root_folder/slug.php and theme_root_folder/calendar-plus/slug.php
    if ( ! $template ) {
        $template = locate_template( array( $slug . '.php', calendarp_get_template_dir() . $slug . '.php' ) );
    }

    $template = apply_filters( 'calendarp_get_template_part', $template, $slug, $name );

    if ( $template ) {
        load_template( $template, false );
    }
}

/**
 * Render a template file
 *
 * @param string $template_name
 * @param array  $args
 */
function calendarp_get_template( $template_name, $args = array() ) {
    $_template = calendarp_locate_template( $template_name );

    if ( ! empty( $args ) ) {
        extract( $args );
    }

    include $_template;
}

function calendarp_locate_template( $template_name ) {
    $template_dir = calendarp_get_template_dir();
    $default_dir = calendarp_get_plugin_dir() . 'public/templates/';

    $template = locate_template(
        array(
            trailingslashit( $template_dir ) . $template_name,
            $template_name,
        )
    );

    if ( ! $template ) {
        $template = $default_dir . $template_name;
    }

    return apply_filters( 'calendarp_locate_template', $template, $template_name );
}

function calendarp_is_calendarp() {
    $is_events_archive = false;
    $is_events_taxonomy = is_tax( get_object_taxonomies( 'calendar_event' ) );
    $is_event = is_singular( array( 'calendar_event' ) );
    $is_events_page = calendarp_get_setting( 'events_page_id' ) && is_page( calendarp_get_setting( 'events_page_id' ) );
    $is_post_type_archive = is_post_type_archive( 'calendar_event' );

    $is_events_archive = $is_events_page || $is_post_type_archive;

    return $is_events_archive || $is_events_taxonomy || $is_event;
}

/**
 * Set the global event variable
 *
 * @param WP_Post $post
 */
function calendarp_set_global_event( $post ) {
    global $calendar_event;

    $event = calendarp_get_event( get_the_ID() );
    if ( ! $event ) {
        return;
    }

    $calendar_event = $event;
}

function calendarp_event_post_class( $classes, $class, $post_id ) {
    if ( ! get_post( $post_id ) ) {
        return $classes;
    }

    if ( 'calendar_event' !== get_post_type( $post_id ) ) {
        return $classes;
    }

    $event = calendarp_get_event( $post_id );

    if ( $event ) {

        $classes[] = $event->is_recurring() ? 'recurring' : 'regular';

        if ( $event->is_all_day_event() ) {
            $classes[] = 'all-day';
        }

        if ( $location_id = $event->get_location_id() ) {
            $classes[] = 'location-' . $location_id;
        }

        $event_cats = get_the_terms( $event->ID, 'calendar_event_category' );
        if ( ! empty( $event_cats ) ) {
            foreach ( $event_cats as $key => $value ) {
                $classes[] = 'event-category-' . $value->slug;
            }
        }
    }

    return $classes;
}

/**
 * PLUGABLE FUNCTIONS
 */

 if ( ! function_exists( 'calendarp_get_template_title' ) ) {
	function calendarp_get_template_title() {

		$events_page_id = calendarp_get_setting( 'events_page_id' );

		$title = '';
		if ( isset( $_GET['calendarp_search'] ) ) {
			$title = __( 'Search Results', 'calendar-plus' );
		} else {
			if ( is_search() ) {
				$title = sprintf( __( 'Search Results: %s', 'calendar-plus' ), get_search_query() );
			} elseif ( is_tax() ) {
				$title = sprintf( __( 'Event Category: %s', 'calendar-plus' ), single_term_title( '', false ) );
			} elseif ( $events_page_id && is_page( $events_page_id ) ) {
				$title = get_the_title( $events_page_id );
			} else {
				$post_type_obj = get_post_type_object( 'calendar_event' );
				$title = $post_type_obj->labels->name;
			}
		}

		$title = apply_filters( 'calendarp_template_title', $title );

		return $title;
	}
}

if ( ! function_exists( 'calendarp_post_content_advanced_search_title' ) ) {
    function calendarp_post_content_advanced_search_title() {
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
            <ul class="cal-plus-search-data__list panel no-bullet">
                <?php
                foreach ( $list as $searching => $value ) {
                    if ( $value ) {
                        switch ( $searching ) {
                            case 'searching_by_string':
                                echo '<li class="cal-plus-search-data__item">' . sprintf( __( '<strong>Searching for</strong> %s', 'calendar-plus' ), get_search_query() ) . '</li>';
                                break;

                            case 'searching_by_category':
                                echo '<li class="cal-plus-search-data__item">' . sprintf( __( '<strong>Category</strong> %s', 'calendar-plus' ), single_term_title( '', false ) ) . '</li>';
                                break;

                            case 'searching_by_location':
                                $location = calendarp_get_location( $_GET['location'] );
                                if ( $location ) {
                                    echo '<li class="cal-plus-search-data__item">' . sprintf( __( '<strong>Location</strong> %s', 'calendar-plus' ), get_the_title( $location->ID ) ) . '</li>';
                                }
                                break;

                            case 'searching_from':
                                $from = mysql2date( get_option( 'date_format' ), $_GET['from'] );
                                echo '<li class="cal-plus-search-data__item">' . sprintf( __( '<strong>From</strong> %s', 'calendar-plus' ), $from ) . '</li>';
                                break;

                            case 'searching_to':
                                $to = mysql2date( get_option( 'date_format' ), $_GET['to'] );
                                echo '<li class="cal-plus-search-data__item">' . sprintf( __( '<strong>To</strong> %s', 'calendar-plus' ), $to ) . '</li>';
                                break;

                            case 'show_past_events':
                                echo '<li class="cal-plus-search-data__item">' . __( '<span>Displaying past events</span>', 'calendar-plus' ) . '</li>';
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
                <div class="cal-plus-add-to-cal">
                    <a class="button" href="<?php echo esc_url( $ical_url ); ?>" title="<?php esc_attr_e( 'Download iCal file for search results', 'calendar-plus' ); ?>">
                        <span class="dashicons dashicons-calendar-alt"></span> <?php _e( 'iCal file', 'calendar-plus' ); ?>
                    </a>
                </div>
                <?php
            } elseif ( is_post_type_archive( 'calendar_event' ) && ! is_search() && ! is_tax() && have_posts() ) {
                $ical_url = calendarp_get_ical_file_url();
                ?>
                <div class="cal-plus-add-to-cal">
                    <a class="button" href="<?php echo esc_url( $ical_url ); ?>" title="<?php esc_attr_e( 'Download iCal file for search results', 'calendar-plus' ); ?>">
                        <span class="dashicons dashicons-calendar-alt"></span> <?php _e( 'iCal file', 'calendar-plus' ); ?>
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
                <div class="cal-plus-add-to-cal">
                    <a class="button" href="<?php echo esc_url( $ical_url ); ?>" title="<?php esc_attr_e( 'Download iCal file for search results', 'calendar-plus' ); ?>">
                        <span class="dashicons dashicons-calendar-alt"></span> <?php _e( 'iCal file', 'calendar-plus' ); ?>
                    </a>
                </div>
                <?php
            }

        }
    }
}

if ( ! function_exists( 'calendarp_the_event_thumbnail' ) ) {
    function calendarp_get_the_event_thumbnail( $event_id = false, $size = 'full' ) {
        global $calendar_event;

        if ( ! $event_id && ! $calendar_event ) {
            return '';
        }

        if ( ! $event_id ) {
            $event_id = $calendar_event->ID;
        }

        if ( has_post_thumbnail( $event_id ) ) {
            return get_the_post_thumbnail( $event_id, $size );
        }

        return '';
    }
}

if ( ! function_exists( 'calendarp_post_content_pagination' ) ) {
    function calendarp_post_content_pagination() {
        calendarp_get_template( 'content/post_content-pagination.php' );
    }
}

if ( ! function_exists( 'calendarp_get_sidebar' ) ) {
    function calendarp_get_sidebar() {
        calendarp_get_template( 'sidebars/sidebar.php' );
    }
}

if ( ! function_exists( 'calendarp_event_content' ) ) {
    function calendarp_event_content( $event ) {
        if ( is_post_type_archive( 'calendar_event' ) || is_tax( 'calendar_event_category' ) ) {
            the_excerpt();
        } elseif ( is_single() ) {
            the_content();
        }
    }
}

if ( ! function_exists( 'calendarp_get_post_content_event_categories_list' ) ) {
    function calendarp_get_post_content_event_categories_list( $event_id = false ) {
        if ( ! $event_id ) {
            $event_id = get_the_ID();
        }

        echo get_the_term_list(
            $event_id,
            'calendar_event_category',
            '<div class="cal-plus-event__meta-item cal-plus-event__meta-item--categories">
            <span class="cal-plus-event__meta-item-icon dashicons dashicons-category"></span> 
            <span class="cal-plus-event__meta-item-text">',
            '</span> , <span class="event-category">',
            '</span></div>'
        );
    }
}

if ( ! function_exists( 'calendarp_event_add_to_calendars_links' ) ) {

    /**
     * Display a list of links to add an event date to a
     *
     * @TODO: Add iCal too and iterate through calendars
     *
     * @param int $event_id
     */
    function calendarp_event_add_to_calendars_links( $event_id = 0 ) {

        if ( ! $event_id ) {
            $event_id = get_the_ID();
        }

        $queried_cell = calendarp_get_queried_date_cell( $event_id );
        $links = array(
            'gcal'    => array(
                'name'    => __( 'Google Calendar', 'calendar-plus' ),
                'service' => 'gcal',
            ),
            'outlook' => array(
                'name'    => __( 'Outlook', 'calendar-plus' ),
                'service' => 'ical',
            ),
            'ical'    => array(
                'name'    => __( 'iCal File', 'calendar-plus' ),
                'service' => 'ical',
            ),
        );

        $anchors = array();
        foreach ( $links as $link ) {
            $link_url = calendarp_get_external_calendar_button_link( $link['service'], $event_id, $queried_cell );
            $anchors[] = sprintf( '<a target="_blank" href="%s">%s</a>', esc_url( $link_url ), esc_html( $link['name'] ) );
        }

        echo implode( ' | ', $anchors );
    }
}

if ( ! function_exists( 'calendarp_events_permalink' ) ) {
    function calendarp_events_permalink( $echo = true, $title = '' ) {
        $events_page_id = calendarp_get_setting( 'events_page_id' );
        if ( ! $events_page_id ) {
            return '';
        }

        if ( ! get_post( $events_page_id ) ) {
            return '';
        }

        if ( ! $title ) {
            $title = __( 'See all events &raquo;', 'calendarp' );
        }

        $link = get_permalink( $events_page_id );

        $link = '<a href="' . esc_url( $link ) . '" title="' . esc_attr__( 'See all events', 'calendarp' ) . '">' . esc_html( $title ) . '</a>';
        if ( ! $echo ) {
            return $link;
        }

        echo $link;
    }
}

if ( ! function_exists( 'calendarp_breadcrumbs' ) ) {
    function calendarp_breadcrumbs() {
        $breadcrumbs = array();

        if ( is_single() ) {
            $archive_events = calendarp_events_permalink( false, __( 'Events', 'calendarp' ) );
            $breadcrumbs = array(
                $archive_events,
                get_the_title(),
            );
        }

        if ( empty( $breadcrumbs ) ) {
            return '';
        }

        return apply_filters( 'calendarp_breadcrumbs', implode( ' / ', $breadcrumbs ) );
    }
}

if ( ! function_exists( 'calendarp_event_human_read_dates' ) ) {
    /**
     * Return human read dates for a given field
     *
     * @param string   $field date|recurrence|time
     * @param bool|int $event_id
     *
     * @return string
     */
    function calendarp_event_human_read_dates( $field, $event_id = false ) {
        if ( ! $event_id ) {
            $event_id = get_the_ID();
        }

        if ( ! $event = calendarp_get_event( $event_id ) ) {
            return '';
        }

        if ( calendarp_is_date_cell_queried() && 'date' === $field ) {
            $cell = calendarp_get_queried_date_cell( $event_id );
            // If there's a queried date cell, return just the formatted cell
            $from_date = $cell->from_date;
            $to_date = $cell->until_date;

            if ( $from_date != $to_date ) {
                return calendarp_get_formatted_date( $from_date )
                       . ' - '
                       . calendarp_get_formatted_date( $to_date );
            } else {
                return calendarp_get_formatted_date( $from_date );
            }
        } else {
            $dates = calendarp_get_human_read_dates( $event_id, 'array' );

            return isset( $dates[ $field ] ) ? $dates[ $field ] : '';
        }
    }
}

if ( ! function_exists( 'calendarp_the_event' ) ) {
    function calendarp_the_event() {
        global $calendar_event;

        return $calendar_event;
    }
}

if ( ! function_exists( 'calendarp_event_has_location' ) ) {
    function calendarp_event_has_location( $event_id = false ) {
        if ( ! $event_id ) {
            $event_id = get_the_ID();
        }

        $event = calendarp_get_event( $event_id );
        if ( ! $event ) {
            return false;
        }

        return (bool) $event->get_location();
    }
}

if ( ! function_exists( 'calendarp_the_event_location' ) ) {
    function calendarp_the_event_location( $event_id = false ) {
        if ( ! $event_id ) {
            $event_id = get_the_ID();
        }

        if ( calendarp_event_has_location( $event_id ) ) {
            $event = calendarp_get_event( $event_id );

            return $event->get_location();
        }

        return false;
    }
}

if ( ! function_exists( 'calendarp_get_location_description' ) ) {
    function calendarp_get_location_description() {
        $location = calendarp_the_event_location();
        if ( ! $location ) {
            return '';
        }

        $post = $location->get_post();

        return $post->post_content;
    }
}

if ( ! function_exists( 'calendarp_back_to_calendar_link' ) ) {
    function calendarp_back_to_calendar_link() {
        $cal_backlink = isset( $_GET['cal'] ) ? $_GET['cal'] : false;
        $parsed = parse_url( $cal_backlink );
        // This prevents XSRFs
        if ( isset( $parsed['host'] ) && $parsed['host'] !== $_SERVER['HTTP_HOST'] ) {
            return false;
        }

        return $cal_backlink;
    }
}
