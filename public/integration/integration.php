<?php

$stylesheet = get_stylesheet();

$file = calendarp_get_plugin_dir() . 'public/integration/themes/' . $stylesheet . '.php';
if ( is_file( $file ) ) {
	include_once( $file );
}

if (
	is_plugin_active( 'divi-builder/divi-builder.php' )
) {
	include_once calendarp_get_plugin_dir() . 'public/integration/plugins/divi-builder.php';
}