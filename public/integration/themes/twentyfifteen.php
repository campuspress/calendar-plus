<?php

add_action( 'calendarp_before_page_title', 'calendarp_twentyfifteen_before_elements' );
function calendarp_twentyfifteen_before_elements() {
	?>
	<article class="page type-page status-publish hentry">

		<header class="entry-header">
	<?php
}

add_action( 'calendarp_after_page_title', 'calendarp_twentyfifteen_after_elements' );
function calendarp_twentyfifteen_after_elements() {
	?>
			</header>
			<div class="entry-content"> </div>
		</article>
	<?php
}

add_filter( 'calendarp_page_archive_event_title_class', 'calendarp_twentyfifteen_archive_event_title_class' );
function calendarp_twentyfifteen_archive_event_title_class( $class ) {
	return 'entry-title';
}

function calendarp_before_content() {
	$template = get_option( 'template' );
	?>
		<div id="primary" class="content-area">
			<main id="main" class="site-main calendar-theme-<?php echo esc_attr( $template ); ?>" role="main">
	<?php
}

function calendarp_after_content() {
	?>
			</main>
		</div>
	<?php
}
