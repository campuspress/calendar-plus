<?php

/**
 * @group install
 */
class Calendar_Plus_Install_Tests extends Calendar_Plus_UnitTestCase {
	function test_calendar_table() {
		global $wpdb;
		$this->assertCount( 1, $wpdb->get_results( "SHOW TABLES LIKE '$wpdb->calendarp_calendar'" ) );
	}

	function test_default_event_types() {
		$term_ids = calendarp_get_event_type_term_ids();
		$this->assertCount( 2, $term_ids );
		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id );
			$this->assertInstanceOf( 'WP_Term', $term );
		}
	}

	function test_min_max_dates_table() {
		global $wpdb;
		$this->assertCount( 1, $wpdb->get_results( "SHOW TABLES LIKE '$wpdb->calendarp_min_max_dates'" ) );
	}

}