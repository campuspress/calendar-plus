<?php

class Calendar_Plus_Settings_Fields {

	function render_general_main_country_field( $value ) {
		calendarp_countries_dropdown(
			array(
				'selected' => $value,
				'name'     => calendarp_get_settings_slug() . '[country]',
			)
		);

		?>
		<br>
		<span class="description"><?php _e( 'This will be the default country for locations', 'calendar-plus' ); ?></span>
		<?php

	}

	function render_general_main_time_format_field( $value ) {
		?>
		<select name="<?php echo calendarp_get_settings_slug(); ?>[time_format]">
			<option value="24h" <?php selected( $value, '24h' ); ?>>
				24h
			</option>
			<option value="AM/PM" <?php selected( $value, 'AM/PM' ); ?>>
				AM/PM
			</option>
		</select>
		<?php

	}

	function render_general_main_events_page_field( $page_id ) {
		$args = array(
			'name'              => calendarp_get_settings_slug() . '[events_page_id]',
			'show_option_none'  => __( '-- Select a page --', 'calendar-plus' ),
			'option_none_value' => 0,
		);

		if ( 'page' === get_post_type( $page_id ) ) {
			$args['selected'] = $page_id;
		}

		wp_dropdown_pages( $args );

	}

	function render_general_main_replace_sidebar_field( $replace_sidebar ) {
		global $wp_registered_sidebars;

		$sidebars = $wp_registered_sidebars;

		// Remove our own sidebar
		if ( isset( $sidebars['calendarp'] ) ) {
			unset( $sidebars['calendarp'] );
		}

		?>
		<select name="<?php echo calendarp_get_settings_slug(); ?>[replace_sidebar]">
			<option value=""><?php _e( '-- Don\'t replace any sidebar --', 'calendar-plus' ); ?></option>
			<?php foreach ( $sidebars as $slug => $sidebar ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $replace_sidebar, $slug ); ?>><?php echo $sidebar['name']; ?></option>
			<?php endforeach; ?>
		</select>
		<br />
		<span class="description"><?php _e( 'Calendar Plus Sidebar will replace the selected sidebar only on Events pages', 'calendar-plus' ); ?></span>
		<?php

	}

	function render_media_event_thumbnail_field( $args ) {
		?>

		<label for="event_thumbnail_width"><?php _e( 'Width' ); ?></label>
		<input name="<?php echo calendarp_get_settings_slug(); ?>[event_thumbnail_width]" type="number" step="1" min="0" id="event_thumbnail_width" value="<?php echo absint( $args[0] ); ?>" class="small-text"> px
		<label for="event_thumbnail_height"><?php _e( 'Height' ); ?></label>
		<input name="<?php echo calendarp_get_settings_slug(); ?>[event_thumbnail_height]" type="number" step="1" min="0" id="event_thumbnail_height" value="<?php echo absint( $args[1] ); ?>" class="small-text"> px
		<input name="<?php echo calendarp_get_settings_slug(); ?>[event_thumbnail_crop]" type="checkbox" id="event_thumbnail_crop" value="1" <?php checked( $args[2] ); ?>>
		<label for="event_thumbnail_crop"><?php _e( 'Crop images to exact dimensions', 'calendar-plus' ); ?></label>
		<br />
		<span class="description"><?php _e( 'Images dimensions on events archive, search...', 'calendar-plus' ); ?></span>

		<?php
	}


	function render_media_event_map_height_field( $args ) {
		?>

		<label for="event_single_thumbnail_width"><?php _e( 'Width' ); ?></label>
		<input name="<?php echo calendarp_get_settings_slug(); ?>[event_single_map_height]" type="number" step="1" min="0" id="event_single_map_height" value="<?php echo absint( $args[0] ); ?>" class="small-text"> px
		<br />
		<span class="description"><?php _e( 'Google Maps height when displaying a single event', 'calendar-plus' ); ?></span>

		<?php
	}

	function render_general_users_section() {
		$users = calendarp_get_allowed_users();
		$dropdown_users = get_users(
			array(
				'exclude' => wp_list_pluck( $users, 'ID' ),
			)
		);
		?>
		<p><?php _e( 'Users allowed to manage Calendar Plus:', 'calendar-plus' ); ?></p>
		<ul id="calendarp-users"></ul>

		<table class="form-table">
			<tr>
				<th scope="col">
					<label for="allow-user"><?php _e( 'Allow new user', 'calendar-plus' ); ?></label>
				</th>
				<td>
					<select name="allow-user" id="allow-user">
						<option value=""><?php _e( '-- Select user --', 'calendar-plus' ); ?></option>
						<?php foreach ( $dropdown_users as $user ) : ?>
							<option value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></option>
						<?php endforeach; ?>
					</select>
					<button id="allow-user-add" class="button hidden"><?php _e( 'Allow', 'calendarp' ); ?></button>
				</td>
			</tr>
		</table>
		<?php
	}

