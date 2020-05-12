<?php


/**
 * @group dates-generator
 * @group dates-generator-cron
 */
class Calendar_Plus_Dates_Generator_Cron_Tests extends Calendar_Plus_UnitTestCase {

	function test_set_old_dates_cron() {
		$schedule = wp_get_schedule( 'calendarp_init' );
		$this->assertEquals( 'daily', $schedule );
		$this->assertTrue( is_integer( wp_next_scheduled( 'calendarp_init' ) ) );

		calendarp_unset_old_dates_cron();
		$this->assertFalse( wp_next_scheduled( 'calendarp_init' ) );
	}

}