<?php


/**
 * @group dates-generator
 * @group dates
 */
class Calendar_Plus_Dates_Generator_Tests extends Calendar_Plus_UnitTestCase {

	function test_generate_dates_for_month() {
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
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-16',
				'until_date' => '2015-03-16',
				'from_time' => '10:00',
				'until_time' => '11:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-16',
				'until_date' => '2015-03-16',
				'from_time' => '12:00',
				'until_time' => '13:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-04-16',
				'until_date' => '2015-04-16',
				'from_time' => '12:00',
				'until_time' => '13:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-05-16',
				'until_date' => '2015-05-16',
				'from_time' => '12:00',
				'until_time' => '13:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_1->ID, $rules );

		// Event 2
		$post_id_2 = $this->factory->post->create_object( $args );
		$event_2 = calendarp_get_event( $post_id_2 );
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
				'from_time' => '11:00',
				'until_time' => '12:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-04-30',
				'until_date' => '2015-04-30',
				'from_time' => '10:00',
				'until_time' => '11:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-07-18',
				'until_date' => '2015-07-18',
				'from_time' => '12:00',
				'until_time' => '13:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2014-12-01',
				'until_date' => '2015-12-01',
				'from_time' => '12:00',
				'until_time' => '13:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_2->ID, $rules );

		// Expected for 2015-04
		$expected = array(
			array(
				'from_date' => '2014-12-01',
				'until_date' => '2015-12-01',
				'from_time' => '12:00:00',
				'until_time' => '13:00:00',
				'event_id' => $post_id_2
			),
			array(
				'from_date' => '2015-04-16',
				'until_date' => '2015-04-16',
				'from_time' => '12:00:00',
				'until_time' => '13:00:00',
				'event_id' => $post_id_1
			),
			array(
				'from_date' => '2015-04-30',
				'until_date' => '2015-04-30',
				'from_time' => '10:00:00',
				'until_time' => '11:00:00',
				'event_id' => $post_id_2
			)
		);
		$month_dates = $calendar_plus->generator->get_month_dates( 4, 2015 );
		foreach ( $expected as $key => $value ) {
			$value = (object)$value;
			$this->assertEquals( $value->from_date, $month_dates[ $key ]->from_date );
			$this->assertEquals( $value->until_date, $month_dates[ $key ]->until_date );
			$this->assertEquals( $value->from_time, $month_dates[ $key ]->from_time );
			$this->assertEquals( $value->until_time, $month_dates[ $key ]->until_time );
			$this->assertEquals( $value->event_id, $month_dates[ $key ]->event_id );
		}
		$this->assertCount( count( $expected ), $month_dates );


		// Expected for 2015-03
		$expected = array(
			array(
				'from_date' => '2014-12-01',
				'until_date' => '2015-12-01',
				'from_time' => '12:00:00',
				'until_time' => '13:00:00',
				'event_id' => $post_id_2
			),
			array(
				'from_date' => '2015-03-15',
				'until_date' => '2015-03-15',
				'from_time' => '10:00:00',
				'until_time' => '23:59:00',
				'event_id' => $post_id_1
			),
			array(
				'from_date' => '2015-03-15',
				'until_date' => '2015-03-15',
				'from_time' => '11:00:00',
				'until_time' => '12:00:00',
				'event_id' => $post_id_2
			),
			array(
				'from_date' => '2015-03-16',
				'until_date' => '2015-03-16',
				'from_time' => '10:00:00',
				'until_time' => '11:00:00',
				'event_id' => $post_id_1
			),
			array(
				'from_date' => '2015-03-16',
				'until_date' => '2015-03-16',
				'from_time' => '12:00:00',
				'until_time' => '13:00:00',
				'event_id' => $post_id_1
			),
			array(
				'from_date' => '2015-03-17',
				'until_date' => '2015-03-17',
				'from_time' => '10:00:00',
				'until_time' => '23:59:00',
				'event_id' => $post_id_1
			),
			array(
				'from_date' => '2015-03-17',
				'until_date' => '2015-03-17',
				'from_time' => '10:00:00',
				'until_time' => '23:59:00',
				'event_id' => $post_id_2
			),
		);
		$month_dates = $calendar_plus->generator->get_month_dates( 3, 2015 );
		foreach ( $expected as $key => $value ) {
			$value = (object)$value;
			$this->assertEquals( $value->from_date, $month_dates[ $key ]->from_date );
			$this->assertEquals( $value->until_date, $month_dates[ $key ]->until_date );
			$this->assertEquals( $value->from_time, $month_dates[ $key ]->from_time );
			$this->assertEquals( $value->until_time, $month_dates[ $key ]->until_time );
			$this->assertEquals( $value->event_id, $month_dates[ $key ]->event_id );
		}
		$this->assertCount( count (  $expected ), $month_dates );

		$month_dates = $calendar_plus->generator->get_month_dates( 3, 2017 );
		$this->assertEmpty( $month_dates );
	}

