<?php

/**
 * @group formatter
 */
class Calendar_Plus_Rules_Formatter_Tests extends Calendar_Plus_UnitTestCase {

	/**
	 * @group format_dates
	 */
	function test_format_dates_rules() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$rule = array();
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'dates',
			'from' => '2015-03-01'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'dates',
			'from' => '',
			'until' => ''
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'dates',
			'until' => '2015-03-01'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'dates',
			'from' => '2015-03-01',
			'until' => '2015-02-30' // Not valid
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'dates',
			'from' => '2015-03-01',
			'until' => '2015-02-15' // Lower than from date
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'dates',
			'from' => '2015-03-32', //  not valid
			'until' => '2015-02-15'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'dates',
			'from' => '',
			'until' => ''
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'dates',
			'from' => '2015-03-01',
			'until' => '2015-03-21'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertEquals( array(
			'from' => '2015-03-01',
			'until' => '2015-03-21'
		), $formatted_rule );

		$rule = array(
			'rule_type' => 'dates',
			'from' => '2015-03-01',
			'until' => '2015-03-21'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertEquals( array(
			'from' => '2015-03-01',
			'until' => '2015-03-21'
		), $formatted_rule );
	}



	/**
	 * @group format_every
	 */
	function test_format_every_rules() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$rule = array();
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'every',
			'every' => 2
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'every',
			'every' => 2,
			'what' => 'month'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertEquals( 
			array(
				'every' => 2,
				'what' => 'month',
			),
			$formatted_rule
		);

		$rule = array(
			'rule_type' => 'every',
			'every' => 2,
			'what' => 'week'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertEquals( 
			array(
				'every' => 2,
				'what' => 'week',
			),
			$formatted_rule
		);

		$rule = array(
			'rule_type' => 'every',
			'every' => 15,
			'what' => 'day'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertEquals( 
			array(
				'every' => 15,
				'what' => 'day'
			),
			$formatted_rule
		);

		$rule = array(
			'rule_type' => 'every',
			'every' => array( 3, 1, 7, 8, -1 ),
			'what' => 'dow'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertEquals(
			array(
				'every' => array( 1, 3, 7 ),
				'what' => 'dow'
			),
			$formatted_rule
		);

	}


	/**
	 * @group format_times
	 */
	function test_format_times_rules() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$rule = array();
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'times',
			'from' => 25
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'times',
			'from' => 25322
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'times',
			'from' => ''
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertEquals( 
			array(
				'from' => false,
				'until' => '00:00'
			),
			$formatted_rule
		);

		$rule = array(
			'rule_type' => 'times',
			'from' => '15:00'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertEquals( 
			array(
				'from' => false,
				'until' => '15:00'
			),
			$formatted_rule
		);


		$rule = array(
			'rule_type' => 'times',
			'from' => '15:35',
			'until' => '17:02'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertEquals( 
			array(
				'from' => '15:35',
				'until' => '17:02'
			),
			$formatted_rule
		);
	}

	/**
	 * @group format_exclusions
	 */
	function test_exclusions_rules() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$rule = array();
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'exclusions',
			'date' => '2015-01' // Wrong date
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'exclusions',
			'date' => '2015-02-31' // Wrong date
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertFalse( $formatted_rule );

		$rule = array(
			'rule_type' => 'exclusions',
			'date' => '2015-03-16'
		);
		$formatted_rule = $event->format_rule( $rule );
		$this->assertEquals( $formatted_rule, array( 'date' => $rule['date'] ) );
	}

	/**
	 * @group standard_rules
	 */
	function test_format_standard_rules() {
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
			)
		);

		$formatted = $event->format_rules( $rules );

		$this->assertEquals( $formatted, array(
			'standard' => array(
				false,
				array(
					'from_date' => '2015-01-17',
					'until_date' => '2015-01-17',
					'from_time' => '10:00',
					'until_time' => '23:59'
				),
				array(
					'from_date' => '2015-01-15',
					'until_date' => '2015-01-15',
					'from_time' => '10:00',
					'until_time' => '23:59'
				),
				array(
					'from_date' => '2015-01-16',
					'until_date' => '2015-01-16',
					'from_time' => '10:00',
					'until_time' => '11:00'
				) )
			)
		);

		$event->update_rules( $formatted );
		$expected = array(
			'standard' => array(
				array(
					'from_date' => '2015-01-17',
					'until_date' => '2015-01-17',
					'from_time' => '10:00',
					'until_time' => '23:59'
				),
				array(
					'from_date' => '2015-01-15',
					'until_date' => '2015-01-15',
					'from_time' => '10:00',
					'until_time' => '23:59'
				),
				array(
					'from_date' => '2015-01-16',
					'until_date' => '2015-01-16',
					'from_time' => '10:00',
					'until_time' => '11:00'
				) 
			)
		);

		$this->assertEquals( $event->get_rules(), $expected );
	}

