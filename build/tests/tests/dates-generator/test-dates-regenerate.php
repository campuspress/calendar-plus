<?php


/**
 * @group dates-regenerate
 * @group dates
 */
class Calendar_Plus_Dates_Regenerate_Tests extends Calendar_Plus_UnitTestCase {

	function _generate_events() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$event_1 =  calendarp_get_event( $this->factory->post->create_object( $args ) );

		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$event_2 =  calendarp_get_event( $this->factory->post->create_object( $args ) );

		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$event_3 =  calendarp_get_event( $this->factory->post->create_object( $args ) );

		// Event 1
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

		// Event 3
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-07-08',
				'until_date' => '2015-07-08',
				'from_time' => '10:00'
			),
			array(
				'rule_type' => 'standard',
				'from_date' => '2016-11-08',
				'until_date' => '2016-11-08',
				'from_time' => '10:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_3->ID, $rules );

		return compact( 'event_1', 'event_2', 'event_3' );
	}

	function test_min_max_dates() {
		$calendar_plus = calendar_plus();
		$calendar_plus->generator->delete_dates();

		extract( $this->_generate_events() );

		/** @var Calendar_Plus_Event $event_1 */
		/** @var Calendar_Plus_Event $event_2 */
		/** @var Calendar_Plus_Event $event_3 */
		/** @var Calendar_Plus_Event $event_4 */

		$min = $event_1->get_min_date();
		$max = $event_1->get_max_date();

		$this->assertEquals( '2015-03-15', $min );
		$this->assertEquals( '2015-05-16', $max );


		$min = $event_2->get_min_date();
		$max = $event_2->get_max_date();

		$this->assertEquals( '2014-12-01', $min );
		$this->assertEquals( '2015-12-01', $max );

		$min = $event_3->get_min_date();
		$max = $event_3->get_max_date();

		$this->assertEquals( '2015-07-08', $min );
		$this->assertEquals( '2016-11-08', $max );
	}

	function test_get_event_ids_for_month() {
		$calendar_plus = calendar_plus();
		$calendar_plus->generator->delete_dates();

		extract( $this->_generate_events() );

		$ids = calendarp_get_event_ids_for_month( 5, 2015 );
		sort( $ids );
		$this->assertEquals( array( $event_1->ID, $event_2->ID ), $ids );

		$ids = calendarp_get_event_ids_for_month( 1, 2015 );
		$this->assertEquals( array( $event_2->ID ), $ids );

		$ids = calendarp_get_event_ids_for_month( 11, 2015 );
		sort( $ids );
		$this->assertEquals( array( $event_2->ID, $event_3->ID ), $ids );

		$ids = calendarp_get_event_ids_for_month( 3, 2016 );
		sort( $ids );
		$this->assertEquals( array( $event_3->ID ), $ids );
	}

	function test_delete_old_dates_and_regenerate() {
		global $wpdb;
		$calendar_plus = calendar_plus();
		$calendar_plus->generator->delete_dates();
		extract( $this->_generate_events() );

		function _clean_dates_id( $date ) {
			$date->ID = 0;
		}

		$month_7_2015 = calendarp_get_events_in_month( 7, 2015 );
		$month_1_2015 = calendarp_get_events_in_month( 1, 2015 );
		$month_8_2016 = calendarp_get_events_in_month( 8, 2016 );

		// Do not compare IDs as they have been changed already
		array_map( '_clean_dates_id', $month_7_2015 );
		array_map( '_clean_dates_id', $month_1_2015 );
		array_map( '_clean_dates_id', $month_8_2016 );

		Calendar_Plus_Dates_Generator::delete_old_dates( '2015-07-31' );


		$results_7_2015 = calendarp_get_events_in_month( 7, 2015 );
		$results_1_2015 = calendarp_get_events_in_month( 1, 2015 );
		$results_8_2016 = calendarp_get_events_in_month( 1, 2016 );
		// Do not compare IDs as they have been changed already
		array_map( '_clean_dates_id', $results_7_2015 );
		array_map( '_clean_dates_id', $results_1_2015 );
		array_map( '_clean_dates_id', $results_8_2016 );

		$this->assertEquals( $month_7_2015, $results_7_2015 );
		$this->assertEquals( $month_1_2015, $results_1_2015 );
		$this->assertEquals( $month_8_2016, $results_8_2016 );


	}

}