<?php

function calendarp_polylang_language() {
	if ( function_exists( 'pll_current_language' ) ) {
		return pll_current_language();
	}
	return false;
}
add_filter( 'calendarp_calendar_language', 'calendarp_polylang_language' );

function calendarp_polylang_filter_events( $events_data ) {
	if ( ! function_exists( 'pll_current_language' ) || ! function_exists( 'pll_default_language' ) || ! function_exists( 'pll_get_post_language' ) ) {
		return $events_data;
	}

	$current_language = pll_current_language();
	if ( ! $current_language ) {
		$current_language = pll_default_language();
	}

	$events_data = array_filter( $events_data, function( $event ) use ( $current_language ) {
		$post_language = pll_get_post_language( $event->event_id );
		return $post_language === $current_language;
	});
	return array_values( $events_data );
}
add_filter( 'calendarp_events_data', 'calendarp_polylang_filter_events', 15, 1 );
