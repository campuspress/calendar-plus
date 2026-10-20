<?php
/**
 * Calendar Plus: public/integration/integration.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$calendarp_stylesheet = get_stylesheet();

$calendarp_file = calendarp_get_plugin_dir() . 'public/integration/themes/' . $calendarp_stylesheet . '.php';
if ( is_file( $calendarp_file ) ) {
	include_once $calendarp_file;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if (
	is_plugin_active( 'divi-builder/divi-builder.php' )
) {
	include_once calendarp_get_plugin_dir() . 'public/integration/plugins/divi-builder.php';
}

if (
	is_plugin_active( 'polylang/polylang.php' )
) {
	include_once calendarp_get_plugin_dir() . 'public/integration/plugins/polylang.php';
}
