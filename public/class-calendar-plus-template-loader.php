<?php

class Calendar_Plus_Template_Loader {

	private $template;

	public function __construct() {
		if ( ! wp_is_block_theme() ) {
			add_action( 'wp', array( $this, 'determine_template' ) );
			add_filter( 'template_include', array( $this, 'load_template' ) );
		}
	}

	/**
	 * Determine the template to use and store it in the $template property.
	 */
	public function determine_template() {
		$events_page_id     = calendarp_get_setting( 'events_page_id' );
		$legacy_integration = calendarp_get_setting( 'legacy_theme_integration' );
		$templates          = array( 'calendar-plus.php' );
		$file               = '';

		if ( is_single() && get_post_type() == 'calendar_event' ) {
			$replace_post_content_template = 'content-event';

			$file        = 'single-event.php';
			$templates[] = $file;
			$templates[] = calendarp_get_template_dir() . $file;

		} elseif ( is_tax( get_object_taxonomies( 'calendar_event' ) ) ) {
				$replace_post_content_template = 'archive-event';

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
			$replace_post_content_template = 'archive-event';

			$file        = 'archive-event.php';
			$templates[] = $file;
			$templates[] = calendarp_get_template_dir() . $file;
		}

		if ( $file ) {
			$locate_template = locate_template( array_unique( $templates ) );
			if ( ! $locate_template ) {
				if ( ! empty( $legacy_integration ) ) {
					$this->template = calendarp_get_plugin_dir() . 'public/templates/' . $file;
				} else {
					$this->replace_post_details( $replace_post_content_template );

					$this->template = locate_template( array( 'page.php', 'singular.php', 'single.php', 'post.php', 'index.php' ) );
				}
			} else {
				$this->template = $locate_template;
			}
		}
	}

	/**
	 * Load the determined template.
	 *
	 * @param string $template The default template.
	 * @return string The template to use.
	 */
	public function load_template( $template ) {
		return $this->template ? $this->template : $template;
	}

	/**
	 * Replace the post_title and post_content in the global $wp_query object.
	 * Used in modern theme integration method.
	 *
	 * @param string $template_name The specific template name to load.
	 */
	private function replace_post_details( $template_name ) {
		global $wp_query;

		// Start output buffering to capture the template content.
		ob_start();
		calendarp_get_template_part( 'post_content', $template_name );
		$content = '<!-- wp:html -->' . ob_get_clean() . '<!-- /wp:html -->';

		$title = false;

		// If its an archive page, we will force theme to use same templates as for the events page.
		if ( 'archive-event' === $template_name ) {
			$title = calendarp_get_template_title();

			// Get the events page ID and set it in the query.
			$events_page_id = calendarp_get_setting( 'events_page_id' );
			query_posts( array( 'page_id' => $events_page_id ) );
		}

		// Replace post content in the $wp_query object.
		if ( isset( $wp_query->post ) ) {
			if ( ! empty( $title ) ) {
				$wp_query->post->post_title = $title;
			}

			$wp_query->post->post_content = $content;
			$wp_query->posts              = array( $wp_query->post );
		}
	}
}
