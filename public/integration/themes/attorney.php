<?php
/**
 * Calendar Plus: public/integration/themes/attorney.php.
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
function calendarp_before_content() {
	?>
	<div id="content" class="clearfix calendar-theme-attorney">
	<div id="main" class="col620 clearfix" role="main">
	<?php
}

/**
 * Handle calendarp after content.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_after_content() {
	?>
	</div>
	<?php
}

add_action( 'calendarp_sidebar', 'calendarp_attorney_after_sidebar', 50 );
/**
 * Handle calendarp attorney after sidebar.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_attorney_after_sidebar() {
	?>
	</div>
	<?php
}

add_action( 'wp_enqueue_scripts', 'calendarp_attorney_enqueue_styles' );
/**
 * Handle calendarp attorney enqueue styles.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_attorney_enqueue_styles() {
	wp_enqueue_style(
		'attorney-calendarp',
		calendarp_get_plugin_url() . 'public/integration/themes/css/attorney.css',
		array(),
		calendarp_get_version()
	);
}
