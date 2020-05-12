<?php

/**
 * @group ical-parser
 */
class Calendar_Plus_iCal_Parser_Test extends Calendar_Plus_UnitTestCase {

	public function setUp() {
		parent::setUp();
		PHPUnit_Framework_Error_Deprecated::$enabled = false;
	}

	function test_parser() {
		$file = file_get_contents( dirname( __FILE__ ) . '/sample-ical/event-organiser_2017-06-08.ics' );
		$ical_parser = new Calendar_Plus_iCal_Parser( $file );
		$events = $ical_parser->parse();

		$syncer = new Calendar_Plus_iCal_Sync( $events );
		$synced = $syncer->sync();

		$synced_1 = calendarp_get_event( $synced[0] );
		$synced_1_id = $synced_1->ID;
		$dates_list = $synced_1->get_dates_list();

		$this->assertEquals( $events[0]['last_updated'], $synced_1->ical_last_updated );
		$this->assertEquals( 'general', $synced_1->get_event_type() );
		$this->assertCount( 1, $dates_list );
		$this->assertEquals( date( 'Y-m-d', $events[0]['from'] ), $dates_list[0]['from_date'] );
		$this->assertEquals( date( 'Y-m-d', $events[0]['to'] ), $dates_list[0]['until_date'] );
		$this->assertEquals( date( 'H:i', $events[0]['from'] ), $dates_list[0]['from_time'] );
		$this->assertEquals( date( 'H:i', $events[0]['to'] ), $dates_list[0]['until_time'] );
		$this->assertEquals( 'publish', get_post_status( $synced_1->ID ) );

		$new_modified_date = time() + 15;
		$events[0]['last_updated'] = $new_modified_date;

		$syncer = new Calendar_Plus_iCal_Sync( array( $events[0] ) );
		$synced = $syncer->sync();
		$synced_1 = calendarp_get_event( $synced[0] );

		// Last modified date should be changed now
		$this->assertEquals( $new_modified_date, $synced_1->ical_last_updated );

		// But it should keep its old ID
		$this->assertEquals( $synced[0], $synced_1_id );

		$events[0]['post_status'] = 'trash';
		$syncer = new Calendar_Plus_iCal_Sync( array( $events[0] ) );
		$synced = $syncer->sync();
		$synced_1 = calendarp_get_event( $synced[0] );
		$this->assertFalse( $synced_1 );
	}

	function test_parser_event_description() {
		$file = file_get_contents( dirname( __FILE__ ) . '/sample-ical/event-organiser_2017-06-08.ics' );
		$ical_parser = new Calendar_Plus_iCal_Parser( $file );
		$events = $ical_parser->parse();

		$syncer = new Calendar_Plus_iCal_Sync( $events );
		$synced = $syncer->sync();

		$synced_2 = calendarp_get_event( $synced[1] );
		$this->assertEquals( '<p>This outage is needed to update the emergency power system. The outage will affect the 3rd, 4th, and 5th floors onthe South end. Affected during the outage will be outlets, cold rooms, -80 freezers and some lighting that is on emergency power.</p>', $synced_2->post->post_content );
	}

	public function test_import_event_dates() {
		$this->_import_ical_file(
			'outlook-mst',
			-7 * HOUR_IN_SECONDS
		);
		$this->_import_ical_file(
			'outlook-west',
			HOUR_IN_SECONDS
		);
		$this->_import_ical_file(
			'event-chicago-single',
			-5 * HOUR_IN_SECONDS
		);
		$this->_import_ical_file(
			'event-utc-single',
			0
		);
	}

	public function test_get_event_uid_returns_incremental_uids_for_recur_events() {
		$p = new Calendar_Plus_iCal_Parser( '', true );
		$rrule = 'test test test';
		$events = [
			[ 'uid' => '1312', 'rrule' => $rrule ],
			[ 'uid' => '161', 'rrule' => $rrule ],
			[ 'uid' => 'testtest', 'rrule' => $rrule ],
		];
		foreach ( $events as $idx => $event ) {
			$test = (object) $event;
			$uid = $p->get_event_uid( $test );
			$this->assertNotEquals(
				$uid, $test->uid,
				'Generated uids are NOT equal to event UIDs for recurring events'
			);
			$this->assertNotEquals(
				$uid . '-' . ( $idx + 1 ), $test->uid,
				'Generated uids have index info for recurring events'
			);
		}
	}

