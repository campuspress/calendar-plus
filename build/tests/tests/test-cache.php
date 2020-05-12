<?php

/**
 * @group cache
 */
class Calendar_Plus_Cache_Tests extends Calendar_Plus_UnitTestCase {

	function test_get_events_in_date_range() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		calendarp_update_event_type_recurrence( $post_id, true );

		$rules = array(
			array(
				'rule_type' => 'dates',
				'from' => '2015-03-31',
				'until' => '2025-02-21'
			),
			array(
				// Every 2 years
				'rule_type' => 'every',
				'every' => 2,
				'what' => 'year'
			),
			array(
				// From 15:15 to 17:20
				'rule_type' => 'times',
				'from' => '15:15',
				'until' => '17:20'
			),
			array(
				'rule_type' => 'exclusions',
				'date' => '2017-03-31'
			)
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		$from = mktime( 0,0,0,3,31,2015);
		$to = mktime( 23,59,59,3,31,2020);
		$events = calendarp_get_events_in_date_range( $from, $to );
		
		$this->assertCount( 2, $events );

		// Now remove the excluded date
		$rules = array(
			array(
				'rule_type' => 'dates',
				'from' => '2015-03-31',
				'until' => '2025-02-21'
			),
			array(
				// Every 2 years
				'rule_type' => 'every',
				'every' => 2,
				'what' => 'year'
			),
			array(
				// From 15:15 to 17:20
				'rule_type' => 'times',
				'from' => '15:15',
				'until' => '17:20'
			)
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		$events = calendarp_get_events_in_date_range( $from, $to );

		$this->assertCount( 3, $events );

	}

	function test_delete_calendar_cell() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		calendarp_update_event_type_recurrence( $post_id, true );

		$rules = array(
			array(
				'rule_type' => 'dates',
				'from' => '2015-03-31',
				'until' => '2025-02-21'
			),
			array(
				// Every 2 years
				'rule_type' => 'every',
				'every' => 2,
				'what' => 'year'
			),
			array(
				// From 15:15 to 17:20
				'rule_type' => 'times',
				'from' => '15:15',
				'until' => '17:20'
			),
			array(
				'rule_type' => 'exclusions',
				'date' => '2017-03-31'
			)
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		$from = mktime( 0,0,0,3,31,2015);
		$to = mktime( 23,59,59,3,31,2020);
		$events = calendarp_get_events_in_date_range( $from, $to );

		// Let's delete a cell
		$delete_cell_id = $events[ key( $events ) ][0]->dates_data['calendar_cell_id'];

		calendarp_delete_event_cell( $delete_cell_id );

		$events = calendarp_get_events_in_date_range( $from, $to );
		$this->assertCount( 1, $events );


	}

	function test_get_month_dates_cache() {
		$calendar_plus = calendar_plus();
		$calendar_plus->generator->delete_dates();
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';

		// Event 1
		$post_id_1 = $this->factory->post->create_object( $args );
		$event_1 = calendarp_get_event( $post_id_1 );
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-17',
				'until_date' => '2015-03-17',
				'from_time' => '10:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-15',
				'until_date' => '2015-03-15',
				'from_time' => '10:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_1->ID, $rules );

		$events = calendarp_get_events_in_month( 3, 2015 );
		$this->assertCount( 2, $events );

		$post_id_2 = $this->factory->post->create_object( $args );
		$event_2 = calendarp_get_event( $post_id_2 );
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-20',
				'until_date' => '2015-03-20',
				'from_time' => '10:00',
				'until_time' => '11:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_2->ID, $rules );

		$events = calendarp_get_events_in_month( 3, 2015 );
		$this->assertCount( 3, $events );
	}

	function test_get_month_dates_cache_when_update_event() {
		$calendar_plus = calendar_plus();
		$calendar_plus->generator->delete_dates();
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';

		// Event 1
		$post_id_1 = $this->factory->post->create_object( $args );
		$event_1 = calendarp_get_event( $post_id_1 );
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-17',
				'until_date' => '2015-03-17',
				'from_time' => '10:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_1->ID, $rules );

		$events = calendarp_get_events_in_month( 3, 2015 );

		$this->assertEquals( $post_id_1, $events[0]->event_id );
		$this->assertEquals( '2015-03-17', $events[0]->from_date );

		$post_id_2 = $this->factory->post->create_object( $args );
		$event_2 = calendarp_get_event( $post_id_2 );
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-20',
				'until_date' => '2015-03-20',
				'from_time' => '10:00',
				'until_time' => '11:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_2->ID, $rules );

		$events = calendarp_get_events_in_month( 3, 2015 );

		$this->assertEquals( $post_id_1, $events[0]->event_id );
		$this->assertEquals( '2015-03-17', $events[0]->from_date );
		$this->assertEquals( $post_id_2, $events[1]->event_id );
		$this->assertEquals( '2015-03-20', $events[1]->from_date );

		$new_rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-28',
				'until_date' => '2015-03-28',
				'from_time' => '10:00',
				'until_time' => '11:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_2->ID, $new_rules );

		$events = calendarp_get_events_in_month( 3, 2015 );

		$this->assertEquals( $post_id_1, $events[0]->event_id );
		$this->assertEquals( '2015-03-17', $events[0]->from_date );
		$this->assertEquals( $post_id_2, $events[1]->event_id );
		$this->assertEquals( '2015-03-28', $events[1]->from_date );
	}



}

