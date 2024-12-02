<?php

$stylesheet = get_stylesheet();

$file = calendarp_get_plugin_dir() . 'public/integration/themes/' . $stylesheet . '.php';
if ( is_file( $file ) ) {
	include_once( $file );
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