<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function calendarp_get_settings() {
	return calendar_plus()->settings->get_settings();
}

function calendarp_get_setting( $name ) {
	return calendar_plus()->settings->get_setting( $name );
}

function calendarp_update_settings( $new_settings ) {
	calendar_plus()->settings->update_settings( $new_settings );
}

function calendarp_get_settings_slug() {
	return calendar_plus()->settings->get_settings_slug();
}
