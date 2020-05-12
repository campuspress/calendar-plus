<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
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
 * @author     Your Name <email@example.com>
 */
class Calendar_Plus_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.1
	 */
	public static function activate() {
		update_option( 'calendar-plus-version', calendarp_get_version() );
		$model = Calendar_Plus_Model::get_instance();
		$model->create_calendar_table();
		$model->create_min_max_dates_table();

		calendar_plus()->taxonomies->register();

		if ( apply_filters( 'calendarp_flush_rewrite_rules', true ) ) {
			// This is not flushing in CampusPress, see campus-files/plugins-mods/calendar-plus.php
			//flush_rewrite_rules();
		}

		self::create_roles();
		calendarp_get_event_type_term_ids();

		calendarp_set_old_dates_cron();
	}

	private static function create_roles() {
		global $wp_roles;

		if ( ! $wp_roles ) {
			$wp_roles = new WP_Roles();
        }

		if ( get_role( 'calendarp_events_manager' ) ) {
			remove_role( 'calendarp_events_manager' );
        }

		add_role( 'calendarp_events_manager', __( 'Calendar Plus Manager', 'calendar-plus' ), array(
			'read'                   => true,
			'read_private_pages'     => true,
			'read_private_posts'     => true,
			'edit_posts'             => true,
			'edit_pages'             => true,
			'edit_published_posts'   => true,
			'edit_published_pages'   => true,
			'edit_private_pages'     => true,
			'edit_private_posts'     => true,
			'edit_others_pages'      => true,
			'publish_posts'          => true,
			'publish_pages'          => true,
			'delete_posts'           => true,
			'delete_pages'           => true,
			'delete_private_pages'   => true,
			'delete_private_posts'   => true,
			'delete_published_pages' => true,
			'delete_published_posts' => true,
			'manage_categories'      => true,
			'unfiltered_html'        => true,
			'upload_files'           => true,
		) );

		$calendar_plus_capabilities = calendarp_get_default_capabilities();

		foreach ( $calendar_plus_capabilities as $capability ) {
			$wp_roles->add_cap( 'calendarp_events_manager', $capability );
			$wp_roles->add_cap( 'administrator', $capability );
		}

	}
}
