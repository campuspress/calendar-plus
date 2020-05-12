<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
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
 * @author     Your Name <email@example.com>
 */
class Calendar_Plus_Deactivator {

	public static function deactivate() {
		delete_option( 'calendar-plus-version' );
		delete_option( 'calendarp_first_date_generated' );
		wp_clear_scheduled_hook( 'calendar_plus_delete_old_dates' );
		calendarp_unschedule_ical_sync_cron();
	}

}
