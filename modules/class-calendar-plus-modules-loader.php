<?php
/**
 * Calendar Plus: modules/class-calendar-plus-modules-loader.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Modules_Loader. */
class Calendar_Plus_Modules_Loader {

	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {
		if ( did_action( 'init' ) ) {
			$this->load_modules();
		} else {
			add_action( 'init', array( $this, 'load_modules' ), 5 );
		}
	}

	/**
	 * Load enabled modules after WordPress can initialize translations.
	 *
	 * @return void
	 */
	public function load_modules() {
		$modules      = apply_filters( 'calendarp_load_modules', array() );
		$modules_list = $this->list_modules();
		foreach ( $modules as $module_slug ) {
			$file = calendarp_get_plugin_dir() . 'modules/modules/' . $module_slug . '.php';
			$file = apply_filters( 'calendarp_module_file', $file, $module_slug );
			if ( array_key_exists( $module_slug, $modules_list ) && is_file( $file ) ) {
				include_once $file;
			}
		}
	}

	/**
	 * Handle list modules.
	 *
	 * @return mixed Calendar operation result.
	 */
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

/**
 * Handle calendarp list modules.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_list_modules() {
	return calendar_plus()->modules_loader->list_modules();
}
