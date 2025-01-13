<?php

/**
 * Manages the upgrades for the plugin
 *
 * @link       http://example.com
 * @since      0.1
 *
 * @package    calendarp
 * @subpackage calendarp/admin
 */

/**
 * The upgrades functionalities for the plugin
 *
 * @package    calendarp
 * @subpackage calendarp/admin
 * @author     Your Name <email@example.com>
 */
class Calendar_Plus_Upgrader {

	/**
	 * Upgrades the plugin if needed
	 */
	public static function maybe_upgrade() {
		$current_version = get_option( 'calendar-plus-version' );
		$real_version = calendarp_get_version();

		if ( $current_version === $real_version ) {
			return;
		}

		if ( false === $current_version ) {
			// If the version is not in DB, we'll trigger the activation
			Calendar_Plus_Activator::activate();
			return;
		}

		if ( version_compare( $current_version, '0.2', '<' ) ) {
			wp_cache_set( 'get_calendar_plus_widget', false, 'calendar' );
		}

		if ( version_compare( $current_version, '0.3', '<' ) ) {
			wp_cache_set( 'get_calendar_plus_widget', false, 'calendar' );
		}

		if ( version_compare( $current_version, '2.0-alpha-1', '<' ) ) {
			self::upgrade_2_0_alpha_1();
		}

		if ( version_compare( $current_version, '2.0-alpha-2', '<' ) ) {
			self::upgrade_2_0_alpha_2();
		}

		if ( version_compare( $current_version, '2.0-alpha-3', '<' ) ) {
			self::upgrade_2_0_alpha_3();
		}

		if ( version_compare( $current_version, '2.0-alpha-4', '<' ) ) {
			self::upgrade_2_0_alpha_4();
		}
		if( version_compare( $current_version, '2.2.6.8', '<' ) ) {
			self::upgrade_2_2_6_8();
		}

		update_option( 'calendar-plus-version', calendarp_get_version() );
	}

	private static function upgrade_2_0_alpha_1() {
		$recurrent_events = get_posts(
			array(
                'post_status'    => array( 'any', 'trash' ),
                'post_type'      => 'calendar_event',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array(
					array(
                        'key'     => '_recurrence',
                        'value'   => 'recurrent',
                        'compare' => '=',
					),
				),
			)
		);

		foreach ( $recurrent_events as $event_id ) {
			delete_post_meta( $event_id, '_recurrence' );
			calendarp_update_event_type_recurrence( $event_id, true );
		}
	}

	private static function upgrade_2_0_alpha_2() {
		$model = Calendar_Plus_Model::get_instance();
		$model->create_min_max_dates_table();
	}

	private static function upgrade_2_0_alpha_3() {
		$model = Calendar_Plus_Model::get_instance();
		$model->create_min_max_dates_table();

		// Fill the min/max dates table
		$events_ids = get_posts(
			array(
                'post_status'    => array( 'any' ),
                'post_type'      => 'calendar_event',
                'posts_per_page' => -1,
                'fields'         => 'ids',
			)
		);

		foreach ( $events_ids as $id ) {
			Calendar_Plus_Dates_Generator::refresh_event_min_max_dates( $id );
		}
	}

	private static function upgrade_2_0_alpha_4() {
		$model = Calendar_Plus_Model::get_instance();
		$model->create_calendar_table();
	}

	private static function upgrade_2_2_6_8() {
		calendarp_update_settings( array(
			'single_event_template_source' => 'calendar_plus',
			'event_archive_template_source' => 'calendar_plus',
		) );
	}
}
