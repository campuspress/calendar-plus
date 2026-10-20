<?php
/**
 * Calendar Plus: public/integration/themes/divi.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Handle calendarp before content.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_before_content() { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound -- Preserve this existing public API or native callback signature; compatibility includes parameter names and the historical class/property name.
	echo '<div id="main-content"><div class="container"><div id="content-area" class="clearfix"><div id="left-area">';
}

/**
 * Handle calendarp after content.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_after_content() {
	echo '</div>';
}

add_action( 'calendarp_sidebar', 'calendarp_divi_after_sidebar', 100 );
/**
 * Handle calendarp divi after sidebar.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_divi_after_sidebar() {
	echo '</div></div></div>';
}


add_filter( 'post_class', 'calendarp_divi_event_post_class', 11, 3 );
/**
 * Handle calendarp divi event post class.
 *
 * @param mixed $classes Classes.
 * @param mixed $class Class.
 * @param int   $post_id Post id.
 *
 * @return mixed Calendar operation result.
 */
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
