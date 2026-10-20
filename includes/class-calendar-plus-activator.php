<?php
/**
 * Calendar Plus: includes/class-calendar-plus-activator.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Fired during plugin activation
 *
 * @since      0.1
 *
 * @package    calendarp
 * @subpackage calendarp/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.1
 * @package    calendarp
 * @subpackage calendarp/includes
 * @author     CampusPress
 */
class Calendar_Plus_Activator {

	/**
	 * Short Description. (use period).
	 *
	 * Long Description.
	 *
	 * @since    0.1
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function activate() {
		update_option( 'calendar-plus-version', calendarp_get_version() );
		$model = Calendar_Plus_Model::get_instance();
		$model->create_calendar_table();
		$model->create_min_max_dates_table();

		calendar_plus()->taxonomies->register();

		self::create_roles();
	}

	/**
	 * Create roles.
	 *
	 * @return mixed Calendar operation result.
	 */
	private static function create_roles() {
		$roles = wp_roles();

		$new_role = ! get_role( 'calendarp_events_manager' );
		if ( $new_role ) {
			add_role(
				'calendarp_events_manager',
				__( 'Calendar Plus Manager', 'calendar-plus' ),
				array(
					'read'         => true,
					'upload_files' => true,
				)
			);
		}

		$calendar_plus_capabilities = calendarp_get_default_capabilities();
		$manager                    = $roles->get_role( 'calendarp_events_manager' );
		$administrator              = $roles->get_role( 'administrator' );

		foreach ( $calendar_plus_capabilities as $capability ) {
			if ( $new_role && $manager ) {
				$manager->add_cap( $capability );
			}
			if ( $administrator ) {
				$administrator->add_cap( $capability );
			}
		}
	}
}
