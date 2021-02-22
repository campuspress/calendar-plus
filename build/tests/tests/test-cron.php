<?php

/**
 * @group cron
 * @group ical
 */
class Calendar_Plus_Crons extends Calendar_Plus_UnitTestCase {

	public function test_calendarp_unschedule_ical_sync_cron_does_not_clean_events_with_args() {
		$args = [ 'test event arg' ];
		$this->create_asserted_calendarp_schedule( $args );
		$this->assertTrue(
			$this->has_scheduled_events( $args ),
			'there should be an event scheduled WITH args'
		);

		calendarp_unschedule_ical_sync_cron();
		$this->assertTrue(
			$this->has_scheduled_events( $args ),
			'there should STILL be an event scheduled WITH args'
		);

		calendarp_unschedule_all_ical_sync_crons();
		$this->assertFalse(
			$this->has_any_scheduled_events( $args ),
			'all should be clear now'
		);
	}

	public function test_calendarp_unschedule_all_ical_sync_crons_removes_all_schedules() {
		$this->create_asserted_calendarp_schedule();
		$this->assertTrue(
			$this->has_scheduled_events(),
			'there should be an event scheduled, without args'
		);

		calendarp_unschedule_all_ical_sync_crons();
		$this->assertFalse(
			$this->has_scheduled_events(),
			'there should be no events scheduled post cleanup'
		);
	}

	public function test_legacy_schedule_clear_handler_passes_through_new_events() {
		$this->create_asserted_calendarp_schedule();
		$this->assertTrue(
			$this->has_any_scheduled_events(),
			'there should be an event scheduled, without args'
		);


		$this->simulate_legacy_cron_trigger();
		$this->assertTrue(
			$this->has_any_scheduled_events(),
			'there should be an event scheduled, without args'
		);
		calendarp_unschedule_all_ical_sync_crons();
	}

	public function test_legacy_schedule_clear_handler_clears_up_legacy_schedules() {
		$args = [ 'test event arg' ];
		$this->create_asserted_calendarp_schedule( $args );
		$this->assertTrue(
			$this->has_any_scheduled_events( $args ),
			'there should be an event scheduled, without args'
		);

		$this->simulate_legacy_cron_trigger();
		$this->assertFalse(
			$this->has_any_scheduled_events( $args ),
			'there should be an event scheduled, without args'
		);
		calendarp_unschedule_all_ical_sync_crons();
	}

	private function create_asserted_calendarp_schedule( $args = [] ) {
		$this->assertFalse(
			$this->has_scheduled_events( $args ),
			'there should be zero events initially'
		);
		$this->create_calendarp_schedule( $args );
		$this->assertTrue(
			$this->has_scheduled_events( $args ),
			'there should be some events now'
		);
	}

	private function create_calendarp_schedule( $args = [] ) {
		calendarp_schedule_ical_sync_cron( 'hourly', $args );
	}

	private function has_scheduled_events( $args = [] ) {
		if ( empty( $args ) ) {
			return calendarp_is_ical_sync_cron_scheduled();
		}
		return false !== wp_next_scheduled( 'calendar_plus_sync_ical_events', $args );
	}

	private function has_any_scheduled_events( $args = [] ) {
		return $this->has_scheduled_events( $args ) ||
			$this->has_scheduled_events();
	}

	private function simulate_legacy_cron_trigger() {
		remove_action( 'calendar_plus_sync_ical_events', 'calendarp_ical_sync_events' );
		return $this->simulate_cron_trigger();
	}

	private function simulate_cron_trigger() {
		$crons = wp_get_ready_cron_jobs();
		if ( empty( $crons ) ) {
			return 0;
		}

		$gmt_time = microtime( true );
		$keys     = array_keys( $crons );
		if ( isset( $keys[0] ) && $keys[0] > $gmt_time ) {
			return 0;
		}

		$schedules = wp_get_schedules();
		$results   = array();
		foreach ( $crons as $timestamp => $cronhooks ) {
			if ( $timestamp > $gmt_time ) {
				break;
			}

			foreach ( $cronhooks as $hook => $keys ) {
				if ( ! preg_match( '/calendar_plus/', $hook ) ) {
					continue;
				}

				foreach ( $keys as $k => $v ) {

					$schedule = $v['schedule'];

					if ( $schedule ) {
						wp_reschedule_event( $timestamp, $schedule, $hook, $v['args'] );
					}

					wp_unschedule_event( $timestamp, $hook, $v['args'] );

					/**
					 * Fires scheduled events.
					 *
					 * @ignore
					 * @since 2.1.0
					 *
					 * @param string $hook Name of the hook that was scheduled to be fired.
					 * @param array  $args The arguments to be passed to the hook.
					 */
					do_action_ref_array( $hook, $v['args'] );
				}
			}
		}

		if ( in_array( false, $results, true ) ) {
			return false;
		}
		return count( $results );
	}
}
