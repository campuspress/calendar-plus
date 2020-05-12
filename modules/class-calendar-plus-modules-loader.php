<?php

class Calendar_Plus_Modules_Loader {

	public function __construct() {
		$modules = apply_filters( 'calendarp_load_modules', array() );
		$modules_list = $this->list_modules();
		foreach ( $modules as $module_slug ) {
			$file = calendarp_get_plugin_dir() . 'modules/modules/' . $module_slug . '.php';
			$file = apply_filters( 'calendarp_module_file', $file, $module_slug );
			if ( array_key_exists( $module_slug, $modules_list ) && is_file( $file ) ) {
				include_once( $file );
			}
		}
	}

	public function list_modules() {
		$modules = array(
			'bp-activity-auto-updates' => array(
				'name'        => __( 'BuddyPress: Activity auto-updates', 'calendar-plus' ),
				'version'     => '1.0',
				'author'      => 'WPMU DEV',
				'author-url'  => false,
				'description' => __( 'Auto-post an activity update when something happens with your Events.', 'calendar-plus' ),
			),
		);

		return apply_filters( 'calendarp_modules_list', $modules );
	}
}

function calendarp_list_modules() {
	return calendar_plus()->modules_loader->list_modules();
}
