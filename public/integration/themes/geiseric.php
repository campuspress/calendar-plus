<?php

function calendarp_before_content() {
	echo '<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main" itemprop="mainContentOfPage">';
}

function calendarp_after_content() {
	echo '</main></div>';
}

//by default plugin tries to find calendarp sidebar in theme but if it fails, it seems to load default WP sidebar... not sure why.
add_action( 'calendarp_public_loaded', 'calendarp_default_sidebar_remove' );
function calendarp_default_sidebar_remove() {
	remove_action( 'calendarp_sidebar', 'calendarp_get_sidebar' );
}
