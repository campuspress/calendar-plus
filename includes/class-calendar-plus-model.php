<?php

class Calendar_Plus_Model {

	private static $instance = null;
	private $charset_collate = '';

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
        }

		return self::$instance;
	}

	public function __construct() {
		global $wpdb;

		if ( ! empty( $wpdb->charset ) ) {
			$this->charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        }
		if ( ! empty( $wpdb->collate ) ) {
			$this->charset_collate .= " COLLATE $wpdb->collate";
        }

		$wpdb->calendarp_calendar = $wpdb->prefix . 'calendarp_calendar';
		$wpdb->calendarp_min_max_dates = $wpdb->prefix . 'calendarp_min_max_dates';
	}

	public function create_calendar_table() {
		global $wpdb;

		$sql = "CREATE TABLE $wpdb->calendarp_calendar (
			ID bigint(20) NOT NULL AUTO_INCREMENT,
			event_id bigint(20) NOT NULL,
			from_date DATE NOT NULL,
			until_date DATE NOT NULL,
			from_time TIME NOT NULL,
			until_time TIME NOT NULL, 
			series_number bigint(20) NOT NULL,
			PRIMARY KEY  (ID),
			KEY event_id (event_id),
			KEY from_date (from_date),
			KEY until_date (until_date),
			KEY from_time (from_time)
			) $this->charset_collate";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function create_min_max_dates_table() {
		global $wpdb;

		$sql = "CREATE TABLE $wpdb->calendarp_min_max_dates (
			ID bigint(20) NOT NULL AUTO_INCREMENT,
			event_id bigint(20) NOT NULL,
			min_date DATE NOT NULL,
			max_date DATE NOT NULL,
			PRIMARY KEY  (ID),
			KEY min_date (min_date),
			KEY max_date (max_date)
			) $this->charset_collate";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

}
