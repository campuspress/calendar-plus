<?php

class Calendar_Plus_Taxonomies {

	/**
	 * Trigger all the functions needed to register CPTs and taxonomies
	 */
	public function register() {
		$this->register_location_post_type();
		$this->register_event_post_type();
		$this->register_event_category_taxonomy();
		$this->register_event_tag_taxonomy();
		$this->register_event_type_taxonomy();
		$this->register_uid_taxonomy();
	}

	/**
	 * Registers the calendar_event Post Type
	 */
	public function register_event_post_type() {
		$events_page_id = calendarp_get_setting( 'events_page_id' );
		$event_page     = get_post( $events_page_id );
		if ( $event_page ) {
			$path = get_page_uri( $events_page_id );
		} else {
			$path = false;
		}

		$labels = array(
			'name'               => __( 'Events', 'calendar-plus' ),
			'singular_name'      => __( 'Event', 'calendar-plus' ),
			'add_new'            => _x( 'Add New Event', 'Add new Event string on admin', 'calendar-plus' ),
			'add_new_item'       => __( 'Add New Event', 'calendar-plus' ),
			'edit_item'          => __( 'Edit Event', 'calendar-plus' ),
			'new_item'           => __( 'New Event', 'calendar-plus' ),
			'view_item'          => __( 'View Event', 'calendar-plus' ),
			'search_items'       => __( 'Search Events', 'calendar-plus' ),
			'not_found'          => __( 'No Events found', 'calendar-plus' ),
			'not_found_in_trash' => __( 'No Events found in Trash', 'calendar-plus' ),
			'parent_item_colon'  => __( 'Parent Event:', 'calendar-plus' ),
			'menu_name'          => __( 'Events', 'calendar-plus' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'Calendar+ Events', 'calendar-plus' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => null,
			'menu_icon'           => 'dashicons-calendar-alt',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => $path,
			'query_var'           => false,
			'can_export'          => true,
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
			'capability_type'     => 'calendar_event',
			'show_in_rest'        => true,
			'supports'            => array(
				'title',
				'editor',
				'thumbnail',
				'excerpt',
			),
		);

		if( wp_is_block_theme() ) {
			$args['rewrite'] = array( 'slug' => $path );
		}

		register_post_type( 'calendar_event', $args );
	}

	/**
	 * Registers the calendar_event_category Post Type
	 */
	public function register_event_category_taxonomy() {

		$labels = array(
			'name'                  => _x( 'Event Categories', 'Taxonomy Event Categories', 'calendar-plus' ),
			'singular_name'         => _x( 'Event Category', 'Taxonomy Event Category', 'calendar-plus' ),
			'search_items'          => __( 'Search Event Categories', 'calendar-plus' ),
			'popular_items'         => __( 'Popular Event Categories', 'calendar-plus' ),
			'all_items'             => __( 'All Event Categories', 'calendar-plus' ),
			'parent_item'           => __( 'Parent Event Category', 'calendar-plus' ),
			'parent_item_colon'     => __( 'Parent Event Category', 'calendar-plus' ),
			'edit_item'             => __( 'Edit Event Category', 'calendar-plus' ),
			'update_item'           => __( 'Update Event Category', 'calendar-plus' ),
			'add_new_item'          => __( 'Add New Event Category', 'calendar-plus' ),
			'new_item_name'         => __( 'New Event Category Name', 'calendar-plus' ),
			'add_or_remove_items'   => __( 'Add or remove Event Categories', 'calendar-plus' ),
			'choose_from_most_used' => __( 'Choose from most used text-domain', 'calendar-plus' ),
			'menu_name'             => __( 'Event Categories', 'calendar-plus' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => true,
			'show_admin_column' => false,
			'hierarchical'      => true,
			'show_tagcloud'     => true,
			'show_ui'           => true,
			'query_var'         => true,
			'map_meta_cap'      => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => apply_filters( 'calendarp_event_category_slug', 'event-category' ) ),
			'capabilities'      => array(
				'manage_terms' => 'manage_calendar_event_terms',
				'edit_terms'   => 'edit_calendar_event_terms',
				'delete_terms' => 'delete_calendar_event_terms',
				'assign_terms' => 'assign_calendar_event_terms',
			),
		);

		register_taxonomy( 'calendar_event_category', array( 'calendar_event' ), $args );
	}

	/**
	 * Registers the calendar_event_tag Taxonomy
	 */
	public function register_event_tag_taxonomy() {

		$labels = array(
			'name'                  => _x( 'Event Tags', 'Taxonomy Event Tags', 'calendar-plus' ),
			'singular_name'         => _x( 'Event Tag', 'Taxonomy Event Tag', 'calendar-plus' ),
			'search_items'          => __( 'Search Event Tags', 'calendar-plus' ),
			'popular_items'         => __( 'Popular Event Tags', 'calendar-plus' ),
			'all_items'             => __( 'All Event Tags', 'calendar-plus' ),
			'parent_item'           => __( 'Parent Event Tag', 'calendar-plus' ),
			'parent_item_colon'     => __( 'Parent Event Tag', 'calendar-plus' ),
			'edit_item'             => __( 'Edit Event Tag', 'calendar-plus' ),
			'update_item'           => __( 'Update Event Tag', 'calendar-plus' ),
			'add_new_item'          => __( 'Add New Event Tag', 'calendar-plus' ),
			'new_item_name'         => __( 'New Event Tag Name', 'calendar-plus' ),
			'add_or_remove_items'   => __( 'Add or remove Event Tags', 'calendar-plus' ),
			'choose_from_most_used' => __( 'Choose from most used text-domain', 'calendar-plus' ),
			'menu_name'             => __( 'Event Tags', 'calendar-plus' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => false,
			'show_admin_column' => false,
			'hierarchical'      => false,
			'show_tagcloud'     => true,
			'show_ui'           => true,
			'query_var'         => true,
			'map_meta_cap'      => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => apply_filters( 'calendarp_event_tag_slug', 'event-tag' ) ),
			'capabilities'      => array(
				'manage_terms' => 'manage_calendar_event_terms',
				'edit_terms'   => 'edit_calendar_event_terms',
				'delete_terms' => 'delete_calendar_event_terms',
				'assign_terms' => 'assign_calendar_event_terms',
			),
		);

		register_taxonomy( 'calendar_event_tag', array( 'calendar_event' ), $args );
	}

	/**
	 * Registers the calendar_event_all_day private Taxonomy
	 */
	public function register_event_type_taxonomy() {
		$args = array(
			'labels'            => array(),
			'public'            => false,
			'show_in_nav_menus' => false,
			'show_admin_column' => false,
			'hierarchical'      => false,
			'show_tagcloud'     => false,
			'show_ui'           => false,
			'query_var'         => false,
			'map_meta_cap'      => false,
		);

		register_taxonomy( 'calendar_event_type', array( 'calendar_event' ), $args );
	}

	/**
	 * Registers the calendar_location Post Type
	 */
	public function register_location_post_type() {

			$labels = array(
				'name'               => __( 'Locations', 'calendar-plus' ),
				'singular_name'      => __( 'Location', 'calendar-plus' ),
				'add_new'            => _x( 'Add New', 'calendar location', 'calendar-plus' ),
				'add_new_item'       => __( 'Add New Location', 'calendar-plus' ),
				'edit_item'          => __( 'Edit Location', 'calendar-plus' ),
				'new_item'           => __( 'New Location', 'calendar-plus' ),
				'view_item'          => __( 'View Location', 'calendar-plus' ),
				'search_items'       => __( 'Search Locations', 'calendar-plus' ),
				'not_found'          => __( 'No Locations found', 'calendar-plus' ),
				'not_found_in_trash' => __( 'No Locations found in Trash', 'calendar-plus' ),
				'parent_item_colon'  => __( 'Parent Location:', 'calendar-plus' ),
				'menu_name'          => __( 'Locations', 'calendar-plus' ),
			);

			$args = array(
				'labels'              => $labels,
				'hierarchical'        => false,
				'description'         => __( 'Event Locations', 'calendar-plus' ),
				'taxonomies'          => array(),
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=calendar_event',
				'show_in_admin_bar'   => false,
				'menu_position'       => null,
				'menu_icon'           => null,
				'map_meta_cap'        => true,
				'show_in_nav_menus'   => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'has_archive'         => true,
				'query_var'           => true,
				'can_export'          => true,
				'rewrite'             => array( 'slug' => apply_filters( 'calendarp_location_slug', 'location' ) ),
				'capability_type'     => 'calendar_location',
				'supports'            => array( 'title', 'thumbnail', 'editor' ),
			);

			register_post_type( 'calendar_location', $args );
	}

	public function register_uid_taxonomy() {

		$args = array(
			'labels'            => array(),
			'public'            => false,
			'show_in_nav_menus' => false,
			'show_admin_column' => false,
			'hierarchical'      => false,
			'show_tagcloud'     => false,
			'show_ui'           => false,
			'query_var'         => false,
			'map_meta_cap'      => false,
		);

		register_taxonomy( 'calendar_event_uid', array( 'calendar_event' ), $args );
	}
}
