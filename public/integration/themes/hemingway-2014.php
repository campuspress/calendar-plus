<?php
/**
 * Calendar Plus: public/integration/themes/hemingway-2014.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


add_action( 'calendarp_after_sidebar', 'calendarp_hemingway_after_sidebar' );
/**
 * Handle calendarp hemingway after sidebar.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_hemingway_after_sidebar() {
	?>
		<div class="clear"></div>
	</div><!-- .wrapper -->
	<?php
}
add_action( 'calendarp_before_content_event', 'calendarp_hemingway_before_content_event' );
/**
 * Handle calendarp hemingway before content event.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_hemingway_before_content_event() {
	?>
		<div class="post">
	<?php
}
add_action( 'calendarp_after_content_event', 'calendarp_hemingway_after_content_event' );
/**
 * Handle calendarp hemingway after content event.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_hemingway_after_content_event() {
	?>
		</div><!-- .post -->
	<?php
}

/**
 * Handle calendarp before content.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_before_content() {
	?>
	<div class="wrapper section-inner">
		<div class="content left">
			<div class="posts">
				
	<?php
}

/**
 * Handle calendarp after content.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_after_content() {
	?>
			</div><!-- .posts -->

			<div class="clear"></div>
		</div><!-- .content left -->
	<?php
}
