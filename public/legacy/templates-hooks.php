<?php
/**
 * Template hooks used only in legacy theme integration method.
 *
 * @package CalendarPlus
 */

add_action( 'calendarp_before_content', 'calendarp_before_content' );
add_action( 'calendarp_after_content', 'calendarp_after_content' );

add_action( 'calendarp_after_events_loop', 'calendarp_pagination' );

add_action( 'calendarp_content_event_content', 'calendarp_event_content' );

add_action( 'calendarp_after_page_title', 'calendarp_advanced_search_title', 1 );