	public function test_get_event_uid_returns_uid_for_normal_event() {
		$p = new Calendar_Plus_iCal_Parser( '', true );
		$events = [
			[ 'uid' => '1312' ],
			[ 'uid' => '161' ],
			[ 'uid' => 'testtest' ],
		];
		foreach ( $events as $event ) {
			$test = (object) $event;
			$uid = $p->get_event_uid( $test );
			$this->assertEquals(
				$uid, $test->uid,
				'Generated uids are equal to event UIDs for normal events'
			);
		}
	}

	public function test_get_event_uid_throws_for_non_object() {
		$p = new Calendar_Plus_iCal_Parser( '', true );
		try {
			$p->get_event_uid( [] );
		} catch( Exception $e ) {
			$this->assertTrue( true );
			return true;
		}
		$this->fail( 'Expected exception' );
	}

	public function test_parse_recurring_events_do_not_have_same_uid() {
		$file = file_get_contents( dirname( __FILE__ ) . "/sample-ical/recurring.ics" );
		$ical_parser = new Calendar_Plus_iCal_Parser( $file, true );
		$events = $ical_parser->parse();

		$source_events = substr_count( $file, 'BEGIN:VEVENT' );

		$this->assertFalse( empty( $events ), 'We have some recurring events' );
		$this->assertTrue( is_array( $events ), 'Recurring events is an array' );
		$this->assertTrue(
			count( $events ) > $source_events,
			'Parsed recurring events count is greater than the events in source file'
		);

		for ( $i = 1; $i < count( $events ); $i ++ ) {
			$previous_uid = $events[ $i - 1 ]['uid'];
			$this->assertNotEquals(
				$previous_uid, $events[ $i ]['uid'],
				"UIDs are NOT the same for recurring event instances"
			);
		}
	}

	public function test_parse_recurring_events_have_same_uid_normally() {
		$file = file_get_contents( dirname( __FILE__ ) . "/sample-ical/recurring.ics" );
		$ical_parser = new Calendar_Plus_iCal_Parser( $file );
		$events = $ical_parser->parse();

		$source_events = substr_count( $file, 'BEGIN:VEVENT' );

		$this->assertFalse( empty( $events ), 'We have some recurring events' );
		$this->assertTrue( is_array( $events ), 'Recurring events is an array' );
		$this->assertTrue(
			count( $events ) > $source_events,
			'Parsed recurring events count is greater than the events in source file'
		);

		for ( $i = 1; $i < count( $events ); $i ++ ) {
			$previous_uid = $events[ $i - 1 ]['uid'];
			$this->assertEquals(
				$previous_uid, $events[ $i ]['uid'],
				"UIDs are the same for recurring event instances unless explicitly requested otherwise"
			);
		}
	}


	private function _import_ical_file( $fname, $offset ) {
		$file = file_get_contents( dirname( __FILE__ ) . "/sample-ical/${fname}.ics" );
		$ical_parser = new Calendar_Plus_iCal_Parser( $file );
		$events = $ical_parser->parse();

		$event_start_ts = $events[0]['from'];
		$event_end_ts = $events[0]['to'];
		$file = preg_replace( '/\r/', "\n", $file ); // normalize newlines

		$start = date( 'Ymd\THis', $event_start_ts );
		$start_rx = '/^DTSTART;TZID.*' .
			preg_quote( date( 'Ymd\THis', ( $event_start_ts + $offset ) ), '/' ) .
		'$/m';
		$this->assertTrue(
			(bool) preg_match( $start_rx, $file ),
			"Date start properly imported from outlook - $fname:\n{$start_rx}\n{$start}"
		);

		$end = date( 'Ymd\THis', $event_end_ts );
		$end_rx = '/^DTEND;TZID.*' .
			preg_quote( date( 'Ymd\THis', $event_end_ts + $offset ), '/' ) .
		'$/m';
		$this->assertTrue(
			(bool) preg_match( $end_rx, $file ),
			"Date end properly imported from outlook - $file:\n{$end_rx}\n{$end}"
		);
	}
}
