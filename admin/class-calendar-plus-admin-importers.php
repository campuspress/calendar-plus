<?php

class Calendar_Plus_Admin_Importers {

	public $importers;

	public function __construct() {
		add_action( 'admin_init', array( $this, 'add_importers' ) );
		add_action( 'wp_import_insert_post', array( $this, 'maybe_generate_event_dates' ), 15, 4 );
	}

	public function add_importers() {
		register_importer(
			'calendar-plus-the-events-calendar',
			__( 'The Events Calendar (Calendar+)', 'calendar-plus' ),
			__( 'Import Events from The Events Calendar Plugin to Calendar+', 'calendar-plus' ),
			array( $this, 'the_events_calendar_importer' )
		);
	}

	public function the_events_calendar_importer() {
		// The WP Importer API
		include_once( ABSPATH . 'wp-admin/includes/import.php' );

		if ( ! class_exists( 'WP_Importer' ) ) {
			$file = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
			if ( is_file( $file ) ) {
				include_once $file;
			}
		}

		// includes
		include_once( 'importers/class-the-events-calendar-importer.php' );

		// Dispatch
		$this->importers['the-events-calendar'] = new Calendar_Plus_The_Events_Calendar_Importer();
		$this->importers['the-events-calendar']->dispatch();
	}

	public function maybe_generate_event_dates( $post_id, $original_post_id, $postdata, $post ) {
		if ( 'calendar_event' === $postdata['post_type'] ) {
			if ( isset( $post['postmeta'] ) ) {
				foreach ( $post['postmeta'] as $meta ) {
					if (
						'_standard_rules' === $meta['key'] ||
						'_datespan_rules' === $meta['key'] ||
						'_recurring_rules' === $meta['key']
					) {
						$rules_data = maybe_unserialize( $meta['value'] );
						if ( $rules_data ) {
							$event = Calendar_Plus_Event::get_instance( $post_id );
							if ( $event ) {
								$event->update_rules( $rules_data );
							}
						}
					}
				}
			}
		}
	}
}
