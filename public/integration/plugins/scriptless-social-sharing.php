<?php

function filter_sss_content( $post_content ) {
	$setting = apply_filters( 'scriptlesssocialsharing_get_setting', 'post_types' );

	$cleanup = empty( $setting[ 'calendar_location' ]['before'] ) && empty( $setting[ 'calendar_location' ]['after'] );
	
	if ( $cleanup ) {
		$post_content = preg_replace(
			'/<div class="scriptlesssocialsharing">.*?<\/div><\/div>/s',
			'',
			$post_content
		);
	}

	return $post_content;
}

add_filter( 'the_calendar_location_content', 'filter_sss_content', 10, 1 );