	function render_permissions_capabilities_section() {
		global $wp_roles;

		echo '<p>', esc_html__( 'Choose which calendar capabilities belong to which roles', 'calendar-plus' ), '</p>';

		$roles = get_editable_roles();
		$caps = calendarp_get_assignable_capabilities();

		?>

		<table class="widefat fixed" id="calendarp-permissions">
			<thead>
			<tr>
				<th style="width: 200px;"><?php esc_html_e( 'Role', 'eventorganiser' ); ?></th>
				<?php foreach ( $roles as $role => $role_data ) { ?>
					<th>
					<?php

						echo isset( $wp_roles->role_names[ $role ] ) ?
							translate_user_role( $wp_roles->role_names[ $role ] ) :
							esc_html__( 'None', 'calendar-plus' );

					?>
						</th>
				<?php } ?>
			</tr>
			</thead>
			<tbody id="the-list">
			<?php
			$alternate = false;
			foreach ( $caps as $cap => $cap_label ) {
				$alternate = ! $alternate;
				?>

				<tr<?php echo $alternate ? ' class="alternate"' : ''; ?>>
					<th><?php echo esc_html( $cap_label ); ?></th>

					<?php

					foreach ( $roles as $role => $role_data ) {
						$role_obj = get_role( $role );
						$field_name = sprintf( '%s[permissions][%s][%s]', calendarp_get_settings_slug(), $role, $cap );
						?>
						<td>
							<input type="checkbox" name="<?php echo esc_attr( $field_name ); ?>" value="true"
															 <?php
																checked( $role_obj->has_cap( $cap ) );
																disabled( $role, 'administrator' );
																?>
							>
						</td>
					<?php } ?>

				</tr>
			<?php } ?>
			</tbody>
		</table>


		<?php
	}

	function render_general_main_display_country_field( $args ) {
		?>
		<input name="<?php echo calendarp_get_settings_slug(); ?>[display_location_country]" type="checkbox" id="display_location_country" value="1" <?php checked( $args[0] ); ?>>
		<?php
	}

