<?php

function calendarp_before_content() {
	echo '<div id="main-content"><div class="container"><div id="content-area" class="clearfix"><div id="left-area">';
}

function calendarp_after_content() {
	echo '</div>';
}

add_action( 'calendarp_sidebar', 'divi_after_sidebar', 100 );
function divi_after_sidebar() {
	echo '</div></div></div>';
}


add_filter( 'post_class', 'calendarp_divi_event_post_class', 11, 3 );
function calendarp_divi_event_post_class( $classes, $class, $post_id ) {
	if ( 'calendar_event' !== get_post_type( $post_id ) ) {
		return $classes;
    }

	$event = calendarp_get_event( $post_id );

	if ( $event ) {
		$classes[] = 'et_pb_post';
	}

	return $classes;
}
