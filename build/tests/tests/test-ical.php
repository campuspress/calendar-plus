<?php

/**
 * @group ical
 */
class Calendar_Plus_Event_iCal extends Calendar_Plus_UnitTestCase {

	function insert_recurring_event() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		calendarp_update_event_type_recurrence( $post_id, true );

		$from_datetime = current_time( 'timestamp' ) + ( 24 * 3600 ); // From tomorrow
		$until_datetime = $from_datetime + ( 24 * 30 * 3600 ); // Until one month later

		$rules = array(
			array(
				// 20 days on march 2015
				'rule_type' => 'dates',
				'from' => date( 'Y-m-d', $from_datetime ),
				'until' => date( 'Y-m-d', $until_datetime )
			),
			array(
				// Every day
				'rule_type' => 'every',
				'every' => 1,
				'what' => 'day',
			),
			array(
				// From 15:00 to 17:00
				'rule_type' => 'times',
				'from' => '15:00',
				'until' => '17:00'
			)
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		return $event->ID;
	}

	function insert_location() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_location';
		$post_id = $this->factory->post->create_object( $args );

		return $post_id;
	}

	function insert_category() {
		$args = $this->factory->term->generate_args();
		$args['taxonomy'] = 'calendar_event_category';
		return $this->factory->term->create_object( $args );
	}


	function test_get_event_ids() {
		$event_ids['recurring'] = $this->insert_recurring_event();
		$event_ids['1'] = $this->insert_recurring_event();
		$event_ids['2'] = $this->insert_recurring_event();

		$location_id = $this->insert_location();

		// Get all events
		$args = array();
		$ids = Calendar_Plus_iCal_Calendar_Button::get_event_ids( $args );
		
		foreach ( $ids as $id )
			$this->assertContains( $id, $event_ids );

		// Get only one event
		$args = array( 'event' => $event_ids['recurring'] );
		$ids = Calendar_Plus_iCal_Calendar_Button::get_event_ids( $args );
		
		$this->assertEquals( $ids, array( $event_ids['recurring'] ) );

		// Get events by location
		update_post_meta( $event_ids['1'], '_location_id', $location_id );
		update_post_meta( $event_ids['2'], '_location_id', $location_id );

		$args = array( 'location' => $location_id );
		$ids = Calendar_Plus_iCal_Calendar_Button::get_event_ids( $args );

		$this->assertCount( 2, $ids );
		$this->assertContains( $event_ids['1'], $ids );
		$this->assertContains( $event_ids['2'], $ids );

		// Get events by location + event ID
		$args = array( 'location' => $location_id, 'event' => $event_ids['1'] );
		$ids = Calendar_Plus_iCal_Calendar_Button::get_event_ids( $args );

		$this->assertCount( 1, $ids );
		$this->assertContains( $event_ids['1'], $ids );

		$args = array( 'location' => $location_id, 'event' => $event_ids['recurring'] );
		$ids = Calendar_Plus_iCal_Calendar_Button::get_event_ids( $args );

		$this->assertCount( 0, $ids );

		// Get events by category
		$cat_id = $this->insert_category();
		wp_set_object_terms( $event_ids['2'], array( $cat_id ), 'calendar_event_category' );

		$args = array( 'category' => $cat_id );
		$ids = Calendar_Plus_iCal_Calendar_Button::get_event_ids( $args );

		$this->assertCount( 1, $ids );
		$this->assertContains( $event_ids['2'], $ids );

		// Get events by all
		$args = array( 'location' => $location_id, 'event' => $event_ids['2'], 'category' => $cat_id );
		$ids = Calendar_Plus_iCal_Calendar_Button::get_event_ids( $args );

		$this->assertCount( 1, $ids );
		$this->assertContains( $event_ids['2'], $ids );
	}

}