	function test_format_datespan_rules() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		calendarp_update_event_type( $post_id, 'datespan' );

		$rules = array(
			array(
				'rule_type' => 'datespan',
				'from_date' => '2015-01-15',
				'until_date' => '2015-01-17',
				'from_time' => '10:00'
			)
		);

		$formatted = $event->format_rules( $rules );

		$this->assertEquals( $formatted, array(
				'datespan' => array(
					array(
						'from_date'  => '2015-01-15',
						'until_date' => '2015-01-17',
						'from_time'  => '10:00',
						'until_time' => '23:59'
					)
				)
			)
		);
	}

	/**
	 * @group recurrent_rules
	 */
	function test_format_all_recurrent_rules() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );
		calendarp_update_event_type_recurrence( $event->ID, true );

		$rules = array(
			array(
				'rule_type' => 'dates',
				'from' => '2015-01-03',
				'until' => '2015-01-08'
			),
			array(
				'rule_type' => 'times',
				'from' => '10:00',
				'until' => '11:00'
			),
			array(
				'rule_type' => 'every',
				'every' => 2,
				'what' => 'week'
			),
		);

		$formatted = $event->format_rules( $rules );
		$event->update_rules( $formatted );

		$expected = array(
			'dates' => array(
				array(
					'from' => '2015-01-03',
					'until' => '2015-01-08'
				)
			),
			'every' => array(
				array(
					'every' => 2,
					'what' => 'week'
				)
			),
			'times' => array(
				array(
					'from' => '10:00',
					'until' => '11:00'
				)
			)
		);

		$this->assertEquals( $expected, $event->get_rules() );

		// Another for 'Every 2 weeks' rules
		$rules = array(
			array(
				'rule_type' => 'dates',
				'from' => '2015-01-03',
				'until' => '2015-01-08'
			),
			array(
				'rule_type' => 'times',
				'from' => '10:00',
				'until' => '11:00'
			),
			array(
				'rule_type' => 'every',
				'every' => 2,
				'what' => 'week'
			),
		);
		$formatted = $event->format_rules( $rules );
		$event->update_rules( $formatted );

		$expected = array(
			'dates' => array(
				array(
					'from' => '2015-01-03',
					'until' => '2015-01-08'
				)
			),
			'every' => array(
				array(
					'every' => 2,
					'what' => 'week'
				)
			),
			'times' => array(
				array(
					'from' => '10:00',
					'until' => '11:00'
				)
			)
		);

		$this->assertEquals( $expected, $event->get_rules() );

		// Another for 'Every 3 days' rules
		$rules = array(
			array(
				'rule_type' => 'dates',
				'from' => '2015-01-03',
				'until' => '2015-01-08'
			),
			array(
				'rule_type' => 'times',
				'from' => '10:00',
				'until' => '11:00'
			),
			array(
				'rule_type' => 'every',
				'every' => 3,
				'what' => 'day'
			),
		);
		$formatted = $event->format_rules( $rules );
		$event->update_rules( $formatted );

		$expected = array(
			'dates' => array(
				array(
					'from' => '2015-01-03',
					'until' => '2015-01-08'
				)
			),
			'every' => array(
				array(
					'every' => 3,
					'what' => 'day',
				)
			),
			'times' => array(
				array(
					'from' => '10:00',
					'until' => '11:00'
				)
			)
		);

		$this->assertEquals( $expected, $event->get_rules() );
	}


}

