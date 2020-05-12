<?php


/**
 * @group cache-class
 * @group cache
 */
class Calendar_Plus_Cache_Class_Tests extends Calendar_Plus_UnitTestCase {

	function test_get_cache_prefix() {
		$prefix = Calendar_Plus_Cache::get_cache_group_prefix( 'calendarp_group' );
		$this->assertEquals( 'calendarp_cache_1_', $prefix );
	}

	function test_set_cache() {
		$data = array( 'my-data' );
		Calendar_Plus_Cache::set_cache( 'a-key', $data );
		$this->assertEquals( $data, Calendar_Plus_Cache::get_cache( 'a-key' ) );
		Calendar_Plus_Cache::delete_cache( 'a-key' );
		$this->assertFalse( Calendar_Plus_Cache::get_cache( 'a-key' ) );
	}

	function test_set_cache_group() {
		$data1 = array( 'my-data-1' );
		Calendar_Plus_Cache::set_cache( 'first-key', $data1, 'a-group' );
		$data2 = array( 'my-data-2' );
		Calendar_Plus_Cache::set_cache( 'second-key', $data2, 'a-group' );

		$this->assertEquals( $data1, Calendar_Plus_Cache::get_cache( 'first-key', 'a-group' ) );
		$this->assertEquals( $data2, Calendar_Plus_Cache::get_cache( 'second-key', 'a-group' ) );

		Calendar_Plus_Cache::delete_cache_group( 'a-group' );
		$this->assertFalse(Calendar_Plus_Cache::get_cache( 'first-key', 'a-group' ));
		$this->assertFalse(Calendar_Plus_Cache::get_cache( 'second-key', 'a-group' ));
	}

	function test_delete_single_key_in_group() {
		$data1 = array( 'my-data-1' );
		Calendar_Plus_Cache::set_cache( 'first-key', $data1, 'a-group' );
		$data2 = array( 'my-data-2' );
		Calendar_Plus_Cache::set_cache( 'second-key', $data2, 'a-group' );

		Calendar_Plus_Cache::delete_cache( 'first-key', 'a-group' );

		$this->assertFalse(Calendar_Plus_Cache::get_cache( 'first-key', 'a-group' ));
		$this->assertEquals( $data2, Calendar_Plus_Cache::get_cache( 'second-key', 'a-group' ) );
	}


}