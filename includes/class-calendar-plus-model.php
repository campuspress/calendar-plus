<?php
/**
 * Calendar Plus: includes/class-calendar-plus-model.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Model. */
class Calendar_Plus_Model {

	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	private static $instance = null;
	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	private $charset_collate = '';

	/**
	 * Retrieve instance.
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {
		global $wpdb;

		if ( ! empty( $wpdb->charset ) ) {
			$this->charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$this->charset_collate .= " COLLATE $wpdb->collate";
		}

		$this->set_table_names();
		add_action( 'switch_blog', array( $this, 'set_table_names' ) );
	}

	/**
	 * Scope scheduling tables to the current blog, including nested switches.
	 *
	 * @return void
	 */
	public function set_table_names() {
		global $wpdb;

		$wpdb->calendarp_calendar      = $wpdb->prefix . 'calendarp_calendar';
		$wpdb->calendarp_min_max_dates = $wpdb->prefix . 'calendarp_min_max_dates';
	}

	/**
	 * Create calendar table.
	 *
	 * @return mixed Calendar operation result.
	 */
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

	/**
	 * Create min max dates table.
	 *
	 * @return mixed Calendar operation result.
	 */
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
