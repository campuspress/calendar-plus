<?php

class Calendar_Plus_Admin_Importer_Page {

	public function __construct( $plugin_name ) {
		$this->slug = $plugin_name . '-calendar';
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	public function add_menu() {
		$page_id = add_submenu_page( 'edit.php?post_type=calendar_event', __( 'Import', 'calendar-plus' ), __( 'Import', 'calendar-plus' ), 'manage_calendar_plus', $this->slug, array( $this, 'render' ) );
		//add_action( 'load-' . $page_id, array( $this, 'redirect_on_submit_form' ) );
	}

	public function get_other_calendar_plugins_activated() {
		$plugins = array();
		if ( is_plugin_active( 'the-events-calendar/the-events-calendar.php' ) ) {
			$plugins['the-events-calendar'] = 'The Events Calendar';
		}

		return $plugins;
	}

	public function render() {
		$calendar_plugins = $this->get_other_calendar_plugins_activated();

		?>
			<h2><?php _e( 'Import data', 'calendar-plus' ); ?></h2>

			<ul>
				<li><a href="the-events-calendar"><?php _e( 'Import data from The Events Calendar', 'calendar-plus' ); ?></a></li>
			</ul>
		<?php

	}

}
