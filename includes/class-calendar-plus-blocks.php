<?php

/**
 * Registers all the plugin blocks
 *
 * @since      2.2.12
 *
 * @package    calendarp
 * @subpackage calendarp/includes
 */

/**
 * Registers all plugin blocks, excluding the ones that are also a shortcode.
 *
 * @since      2.2.12
 * @package    calendarp
 * @subpackage calendarp/includes
 */
class Calendar_Plus_Blocks {

	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	public static function register_blocks() {
		register_block_type( calendarp_get_plugin_dir() . '/includes/blocks/event-date' );
		register_block_type( calendarp_get_plugin_dir() . '/includes/blocks/event-location' );
	}

}
