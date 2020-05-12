<?php

/**
 * @group upgrade
 */
class Calendar_Plus_Upgrade_Tests extends Calendar_Plus_UnitTestCase {
	function test_upgrade_to_2_0_alpha_1() {
		// Test that all day meta is moved to All Day taxonomy
		$event_id_1 = $this->factory()->post->create( array( 'post_type' => 'calendar_event' ) );
		$event_id_2 = $this->factory()->post->create( array( 'post_type' => 'calendar_event' ) );
		$event_id_3 = $this->factory()->post->create( array( 'post_type' => 'calendar_event', 'post_status' => 'trash' ) );

		update_post_meta( $event_id_1, '_recurrence', 'recurrent' );
		update_post_meta( $event_id_3, '_recurrence', 'recurrent' );

		array_map( function( $term_id ) {
			wp_delete_term( $term_id, 'calendar_event_type' );
		}, calendarp_get_event_type_term_ids() );

		delete_option( 'calendarp_event_type_term_ids' );


		update_option( 'calendar-plus-version', '0.3' );
		set_current_screen( 'edit.php' );
		new Calendar_Plus_Admin( 'calendar-plus' );
		Calendar_Plus_Upgrader::maybe_upgrade();

		$this->assertEquals( get_option( 'calendar-plus-version' ), calendarp_get_version() );

		$term_ids = calendarp_get_event_type_term_ids();
		$this->assertCount( 2, $term_ids );
		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id );
			$this->assertInstanceOf( 'WP_Term', $term );
		}

		$this->assertEmpty( get_post_meta( $event_id_1, '_recurrence', true ) );
		$this->assertEmpty( get_post_meta( $event_id_3, '_recurrence', true ) );
		
		$this->assertTrue( calendarp_get_event( $event_id_1 )->is_recurring() );
		$this->assertFalse( calendarp_get_event( $event_id_2 )->is_recurring() );
		$this->assertTrue( calendarp_get_event( $event_id_3 )->is_recurring() );
	}

}