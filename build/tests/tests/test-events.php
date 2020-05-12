<?php

/**
 * @group event
 */
class Calendar_Plus_Event_Tests extends Calendar_Plus_UnitTestCase {

	function test_get_event() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$this->assertEquals( $post_id, $event->ID );
		$this->assertTrue( $event instanceof Calendar_Plus_Event );

		$same_event = calendarp_get_event( $event );
		$this->assertEquals( $post_id, $same_event->ID );
		$this->assertTrue( $same_event instanceof Calendar_Plus_Event );

		$post_event = get_post( $post_id );
		$post_event = calendarp_get_event( $post_event );
		$this->assertEquals( $post_id, $post_event->ID );
		$this->assertTrue( $post_event instanceof Calendar_Plus_Event );
	}

	function test_get_non_event() {
		$args = $this->factory->post->generate_args();
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$this->assertFalse( $event );
	}

	function test_is_recurring() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );
		
		$this->assertFalse( $event->is_recurring() );
		
		calendarp_update_event_type_recurrence( $event->ID, true );
		$this->assertTrue( $event->is_recurring() );

		$this->assertEquals( 'recurrent', $event->get_event_type() );
	}

	function test_event_type() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$this->assertFalse( $event->is_recurring() );

		calendarp_update_event_type( $event->ID, 'recurrent' );
		$this->assertTrue( $event->is_recurring() );
		$this->assertEquals( 'recurrent', $event->get_event_type() );

		calendarp_update_event_type( $event->ID, 'datespan' );
		$this->assertFalse( $event->is_recurring() );
		$this->assertEquals( 'datespan', $event->get_event_type() );

		calendarp_update_event_type( $event->ID, '' );
		$this->assertFalse( $event->is_recurring() );
		$this->assertEquals( 'general', $event->get_event_type() );
	}

	function test_update_event_uid() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$uid = '20140827T1617Z-1409156246.7042-EO-398-1@128.252.98.35';
		calendarp_update_event_uid( $event->ID, $uid );

		$meta_uid = get_post_meta( $event->ID, '_event_uid', true );
		$this->assertEquals( $meta_uid, $uid );
		$this->assertEquals( $uid, calendarp_get_event_uid( $event->ID ) );


		$uid = '20140827T1617Z-1409156246ANOTHER.7042-EO-398-1@128.252.98.35';
		calendarp_update_event_uid( $event->ID, $uid );
		$this->assertEquals( $uid, calendarp_get_event_uid( $event->ID ) );
	}

	function test_remove_event_uid() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event = calendarp_get_event( $post_id );

		$uid = '20140827T1617Z-1409156246.7042-EO-398-1@128.252.98.35';
		calendarp_update_event_uid( $event->ID, $uid );
		calendarp_update_event_uid( $event->ID, false );

		$this->assertFalse( calendarp_get_event_uid( $event->ID ) );
		$this->assertEmpty( wp_get_object_terms( $event->ID, 'calendar_event_uid' ) );
	}

	function test_get_event_by_uid() {
		$args = $this->factory->post->generate_args();
		$args['post_type'] = 'calendar_event';
		$post_id = $this->factory->post->create_object( $args );
		$event_1 = calendarp_get_event( $post_id );
		$post_id = $this->factory->post->create_object( $args );
		$event_2 = calendarp_get_event( $post_id );

		$uid_1 = '20140827T1617Z-1409156246.7042-EO-398-1@128.252.98.35';
		$uid_2 = '20140827T1617Z-1409156246ANOTHER.7042-EO-398-1@128.252.98.35';

		calendarp_update_event_uid( $event_1->ID, $uid_1 );
		calendarp_update_event_uid( $event_2->ID, $uid_2 );

		$this->assertEquals( $event_1->ID, calendarp_get_event_by_uid( $uid_1 )->ID );
		$this->assertEquals( $event_2->ID, calendarp_get_event_by_uid( $uid_2 )->ID );
	}
}

