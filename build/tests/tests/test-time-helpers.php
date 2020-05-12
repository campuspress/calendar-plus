<?php


/**
 * @group time_helpers
 */
class Calendar_Plus_Time_Helpers_Tests extends Calendar_Plus_UnitTestCase { 

	function test_am_pm_to_24h() {
		$result = calendarp_am_pm_to_24h( 1, 'pm' );
		$this->assertEquals( $result, '13' );

		$result = calendarp_am_pm_to_24h( '01', 'pm' );
		$this->assertEquals( $result, '13' );

		$result = calendarp_am_pm_to_24h( '01', 'am' );
		$this->assertEquals( $result, '01' );

		$result = calendarp_am_pm_to_24h( 1, 'am' );
		$this->assertEquals( $result, '01' );

		$result = calendarp_am_pm_to_24h( 12, 'pm' );
		$this->assertEquals( $result, '12' );

		$result = calendarp_am_pm_to_24h( 12, 'am' );
		$this->assertEquals( $result, '00' );
	}

	function test_human_read_regular_dates() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		// Test with no dates
		$result = calendarp_get_human_read_dates( $event->ID );
		$this->assertEquals( $result, 'No dates for this event' );
		

		// Test with only one date
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-17',
				'until_date' => '2015-01-17',
				'from_time' => '10:00'
			)		
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );
		$result = calendarp_get_human_read_dates( $event->ID );
		$this->assertEquals( $result, 'On January 17, 2015 at 10:00 am' );

		// Test with one date an all day event
		update_post_meta( $event->ID, '_all_day', true );
		$result = calendarp_get_human_read_dates( $event->ID );
		$this->assertEquals( $result, 'On January 17, 2015' );
		$result = calendarp_get_human_read_dates( $event->ID, 'array' );
		$this->assertEquals( $result, array( 'date' => 'January 17, 2015', 'time' => '', 'recurrence' => '' ) );

		delete_post_meta( $event->ID, '_all_day' );

		// Test with two dates at the same time
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-17',
				'until_date' => '2015-01-17',
				'from_time' => '10:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-18',
				'until_date' => '2015-01-18',
				'from_time' => '10:00'
			)		
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );
		$result = calendarp_get_human_read_dates( $event->ID );
		$this->assertEquals( $result, 'On January 17, 2015 and January 18, 2015 at 10:00 am' );
		$result = calendarp_get_human_read_dates( $event->ID, 'array' );
		$this->assertEquals( $result, array( 'date' => 'January 17, 2015 and January 18, 2015', 'time' => '10:00 am', 'recurrence' => '' ) );

		// Test with two dates at different times
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-17',
				'until_date' => '2015-01-17',
				'from_time' => '10:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-18',
				'until_date' => '2015-01-18',
				'from_time' => '11:00'
			)		
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );
		$result = calendarp_get_human_read_dates( $event->ID );
		$this->assertEquals( $result, 'On January 17, 2015 and January 18, 2015' );
		$result = calendarp_get_human_read_dates( $event->ID, 'array' );
		$this->assertEquals( $result, array( 'date' => 'January 17, 2015 and January 18, 2015', 'time' => '', 'recurrence' => '' ) );

		// Test with more than two dates at the same time
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-17',
				'until_date' => '2015-01-17',
				'from_time' => '22:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-18',
				'until_date' => '2015-01-18',
				'from_time' => '22:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-20',
				'until_date' => '2015-01-20',
				'from_time' => '22:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-28',
				'until_date' => '2015-01-28',
				'from_time' => '22:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event->ID, $rules );
		$result = calendarp_get_human_read_dates( $event->ID );
		$this->assertEquals( $result, 'From January 17, 2015 to January 28, 2015 at 10:00 pm' );
		$result = calendarp_get_human_read_dates( $event->ID, 'array' );
		$this->assertEquals( $result, array( 'date' => 'January 17, 2015 to January 28, 2015', 'time' => '10:00 pm', 'recurrence' => '' ) );

		// Test with more than two dates at different times
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-17',
				'until_date' => '2015-01-17',
				'from_time' => '10:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-18',
				'until_date' => '2015-01-18',
				'from_time' => '10:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-20',
				'until_date' => '2015-01-20',
				'from_time' => '10:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-28',
				'until_date' => '2015-01-28',
				'from_time' => '11:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event->ID, $rules );
		$result = calendarp_get_human_read_dates( $event->ID );
		$this->assertEquals( $result, 'From January 17, 2015 to January 28, 2015' );
		$result = calendarp_get_human_read_dates( $event->ID, 'array' );
		$this->assertEquals( $result, array( 'date' => 'January 17, 2015 to January 28, 2015', 'time' => '', 'recurrence' => '' ) );


	}

	function test_human_read_recurring_dates() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );
		calendarp_update_event_type_recurrence( $event->ID, true );

		// Test with no dates
		$result = calendarp_get_human_read_dates( $event->ID );
		$this->assertEquals( $result, 'No dates for this event' );
		
		// Every 2 days
		$rules = array(
			array(
				// 20 days on march 2015
				'rule_type' => 'dates',
				'from' => '2015-03-01',
				'until' => '2015-03-21'
			),
			array(
				// Every 2 days
				'rule_type' => 'every',
				'every' => 2,
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
		$result = calendarp_get_human_read_dates( $event->ID );
		$this->assertEquals( $result, 'From March 1, 2015 to March 21, 2015, Every 2 days at 3:00 pm' );
		$result = calendarp_get_human_read_dates( $event->ID, 'array' );
		$this->assertEquals( $result, array( 'date' => 'March 1, 2015 to March 21, 2015', 'time' => '3:00 pm', 'recurrence' => 'Every 2 days' ) );

		// Every 1 month
		$rules = array(
			array(
				'rule_type' => 'dates',
				'from' => '2015-03-31',
				'until' => '2016-02-21'
			),
			array(
				// Every 1 months
				'rule_type' => 'every',
				'every' => 1,
				'what' => 'month'
			),
			array(
				// From 15:15 to 17:20
				'rule_type' => 'times',
				'from' => '15:15',
				'until' => '17:20'
			),
			array(
				'rule_type' => 'exclusions',
				'date' => '2015-05-31'
			)
		);
		calendarp_generate_event_rules_and_dates( $event->ID, $rules );
		$result = calendarp_get_human_read_dates( $event->ID );
		$this->assertEquals( $result, 'From March 31, 2015 to January 31, 2016, Every month at 3:15 pm' );
		$result = calendarp_get_human_read_dates( $event->ID, 'array' );
		$this->assertEquals( $result, array( 'date' => 'March 31, 2015 to January 31, 2016', 'time' => '3:15 pm', 'recurrence' => 'Every month' ) );

		// Now let's suppose that the calendar has custom dates
		update_post_meta( $event->ID, '_has_custom_dates', true );
		$result = calendarp_get_human_read_dates( $event->ID );
		$this->assertEquals( $result, 'From March 31, 2015 to January 31, 2016 at 3:15 pm' );
		$result = calendarp_get_human_read_dates( $event->ID, 'array' );
		$this->assertEquals( $result, array( 'date' => 'March 31, 2015 to January 31, 2016', 'time' => '3:15 pm', 'recurrence' => '' ) );


	}
}