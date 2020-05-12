<?php

function calendarp_before_content() {
	$template = get_option( 'template' );
	?>
		<div id="primary" class="content-area">
			<div id="content" role="main" class="site-content calendar-theme-<?php echo esc_attr( $template ); ?>">
				<div class="tfwc">	
	<?php
}

function calendarp_after_content() {
	?>
				</div>
			</div>
		</div>
	<?php
}


add_action( 'calendarp_before_page_title', 'calendarp_twentyfourteen_before_elements' );
function calendarp_twentyfourteen_before_elements() {
	?>
	<article class="page type-page status-publish hentry">

		<header class="entry-header">
	<?php
}

add_action( 'calendarp_after_page_title', 'calendarp_twentyfourteen_after_elements' );
function calendarp_twentyfourteen_after_elements() {
	?>
			</header>
			<div class="entry-content"> </div>
		</article>
	<?php
}

add_filter( 'calendarp_page_archive_event_title_class', 'calendarp_twentyfourteen_archive_event_title_class' );
function calendarp_twentyfourteen_archive_event_title_class( $class ) {
	return 'entry-title';
}


add_action( 'calendarp_event_content_footer_class', 'calendarp_twentyfourteen_footer_class' );
function calendarp_twentyfourteen_footer_class( $class ) {
	return $class . ' entry-meta';
}
