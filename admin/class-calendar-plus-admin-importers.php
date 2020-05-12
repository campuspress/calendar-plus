<?php

class Calendar_Plus_Admin_Importers {

	public $importers;

	public function __construct() {
		add_action( 'admin_init', array( $this, 'add_importers' ) );
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
}
