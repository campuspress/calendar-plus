<?php
/**
 * @package Calendar_Plus
 *
 * @wordpress-plugin
 * Plugin Name: Calendar+
 * Plugin URI:
 * Description: Create, manage, and share your calendar and upcoming events.
 * Version:     2.2.6.7
 * Author:      WPMU DEV
 * Author URI:  https://premium.wpmudev.org/
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: calendar-plus
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

include_once plugin_dir_path( __FILE__ ) . 'eb-mods/eb-mods.php';
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Return the plugin version
 * @return string Plugin version
 */
function calendarp_get_version() {
	return '2.2.6.7';
}

/**
 * Return the plugin URL
 *
 * @return string Plugin URL
 */
function calendarp_get_plugin_url() {
	return trailingslashit( plugin_dir_url( __FILE__ ) );
}

/**
 * Return the plugin directory
 *
 * @return string Plugin directory
 */
function calendarp_get_plugin_dir() {
	return trailingslashit( plugin_dir_path( __FILE__ ) );
}

/* Register hooks to run when the plugin is activated or deactivated */
register_activation_hook( __FILE__, array( 'Calendar_Plus_Deactivator', 'deactivate' ) );
register_deactivation_hook( __FILE__, array( 'Calendar_Plus_Activator', 'activate' ) );

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	require plugin_dir_path( __FILE__ ) . 'includes/ajax.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function calendar_plus() {
	return Calendar_Plus::get_instance();
}

calendar_plus();

// Load hooks
include_once 'includes/hooks/calendar-plus-hooks.php';

