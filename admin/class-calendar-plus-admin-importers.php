<?php
/**
 * Calendar Plus: admin/class-calendar-plus-admin-importers.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Calendar Plus importers and authenticated batch handlers.
 *
 * @package Calendar_Plus
 */

/** Connects importers to WordPress administration. */
class Calendar_Plus_Admin_Importers {
	/**
	 * Loaded importer instances.
	 *
	 * @var array
	 */
	public $importers = array();

	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'add_importers' ) );
		add_action( 'wp_ajax_calendarp_tec_import_batch', array( $this, 'import_batch' ) );
		add_action( 'wp_import_insert_post', array( $this, 'maybe_generate_event_dates' ), 15, 4 );
	}

	/**
	 * Add importers.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function add_importers() {
		register_importer(
			'calendar-plus-the-events-calendar',
			__( 'The Events Calendar (Calendar+)', 'calendar-plus' ),
			__( 'Import Events from The Events Calendar Plugin to Calendar+', 'calendar-plus' ),
			array( $this, 'the_events_calendar_importer' )
		);
	}

	/**
	 * Handle the events calendar importer.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function the_events_calendar_importer() {
		$this->load_importer()->dispatch();
	}

	/**
	 * Handle import batch.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function import_batch() {
		$this->load_importer()->ajax_batch();
	}

	/**
	 * Load the WordPress importer API and the TEC adapter.
	 *
	 * @return Calendar_Plus_The_Events_Calendar_Importer Importer.
	 */
	private function load_importer() {
		require_once ABSPATH . 'wp-admin/includes/import.php';
		if ( ! class_exists( 'WP_Importer' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		}
		require_once __DIR__ . '/importers/class-calendar-plus-the-events-calendar-importer.php';
		$this->importers['the-events-calendar'] = new Calendar_Plus_The_Events_Calendar_Importer();
		return $this->importers['the-events-calendar'];
	}

	/**
	 * Generate native schedules after a standard WordPress import.
	 *
	 * @param int   $post_id Destination ID.
	 * @param int   $original_post_id Source ID.
	 * @param array $postdata Destination fields.
	 * @param array $post Source record.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function maybe_generate_event_dates( $post_id, $original_post_id, $postdata, $post ) {
		if ( 'calendar_event' !== $postdata['post_type'] || empty( $post['postmeta'] ) ) {
			return;
		}
		foreach ( $post['postmeta'] as $meta ) {
			if ( ! in_array( $meta['key'], array( '_standard_rules', '_datespan_rules', '_recurring_rules' ), true ) || ! is_serialized( $meta['value'] ) ) {
				continue;
			}
			// Source exports are untrusted. Never instantiate PHP objects from XML.
			$rules = unserialize( $meta['value'], array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Legacy native format; object instantiation is explicitly disabled.
			if ( is_array( $rules ) && $this->safe_rules( $rules ) ) {
				$event = Calendar_Plus_Event::get_instance( $post_id );
				if ( $event ) {
					$event->update_rules( $rules );
				}
			}
		}
	}

	/**
	 * Accept only bounded scalar rule arrays from native exports.
	 *
	 * @param array $rules Rules.
	 * @param int   $depth Current nesting depth.
	 * @return bool Whether the rules are safe to pass to the native generator.
	 */
	private function safe_rules( $rules, $depth = 0 ) {
		if ( $depth > 8 || count( $rules ) > 100 ) {
			return false;
		}
		foreach ( $rules as $value ) {
			if ( is_array( $value ) ) {
				if ( ! $this->safe_rules( $value, $depth + 1 ) ) {
					return false;
				}
			} elseif ( ! is_scalar( $value ) && null !== $value ) {
				return false;
			}
		}
		return true;
	}
}
