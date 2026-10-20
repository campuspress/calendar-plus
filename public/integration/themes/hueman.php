<?php
/**
 * Calendar Plus: public/integration/themes/hueman.php.
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
		<section class="content calendar-theme-hueman">
	<?php
}

/**
 * Handle calendarp after content.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_after_content() {
	?>
		</section>
	<?php
}

add_action( 'calendarp_before_page_title', 'calendarp_hueman_before_page_title' );
/**
 * Handle calendarp hueman before page title.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_hueman_before_page_title() {
	?>
		<div class="page-title pad group">
	<?php
}

add_action( 'calendarp_after_page_title', 'calendarp_hueman_after_page_title' );
/**
 * Handle calendarp hueman after page title.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_hueman_after_page_title() {
	?>
		</div>
	<?php
}

add_filter( 'calendarp_page_archive_event_title_class', '__return_empty_string' );

add_action( 'calendarp_public_loaded', 'calendarp_hueman_public_loaded' );
/**
 * Handle calendarp hueman public loaded.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_hueman_public_loaded() {
	remove_action( 'calendarp_after_page_title', 'calendarp_advanced_search_title', 1 );
	add_action( 'calendarp_after_page_title', 'calendarp_advanced_search_title', 20 );

	add_action( 'calendarp_after_page_title', 'calendarp_hueman_before_advanced_search_title', 19 );
	add_action( 'calendarp_after_page_title', 'calendarp_hueman_after_advanced_search_title', 100 );
}

/**
 * Handle calendarp hueman before advanced search title.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_hueman_before_advanced_search_title() {
	?>
		<div class="row"><div class="columns large-12">
	<?php
}

/**
 * Handle calendarp hueman after advanced search title.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_hueman_after_advanced_search_title() {
	?>
		</div></div>
	<?php
}


add_action( 'wp_enqueue_scripts', 'calendarp_hueman_enqueue_styles' );
/**
 * Handle calendarp hueman enqueue styles.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_hueman_enqueue_styles() {
	wp_enqueue_style(
		'hueman-calendarp',
		calendarp_get_plugin_url() . 'public/integration/themes/css/hueman.css',
		array(),
		calendarp_get_version()
	);
}
