<?php

/**
 * PHPUnit bootstrap file
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load plugins/themes
 */
function _manually_load_plugin() {
	require dirname( dirname ( dirname( __FILE__ ) ) ) . '/calendar-plus.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );


tests_add_filter( 'setup_theme', function () {
	// Removes a warning coming from WP Bootstrap
	global $wp_theme_directories;
	foreach ( $wp_theme_directories as $key => $theme_dir ) {
		if ( strpos( $theme_dir, 'themedir1' ) > 0 ) {
			unset( $wp_theme_directories[ $key ] );
		}
	}
} );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';


/**
 * Sample test case.
 */
class CampusPress_UnitTestCase extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	function tearDown() {
		parent::tearDown();
	}
}

register_shutdown_function( function () {
	//system( 'sudo service memcached start' );
} );


require_once dirname( __FILE__ ) . '/tests/bootstrap.php';
