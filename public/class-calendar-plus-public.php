<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      0.1
 *
 * @package    calendarp
 * @subpackage calendarp/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    calendarp
 * @subpackage calendarp/public
 * @author     Your Name <email@example.com>
 */
class Calendar_Plus_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.1
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.1
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Template loader class that manage the custom templates
	 * @since    0.1
	 * @access   protected
	 * @var      Calendar_Plus_Template_Loader $template_loader
	 */
	protected $template_loader;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1
	 *
	 * @param      string $plugin_name The name of the plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->load_dependencies();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 100 );

		do_action( 'calendarp_public_loaded' );

		if( ! wp_is_block_theme() ) {
			$this->template_loader = new Calendar_Plus_Template_Loader();
		}
	}

	private function load_dependencies() {
		require_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';
		require_once calendarp_get_plugin_dir() . 'public/templates-hooks.php';

		$legacy_integration = calendarp_get_setting( 'legacy_theme_integration' );
		if( ! empty( $legacy_integration ) ) {
			require_once calendarp_get_plugin_dir() . 'public/legacy/helpers-templates.php';
			require_once calendarp_get_plugin_dir() . 'public/legacy/templates-hooks.php';
		}
		require_once calendarp_get_plugin_dir() . 'public/integration/integration.php';
	}

	public function enqueue_scripts() {
		if ( calendarp_is_calendarp() ) {
			calendarp_enqueue_public_script_and_styles();
			calendarp_enqueue_public_styles();
		}
	}
}
