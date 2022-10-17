<?php
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}


if (
	is_plugin_active( 'formidable/formidable.php' ) ||
	is_plugin_active( 'formidable/pro/formidable-pro.php' )
) {
	include_once calendarp_get_plugin_dir() . 'admin/integration/plugins/formidable.php';
}