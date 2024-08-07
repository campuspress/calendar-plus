<?php

class Calendar_Plus_Admin_Settings_Page {

    public $slug;

	public function __construct( $plugin_name ) {
		$this->slug = $plugin_name . '-settings';
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'option_page_capability_calendar_plus_settings', array( $this, 'set_settings_api_capability' ) );
	}

	public function set_settings_api_capability( $cap ) {
		return 'manage_calendar_plus';
	}

	public function add_menu() {
		$id = add_submenu_page( 'edit.php?post_type=calendar_event', __( 'Calendar+ Settings', 'calendar-plus' ), __( 'Settings', 'calendar-plus' ), 'manage_calendar_plus', $this->slug, array( $this, 'render' ) );
		add_action( 'load-' . $id, array( $this, 'on_load' ) );
	}

	public function on_load() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		$this->handle_remote_feed_deletion();
	}

	private function handle_remote_feed_deletion() {

		if ( ! isset( $_GET['delete_remote_feed'] ) ) {
			return;
		}

		check_admin_referer( calendarp_get_settings_slug() . '_import' );

		$_SERVER['REQUEST_URI'] = remove_query_arg( array( '_wpnonce', 'delete_remote_feed' ) );

		$feed = $_GET['delete_remote_feed'];
		$feeds = get_option( 'calendar_plus_remote_feeds', array() );

		if ( $feeds && is_numeric( $_GET['delete_remote_feed'] ) && isset( $feeds[ $feed ] ) ) {
			unset( $feeds[ $feed ] );
		}

		update_option( 'calendar_plus_remote_feeds', $feeds );

		if ( count( $feeds ) < 1 ) {
			calendarp_unschedule_ical_sync_cron();
		}

		add_settings_error( calendarp_get_settings_slug(), 'remote-feed-deleted', __( 'Remote feed deleted.', 'calendar-plus' ), 'updated' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'calendarp-settings',
			calendarp_get_plugin_url() . 'admin/js/settings.js',
			[ 'jquery' ], calendarp_get_version(), true
		);

		wp_enqueue_style(
			'calendarp-settings',
			calendarp_get_plugin_url() . 'admin/css/settings.css',
			[], calendarp_get_version()
		);

		$users = calendarp_get_allowed_users();

		// Convert to JSON, JS will manage  to add the users
		$users_list = array();
		foreach ( $users as $user ) {
			/** @var WP_User $user */
			$element = array(
				'id'   => $user->ID,
				'name' => $user->display_name,
			);

			if ( get_edit_user_link( $user->ID ) ) {
				$element['link'] = get_edit_user_link( $user->ID );
				/* translators: %s: user display name */
				$element['linkTitle'] = sprintf( __( 'Edit %s user', 'calendar-plus' ), $user->display_name );
			}

			$element['removable'] = calendarp_is_user_removable( $user->ID );

			$users_list[] = $element;
		}

		// Some strings for JS that needs translations
		$i10n = array(
			'ays'         => __( 'Are you sure?', 'calendar-plus' ),
			'removeTitle' => __( 'Remove user from list', 'calendar-plus' ),
			'nonce'       => wp_create_nonce( 'calendarp-users-list' ),
			'currentUser' => get_current_user_id(),
			'usersList'   => $users_list,
		);
		wp_localize_script( 'calendarp-settings', 'calendarpSettings', $i10n );
	}

	private function get_tabs() {
		return apply_filters(
			'calendarp_settings_tabs',
			array(
				'general'     => __( 'General', 'calendar-plus' ),
				'media'       => __( 'Media', 'calendar-plus' ),
				'permissions' => __( 'Permissions', 'calendar-plus' ),
				'import'      => __( 'Import', 'calendar-plus' ),
				'export'      => __( 'Export', 'calendar-plus' ),
			)
		);
	}

	private function get_current_tab() {
		$tabs = $this->get_tabs();

		if ( ! isset( $_GET['tab'] ) ) {
			return key( $tabs );
		}

		return isset( $tabs[ $_GET['tab'] ] ) ? $_GET['tab'] : key( $tabs );
	}

	private function render_tabs() {
		$tabs = $this->get_tabs();
		$current_tab = $this->get_current_tab();

		require_once calendarp_get_plugin_dir() . 'admin/views/settings-tabs.php';
	}

	private function get_fields() {
		$settings = calendarp_get_settings();

		return array(
			'general'     => array(
				'general' => array(
					'title'  => null,
					'fields' => array(
						'general-main-country'                => array(
							'title' => __( 'Default Country', 'calendar-plus' ),
							'args'  => $settings['country'],
						),
						'general-main-display-country'        => array(
							'title'    => __( 'Display country in locations address', 'calendar-plus' ),
							'args'     => $settings['display_location_country'],
							'sanitize' => 'isset',
						),
						'general-main-time-format'            => array(
							'title' => __( 'Time format selector', 'calendar-plus' ),
							'args'  => $settings['time_format'],
						),
						'general-main-events-page'            => array(
							'title'    => __( 'Events Page', 'calendar-plus' ),
							'args'     => absint( $settings['events_page_id'] ),
							'sanitize' => 'absint',
						),
						'general-main-replace-sidebar'        => array(
							'title' => __( 'Replace Calendar Plus sidebar for', 'calendar-plus' ),
							'args'  => $settings['replace_sidebar'],
						),
					),
				),
				'gmaps'   => array(
					'title'  => __( 'Google Maps', 'calendar-plus' ),
					'fields' => array(
						'gmaps-api-key' => array(
							'title'    => '<label for="gmaps_api_key">' . __( 'Google Maps API Key', 'calendar-plus' ) . '</label>',
							'args'     => $settings['gmaps_api_key'],
							'sanitize' => 'sanitize_text_field',
						),
					),
				),
			),
			'media'       => array(
				'media' => array(
					'title'  => null,
					'fields' => array(
						'media-event-thumbnail'  => array(
							'title' => __( 'Events images', 'calendar-plus' ),
							'args'  => array(
								$settings['event_thumbnail_width'],
								$settings['event_thumbnail_height'],
								$settings['event_thumbnail_crop'],
							),

						),
						'media-event-map-height' => array(
							'title'    => __( 'Single Events maps height', 'calendar-plus' ),
							'args'     => array( $settings['event_single_map_height'] ),
							'sanitize' => 'absint',
						),
					),
				),
			),
			'permissions' => array(
				'users'        => array(
					'callback' => 'render_general_users_section',
					'title'    => __( 'Users', 'calendar-plus' ),
					'fields'   => array(),
				),
				'capabilities' => array(
					'callback' => 'render_permissions_capabilities_section',
					'title'    => __( 'Roles and Capabilities', 'calendar-plus' ),
					'fields'   => array(),
				),
			),
			'export'      => array(
				'export' => array(
					'title'  => __( 'Export events in CSV format', 'calendar-plus' ),
					'fields' => array(
						'export_fields' => array(
							'title' => __( 'Fields', 'calendar-plus' ),
							'args'  => array(),
						),
					),
				),
			),
		);
	}

	public function register_settings() {
		include_once calendarp_get_plugin_dir() . 'admin/settings-fields.php';

		$settings = calendarp_get_settings();
		register_setting( calendarp_get_settings_slug(), calendarp_get_settings_slug(), array( $this, 'sanitize_settings' ) );

		$fields = $this->get_fields();

		$fields_class = new Calendar_Plus_Settings_Fields();

		foreach ( $fields as $tab => $tab_sections ) {
			if ( $tab === $this->get_current_tab() ) {
				foreach ( $tab_sections as $section_slug => $section ) {
					$callback = null;
					if ( isset( $section['callback'] ) ) {
						$callback = array( $fields_class, $section['callback'] );
						unset( $section['callback'] );
					}

					$section_id = "$tab-$section_slug-section";
					add_settings_section( $section_id, $section['title'], $callback, $this->slug );

					foreach ( $section['fields'] as $field_key => $field ) {
						$callback = str_replace( '-', '_', 'render_' . $field_key . '_field' );
						$callback = array( $fields_class, $callback );
						add_settings_field( "$section_id-$field_key", $field['title'], $callback, $this->slug, $section_id, $field['args'] );
					}
				}
			}
		}

		do_action( 'calendarp_settings_fields', $settings );
	}

	public function render() {
		$tab = $this->get_current_tab();
		$tabs = $this->get_tabs();

		?>
		<div class="wrap">
			<?php

			$this->render_tabs();
			$this->render_errors();

			if ( 'import' === $tab ) {
				require_once dirname( __FILE__ ) . '/ical-import-view.php';
			} else {
				?>

				<form action="<?php echo self_admin_url( 'options.php' ); ?>" method="post" enctype="multipart/form-data">
					<?php

					settings_fields( calendarp_get_settings_slug() );
					do_settings_sections( $this->slug );

					$submit_text = 'export' === $tab ? $tabs[ $tab ] : __( 'Save Changes' );
					submit_button( $submit_text, 'primary', calendarp_get_settings_slug() . "[submit-$tab]" );

					?>
				</form>
			<?php } ?>
		</div>
		<?php
	}

	private function render_errors() {
		settings_errors( calendarp_get_settings_slug() );
	}

	public function sanitize_settings( $input ) {
		$settings = calendarp_get_settings();

		if ( ! current_user_can( 'manage_calendar_plus' ) ) {
			return $settings;
		}

		$ignore_fields = array( 'remote_feed' );

		foreach ( $input as $field => $value ) {
			if ( ! in_array( $field, $ignore_fields ) ) {
				$settings[ $field ] = $value;
			}
		}

		if ( isset( $input['submit-media'] ) ) {
			$settings['event-thumbnail_crop'] = isset( $input['event_thumbnail_crop'] );

		} elseif ( isset( $input['submit-export'] ) ) {
			$exclude_columns = array();

			if ( isset( $input['export_fields'] ) ) {
				$fields = $input['export_fields'];

				foreach ( array( 'title', 'description', 'location', 'categories', 'tags' ) as $field ) {
					if ( ! in_array( $field, $fields ) ) {
						$exclude_columns[] = $field;
					}
				}

				if ( ! in_array( 'recurrence', $fields ) ) {
					$exclude_columns[] = 'type';
					$exclude_columns[] = 'recurring_every';
				}

				if ( ! in_array( 'time', $fields ) ) {
					$exclude_columns[] = 'from_time';
					$exclude_columns[] = 'until_time';
				}

				if ( ! in_array( 'date', $fields ) ) {
					$exclude_columns[] = 'from_date';
					$exclude_columns[] = 'until_date';
				}
			}

			$include_columns = array_diff( Calendar_Plus_Export_CSV::get_supported_columns(), $exclude_columns );
			$export = new Calendar_Plus_Export_CSV( array(), $include_columns );
			$export->export();

		} elseif ( isset( $input['submit-permissions'] ) ) {

			$caps = array_keys( calendarp_get_assignable_capabilities() );

			foreach ( get_editable_roles() as $role_name => $role_data ) {

				if ( 'administrator' === $role_name || empty( $input['permissions'][ $role_name ] ) ) {
					continue;
				}

				$role = get_role( $role_name );
				$role_options = $input['permissions'][ $role_name ];

				foreach ( $caps as $cap ) {
					if ( isset( $role_options[ $cap ] ) && $role_options[ $cap ] ) {
						$role->add_cap( $cap );
					} else {
						$role->remove_cap( $cap );
					}
				}
			}
		} elseif ( isset( $input['submit-general'] ) ) {
			$settings['display_location_country'] = isset( $input['display_location_country'] );

		} elseif ( isset( $input['submit-ical-import'] ) ) {

			if ( empty( $_FILES['ical-file'] ) ) {
				wp_die( __( 'Please select a file to upload', 'calendarp' ) );
			}

			$file = $_FILES['ical-file'];
			$tmp_file = $file['tmp_name'];
			$content = file_get_contents( $tmp_file );

			// We'll use wp_handle_sideload to make sure
			// that the file is properly checked and sanitized

			// BUT FIRST! Let's temporarily knock off
			// the bug workaround in S3 handler:
			if ( class_exists( 'S3_Uploads\\Plugin' ) ) {
				$s3 = S3_Uploads\Plugin::get_instance();
				remove_filter(
					'wp_handle_sideload_prefilter',
					array( $s3, 'filter_sideload_move_temp_file_to_s3' )
				);
			}
			// Okay, so now, back to sideloading images as usual.

			$upload = wp_handle_sideload(
				$file,
				array(
					'test_form' => false,
					'mimes'     => array( 'ics' => 'text/calendar' ),
				)
			);
			if ( isset( $upload['error'] ) ) {
				wp_die( $upload['error'] );
			}

			$object = array(
				'post_title'     => basename( $upload['file'] ),
				'post_content'   => $upload['url'],
				'post_mime_type' => $upload['type'],
				'guid'           => $upload['url'],
				'context'        => 'import',
				'post_status'    => 'private',
			);
			$id = wp_insert_attachment( $object, $upload['file'] );

			$import_recurring = ! empty( $input['import-recurring'] );
			$result = calendarp_import_events( $content, $import_recurring );

			if ( is_wp_error( $result ) ):
				add_settings_error(
					calendarp_get_settings_slug(), $result->get_error_code(),
					$result->get_error_message(), 'error'
				);
			else:
				unset( $settings['import-recurring'], $settings['submit-ical-import'] );
				wp_delete_attachment( $id, true );

				add_settings_error(
					calendarp_get_settings_slug(), 'ical-imported',
					__( 'Events imported successfully.', 'calendar-plus' ), 'updated'
				);
			endif;

		} elseif ( isset( $input['submit-feed-sync-interval'] ) ) {

			if ( isset( $input['feed_sync_interval'] ) && in_array( $input['feed_sync_interval'], [ 'hourly', 'twicedaily', 'daily' ] ) ) {
				calendarp_schedule_ical_sync_cron( $input['feed_sync_interval'] );
			}

			add_settings_error(
				calendarp_get_settings_slug(), 'updated-sync-interval',
				__( 'Updated feed sync interval.', 'calendar-plus' ),'updated'
			);

		} elseif ( isset( $input['submit-add-remote-feed'] ) ) {

			if ( ! empty( $_POST['remote_feed'] ) && ! empty( $_POST['remote_feed']['source'] ) ) {
				$feed_info = stripslashes_deep( $_POST['remote_feed'] );

				$feeds = get_option( 'calendar_plus_remote_feeds', [] );

				$feed = [
					'name'         => sanitize_text_field( $feed_info['name'] ),
					'source'       => $feed_info['source'],
					'type'         => sanitize_text_field( $feed_info['type'] ),
					'author'       => intval( $feed_info['author'] ),
					'category'     => intval( $feed_info['category'] ),
					'status'       => in_array( $feed_info['status'], [ 'publish', 'draft', 'pending' ] ) ? $feed_info['status'] : 'publish',
					'exclude_past' => intval( $feed_info['exclude_past'] ),
					'keep_updated' => intval( $feed_info['keep_updated'] ),
					'last_sync'    => [],
				];

				if ( isset( $_POST['remote_feed_id'] ) && isset( $feeds[ $_POST['remote_feed_id'] ] ) ) {
					$feed_id = $_POST['remote_feed_id'];

					foreach ( $feed as $item => $value ) {
						if ( 'last_sync' !== $item ) {
							$feeds[ $feed_id ][ $item ] = $value;
						}
					}

					add_settings_error(
						calendarp_get_settings_slug(), 'updated-remote-feed',
						__( 'Updated remote feed.', 'calendar-plus' ),'updated'
					);

					$_REQUEST['_wp_http_referer'] = remove_query_arg( 'edit_remote_feed', $_REQUEST['_wp_http_referer'] );

				} else {
					$feeds[] = $feed;

					add_settings_error(
						calendarp_get_settings_slug(), 'updated-sync-interval',
						__( 'Added remote feed.', 'calendar-plus' ),'updated'
					);
				}

				update_option( 'calendar_plus_remote_feeds', $feeds );

				$interval = 'hourly';
				if ( isset( $settings['feed_sync_interval'] ) ) {
					$interval = $settings['feed_sync_interval'];
				} elseif ( isset( $settings['ical_sync_interval'] ) ) {
					$interval = $settings['ical_sync_interval'];
				}

				if ( ! calendarp_is_ical_sync_cron_scheduled() ) {
					calendarp_schedule_ical_sync_cron( $interval );
				}
			}
		}

		wp_cache_set( 'get_calendar_plus_widget', false, 'calendar' );

		return $settings;
	}
}
