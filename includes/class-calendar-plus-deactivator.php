<?php
/**
 * Calendar Plus: includes/class-calendar-plus-deactivator.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Fired during plugin deactivation
 *
 * @since      0.1
 *
 * @package    calendarp
 * @subpackage calendarp/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      0.1
 * @package    calendarp
 * @subpackage calendarp/includes
 * @author     CampusPress
 */
class Calendar_Plus_Deactivator {

	/**
	 * Handle deactivate.
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function deactivate() {
		// Preserve version, scheduling bounds and event data for reactivation.
		wp_clear_scheduled_hook( 'calendar_plus_delete_old_dates' );
		wp_clear_scheduled_hook( 'calendarp_init' );
		calendarp_unschedule_ical_sync_cron();
	}
}
