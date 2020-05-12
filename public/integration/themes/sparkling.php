<?php

function calendarp_before_content() {
	echo '<div id="primary" class="content-area"><div id="main" class="site-main" role="main">';
}

function calendarp_after_content() {
	echo '</div>';
}

add_action( 'calendarp_sidebar', 'calendarp_sparkling_before_sidebar' );
function calendarp_sparkling_before_sidebar() {
	echo '</div>';
}

add_action( 'calendarp_before_content_event', 'calendarp_sparkling_before_elements' );
add_action( 'calendarp_before_page_title', 'calendarp_sparkling_before_elements' );
function calendarp_sparkling_before_elements() {
	echo '<div class="blog-item-wrap"><div class="post-inner-content">';
}

add_action( 'calendarp_after_content_event', 'calendarp_sparkling_after_elements' );
add_action( 'calendarp_after_page_title', 'calendarp_sparkling_after_elements' );
function calendarp_sparkling_after_elements() {
	echo '</div></div>';
}
