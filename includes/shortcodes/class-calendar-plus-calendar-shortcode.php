<?php

class Calendar_Plus_Calendar_Shortcode {

	/**
	 * Shortcode instances
	 */
	public static $instances = array();

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_shortcode( 'calendarp-calendar', array( $this, 'render' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor_admin_scripts' ) );

		add_action('init', array($this,'register_block'));

		$this->init_tiny_mce_button();
	}

	/**
	 * Build a new instance array using a list of shortcode attributes
	 *
	 * @param array $atts The shortcode attributes
	 *
	 * @return array A shortcode instance array
	 */
	private function build_instance( $atts ) {

		$default_atts = array(
			'category'          => '',
			'view'              => 'month',
			'day_format'        => 'd',
			'date_format'       => 'm/d',
			'month_name_format' => 'M',
			'dow_format'        => 'l',
			'time_format'       => get_option( 'time_format' ),
		);

		$atts = wp_parse_args( $atts, $default_atts );

		$current_date = current_time( 'timestamp' );

		$instance = array(
			'id'          => 'calendar-plus-calendar-' . count( self::$instances ),
			'currentDate' => array(
				'year'  => date( 'Y', $current_date ),
				'month' => date( 'm', $current_date ),
				'day'   => date( 'd', $current_date ),
			),
			'showPopups'  => true,
		);

		$instance = array_merge( $instance, $atts );

		if ( ! empty( $atts['category'] ) ) {
			$categories = explode( ',', $atts['category'] );

			$categories = array_map( 'absint', $categories );
			$categories = array_filter( $categories, array( $this, 'is_valid_category' ) );

			if ( $categories ) {
				$instance['category'] = $categories;
				$instance['filterable'] = false;
			}
		}

		self::$instances[] = $instance;

		return $instance;
	}

	/**
	 * Determine whether a given Event Category is valid
	 *
	 * @param int|string $category
	 *
	 * @return bool
	 */
	private function is_valid_category( $category ) {
		return ! ! term_exists( $category, 'calendar_event_category' );
	}

	/**
	 * Render the content of the shortcode
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public function render( $atts ) {
		$instance = $this->build_instance( $atts );
		$accessible_view = isset( $_GET['acal'] ) && $_GET['acal'];

		ob_start();

		if ( $accessible_view ) {
			$acal = new Calendar_Plus_Accessible_Calendar();
			$acal->render();

		} else { ?>
			<a href="<?php echo esc_url( add_query_arg( 'acal', true ) ); ?>"><?php
				esc_html_e( 'View Accessible Version', 'calendar-plus' );
				?></a>

			<div class="calendar-plus">
				<div id="<?php echo esc_attr( $instance['id'] ); ?>" class="calendar-plus-calendar-wrap"></div>
			</div>
			<?php
		}

		add_action( 'wp_footer', array( $this, 'enqueue_public_scripts' ) );

		return ob_get_clean();
	}

	/**
	 * Enqueue the necessary scripts to render the calendar
	 */
	public function enqueue_public_scripts() {
		calendarp_enqueue_public_styles();

		$categories = get_terms( array(
			'taxonomy' => 'calendar_event_category',
		) );

		$categories = array_map( function ( $item ) {
			$cat = new stdClass();
			$cat->id = $item->term_id;
			$cat->name = $item->name;

			return $cat;
		}, $categories );

		$i18n = array(
			'messages'         => array(
				'allDay'            => __( 'All Day', 'calendar-plus' ),
				'date'              => 'Date',
				'time'              => 'Time',
				'event'             => 'Event',
				'week'              => 'week',
				'day'               => 'day',
				'month'             => 'month',
				'previous'          => 'back',
				'next'              => 'next',
				'yesterday'         => 'yesterday',
				'tomorrow'          => 'tomorrow',
				'today'             => 'today',
				'agenda'            => 'agenda',
				'loading'           => 'Loading...',
				'selectCategory'    => __( '--- Select Category ---', 'calendar-plus' ),
				'searchPlaceholder' => __( 'Search event', 'calendar-plus' ),
				'close'             => __( 'Close', 'calendar-plus' ),
				'readMore'          => __( 'Read More', 'calendar-plus' ),
				'addTo'             => __( 'Add to:', 'calendar-plus' ),
				'location'          => __( 'Location:', 'calendar-plus' ),
			),
			'currentTimestamp' => current_time( 'timestamp' ),
			'categories'       => $categories,
			'i18n'             => array(
				'rtl'                => is_rtl(),
				'locale'             => get_locale(),
				'dateFormat'         => get_option( 'date_format' ),
				'timeFormat'         => get_option( 'time_format' ),
				'agendaHeaderFormat' => 'F Y',
			),
			'instances'        => self::$instances,
			'baseurl'          => get_rest_url( null, '/calendar-plus/v1' ),
			'apinonce'         => wp_create_nonce( 'wp_rest' ),
			'dowStart'         => get_option( 'start_of_week' ),
		);

		wp_localize_script( 'calendar-plus-calendar', 'calendarPlusi18n', $i18n );
	}

	/**
	 * Register actions for the TinyMCE editor
	 */
	public function init_tiny_mce_button() {
		add_action( 'admin_head', array( $this, 'add_shortcode_button' ) );
	}

	/**
	 * Register actions for adding buttons to the TinyMCE editor
	 */
	function add_shortcode_button() {
		if ( ! current_user_can( 'manage_calendar_plus' ) ) {
			return;
		}

		if ( 'true' == get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', array( $this, 'add_shortcode_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( $this, 'register_shortcode_button' ) );
			add_filter( 'mce_external_languages', array( $this, 'add_tinymce_i18n' ) );
		}
	}

