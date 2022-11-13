<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      0.1
 *
 * @package    calendarp
 * @subpackage calendarp/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    calendarp
 * @subpackage calendarp/admin
 * @author     Your Name <email@example.com>
 */
class Calendar_Plus_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.1
	 * @access   private
	 * @var      string $calendarp The ID of this plugin.
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

	public $meta_boxes;
	public $menu_pages;
	public $importers;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 *
	 * @since 0.1
	 *
	 */
	public function __construct( $plugin_name, $version = '' ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->meta_boxes = array();
		$this->menu_pages = array();
		include_once calendarp_get_plugin_dir() . 'admin/integration/integration.php';

		require_once calendarp_get_plugin_dir() . 'admin/meta-boxes/event/class-event-details-meta-box.php';

		$this->add_meta_boxes();
		$this->add_menu_pages();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		add_filter( 'manage_calendar_event_posts_columns', array( $this, 'event_columns' ) );
		add_action( 'manage_calendar_event_posts_custom_column', array( $this, 'event_column' ), 10, 2 );
        // Make event date column sortable
        add_filter( 'manage_edit-calendar_event_sortable_columns', array( $this, 'event_sortable_column' ), 10, 1 );
        add_action( 'current_screen', array( $this, 'maybe_sort_events_table_by_start_date' ) );

		add_action( 'admin_head', array( $this, 'event_columns_styles' ) );
		add_filter( 'manage_calendar_location_posts_columns', array( $this, 'location_columns' ) );
		add_action( 'manage_calendar_location_posts_custom_column', array( $this, 'location_column' ), 10, 2 );

		add_filter( 'post_row_actions', array( $this, 'event_row_actions' ), 10, 2 );
		add_action( 'post_submitbox_start', array( $this, 'render_duplicate_event_button' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_stick_event_setting' ) );
		add_action( 'admin_action_clone_calendar_event', array( $this, 'clone_calendar_event' ) );

		add_action( 'save_post', array( $this, 'save_sticky_status' ), 99 );

		add_action( 'admin_init', array( $this, 'maybe_delete_old_calendar_slots' ) );

		// Register the importers
		$this->importers = new Calendar_Plus_Admin_Importers();
	}

	/**
	 * Add a setting to the edit event screen for sticking a post
	 *
	 * @param WP_Post $post
	 */
	public function render_stick_event_setting( WP_Post $post ) {

		if ( 'calendar_event' !== $post->post_type || ! current_user_can( 'publish_calendar_events' ) ) {
			return;
		}

		?>

		<div class="misc-pub-section">
			<input id="sticky" name="sticky" type="checkbox" value="sticky"<?php checked( is_sticky( $post->ID ) ); ?>>&nbsp;
			<label for="sticky"><?php esc_html_e( 'Stick this event to the top of the list', 'calendar-plus' ); ?></label>
		</div>

		<?php
	}

	/**
	 * Also save the sticky status of an event when it is being saved
	 *
	 * @param int $event_id
	 */
	public function save_sticky_status( $event_id ) {

		if ( 'calendar_event' !== get_post_type( $event_id ) ) {
			return;
		}

		if ( isset( $_POST['sticky'] ) && 'sticky' === $_POST['sticky'] ) {
			stick_post( $event_id );
		} else {
			unstick_post( $event_id );
		}
	}

	/**
	 * Retrieve the URL for cloning an event
	 *
	 * @param int $post_id
	 *
	 * @return string
	 */
	private function get_clone_event_url( $post_id ) {
		$clone_url = add_query_arg(
			array(
                'action' => 'clone_calendar_event',
                'post'   => $post_id,
			),
			admin_url( 'admin.php' )
		);

		return wp_nonce_url( $clone_url, 'clone_calendar_event_' . $post_id );
	}

	/**
	 * Function for handling the duplication of event posts
	 */
	public function clone_calendar_event() {

		if ( ! current_user_can( 'publish_calendar_events' ) ) {
			return;
		}

		if ( ! isset( $_REQUEST['post'], $_REQUEST['action'] ) || 'clone_calendar_event' !== $_REQUEST['action'] ) {
			return;
		}

		$id = intval( $_REQUEST['post'] );
		check_admin_referer( 'clone_calendar_event_' . $id );

		if ( ! $event = calendarp_get_event( $id ) ) {
			return;
		}

		$post = $event->get_post();

		// Duplicate post fields

		$new_post = get_object_vars( $post );

		$new_post['post_author'] = current_user_can( 'edit_others_calendar_events' ) ? $post->post_author : wp_get_current_user()->ID;
		$new_post['post_title'] = sprintf( __( 'Copy of %s', 'calendar-plus' ), $post->post_title );

		unset( $new_post['ID'], $new_post['guid'] );

		// Insert the post

		$new_id = wp_insert_post( $new_post );
		$new_event = calendarp_get_event( $new_id );

		if ( 0 === $new_id || is_wp_error( $new_id ) || ! $new_event ) {
			return;
		}

		// Copy post taxonomy terms

		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			$post_terms = wp_get_object_terms( $id, $taxonomy, array( 'fields' => 'slugs' ) );
			wp_set_object_terms( $new_id, $post_terms, $taxonomy, false );
		}

		// Copy relevant post meta

		$meta_keys = array( 'location_id', 'all_day', 'color', 'status' );

		foreach ( $meta_keys as $meta_key ) {
			$meta_key = '_' . $meta_key;
			$value = get_post_meta( $id, $meta_key, true );

			if ( '' !== $value ) {
				add_post_meta( $new_id, $meta_key, $value );
			}
		}

		$new_event->update_rules( $event->get_rules() );

		update_post_meta( $new_id, '_event_clone_original', $id );

		// Redirect back to the previous page

		$referer = wp_get_referer();

		if ( false !== strpos( $referer, 'post.php' ) ) {
			$referer = add_query_arg( 'post', $new_id, $referer );
		}

		wp_redirect( esc_url_raw( $referer ) );
		exit;
	}

	/**
	 * Add additional row action links to Calendar Events
	 *
	 * @param array   $actions
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public function event_row_actions( $actions, $post ) {

		if ( 'calendar_event' !== $post->post_type || ! current_user_can( 'publish_calendar_events' ) ) {
			return $actions;
		}

		$actions['clone'] = sprintf(
			'<a href="%s" title="%s">%s</a>',
			esc_url( $this->get_clone_event_url( $post->ID ) ),
			esc_attr__( 'Duplicate this event', 'calendar-plus' ),
			esc_html__( 'Duplicate', 'calendar-plus' )
		);

		return $actions;
	}

	/**
	 * Render a link for duplicating an event
	 */
	public function render_duplicate_event_button() {

		if ( ! isset( $_GET['post'] ) || 'calendar_event' !== get_post_type( intval( $_GET['post'] ) ) ) {
			return;
		}

		if ( ! current_user_can( 'publish_calendar_events' ) ) {
			return;
		}

		$id = intval( $_GET['post'] );

		?>
		<div id="duplicate-action">
			<a class="submit_duplicate duplication" href="<?php echo esc_url( $this->get_clone_event_url( $id ) ); ?>">
				<?php esc_html_e( 'Duplicate', 'calendar-plus' ); ?>
			</a>
		</div>
		<?php
	}

	public function maybe_delete_old_calendar_slots() {
		global $wpdb;

		if ( ! get_transient( 'calendarp_delete_old_calendar_slots' ) ) {
			set_transient( 'calendarp_delete_old_calendar_slots', true, 3600 * 48 ); // We clean every 2 days

			$current_datetime = current_time( 'timestamp' );
			$two_months_ago_date = date( 'Y-m-d', strtotime( '-12 months', $current_datetime ) );

			$event_ids = $wpdb->get_row( $wpdb->prepare( "SELECT DISTINCT(event_id) FROM $wpdb->calendarp_calendar WHERE from_date < %s", $two_months_ago_date ) );

			if ( ! $event_ids ) {
				return;
			}

			foreach ( $event_ids as $event_id ) {
				calendarp_delete_calendar_cache( $event_id );
			}

			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->calendarp_calendar WHERE from_date < %s", $two_months_ago_date ) );
		}
	}

	public function event_columns( $columns ) {
		$columns = array();
		$columns['cb'] = '<input type="checkbox" />';
		$columns['title'] = __( 'Title', 'calendar-plus' );
		$columns['details'] = __( 'Details', 'calendar-plus' );
		//      $columns['color'] = __( 'Event color', 'calendar-plus' );
		$columns['location'] = __( 'Location', 'calendar-plus' );
		$columns['category'] = __( 'Categories', 'calendar-plus' );
		$columns['date'] = __( 'Date', 'calendar-plus' );
		$columns['event_id'] = 'ID';

		return $columns;
	}

	public function event_column( $column, $event_id ) {
		$event = calendarp_get_event( $event_id );
		switch ( $column ) {
			case 'event_id':
				echo $event->ID;
				break;

			case 'location':
				if ( $location = $event->get_location() ) {
					echo '<a href="' . get_edit_post_link( $location->ID ) . '" title="' . esc_attr__( 'Edit Location', 'calendar-plus' ) . '">' . get_the_title( $location->ID ) . '</a>';
				} else {
					echo '<span class="na">–</span>';
				}
				break;

			case 'category':
				if ( ! $terms = get_the_terms( $event_id, 'calendar_event_category' ) ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					foreach ( $terms as $term ) {
						$termlist[] = '<a href="' . admin_url( 'edit.php?calendar_event_category=' . $term->slug . '&post_type=calendar_event' ) . ' ">' . $term->name . '</a>';
					}

					echo implode( ', ', $termlist );
				}
				break;

			case 'color':
				echo '<span style="background-color:' . $event->color . ';width:20px;height:20px;display:inline-block;"></span>';
				break;

			case 'details':
				echo calendarp_get_human_read_dates( $event->ID );
				break;

		}
	}

	public function event_sortable_column( $columns ) {
		$columns['details'] = 'details';
		return $columns;
	}

	public function maybe_sort_events_table_by_start_date() {
		$screen = get_current_screen();
		if (
			'edit-calendar_event' === $screen->id &&
			isset( $_GET['orderby'] ) &&
			'details' === $_GET['orderby']
		) {
			add_filter( 'posts_join', array( $this, 'events_query_join_dates_table' ), 10, 2 );
			add_filter( 'posts_orderby', array( $this, 'events_query_order_by_start_date' ), 10, 2 );
		}
	}

	public function events_query_join_dates_table( $join, $query ) {
		if ( 'calendar_event' === $query->query_vars['post_type'] ) {
			global $wpdb;
			$calendar_table = $wpdb->prefix . 'calendarp_calendar';
			$join .= " LEFT JOIN {$wpdb->prefix}calendarp_calendar ON {$wpdb->posts}.ID=$calendar_table.event_id ";
		}
		return $join;
	}

	public function events_query_order_by_start_date( $orderby, $query ) {
		if ( 'calendar_event' === $query->query_vars['post_type'] ) {
			global $wpdb;
			$calendar_table = $wpdb->prefix . 'calendarp_calendar';
			$orderby = $calendar_table . '.from_date ' . $query->query_vars['order'];
		}
		return $orderby;
	}

	public function event_columns_styles() {
		$screen = get_current_screen();
		if ( 'edit-calendar_event' != $screen->id ) {
			return;
		}
		?>
		<style>
			table.widefat td.column-event_id,
			table.widefat th.column-event_id {
				width: 50px;
			}
		</style>
		<?php
	}

	public function location_columns( $columns ) {
		$columns = array();
		$columns['cb'] = '<input type="checkbox" />';
		$columns['thumbnail'] = __( 'Thumbnail' );
		$columns['title'] = __( 'Title' );
		$columns['address'] = __( 'Address' );
		$columns['date'] = __( 'Date' );

		return $columns;
	}

	public function location_column( $column, $event_id ) {
		$location = calendarp_get_location( $event_id );
		switch ( $column ) {
			case 'thumbnail':
				if ( has_post_thumbnail( $location->ID ) ) {
					echo get_the_post_thumbnail( $location->ID, 'location_mini' );
				} else {
					echo '<span style="width:50px;height:50px;display:inline-block;background:#CBCBCB;border:1px solid #A6A4A4"></span>';
				}
				break;

			case 'address':
				$address = $location->get_full_address();
				if ( get_the_title( $location->ID ) != $address ) {
					echo $address;
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;

		}
	}

	public function enqueue_scripts( $hook ) {
		if ( 'calendar_event' === get_post_type() || 'calendar_location' === get_post_type() || 'calendar_event_page_calendar-plus-calendar' === $hook ) {
			$suffix = '.min';
			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				$suffix = '';
			}

			wp_enqueue_script(
				'calendar-plus-admin',
                calendarp_get_plugin_url() . 'admin/js/admin.js',
				[ 'jquery', 'backbone' ],
				calendarp_get_version()
			);
			wp_enqueue_script( 'wp-api' );

			$i10n = array(
                'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
                'edit_gmap_address_button' => __( 'Edit', 'calendar-plus' ),
                'delete_calendar_event'    => __( 'Are you sure that you want to delete this recurrence for this event?', 'calendar-plus' ),
                'gmaps_api_key_error'      => sprintf( __( 'There was an error loading Google Maps. Please, check that <a href="%s">your API Key is valid</a>', 'calendar-plus' ), admin_url( 'edit.php?post_type=calendar_event&page=calendar-plus-settings' ) ),
			);
			wp_localize_script( 'calendar-plus-admin', 'CalendarPlusi18n', $i10n );

			wp_enqueue_style(
				'calendar-plus-admin-styles',
                calendarp_get_plugin_url() . 'admin/css/calendar-plus-admin.css',
				[], calendarp_get_version()
			);
		}

		if ( 'calendar_event' === get_post_type() ) {
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );
		}

		if ( get_post_type() === 'calendar_location' ) {
			calendarp_enqueue_google_maps_scripts();
		}

		if ( get_post_type() === 'calendar_event' || get_post_type() === 'calendar_location' ) {
			wp_enqueue_style(
				'calendar-plus-admin-post-types-styles',
                calendarp_get_plugin_url() . 'admin/css/post-types.css',
				[], calendarp_get_version()
			);
		}

		if ( 'post.php' === $hook ) {
			wp_enqueue_script( 'wp-api' );
		}
	}

	public function enqueue_block_editor_assets() {
		wp_enqueue_script('calendarp-admin-blocks', calendarp_get_plugin_url() . 'admin/js/editor-blocks.js', [], calendarp_get_version(), true );

		$blocks_options = [
			'calendar_image' => calendarp_get_plugin_url() . 'admin/images/calendar.jpg'
		];

		wp_localize_script( 'calendarp-admin-blocks', 'CalPlusBlocksOptions', $blocks_options );
	}

	public function add_meta_boxes() {
		$this->meta_boxes['event_details'] = new Calendar_Plus_Event_Details_Metabox();
		//$this->meta_boxes['event_status'] = new Calendar_Plus_Event_Status_Metabox();
		$this->meta_boxes['event_location'] = new Calendar_Plus_Event_Location_Metabox();
		//      $this->meta_boxes['event_color'] = new Calendar_Plus_Event_Color_Metabox();
		$this->meta_boxes['location_location'] = new Calendar_Plus_Location_Location_Metabox();
	}

	public function add_menu_pages() {
		$this->menu_pages['settings'] = new Calendar_Plus_Admin_Settings_Page( $this->plugin_name );
		$this->menu_pages['calendar'] = new Calendar_Plus_Admin_Calendar_Page( $this->plugin_name );
	}
}
