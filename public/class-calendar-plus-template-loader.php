<?php

class Calendar_Plus_Template_Loader {

	public function __construct() {
		if( ! wp_is_block_theme() ) {
			add_action( 'template_include', array( $this, 'load_template' ) );
		}
	}

	public function load_template( $template ) {
		$events_page_id = calendarp_get_setting( 'events_page_id' );
		$templates = array( 'calendar-plus.php' );
		$file = '';

		// If this is a taxonomy archive and the post type isn't explicitly calendar_event.
		if ( is_tax() && get_post_type() !== 'calendar_event' ) {
			// if taxonomy is assigned to multiple post types, we are not forcing Cal+ template
			$assigned_to_multiple = calendarp_is_taxonomy_assigned_to_multiple( get_queried_object() );

			return $template;
		}

		if ( is_single() && get_post_type() == 'calendar_event' ) {
			$file = 'single-event.php';
			$templates[] = $file;
			$templates[] = calendarp_get_template_dir() . $file;
		} elseif ( is_tax( get_object_taxonomies( 'calendar_event' ) ) ) {

			$term = get_queried_object();

			if ( is_tax( 'calendar_event_category' ) ) {
				$file = 'taxonomy-' . $term->taxonomy . '.php';
			} else {
				$file = 'archive-event.php';
            }

			$templates[] = 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$templates[] = calendarp_get_template_dir() . 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$templates[] = 'taxonomy-' . $term->taxonomy . '.php';
			$templates[] = calendarp_get_template_dir() . 'taxonomy-' . $term->taxonomy . '.php';
			$templates[] = $file;
			$templates[] = calendarp_get_template_dir() . $file;

		} elseif ( is_post_type_archive( 'calendar_event' ) || ( $events_page_id && is_page( $events_page_id ) ) ) {
			$file   = 'archive-event.php';
			$templates[] = $file;
			$templates[] = calendarp_get_template_dir() . $file;
		}

		if ( $file ) {
			$template = locate_template( array_unique( $templates ) );
			if ( ! $template ) {
				$template = calendarp_get_plugin_dir() . 'public/templates/' . $file;
            }
		}

		return $template;
	}
}
