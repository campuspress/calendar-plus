<?php

/**
 * Theme Compatibility
 */

function calendar_plus_reset_post( $args = array() ) {
	global $wp_query, $post;

	if( isset( $wp_query->post ) ) {
		$defaults = array(
			'ID'                    => $wp_query->post->ID,
			'post_status'           => $wp_query->post->post_status,
			'post_author'           => $wp_query->post->post_author,
			'post_parent'           => $wp_query->post->post_parent,
			'post_type'             => $wp_query->post->post_type,
			'post_date'             => $wp_query->post->post_date,
			'post_date_gmt'         => $wp_query->post->post_date_gmt,
			'post_modified'         => $wp_query->post->post_modified,
			'post_modified_gmt'     => $wp_query->post->post_modified_gmt,
			'post_content'          => $wp_query->post->post_content,
			'post_title'            => $wp_query->post->post_title,
			'post_excerpt'          => $wp_query->post->post_excerpt,
			'post_content_filtered' => $wp_query->post->post_content_filtered,
			'post_mime_type'        => $wp_query->post->post_mime_type,
			'post_password'         => $wp_query->post->post_password,
			'post_name'             => $wp_query->post->post_name,
			'guid'                  => $wp_query->post->guid,
			'menu_order'            => $wp_query->post->menu_order,
			'pinged'                => $wp_query->post->pinged,
			'to_ping'               => $wp_query->post->to_ping,
			'ping_status'           => $wp_query->post->ping_status,
			'comment_status'        => $wp_query->post->comment_status,
			'comment_count'         => $wp_query->post->comment_count,
			'filter'                => $wp_query->post->filter,

			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false
		);
	} else {
		$post_date = date( 'Y/m/d' );

		$defaults = array(
			'ID'                    => -9999,
			'post_status'           => 'publish',
			'post_author'           => 0,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => $post_date,
			'post_date_gmt'         => $post_date,
			'post_modified'         => $post_date,
			'post_modified_gmt'     => $post_date,
			'post_content'          => '',
			'post_title'            => '',
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => '',
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'filter'                => 'raw',

			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false
		);
	}

	$sample = wp_parse_args( $args, $defaults );

	if( empty( $sample ) ) {
		return;
	}

	$post = new WP_Post( (object) $sample );

	$wp_query->post  = $post;
	$wp_query->posts = array( $post );

	$wp_query->post_count = 1;
	$wp_query->is_404     = $sample['is_404'];
	$wp_query->is_page    = $sample['is_page'];
	$wp_query->is_single  = $sample['is_single'];
	$wp_query->is_archive = $sample['is_archive'];
	$wp_query->is_tax     = $sample['is_tax'];

	$wp_query->is_singular = $wp_query->is_single;

	unset( $sample );
}

class Calendar_Plus_Theme_Compat {

	public function __construct() {
		add_filter( 'template_include', array( $this, 'maybe_replace_content' ), 1 );
	}

	function maybe_replace_content( $template ) {
		$shortcodes = Calendar_Plus::get_instance()->shortcodes;
		if( empty( $shortcodes ) ) {
			return $template;
		}

		if( is_singular( 'calendar_event' ) ) {

			$source = calendarp_get_setting( 'single_event_template_source' );
			if( $source === 'calendar_plus' ) {
				return $template;
			}
			$event = calendarp_get_event( get_the_ID() );
			$event_post  = $event->post;
			$event_post->post_content = $shortcodes['single-event']->render_compat( array( 'event_id' => get_the_ID() ) );

			calendar_plus_reset_post( (array) $event_post );

			// This small hack allows avoiding duplications of meta
			add_filter( 'cpschool_post_meta_items', function( $meta, $post_id ) {
				$meta[] = 'empty';
				return $meta;
			}, 10, 2 );
		}

		if( is_archive() ) {

			$source = calendarp_get_setting( 'event_archive_template_source' );
			if( $source === 'calendar_plus' ) {
				return $template;
			}

			$type = '';

			if( is_tax( 'calendar_event_category' ) ) {
				$taxonomy_slug = 'calendar_event_category';
				$type          = 'category';
			} elseif( is_tax( 'calendar_event_tag' ) ) {
				$taxonomy_slug = 'calendar_event_tag';
				$type          = 'tag';
			}

			if( isset( $taxonomy_slug ) ) {

				$term_slug = get_query_var( $taxonomy_slug );
				$term = get_term_by( 'slug', $term_slug, $taxonomy_slug );

				if( $term ) {
					$taxonomy = get_taxonomy( $taxonomy_slug );
					$title    = sprintf( esc_html__( '%1$s: %2$s' ), $taxonomy->label, $term->name );

					$content = $shortcodes['events-by-cat']->render( array(
						$type => $term->term_id . ''
					) );

					calendar_plus_reset_post( array(
						'ID'             => 0,
						'post_author'    => 0,
						'post_date'      => '0000-00-00 00:00:00',
						'post_content'   => $content,
						'post_type'      => '',
						'post_title'     => $title,
						'post_status'    => 'publish',
						'is_archive'     => true,
						'is_page'        => true,
						'comment_status' => 'closed'
					) );

				}
			}
		}
		return locate_template( array(  'single.php', 'page.php' ) );
	}
}