	function test_generate_standard_event_dates() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-33' // Wrong
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-17',
				'until_date' => '2015-01-17',
				'from_time' => '10:00' // Good one
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-15',
				'until_date' => '2015-01-15',
				'from_time' => '10:00' // Good one
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-16',
				'until_date' => '2015-01-16',
				'from_time' => '10:00',
				'until_time' => '11:00' // Good one
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-16',
				'until_date' => '2015-01-16',
				'from_time' => '12:00',
				'until_time' => '13:00' // Good one
			),
			array(
				'rule_type' => 'exclusions',
				'date' => '2015-01-16'
			)
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		// Should be sorted now
		$expected = array(
			array(
				'from_date' => '2015-01-15',
				'until_date' => '2015-01-15',
				'from_time' => '10:00:00',
				'until_time' => '23:59:00',
				'event_id' => $post_id
			),
			array(
				'from_date' => '2015-01-17',
				'until_date' => '2015-01-17',
				'from_time' => '10:00:00',
				'until_time' => '23:59:00',
				'event_id' => $post_id
			)
		);

		$month_dates = $event->get_month_dates( 1, 2015 );
		foreach ( $expected as $key => $value ) {
			$value = (object)$value;
			$this->assertEquals( $value->from_date, $month_dates[ $key ]->from_date );
			$this->assertEquals( $value->until_date, $month_dates[ $key ]->until_date );
			$this->assertEquals( $value->from_time, $month_dates[ $key ]->from_time );
			$this->assertEquals( $value->until_time, $month_dates[ $key ]->until_time );
			$this->assertEquals( $value->event_id, $month_dates[ $key ]->event_id );
		}
		$this->assertCount( count (  $expected ), $month_dates );
	}

	function test_generate_recurrent_every_day_dates() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		calendarp_update_event_type_recurrence( $post_id, true );

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
			),
			array(
				'rule_type' => 'exclusions',
				'date' => '2015-03-11'
			)
		);


		calendarp_generate_event_rules_and_dates( $event->ID, $rules );


		$expected = array(
			array(
				'from_date' => "2015-03-01",
				'until_date' => "2015-03-01",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-03-03",
				'until_date' => "2015-03-03",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-03-05",
				'until_date' => "2015-03-05",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-03-07",
				'until_date' => "2015-03-07",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-03-09",
				'until_date' => "2015-03-09",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-03-13",
				'until_date' => "2015-03-13",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-03-15",
				'until_date' => "2015-03-15",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-03-17",
				'until_date' => "2015-03-17",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-03-19",
				'until_date' => "2015-03-19",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-03-21",
				'until_date' => "2015-03-21",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			)
		);


		$month_dates = $event->get_month_dates( 3, 2015 );
		foreach ( $expected as $key => $value ) {
			$value = (object)$value;
			$this->assertEquals( $value->from_date, $month_dates[ $key ]->from_date );
			$this->assertEquals( $value->until_date, $month_dates[ $key ]->until_date );
			$this->assertEquals( $value->from_time, $month_dates[ $key ]->from_time );
			$this->assertEquals( $value->until_time, $month_dates[ $key ]->until_time );
			$this->assertEquals( $value->event_id, $month_dates[ $key ]->event_id );
		}
		$this->assertCount( count (  $expected ), $month_dates );
	}

	function test_generate_recurrent_every_week_calendar() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		calendarp_update_event_type_recurrence( $post_id, true );

		$rules = array(
			array(
				// 20 days on march 2015
				'rule_type' => 'dates',
				'from' => '2015-03-01',
				'until' => '2015-03-21'
			),
			array(
				// Every 2 weeks
				'rule_type' => 'every',
				'every' => 2,
				'what' => 'week'
			),
			array(
				// From 15:00 to 17:00
				'rule_type' => 'times',
				'from' => '15:00',
				'until' => '17:00'
			),
			array(
				'rule_type' => 'exclusions',
				'date' => '2015-03-11'
			)
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		$expected = array(
			array(
				'from_date' => "2015-03-01",
				'until_date' => "2015-03-01",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-03-15",
				'until_date' => "2015-03-15",
				'from_time' => "15:00:00",
				'until_time' => "17:00:00",
				'event_id' => $post_id
			)
		);

		$month_dates = $event->get_month_dates( 3, 2015 );
		foreach ( $expected as $key => $value ) {
			$value = (object)$value;
			$this->assertEquals( $value->from_date, $month_dates[ $key ]->from_date );
			$this->assertEquals( $value->until_date, $month_dates[ $key ]->until_date );
			$this->assertEquals( $value->from_time, $month_dates[ $key ]->from_time );
			$this->assertEquals( $value->until_time, $month_dates[ $key ]->until_time );
			$this->assertEquals( $value->event_id, $month_dates[ $key ]->event_id );
		}
		$this->assertCount( count ( $expected ), $month_dates );
	}

	function test_generate_recurrent_every_month_dates() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		calendarp_update_event_type_recurrence( $post_id, true );

		$rules = array(
			array(
				'rule_type' => 'dates',
				'from' => '2015-03-31',
				'until' => '2016-02-21'
			),
			array(
				// Every 2 months
				'rule_type' => 'every',
				'every' => 2,
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

		$expected = array(
			array(
				'from_date' => "2015-03-31",
				'until_date' => "2015-03-31",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-07-31",
				'until_date' => "2015-07-31",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-09-30",
				'until_date' => "2015-09-30",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-11-30",
				'until_date' => "2015-11-30",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2016-01-31",
				'until_date' => "2016-01-31",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			)
		);

		$month_dates = array_merge(
			$event->get_month_dates( 3, 2015 ),
			$event->get_month_dates( 7, 2015 ),
			$event->get_month_dates( 9, 2015 ),
			$event->get_month_dates( 11, 2015 ),
			$event->get_month_dates( 1, 2016 )
		);

		foreach ( $expected as $key => $value ) {
			$value = (object)$value;
			$this->assertEquals( $value->from_date, $month_dates[ $key ]->from_date );
			$this->assertEquals( $value->until_date, $month_dates[ $key ]->until_date );
			$this->assertEquals( $value->from_time, $month_dates[ $key ]->from_time );
			$this->assertEquals( $value->until_time, $month_dates[ $key ]->until_time );
			$this->assertEquals( $value->event_id, $month_dates[ $key ]->event_id );
		}
		$this->assertCount( count ( $expected ), $month_dates );
	}

	function test_generate_recurrent_every_year_dates() {
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

		$expected = array(
			array(
				'from_date' => "2015-03-31",
				'until_date' => "2015-03-31",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2019-03-31",
				'until_date' => "2019-03-31",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2021-03-31",
				'until_date' => "2021-03-31",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2023-03-31",
				'until_date' => "2023-03-31",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			)
		);

		$dates = array_merge(
			$event->get_month_dates( 3, 2015 ),
			$event->get_month_dates( 3, 2019 ),
			$event->get_month_dates( 3, 2021 ),
			$event->get_month_dates( 3, 2023 )
		);


		foreach ( $expected as $key => $value ) {
			$value = (object)$value;
			$this->assertEquals( $value->from_date, $dates[ $key ]->from_date );
			$this->assertEquals( $value->until_date, $dates[ $key ]->until_date );
			$this->assertEquals( $value->from_time, $dates[ $key ]->from_time );
			$this->assertEquals( $value->until_time, $dates[ $key ]->until_time );
			$this->assertEquals( $value->event_id, $dates[ $key ]->event_id );
		}
		$this->assertCount( count ( $expected ), $dates );
	}

	function test_generate_recurrent_every_day_of_week_dates() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		calendarp_update_event_type_recurrence( $post_id, true );

		$rules = array(
			array(
				'rule_type' => 'dates',
				'from' => '2015-03-31',
				'until' => '2015-05-21'
			),
			array(
				// Every Monday and Friday
				'rule_type' => 'every',
				'every' => array( 1, 5 ),
				'what' => 'dow'
			),
			array(
				// From 15:15 to 17:20
				'rule_type' => 'times',
				'from' => '15:15',
				'until' => '17:20'
			),
			array(
				'rule_type' => 'exclusions',
				'date' => '2015-04-10'
			)
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		$expected = array(
			array(
				'from_date' => "2015-04-03",
				'until_date' => "2015-04-03",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-04-06",
				'until_date' => "2015-04-06",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-04-13",
				'until_date' => "2015-04-13",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-04-17",
				'until_date' => "2015-04-17",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-04-20",
				'until_date' => "2015-04-20",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-04-24",
				'until_date' => "2015-04-24",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-04-27",
				'until_date' => "2015-04-27",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-05-01",
				'until_date' => "2015-05-01",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-05-04",
				'until_date' => "2015-05-04",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-05-08",
				'until_date' => "2015-05-08",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-05-11",
				'until_date' => "2015-05-11",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-05-15",
				'until_date' => "2015-05-15",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			),
			array(
				'from_date' => "2015-05-18",
				'until_date' => "2015-05-18",
				'from_time' => "15:15:00",
				'until_time' => "17:20:00",
				'event_id' => $post_id
			)
		);

		$dates = array_merge(
			$event->get_month_dates( 3, 2015 ),
			$event->get_month_dates( 4, 2015 ),
			$event->get_month_dates( 5, 2015 )
		);

		$this->assertCount( count ( $expected ), $dates );

		foreach ( $expected as $key => $value ) {
			$value = (object)$value;
			$this->assertEquals( $value->from_date, $dates[ $key ]->from_date );
			$this->assertEquals( $value->until_date, $dates[ $key ]->until_date );
			$this->assertEquals( $value->from_time, $dates[ $key ]->from_time );
			$this->assertEquals( $value->until_time, $dates[ $key ]->until_time );
			$this->assertEquals( $value->event_id, $dates[ $key ]->event_id );
		}
	}


	function test_generate_datespan_dates() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id_1 = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id_1 );

		calendarp_update_event_type( $post_id_1, 'datespan' );

		$rules = array(
			array(
				'rule_type' => 'datespan',
				'from_date' => '2015-01-15',
				'until_date' => '2015-02-10',
				'from_time' => '10:00'
			)
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );
		$this->assertEquals( $event->get_month_dates( 1, 2015 ), $event->get_month_dates( 2, 2015 ) );
		$this->assertCount( 1, $event->get_month_dates( 1, 2015 ) );
	}

	function test_delete_calendar_when_deleting_event() {
		global $wpdb;

		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id_1 = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id_1 );

		calendarp_update_event_type_recurrence( $post_id_1, true );

		$rules = array(
			array(
				// 20 days on march 2015
				'rule_type' => 'dates',
				'from' => '2015-01-01',
				'until' => '2015-01-31'
			),
			array(
				// Every 2 weeks
				'rule_type' => 'every',
				'every' => 2,
				'what' => 'week'
			),
			array(
				// From 15:00 to 17:00
				'rule_type' => 'times',
				'from' => '15:00',
				'until' => '17:00'
			)
		);

		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		wp_delete_post( $post_id_1, true );
		$calendar = $wpdb->get_results( "SELECT * FROM $wpdb->calendarp_calendar WHERE event_id = $post_id_1" );
		$this->assertEmpty( $calendar );
		$this->assertEmpty( $event->get_month_dates( 1, 2015 ) );
	}

	function test_calendarp_delete_standard_event_date() {
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
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-16',
				'until_date' => '2015-03-16',
				'from_time' => '10:00',
				'until_time' => '11:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-16',
				'until_date' => '2015-03-16',
				'from_time' => '12:00',
				'until_time' => '13:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-04-16',
				'until_date' => '2015-04-16',
				'from_time' => '12:00',
				'until_time' => '13:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-05-16',
				'until_date' => '2015-05-16',
				'from_time' => '12:00',
				'until_time' => '13:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_1->ID, $rules );

		$dates = $calendar_plus->generator->get_month_dates( 3, 2015 );
		$delete_dates = array_values( wp_list_pluck( wp_list_filter( $dates, array( 'from_date' => '2015-03-16' ) ), 'ID' ) );
		array_map( 'calendarp_delete_event_cell', $delete_dates );
		$dates = $calendar_plus->generator->get_month_dates( 3, 2015 );
		$this->assertCount( 2, $dates );
		$this->assertTrue( $event_1->has_custom_dates() );
	}


}