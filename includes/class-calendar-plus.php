<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       http://example.com
 * @since      0.1
 *
 * @package    calendarp
 * @subpackage calendarp/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.1
 * @package    calendarp
 * @subpackage calendarp/includes
 */
class Calendar_Plus {

	/**
	 * Contains the instance of this class
	 *
	 * @var Calendar_Plus
	 */
	private static $instance = null;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string $calendarp The string used to uniquely identify this plugin.
	 */
	public $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string $version The current version of the plugin.
	 */
	public $version;

	/**
	 * The class responsible for defining internationalization functionality
	 * of the plugin.
	 *
	 * @var Calendar_Plus_i18n
	 */
	public $i18n;

	/**
	 * The class responsible for defining all actions that occur in the Dashboard.
	 *
	 * @var Calendar_Plus_Admin
	 */
	public $admin;

	/**
	 * The class responsible for defining all actions that occur in the public-facing
	 * side of the site.
	 *
	 * @var Calendar_Plus_Public
	 */
	public $public;

	/**
	 * The class responsible for registering all custom post types and taxonomies
	 *
	 * @var Calendar_Plus_Taxonomies
	 */
	public $taxonomies;

	/**
	 * The class responsible for hooking into WP_Query class
	 *
	 * @var Calendar_Plus_Query
	 */
	public $query;

	/**
	 * The class responsible for managing everything related to settings
	 *
	 * @var Calendar_Plus_Settings
	 */
	public $settings;

	/**
	 * The class responsible for loading extra modules
	 *
	 * @var Calendar_Plus_Modules_Loader
	 */
	public $modules_loader;

	/**
	 * The class responsible for registering the calendar sidebar
	 *
	 * @var Calendar_Plus_Sidebar
	 */
	public $sidebar;

	/**
	 * @var Calendar_Plus_Model
	 */
	public $model;

	/**
	 * The class that manages the REST API
	 *
	 * @var Calendar_Plus_REST_API
	 */
	public $api;

	/**
	 * The class that generates the dates for the events
	 *
	 * @var Calendar_Plus_Dates_Generator
	 */
	public $generator;

	/**
	 * Registered widgets instances
	 *
	 * @var array
	 */
	public $widgets = array();

	/**
	 * Registered shortcodes instances
	 *
	 * @var array
	 */
	public $shortcodes = array();

	/**
	 * Google Calendar Handler
	 *
	 * @var Calendar_Plus_Google_Calendar
	 */
	public $google_calendar;

	/**
	 * Returns the unique instance of the main class of the plugin
	 *
	 * @return Calendar_Plus object
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		$this->plugin_name = 'calendar-plus';
		$this->version = calendarp_get_version();

		$this->load_dependencies();

		$this->i18n = new Calendar_Plus_i18n();
		$this->i18n->set_domain( $this->get_plugin_name() );

		$this->settings = new Calendar_Plus_Settings( $this->plugin_name );
		$this->taxonomies = new Calendar_Plus_Taxonomies();
		$this->query = new Calendar_Plus_Query();
		$this->model = Calendar_Plus_Model::get_instance();
		$this->generator = new Calendar_Plus_Dates_Generator();
		$this->modules_loader = new Calendar_Plus_Modules_Loader();

		if ( is_admin() ) {
			$this->admin = new Calendar_Plus_Admin( $this->plugin_name, $this->version );
		} else {
			$this->public = new Calendar_Plus_Public( $this->plugin_name, $this->version );
		}

		$this->sidebar = new Calendar_Plus_Sidebar( $this->plugin_name );

		$this->api = new Calendar_Plus_REST_API();

		$this->widgets['search-widget'] = new Calendar_Plus_Search_Widget();
		$this->widgets['event-categories-widget'] = new Calendar_Plus_Event_Categories_Widget();
		$this->widgets['this-week-events-widget'] = new Calendar_Plus_This_Week_Events_Widget();
		$this->widgets['this-month-events-widget'] = new Calendar_Plus_This_Month_Events_Widget();
		$this->widgets['calendar-widget'] = new Calendar_Plus_Calendar_Widget();

		$this->shortcodes['calendar'] = new Calendar_Plus_Calendar_Shortcode();
		$this->shortcodes['calendar-old'] = new Calendar_Plus_Calendar_Shortcode_Old();
		$this->shortcodes['events-by-cat'] = new Calendar_Plus_Events_By_Category_Shortcode();
		$this->shortcodes['single-event'] = new Calendar_Plus_Event_Shortcode();
		$this->shortcodes['date'] = new Calendar_Plus_Date_Shortcode();
		$this->shortcodes['location'] = new Calendar_Plus_Location_Shortcode();

		new Calendar_Plus_Blocks();

		// Google Calendar
		//      $this->google_calendar = new Calendar_Plus_Google_Calendar();

		if ( ! wp_next_scheduled( 'calendar_plus_delete_old_dates' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'daily', 'calendar_plus_delete_old_dates' );
		}

		do_action( 'calendarp_init' );
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Calendar_Plus_i18n. Defines internationalization functionality.
	 * - Calendar_Plus_Admin. Defines all hooks for the dashboard.
	 * - Calendar_Plus_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    0.1
	 * @access   private
	 */
	private function load_dependencies() {
		$plugin_dir = calendarp_get_plugin_dir();
		$includes_dir = $plugin_dir . 'includes/';

		require_once $plugin_dir . 'modules/class-calendar-plus-modules-loader.php';
		require_once $includes_dir . 'calendar/class-calendar-plus-calendar.php';
		require_once $includes_dir . 'class-calendar-plus-settings.php';

		require_once $includes_dir . 'helpers/helpers-settings.php';
		require_once $includes_dir . 'helpers/helpers-events.php';
		require_once $includes_dir . 'helpers/helpers-locations.php';
		require_once $includes_dir . 'helpers/helpers-timetables.php';
		require_once $includes_dir . 'helpers/helpers-general.php';
		require_once $includes_dir . 'helpers/helpers-cache.php';
		require_once $includes_dir . 'helpers/helpers-calendar-buttons.php';
		require_once $includes_dir . 'helpers/helpers-ical.php';

		require_once $includes_dir . 'widgets/class-calendar-plus-search-widget.php';
		require_once $includes_dir . 'widgets/class-calendar-plus-event-categories-widget.php';
		require_once $includes_dir . 'widgets/class-calendar-plus-this-week-events-widget.php';
		require_once $includes_dir . 'widgets/class-calendar-plus-this-month-events-widget.php';
		require_once $includes_dir . 'widgets/class-calendar-plus-calendar-widget.php';
		require_once $includes_dir . 'widgets/class-calendar-plus-events-list-widget.php';
	}

	public function init() {
		$this->taxonomies->register();

		// We may need to upgrade something
		Calendar_Plus_Upgrader::maybe_upgrade();

		add_action( 'wp_ajax_download_ical_file', array( 'Calendar_Plus_iCal_Calendar_Button', 'download_file' ) );
		add_action( 'wp_ajax_nopriv_download_ical_file', array( 'Calendar_Plus_iCal_Calendar_Button', 'download_file' ) );
	}

	public function add_image_sizes() {
		$settings = calendarp_get_settings();
		add_image_size( 'event_thumbnail', $settings['event_thumbnail_width'], $settings['event_thumbnail_height'], $settings['event_thumbnail_crop'] );
		add_image_size( 'location_mini', 50, 50, true );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the current plugin version
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
