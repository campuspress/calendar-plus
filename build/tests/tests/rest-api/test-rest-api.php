<?php


/**
 * @group rest-api
 */
class Calendar_Plus_REST_API_Events_Tests extends Calendar_Plus_REST_API_UnitTestCase {

	function test_get_events_empty_parameters() {
		$request = new WP_REST_Request( 'GET', '/calendar-plus/v1/events' );
		/** @var WP_REST_Response $response */
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $data['code'] );
		$this->assertEquals( 'Invalid parameter(s): month, year', $data['message'] );
	}

	function test_get_simple_events() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-17',
				'until_date' => '2015-01-17',
				'from_time' => '10:00',
				'until_time' => '13:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		$request = new WP_REST_Request( 'GET', '/calendar-plus/v1/events' );
		$request->set_query_params( array(
			'month' => 1,
			'year' => 2015
		) );

		/** @var WP_REST_Response $response */
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals( 200, $response->status );
		$this->assertCount( 1, $data );
		$this->assertFalse( $data[0]['allDay'] );
		$start = array(
			'year' => 2015,
			'month' => 1,
			'day' => 17,
			'hour' => 10,
			'minute' => '00'
		);
		$this->assertEquals( $start , $data[0]['start'] );
		$end = array(
			'year' => 2015,
			'month' => 1,
			'day' => 17,
			'hour' => 13,
			'minute' => '00'
		);
		$this->assertEquals( $end , $data[0]['end'] );
	}

	function test_all_day_event() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-17',
				'until_date' => '2015-01-17',
				'from_time' => '00:00',
				'until_time' => '23:59'
			)
		);
		update_post_meta( $event->ID, '_all_day', true );
		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		$request = new WP_REST_Request( 'GET', '/calendar-plus/v1/events' );
		$request->set_query_params( array(
			'month' => 1,
			'year' => 2015
		) );

		/** @var WP_REST_Response $response */
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals( 200, $response->status );
		$this->assertCount( 1, $data );
		$this->assertTrue( $data[0]['allDay'] );
		$start = array(
			'year' => 2015,
			'month' => 1,
			'day' => 17,
			'hour' => '00',
			'minute' => '00'
		);
		$this->assertEquals( $start , $data[0]['start'] );
	}

	function test_recurring_event() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		calendarp_update_event_type_recurrence( $post_id, true );
		$rules = array(
			array(
				// 20 days on march 2015
				'rule_type' => 'dates',
				'from' => '2015-01-17',
				'until' => '2015-07-21'
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


		// January
		$request = new WP_REST_Request( 'GET', '/calendar-plus/v1/events' );
		$request->set_query_params( array(
			'month' => 1,
			'year' => 2015
		) );

		/** @var WP_REST_Response $response */
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertCount( 2, $data );

		// First event in Jan
		$this->assertFalse( $data[0]['allDay'] );
		$start = array(
			'year' => 2015,
			'month' => 1,
			'day' => 17,
			'hour' => 15,
			'minute' => '00'
		);
		$this->assertEquals( $start , $data[0]['start'] );

		$end = array(
			'year' => 2015,
			'month' => 1,
			'day' => 17,
			'hour' => 17,
			'minute' => '00'
		);
		$this->assertEquals( $end , $data[0]['end'] );

		// Second event in Jan
		$this->assertFalse( $data[1]['allDay'] );
		$start = array(
			'year' => 2015,
			'month' => 1,
			'day' => 31,
			'hour' => 15,
			'minute' => '00'
		);
		$this->assertEquals( $start , $data[1]['start'] );

		$end = array(
			'year' => 2015,
			'month' => 1,
			'day' => 31,
			'hour' => 17,
			'minute' => '00'
		);
		$this->assertEquals( $end , $data[1]['end'] );

		// February
		$request = new WP_REST_Request( 'GET', '/calendar-plus/v1/events' );
		$request->set_query_params( array(
			'month' => 2,
			'year' => 2015
		) );

		/** @var WP_REST_Response $response */
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertCount( 2, $data );

		// First event in Jan
		$this->assertFalse( $data[0]['allDay'] );
		$start = array(
			'year' => 2015,
			'month' => 2,
			'day' => 14,
			'hour' => 15,
			'minute' => '00'
		);
		$this->assertEquals( $start , $data[0]['start'] );

		$end = array(
			'year' => 2015,
			'month' => 2,
			'day' => 14,
			'hour' => 17,
			'minute' => '00'
		);
		$this->assertEquals( $end , $data[0]['end'] );

		// Second event in Jan
		$this->assertFalse( $data[1]['allDay'] );
		$start = array(
			'year' => 2015,
			'month' => 2,
			'day' => 28,
			'hour' => 15,
			'minute' => '00'
		);
		$this->assertEquals( $start , $data[1]['start'] );

		$end = array(
			'year' => 2015,
			'month' => 2,
			'day' => 28,
			'hour' => 17,
			'minute' => '00'
		);
		$this->assertEquals( $end , $data[1]['end'] );
	}

	/**
	 * @group temp
	 */
	function test_get_events_by_category() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event_with_category = calendarp_get_event( $post_id );
		$category_id = $this->factory()->term->create( array( 'taxonomy' => 'calendar_event_category' ) );
		wp_set_object_terms( $post_id, array( $category_id ), 'calendar_event_category' );

		$post_id = $this->factory->post->create_object( $args );
		$event_with_no_category = calendarp_get_event( $post_id );

		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-18',
				'until_date' => '2015-01-18',
				'from_time' => '11:00',
				'until_time' => '14:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_with_category->ID, $rules );

		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-17',
				'until_date' => '2015-01-17',
				'from_time' => '10:00',
				'until_time' => '13:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_with_no_category->ID, $rules );

		$request = new WP_REST_Request( 'GET', '/calendar-plus/v1/events' );
		$request->set_query_params( array(
			'month' => 1,
			'year' => 2015,
			'category' => $category_id
		) );

		/** @var WP_REST_Response $response */
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 200, $response->status );
		$this->assertCount( 1, $data );
		$this->assertFalse( $data[0]['allDay'] );
		$start = array(
			'year' => 2015,
			'month' => 1,
			'day' => 18,
			'hour' => 11,
			'minute' => '00'
		);
		$this->assertEquals( $start , $data[0]['start'] );
		$end = array(
			'year' => 2015,
			'month' => 1,
			'day' => 18,
			'hour' => 14,
			'minute' => '00'
		);
		$this->assertEquals( $end , $data[0]['end'] );
	}

	function test_get_events_by_search() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$args['post_title'] = 'First Event';
		$post_id_1 = $this->factory->post->create_object( $args );
		$args['post_title'] = 'Second Event';
		$post_id_2 = $this->factory->post->create_object( $args );
		$event_1 = calendarp_get_event( $post_id_1 );
		$event_2 = calendarp_get_event( $post_id_2 );

		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-18',
				'until_date' => '2015-01-18',
				'from_time' => '11:00',
				'until_time' => '14:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event_1->ID, $rules );
		calendarp_generate_event_rules_and_dates( $event_2->ID, $rules );

		$request = new WP_REST_Request( 'GET', '/calendar-plus/v1/events' );
		$request->set_query_params( array(
			'month' => 1,
			'year' => 2015,
			'search' => 'First'
		) );

		/** @var WP_REST_Response $response */
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 200, $response->status );
		$this->assertCount( 1, $data );
		$this->assertEquals( $post_id_1, $data[0]['id'] );


		$request = new WP_REST_Request( 'GET', '/calendar-plus/v1/events' );
		$request->set_query_params( array(
			'month' => 1,
			'year' => 2015,
			'search' => 'Search'
		) );

		/** @var WP_REST_Response $response */
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 200, $response->status );
		$this->assertCount( 0, $data );
	}

	function test_delete_event_date() {
		$user = get_user_by( 'login', 'admin' );
		wp_set_current_user( $user->ID );

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
			)
		);
		calendarp_generate_event_rules_and_dates( $event_1->ID, $rules );

		$dates = $calendar_plus->generator->get_month_dates( 3, 2015 );
		$delete_dates = array_values( wp_list_pluck( wp_list_filter( $dates, array( 'from_date' => '2015-03-16' ) ), 'ID' ) );
		$delete_date = $delete_dates[0];

		$request = new WP_REST_Request( 'DELETE', "/calendar-plus/v1/events/$event_1->ID/dates/$delete_date" );
		/** @var WP_REST_Response $response */
		$response = $this->server->dispatch( $request );
		$this->assertTrue( $response->get_data() );

		$dates = $calendar_plus->generator->get_month_dates( 3, 2015 );
		$this->assertCount( 2, $dates );
		$this->assertTrue( $event_1->has_custom_dates() );
	}

	function test_events_properties() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$location_args = $this->factory->post->generate_args();
		$location_args['post_type'] = 'calendar_location';
		$location_id = $this->factory->post->create_object( $location_args );
		update_post_meta( $location_id, '_address', sanitize_text_field( 'Location address' ) );
		update_post_meta( $location_id, '_city', sanitize_text_field( 'Location city' ) );
		update_post_meta( $location_id, '_state', sanitize_text_field( 'Location state' ) );
		update_post_meta( $location_id, '_postcode', sanitize_text_field( 'Location postcode' ) );
		update_post_meta( $location_id, '_country', sanitize_text_field( 'Location country' ) );


		update_post_meta( $event->ID, '_location_id', $location_id );

		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-01-17',
				'until_date' => '2015-01-17',
				'from_time' => '10:00',
				'until_time' => '13:00'
			)
		);
		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		$request = new WP_REST_Request( 'GET', '/calendar-plus/v1/events' );
		$request->set_query_params( array(
			'month' => 1,
			'year' => 2015
		) );

		/** @var WP_REST_Response $response */
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$data_event = $data[0];

		$start = array(
			'year' => 2015,
			'month' => 1,
			'day' => 17,
			'hour' => 10,
			'minute' => '00'
		);
		$this->assertEquals( $start , $data_event['start'] );

		$end = array(
			'year' => 2015,
			'month' => 1,
			'day' => 17,
			'hour' => 13,
			'minute' => '00'
		);
		$this->assertEquals( $end , $data_event['end'] );

		$location = calendarp_get_location( $location_id  );
		$this->assertEquals( $location_id, $data_event['location']['id'] );
		$this->assertEquals( $location->post->post_content, $data_event['location']['desc'] );
		$this->assertEquals( $location->get_full_address(), $data_event['location']['address'] );
		$this->assertEquals(
			array(
				'_address' => get_post_meta( $location_id, '_address', true ),
				'_city' => get_post_meta( $location_id, '_city', true ),
				'_state' => get_post_meta( $location_id, '_state', true ),
				'_postcode' => get_post_meta( $location_id, '_postcode', true ),
				'_country' => get_post_meta( $location_id, '_country', true ),
			),
			$data_event['location']['meta']
		);
	}
}