	/**
	 * Enqueue assets for the admin dashboard
	 *
	 * @param string $hook Current page hookname.
	 */
	public function enqueue_editor_admin_scripts( $hook ) {
		$version = calendarp_get_version();
		$url_base = calendarp_get_plugin_url() . 'admin/css/';

		if ( 'widgets.php' === $hook ) {
			wp_enqueue_style( 'calendarp-admin-widgets', $url_base . 'widgets.css', [], $version );
		}

		if ( current_user_can( 'manage_calendar_plus' ) ) {
			wp_enqueue_style( 'calendarp-admin-shortcodes', $url_base . 'editor-shortcode.css', [], $version );
		}
	}

	/**
	 * Add a shortcode insertion button to the TinyMCE editor
	 *
	 * @param array $plugins
	 *
	 * @return array
	 */
	public function add_shortcode_tinymce_plugin( $plugins ) {
		$plugins['calendarp_shortcodes'] = calendarp_get_plugin_url() . 'admin/js/editor-shortcode.js';

		return $plugins;
	}

	/**
	 * Alter the position of the TinyMCE shortcode button
	 *
	 * @param array $buttons
	 *
	 * @return array
	 */
	public function register_shortcode_button( $buttons ) {
		array_push( $buttons, '|', 'calendarp_shortcodes' );

		return $buttons;
	}

	/**
	 * Localise the text of the TinyMCE shortcode button.
	 *
	 * @param array $i18n
	 *
	 * @return array
	 */
	public function add_tinymce_i18n( $i18n ) {
		$i18n['calendarp_shortcodes'] = calendarp_get_plugin_dir() . 'admin/tinymce-shortcodes-i18n.php';

		return $i18n;
	}

	/**
	 * Register block for this shortcode
	 */
	public function register_block() {
		if(function_exists('register_block_type')) {
			register_block_type( 'calendar-plus/calendar', 
				array(
					'render_callback' => array( $this, 'blocks_content' ),
					'attributes' => array(
						'category' => array(
							'type' => 'string',
						),
						'date_format' => array(
							'type' => 'string',
						),
						'day_format' => array(
							'type' => 'string',
						),
						'month_name_format' => array(
							'type' => 'string',
						),
						'dow_format' => array(
							'type' => 'string',
						),
						'time_format' => array(
							'type' => 'string',
							'default' => get_option( 'time_format' )
						),
					)
				)
			);
		}
	}

	/**
	 * Render block on frontend
	 *
	 * @param array $atts
	 * @return void
	 */
	public function blocks_content($atts) {
		return $this->render($atts);
	}
}