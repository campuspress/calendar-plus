<?php

if (
	is_plugin_active( 'formidable/formidable.php' ) ||
	is_plugin_active( 'formidable/pro/formidable-pro.php' )
) {
	include_once calendarp_get_plugin_dir() . 'admin/integration/plugins/formidable.php';
}