	function render_gcal_settings_section() {
		$api = calendar_plus()->google_calendar->get_api_manager();
		$client_id = $api->get_client()->getClientId();
		$client_secret = $api->get_client()->getClientSecret();

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			render_gcal_settings_section_step_1();
		} elseif ( $client_id && $client_secret && ! $api->is_connected() ) {
			render_gcal_settings_section_step_2();
		} else {
			render_gcal_settings_section_step_3();
		}
	}

	function render_gcal_settings_section_step_1() {
		?>
		<h2><?php _e( 'Google Calendar', 'calendar-plus' ); ?></h2>
		<input type="hidden" name="gcal-action" value="step-1">
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="app-client-id"><?php _e( 'Client ID', 'appointments' ); ?></label>
				</th>
				<td>
					<input type="text" class="widefat" name="<?php echo calendarp_get_settings_slug(); ?>[gcal_client_id]" id="app-client-id" value="">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="app-client-secret"><?php _e( 'Client Secret', 'appointments' ); ?></label>
				</th>
				<td>
					<input type="text" name="<?php echo calendarp_get_settings_slug(); ?>[gcal_client_secret]" class="widefat" id="app-client-secret" value="">
				</td>
			</tr>
		</table>
		<?php
	}

	function render_gcal_settings_section_step_2() {
		$api = calendar_plus()->google_calendar->get_api_manager();
		$auth_url = $api->create_auth_url();
		?>
		<h2><?php _e( 'Google Calendar', 'calendar-plus' ); ?></h2>

		<h3><?php _e( 'Google Calendar API: Authorize access to your Google Application', 'calendar-plus' ); ?></h3>
		<ol>
			<li>
				<a href="<?php echo esc_url( $auth_url ); ?>" target="_blank"><?php _e( 'Generate your access code', 'calendar-plus' ); ?></a>
			</li>
			<li><?php _e( 'Fill the form below', 'appointments' ); ?></li>
		</ol>
		<input type="hidden" name="gcal-action" value="step-2">
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="app-access-code"><?php _e( 'Access code', 'appointments' ); ?></label>
				</th>
				<td>
					<input type="text" class="widefat" name="<?php echo calendarp_get_settings_slug(); ?>[gcal_access_code]" id="app-access-code" value="">
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Reset Credentials', 'calendar-plus' ), 'secondary', calendarp_get_settings_slug() . '[reset-gcal]' ); ?>
		<?php
	}

	function render_gcal_settings_section_step_3() {
		$api = calendar_plus()->google_calendar->get_api_manager();
		$calendars = $api->calendars_endpoint->get_calendars_list();
		$selected_calendar = $api->get_calendar();
		if ( is_wp_error( $calendars ) ) {
			?>
			<div class="error">
				<p><?php echo '<strong>' . $calendars->get_error_code() . '</strong>' . $calendars->get_error_message(); ?></p>
			</div>
			<?php
		} else {
			submit_button( __( 'Reset Credentials', 'calendar-plus' ), 'secondary', calendarp_get_settings_slug() . '[reset-gcal]' );
		}
	}

	function render_gmaps_api_key_field( $args ) {
		if ( defined( 'CALENDARP_GOOGLE_MAPS_API_KEY' ) ) {
			?>
			<p class="description"><?php _e( 'API Key has been defined in <code>wp-config.php</code>', 'calendarp' ); ?></p>
											 <?php
		} else {
			?>
			<input name="<?php echo calendarp_get_settings_slug(); ?>[gmaps_api_key]" type="text" id="gmaps_api_key" value="<?php echo esc_attr( $args ); ?>" class="large-text">
			<p class="description">
				<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank"><?php _e( 'How to get an API Key', 'calendar-plus' ); ?></a>
			</p>
			<?php
		}

	}

	function render_single_event_template_source_field( $value ) {
		?>
        <select name="<?php echo calendarp_get_settings_slug(); ?>[single_event_template_source]">
            <option value="calendar_plus_v2" <?php selected( $value, 'calendar_plus' ); ?>>
				<?php _e( 'Calendar plus theme compat', 'calendar-plus' ); ?>
            </option>
            <option value="calendar_plus" <?php selected( $value, 'calendar_plus' ); ?>>
				<?php _e( 'Calendar plus template', 'calendar-plus' ); ?>
            </option>
            <option value="theme_default" <?php selected( $value, 'theme_default' ); ?>>
				<?php _e( 'Theme default template', 'calendar-plus' ); ?>
            </option>
        </select>
		<?php

	}

	function render_event_archive_template_source_field( $value ) {
		?>
        <select name="<?php echo calendarp_get_settings_slug(); ?>[event_archive_template_source]">
            <option value="calendar_plus_v2" <?php selected( $value, 'calendar_plus' ); ?>>
				<?php _e( 'Calendar plus theme compat', 'calendar-plus' ); ?>
            </option>
            <option value="calendar_plus" <?php selected( $value, 'calendar_plus' ); ?>>
				<?php _e( 'Calendar plus template', 'calendar-plus' ); ?>
            </option>
            <option value="theme_default" <?php selected( $value, 'theme_default' ); ?>>
				<?php _e( 'Theme default template', 'calendar-plus' ); ?>
            </option>
        </select>
		<?php

	}

	function render_export_fields_field() {

		$fields = array(
			'title'       => __( 'Name', 'calendar-plus' ),
			'description' => __( 'Description', 'calendar-plus' ),
			'location'    => __( 'Event Location', 'calendar-plus' ),
			'categories'  => __( 'Categories', 'calendar-plus' ),
			'tags'        => __( 'Tags', 'calendar-plus' ),
			'recurrence'  => __( 'Recurrence', 'calendar-plus' ),
			'time'        => __( 'Start and End Times', 'calendar-plus' ),
			'date'        => __( 'Dates', 'calendar-plus' ),
		);

		?>
		<fieldset>

			<?php foreach ( $fields as $field => $label ) { ?>
				<label>
					<input type="checkbox" name="<?php echo calendarp_get_settings_slug(); ?>[export_fields][]" value="<?php echo esc_attr( $field ); ?>" checked="checked">
					<?php echo esc_html( $label ); ?>
				</label>
				<br>
			<?php } ?>

		</fieldset>
		<p class="description"><?php esc_html_e( 'Choose which fields to include in the export file', 'calendar-plus' ); ?></p>

		<?php
	}
}
