<?php

add_filter( 'post_class', 'calendarp_event_post_class', 10, 3 );

add_action( 'calendarp_before_content', 'calendarp_before_content' );
add_action( 'calendarp_after_content', 'calendarp_after_content' );

add_action( 'calendarp_after_events_loop', 'calendarp_pagination' );

add_action( 'the_post', 'calendarp_set_global_event' );
add_action( 'calendarp_sidebar', 'calendarp_get_sidebar' );
add_action( 'calendarp_content_event_content', 'calendarp_event_content' );

add_action( 'calendarp_after_page_title', 'calendarp_advanced_search_title', 1 );
