<?php

function calendarp_before_content() {
	?>
		<section class="content calendar-theme-hueman">
	<?php
}

function calendarp_after_content() {
	?>
		</section>
	<?php
}

add_action( 'calendarp_before_page_title', 'calendarp_hueman_before_page_title' );
function calendarp_hueman_before_page_title() {
	?>
		<div class="page-title pad group">
	<?php
}

add_action( 'calendarp_after_page_title', 'calendarp_hueman_after_page_title' );
function calendarp_hueman_after_page_title() {
	?>
		</div>
	<?php
}

add_filter( 'calendarp_page_archive_event_title_class', '__return_empty_string' );

add_action( 'calendarp_public_loaded', 'calendarp_hueman_public_loaded' );
function calendarp_hueman_public_loaded() {
	if( function_exists( 'calendarp_advanced_search_title' ) ) {
		remove_action( 'calendarp_post_content_after_page_title', 'calendarp_advanced_search_title', 1 );
		add_action( 'calendarp_post_content_after_page_title', 'calendarp_advanced_search_title', 20 );
	}

	add_action( 'calendarp_after_page_title', 'calendarp_hueman_before_advanced_search_title', 19 );
	add_action( 'calendarp_after_page_title', 'calendarp_hueman_after_advanced_search_title', 100 );
}

function calendarp_hueman_before_advanced_search_title() {
	?>
		<div class="row"><div class="columns large-12">
	<?php
}

function calendarp_hueman_after_advanced_search_title() {
	?>
		</div></div>
	<?php
}


add_action( 'wp_enqueue_scripts', 'calendarp_hueman_enqueue_styles' );
function calendarp_hueman_enqueue_styles() {
	wp_enqueue_style(
		'hueman-calendarp',
		calendarp_get_plugin_url() . 'public/integration/themes/css/hueman.css',
		[], calendarp_get_version()
	);
}
