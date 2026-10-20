<?php
/**
 * Calendar Plus: admin/pages/class-importer-page.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Admin_Importer_Page. */
class Calendar_Plus_Admin_Importer_Page {

	/**
	 * Initialize this calendar component.
	 *
	 * @param mixed $plugin_name Plugin name.
	 */
	public function __construct( $plugin_name ) {
		$this->slug = $plugin_name . '-calendar';
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Add menu.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function add_menu() {
		$page_id = add_submenu_page( 'edit.php?post_type=calendar_event', __( 'Import', 'calendar-plus' ), __( 'Import', 'calendar-plus' ), 'manage_calendar_plus', $this->slug, array( $this, 'render' ) );
	}

	/**
	 * Retrieve other calendar plugins activated.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_other_calendar_plugins_activated() {
		$plugins = array();
		if ( is_plugin_active( 'the-events-calendar/the-events-calendar.php' ) ) {
			$plugins['the-events-calendar'] = 'The Events Calendar';
		}

		return $plugins;
	}

	/**
	 * Handle render.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function render() {
		$calendar_plugins = $this->get_other_calendar_plugins_activated();

		?>
			<h2><?php esc_html_e( 'Import data', 'calendar-plus' ); ?></h2>

			<ul>
				<li><a href="the-events-calendar"><?php esc_html_e( 'Import data from The Events Calendar', 'calendar-plus' ); ?></a></li>
			</ul>
		<?php
	}
}
