<?php
/**
 * Calendar Plus: includes/rest-api/endpoints/class-calendar-plus-rest-endpoints-event.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Calendar component: Calendar_Plus_REST_Endpoints_Events. */
class Calendar_Plus_REST_Endpoints_Events extends Calendar_Plus_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'events';

	/**
	 * Register routes.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/dates',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_dates' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/dates/(?P<date_id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item_date' ),
					'permission_callback' => array( $this, 'edit_item_date_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'edit_item_date' ),
					'permission_callback' => array( $this, 'edit_item_date_permissions_check' ),
					'args'                => array(
						'edit_all_recurrences' => array(
							'description'       => __( 'Edit all recurrences for this date', 'calendar-plus' ),
							'type'              => 'boolean',
							'sanitize_callback' => 'boolval',
							'validate_callback' => 'rest_validate_request_arg',
							'default'           => false,
						),
						'from_time'            => array(
							'description'       => __( 'New "from time" for this date', 'calendar-plus' ),
							'required'          => true,
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'until_time'           => array(
							'description'       => __( 'New "to time" for this date', 'calendar-plus' ),
							'required'          => true,
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Checks if a given request has access to read events.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @since 2.0
	 * @access public
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {

		$post_type = get_post_type_object( $this->post_type );

		if ( 'edit' === $request['context'] && ( ! current_user_can( $post_type->cap->edit_posts ) || ! current_user_can( 'manage_calendar_plus' ) ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit events.', 'calendar-plus' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to delete a post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @since 4.7.0
	 * @access public
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function edit_item_permissions_check( $request ) {

		$post      = get_post( $request['id'] );
		$post_type = get_post_type_object( $this->post_type );

		if ( ! $post || $this->post_type !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) || ! current_user_can( 'manage_calendar_plus' ) ) {
			return new WP_Error( 'rest_cannot_delete', __( 'Sorry, you are not allowed to edit this event.', 'calendar-plus' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}



	/**
	 * Handle edit item date permissions check.
	 *
	 * @param mixed $request Request.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function edit_item_date_permissions_check( $request ) {
		$permission = $this->edit_item_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}
		$cell = calendarp_get_event_cell( absint( $request['date_id'] ) );
		if ( ! $cell || (int) $cell->event_id !== (int) $request['id'] ) {
			return new WP_Error( 'calendarp_rest_wrong_date', __( 'The date does not exist for that event.', 'calendar-plus' ), array( 'status' => 404 ) );
		}
		return true;
	}

	/**
	 * Retrieves a collection of posts.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @since 4.7.0
	 * @access public
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();

		$year  = $request['year'];
		$month = $request['month'];

		if ( ! checkdate( $month, 1, $year ) ) {
			return new WP_Error( 'calendarp_rest_wrong_date', __( 'Please, enter a valid date.', 'calendar-plus' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
		$year  = str_pad( $year, 4, '0', STR_PAD_LEFT );
		$args  = array(
			'grouped_by_day' => false,
		);

		if ( ! empty( $request['category'] ) ) {
			$args['category'] = $request['category'];
		}

		if ( ! empty( $request['search'] ) ) {
			$args['search'] = $request['search'];
		}

		$query_result = calendarp_get_events_in_month( $month, $year, $args );
		$events       = array();
		foreach ( $query_result as $_event ) {
			$event = calendarp_get_event( $_event->event_id );
			if ( ! $event->current_user_can_read() ) {
				continue;
			}

			$event->attributes = $_event;

			$data     = $this->prepare_item_for_response( $event, $request );
			$events[] = $this->prepare_response_for_collection( $data );
		}

		// Reset filter.
		if ( 'edit' === $request['context'] ) {
			remove_filter( 'post_password_required', '__return_false' );
		}

		return rest_ensure_response( $events );
	}

	/**
	 * Retrieves a collection of event dates.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @since 4.7.0
	 * @access public
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item_dates( $request ) {
		$event_id = absint( $request['id'] );
		$event    = calendarp_get_event( $event_id );
		if ( ! $event || ! $event->current_user_can_read() ) {
			return new WP_Error( 'calendarp_rest_forbidden', __( 'You cannot view this event.', 'calendar-plus' ), array( 'status' => 403 ) );
		}
		return rest_ensure_response( calendar_plus()->generator->get_all_event_dates( $event_id ) );
	}

	/**
	 * Delete an event date from its timetable.
	 *
	 * @param mixed $request Request.
	 * @return mixed|WP_REST_Response
	 */
	public function delete_item_date( $request ) {
		calendarp_delete_event_cell( absint( $request['date_id'] ) );
		return rest_ensure_response( true );
	}

	/**
	 * Handle edit item date.
	 *
	 * @param mixed $request Request.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function edit_item_date( $request ) {
		global $wpdb;
		$cell_id              = absint( $request['date_id'] );
		$edit_all_recurrences = $request['edit_all_recurrences'];

		$cell = calendarp_get_event_cell( $cell_id );
		if ( ! $cell ) {
			return new WP_Error( 'calendarp_rest_wrong_date', __( 'The date does not exist for that event.', 'calendar-plus' ), array( 'status' => 404 ) );
		}

		$event_id = absint( $cell->event_id );
		$event    = calendarp_get_event( $event_id );

		if ( ! $event ) {
			return new WP_Error( 'calendarp_rest_wrong_event', __( 'The event does not exist.', 'calendar-plus' ), array( 'status' => 404 ) );
		}

		$from = $request['from_time'];
		$to   = $request['until_time'];
		$from = Calendar_Plus_Event_Rules_Times_Formatter::format_single_time( $from );
		$to   = Calendar_Plus_Event_Rules_Times_Formatter::format_single_time( $to );
		if ( false === $from || false === $to ) {
			return new WP_Error( 'calendarp_rest_malformed_rule', __( 'Please, use correct dates.', 'calendar-plus' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $request['from_am_pm'] ) ) {
			$from    = explode( ':', $from );
			$from[0] = calendarp_am_pm_to_24h( $from[0], $request['from_am_pm'] );
			$from    = $from[0] . ':' . $from[1];
		}
		if ( ! empty( $request['until_am_pm'] ) ) {
			$to    = explode( ':', $to );
			$to[0] = calendarp_am_pm_to_24h( $to[0], $request['until_am_pm'] );
			$to    = $to[0] . ':' . $to[1];
		}

		// Check if times are wellformed.
		$rule           = array(
			'rule_type' => 'times',
			'from'      => $from,
			'until'     => $to,
		);
		$formatted_rule = $event->format_rule( $rule );

		if ( ! $formatted_rule ) {
			return new WP_Error( 'calendarp_rest_malformed_rule', __( 'Please, use correct dates.', 'calendar-plus' ), rest_authorization_required_code() );
		}

		if ( $edit_all_recurrences ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Native schedule write uses typed update placeholders; both schedule and event caches are invalidated below.
			$wpdb->update(
				$wpdb->calendarp_calendar,
				array(
					'from_time'  => $formatted_rule['from'],
					'until_time' => $formatted_rule['until'],
				),
				array( 'event_id' => $event->ID ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			// Update the new rules.
			$rules                      = $event->get_rules();
			$rules['times'][0]['from']  = $formatted_rule['from'];
			$rules['times'][0]['until'] = $formatted_rule['until'];
			$event->update_rules( $rules );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Native schedule write uses typed update placeholders; both schedule and event caches are invalidated below.
			$wpdb->update(
				$wpdb->calendarp_calendar,
				array(
					'from_time'  => $formatted_rule['from'],
					'until_time' => $formatted_rule['until'],
				),
				array( 'ID' => $cell_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		calendarp_delete_calendar_cache( $event_id );
		calendarp_delete_events_since_cache();
		update_post_meta( $event_id, '_has_custom_dates', true );

		return rest_ensure_response( true );
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @since 2.0
	 * @access public
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';
		unset( $params['page'] );
		unset( $params['per_page'] );
		unset( $params['search'] );

		$params['month'] = array(
			'description'       => __( 'The month from when retrieve the list of the events.', 'calendar-plus' ),
			'required'          => true,
			'type'              => 'integer',
			'default'           => 0,
			'minimum'           => 1,
			'maximum'           => 12,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['year'] = array(
			'description'       => __( 'The year from when retrieve the list of the events.', 'calendar-plus' ),
			'required'          => true,
			'type'              => 'integer',
			'default'           => 0,
			'minimum'           => 1977,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['category'] = array(
			'description'       => __( 'Limit result set to products assigned to specific category IDs separated by commas.', 'calendar-plus' ),
			'required'          => false,
			'type'              => 'any',
			'default'           => '',
			'sanitize_callback' => array( $this, 'sanitize_categories_list' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['search'] = array(
			'description'       => __( 'Search events.', 'calendar-plus' ),
			'required'          => false,
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Handle sanitize categories list.
	 *
	 * @param mixed $categories Categories.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function sanitize_categories_list( $categories ) {
		if ( empty( $categories ) ) {
			return array();
		}

		if ( is_int( $categories ) ) {
			return array( $categories );
		}

		$cats = explode( ',', $categories );
		if ( ! is_array( $cats ) ) {
			$cats = array();
		}
		return array_map( 'absint', $cats );
	}

	/**
	 * Retrieve item schema.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Event',
			'type'       => 'object',
			'properties' => array(
				'id'        => array(
					'description' => esc_html__( 'Unique identifier for the event.', 'calendar-plus' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'     => array(
					'description' => esc_html__( 'The title of the event.', 'calendar-plus' ),
					'type'        => 'string',
				),
				'desc'      => array(
					'description' => esc_html__( 'The event description.', 'calendar-plus' ),
					'type'        => 'string',
				),
				'url'       => array(
					'description' => esc_html__( 'The event permalink.', 'calendar-plus' ),
					'type'        => 'string',
					'format'      => 'uri',
				),
				'start'     => array(
					'description' => esc_html__( 'The event start date.', 'calendar-plus' ),
					'type'        => 'object',
				),
				'end'       => array(
					'description' => esc_html__( 'The event end date.', 'calendar-plus' ),
					'type'        => 'object',
				),
				'allDay'    => array(
					'description' => esc_html__( 'If the event is all day or not.', 'calendar-plus' ),
					'type'        => 'boolean',
				),
				'location'  => array(
					'description' => esc_html__( 'Location attributes. False if there is no location attached to the event.', 'calendar-plus' ),
					'type'        => 'mixed',
				),
				'humanDate' => array(
					'description' => esc_html__( 'Dates string adapted to human read.', 'calendar-plus' ),
					'type'        => 'string',
				),
				'calendars' => array(
					'description' => esc_html__( 'URLs to import to external calendars', 'calendar-plus' ),
					'type'        => 'object',
				),
			),
		);

		return $schema;
	}

	/**
	 * Checks if the user can access password-protected content.
	 *
	 * This method determines whether we need to override the regular password
	 * check in core with a filter.
	 *
	 * @param Calendar_Plus_Event $event Event to check against.
	 * @param WP_REST_Request     $request Request data to check.
	 * @since 2.0
	 * @access public
	 *
	 * @return bool True if the user can access password-protected content, otherwise false.
	 */
	public function can_access_password_content( $event, $request ) {
		$post = $event->post;
		if ( empty( $post->post_password ) ) {
			// No filter required.
			return false;
		}

		// Edit context always gets access to password-protected posts.
		if ( 'edit' === $request['context'] ) {
			return true;
		}

		// No password, no auth.
		if ( empty( $request['password'] ) ) {
			return false;
		}

		// Double-check the request password.
		return hash_equals( $post->post_password, $request['password'] );
	}


	/**
	 * Prepares a single event output for response.
	 *
	 * @param Calendar_Plus_Event $event Event object.
	 * @param WP_REST_Request     $request Request object.
	 * @since 2.0
	 * @access public
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $event, $request ) {
		$post = $event->post;

		setup_postdata( $post );

		$schema = $this->get_item_schema();

		// Base fields for every post.
		$data = array();

		if ( ! empty( $schema['properties']['id'] ) ) {
			$data['id'] = $post->ID;
		}

		$data['allDay'] = false;
		if ( ! empty( $schema['properties']['allDay'] ) && $event->is_all_day_event() ) {
			$data['allDay'] = true;
		}

		$start = '';
		if ( ! empty( $schema['properties']['start'] ) ) {
			$start         = strtotime( $event->attributes->from_date . ' ' . $event->attributes->from_time );
			$data['start'] = array(
				'year'   => gmdate( 'Y', $start ),
				'month'  => gmdate( 'n', $start ),
				'day'    => gmdate( 'j', $start ),
				'hour'   => gmdate( 'H', $start ),
				'minute' => gmdate( 'i', $start ),
			);
		}

		$end = '';
		if ( ! empty( $schema['properties']['end'] ) ) {
			$end         = strtotime( $event->attributes->until_date . ' ' . $event->attributes->until_time );
			$data['end'] = array(
				'year'   => gmdate( 'Y', $end ),
				'month'  => gmdate( 'n', $end ),
				'day'    => gmdate( 'j', $end ),
				'hour'   => gmdate( 'H', $end ),
				'minute' => gmdate( 'i', $end ),
			);
		}

		if ( ! empty( $schema['properties']['url'] ) ) {
			$data['url'] = get_permalink( $post->ID );
			$data['url'] = add_query_arg( 'event-date', $event->attributes->ID, $data['url'] );
		}

		if ( ! empty( $schema['properties']['title'] ) ) {
			add_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );
			$data['title'] = $post->post_title;
			remove_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );
		}

		$has_password_filter = false;

		if ( $this->can_access_password_content( $event, $request ) ) {
			// Allow access to the post, permissions already checked before.
			add_filter( 'post_password_required', '__return_false' );

			$has_password_filter = true;
		}

		if ( ! empty( $schema['properties']['desc'] ) ) {
			/** This filter is documented in wp-includes/post-template.php */
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve native WordPress excerpt filters.
			$excerpt      = apply_filters( 'the_excerpt', apply_filters( 'get_the_excerpt', $post->post_excerpt, $post ) );
			$data['desc'] = post_password_required( $post ) ? '' : $excerpt;
		}

		if ( $has_password_filter ) {
			// Reset filter.
			remove_filter( 'post_password_required', '__return_false' );
		}

		if ( ! empty( $schema['properties']['location'] ) ) {
			$location = $event->get_location();
			if ( $location ) {
				$data['location'] = array(
					'id'        => $location->ID,
					'address'   => $location->get_full_address(),
					'desc'      => $location->post->post_content,
					'thumbnail' => get_the_post_thumbnail_url( $location->ID ),
				);

				$meta = get_post_meta( $location->ID );
				foreach ( $meta as $key => $value ) {
					if ( in_array( $key, array( '_location_type', '_address', '_city', '_state', '_postcode', '_country', '_gmaps_options' ), true ) ) {
						$data['location']['meta'][ $key ] = $value[0];
					}
				}
			} else {
				$data['location'] = false;
			}
		}

		if ( ! empty( $schema['properties']['humanDate'] ) ) {
			$data['humanDate'] = calendarp_get_human_read_dates( $event->ID );
		}

		$args = (object) array(
			'from_time'  => gmdate( 'H:i', $start ),
			'until_time' => gmdate( 'H:i', $end ),
			'from_date'  => gmdate( 'Y-m-d', $start ),
			'until_date' => gmdate( 'Y-m-d', $end ),
		);

		$data['calendars'] = array(
			'google'  => array(
				'name' => __( 'Google Calendar', 'calendar-plus' ),
				'url'  => calendarp_get_external_calendar_button_link( 'gcal', $event->ID, $args ),
			),
			'outlook' => array(
				'name' => __( 'Outlook', 'calendar-plus' ),
				'url'  => calendarp_get_external_calendar_button_link( 'ical', $event->ID, $args ),
			),
			'ical'    => array(
				'name' => __( 'iCal File', 'calendar-plus' ),
				'url'  => calendarp_get_external_calendar_button_link( 'ical', $event->ID, $args ),
			),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $event ) );
		return $response;
	}

	/**
	 * Prepares links for the request.
	 *
	 * @param Calendar_Plus_Event $event Event object.
	 * @since 2.0
	 * @access protected
	 *
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $event ) {
		$post = $event->post;
		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $post->ID ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		if ( in_array( $post->post_type, array( 'post', 'page' ), true ) || post_type_supports( $post->post_type, 'revisions' ) ) {
			$links['version-history'] = array(
				'href' => rest_url( trailingslashit( $base ) . $post->ID . '/revisions' ),
			);
		}

		$post_type_obj = get_post_type_object( $post->post_type );

		// If we have a featured media, add that.
		$featured_media = get_post_thumbnail_id( $post->ID );
		if ( $featured_media ) {
			$image_url = rest_url( 'wp/v2/media/' . $featured_media );

			$links['https://api.w.org/featuredmedia'] = array(
				'href'       => $image_url,
				'embeddable' => true,
			);
		}

		if ( ! in_array( $post->post_type, array( 'attachment', 'nav_menu_item', 'revision' ), true ) ) {
			$attachments_url = rest_url( 'wp/v2/media' );
			$attachments_url = add_query_arg( 'parent', $post->ID, $attachments_url );

			$links['https://api.w.org/attachment'] = array(
				'href' => $attachments_url,
			);
		}

		$taxonomies = get_object_taxonomies( $post->post_type );

		if ( ! empty( $taxonomies ) ) {
			$links['https://api.w.org/term'] = array();

			foreach ( $taxonomies as $tax ) {
				$taxonomy_obj = get_taxonomy( $tax );

				// Skip taxonomies that are not public.
				if ( empty( $taxonomy_obj->show_in_rest ) ) {
					continue;
				}

				$tax_base = ! empty( $taxonomy_obj->rest_base ) ? $taxonomy_obj->rest_base : $tax;

				$terms_url = add_query_arg(
					'post',
					$post->ID,
					rest_url( 'wp/v2/' . $tax_base )
				);

				$links['https://api.w.org/term'][] = array(
					'href'       => $terms_url,
					'taxonomy'   => $tax,
					'embeddable' => true,
				);
			}
		}

		return $links;
	}

	/**
	 * Overwrites the default protected title format.
	 *
	 * By default, WordPress will show password protected posts with a title of
	 * "Protected: %s", as the REST API communicates the protected status of a post
	 * in a machine readable format, we remove the "Protected: " prefix.
	 *
	 * @return string Protected title format.
	 */
	public function protected_title_format() {
		return '%s';
	}
}
