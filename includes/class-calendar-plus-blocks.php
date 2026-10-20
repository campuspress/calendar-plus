<?php
/**
 * Calendar Plus: includes/class-calendar-plus-blocks.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {
		require_once calendarp_get_plugin_dir() . 'includes/class-calendar-plus-event-feed.php';
		require_once calendarp_get_plugin_dir() . 'includes/class-calendar-plus-event-display.php';
		require_once calendarp_get_plugin_dir() . 'includes/class-calendar-plus-events-showcase.php';
		new Calendar_Plus_Event_Display();
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register blocks.
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function register_blocks() {
		$fields = require calendarp_get_plugin_dir() . 'includes/blocks/event-fields-editor.asset.php';
		wp_register_script( 'calendarp-event-fields-editor', calendarp_get_plugin_url() . 'includes/blocks/event-fields-editor.js', $fields['dependencies'], $fields['version'], true );
		wp_set_script_translations( 'calendarp-event-fields-editor', 'calendar-plus' );
		wp_register_style( 'calendarp-event-presentation', calendarp_get_plugin_url() . 'includes/blocks/event-feed/style.css', array(), calendarp_get_version() );
		register_block_type( calendarp_get_plugin_dir() . '/includes/blocks/event-date' );
		register_block_type( calendarp_get_plugin_dir() . '/includes/blocks/event-location' );
		register_block_type( calendarp_get_plugin_dir() . '/includes/blocks/event-add-to' );
		register_block_type( calendarp_get_plugin_dir() . '/includes/blocks/event-status' );
		register_block_type( calendarp_get_plugin_dir() . '/includes/blocks/event-feed' );
		Calendar_Plus_Events_Showcase::register();
	}
}
