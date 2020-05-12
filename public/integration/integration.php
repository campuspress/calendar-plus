<?php

$stylesheet = get_stylesheet();

$file = calendarp_get_plugin_dir() . 'public/integration/themes/' . $stylesheet . '.php';
if ( is_file( $file ) ) {
	include_once( $file );
}
