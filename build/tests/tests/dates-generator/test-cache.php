<?php


/**
 * @group dates-generator
 * @group dates-generator-cache
 * @group cache
 */
class Calendar_Plus_Dates_Generator_Cache_Tests extends Calendar_Plus_UnitTestCase {

	function test_dates_generator_cache() {
		$calendar_plus = calendar_plus();
		$calendar_plus->generator->delete_dates();

		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';

		// Event 1
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-17',
				'until_date' => '2015-03-17',
				'from_time' => '10:00'
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
		calendarp_generate_event_rules_and_dates( $event->ID, $rules );

		global $wpdb;
		$calendar_plus->generator->get_month_dates( 3, 2015 );
		$current_queries = $wpdb->num_queries;
		$calendar_plus->generator->get_month_dates( 3, 2015 );
		$this->assertEquals( 0, $wpdb->num_queries - $current_queries );
	}

	function test_delete_cache_on_update_rules() {
		$calendar_plus = calendar_plus();
		$calendar_plus->generator->delete_dates();

		$post_id = $this->factory->post->create( array( 'post_type' => 'calendar_event' ) );
		$event = calendarp_get_event( $post_id );
		$rules = array(
			array(
				'rule_type' => 'standard',
				'from_date' => '2015-03-17',
				'until_date' => '2015-03-17',
				'from_time' => '10:00'
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
		$formatted = $event->format_rules( $rules );
		$event->update_rules( $formatted );

		$calendar_plus->generator->get_month_dates( 3, 2015 );
		$cache_key = wp_hash( maybe_serialize( array( 'grouped_by_day' => false, 'month' => "03", 'year' => "2015" ) ) );

		// This should clear cache
		$event->update_rules( $formatted );

		$this->assertFalse( Calendar_Plus_Cache::get_cache( $cache_key, 'calendarp_months_dates' ) );
	}

}