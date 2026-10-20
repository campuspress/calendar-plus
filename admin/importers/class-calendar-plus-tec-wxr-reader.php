<?php
/**
 * Bounded, non-expanding reader for WordPress event exports.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Reads only the WXR fields used by the TEC importer. */
class Calendar_Plus_TEC_WXR_Reader {
	/**
	 * Combine up to three related exports under one aggregate budget.
	 *
	 * @param string[] $files WordPress-managed attachment filenames.
	 * @return array|WP_Error Combined source data.
	 */
	public function parse_files( $files ) {
		if ( ! $files || count( $files ) > 3 ) {
			return new WP_Error( 'calendarp_files', __( 'Supply one events XML and up to two companion XML files.', 'calendar-plus' ) );
		}
		$bytes  = 0;
		$merged = null;
		$posts  = array();
		$terms  = array();
		foreach ( $files as $file ) {
			$bytes += wp_filesize( $file );
			if ( $bytes > 8 * MB_IN_BYTES ) {
				return new WP_Error( 'calendarp_size', __( 'The combined exports exceed 8 MB.', 'calendar-plus' ) );
			}
			$data = $this->parse( $file );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			if ( null === $merged ) {
				$merged          = $data;
				$merged['posts'] = array();
				$merged['terms'] = array();
			} elseif ( untrailingslashit( $merged['base_url'] ) !== untrailingslashit( $data['base_url'] ) ) {
				return new WP_Error( 'calendarp_source', __( 'All exports must come from the same source site.', 'calendar-plus' ) );
			}
			$merged['authors'] += $data['authors'];
			foreach ( $data['posts'] as $post ) {
				$id = absint( $post['post_id'] );
				if ( ! $id || ( isset( $posts[ $id ] ) && $posts[ $id ] !== $post ) ) {
					return new WP_Error( 'calendarp_ids', __( 'The exports contain missing or conflicting record IDs.', 'calendar-plus' ) );
				}
				$posts[ $id ] = $post;
			}
			foreach ( $data['terms'] as $term ) {
				$key = $term['taxonomy'] . ':' . $term['slug'];
				if ( isset( $terms[ $key ] ) && $terms[ $key ] !== $term ) {
					return new WP_Error( 'calendarp_term_conflict', __( 'The exports contain conflicting term definitions.', 'calendar-plus' ) );
				}
				$terms[ $key ] = $term;
			}
			if ( count( $posts ) > 1000 || count( $terms ) > 2000 || count( $merged['authors'] ) > 1000 ) {
				return new WP_Error( 'calendarp_records', __( 'The combined exports exceed the related record limit.', 'calendar-plus' ) );
			}
		}
		$merged['posts'] = array_values( $posts );
		$merged['terms'] = array_values( $terms );
		return $merged;
	}

	/**
	 * Read a WordPress-managed import attachment.
	 *
	 * @param string $file Attachment filename.
	 * @return array|WP_Error Parsed data or an error.
	 */
	public function parse( $file ) {
		if ( ! class_exists( 'XMLReader' ) || ! function_exists( 'simplexml_load_string' ) ) {
			return new WP_Error( 'calendarp_xml', __( 'The XMLReader and SimpleXML PHP extensions are required.', 'calendar-plus' ) );
		}
		if ( ! $file || ! wp_filesize( $file ) || wp_filesize( $file ) > 8 * MB_IN_BYTES ) {
			return new WP_Error( 'calendarp_size', __( 'Use a WordPress XML export smaller than 8 MB. Split larger exports before importing.', 'calendar-plus' ) );
		}

		$data     = array(
			'version'  => '',
			'base_url' => '',
			'authors'  => array(),
			'terms'    => array(),
			'posts'    => array(),
		);
		$previous = libxml_use_internal_errors( true );
		libxml_clear_errors();
		$reader = new XMLReader();
		try {
			if ( ! $reader->open( $file, null, LIBXML_NONET ) ) {
				return $this->invalid();
			}
			$reader->setParserProperty( XMLReader::SUBST_ENTITIES, false );
			$reader->setParserProperty( XMLReader::LOADDTD, false );
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- XMLReader exposes these PHP properties.
			while ( $reader->read() ) {
				if ( XMLReader::DOC_TYPE === $reader->nodeType || XMLReader::ENTITY_REF === $reader->nodeType || $reader->depth > 64 ) {
					return $this->invalid();
				}
				if ( XMLReader::ELEMENT !== $reader->nodeType || 2 !== $reader->depth ) {
					continue;
				}
				$name = $reader->localName;
				if ( ! in_array( $name, array( 'wxr_version', 'base_site_url', 'base_blog_url', 'author', 'term', 'tag', 'item' ), true ) ) {
					continue;
				}
				$markup = $reader->readOuterXml();
				if ( strlen( $markup ) > MB_IN_BYTES ) {
					return $this->invalid();
				}
				$node = simplexml_load_string( $markup, 'SimpleXMLElement', LIBXML_NONET );
				if ( false === $node ) {
					return $this->invalid();
				}
				$namespaces = $node->getNamespaces( true );
				$wp         = isset( $namespaces['wp'] ) ? $node->children( $namespaces['wp'] ) : $node;
				if ( 'wxr_version' === $name ) {
					$data['version'] = trim( (string) $node );
				} elseif ( 'base_blog_url' === $name || ( 'base_site_url' === $name && ! $data['base_url'] ) ) {
					$data['base_url'] = esc_url_raw( trim( (string) $node ), array( 'http', 'https' ) );
				} elseif ( 'author' === $name ) {
					$author = array();
					foreach ( array( 'author_id', 'author_login', 'author_email', 'author_display_name', 'author_first_name', 'author_last_name' ) as $key ) {
						$author[ $key ] = (string) $wp->$key;
					}
					$data['authors'][ $author['author_login'] ] = $author;
				} elseif ( 'term' === $name || 'tag' === $name ) {
					$data['terms'][] = array(
						'taxonomy'    => 'tag' === $name ? 'post_tag' : (string) $wp->term_taxonomy,
						'slug'        => (string) ( 'tag' === $name ? $wp->tag_slug : $wp->term_slug ),
						'name'        => (string) ( 'tag' === $name ? $wp->tag_name : $wp->term_name ),
						'parent'      => (string) $wp->term_parent,
						'description' => (string) ( 'tag' === $name ? $wp->tag_description : $wp->term_description ),
					);
				} elseif ( 'item' === $name ) {
					$post = $this->post( $node, $wp, $namespaces );
					if ( count( $post['comments'] ) > 100 || count( $post['postmeta'] ) > 200 ) {
						return new WP_Error( 'calendarp_item_limit', __( 'An item exceeds 100 comments or 200 metadata fields. Split or simplify this export.', 'calendar-plus' ) );
					}
					if ( in_array( $post['post_type'], array( 'tribe_events', 'tribe_venue', 'tribe_organizer', 'attachment' ), true ) ) {
						$data['posts'][] = $post;
					}
				}
				if ( count( $data['posts'] ) > 1000 || count( $data['terms'] ) > 2000 || count( $data['authors'] ) > 1000 ) {
					return new WP_Error( 'calendarp_records', __( 'This export exceeds the import limit. Use exports with at most 1,000 related records.', 'calendar-plus' ) );
				}
			}
			// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( libxml_get_errors() || ! in_array( $data['version'], array( '1.0', '1.1', '1.2' ), true ) || ! $data['base_url'] ) {
				return $this->invalid();
			}
			return $data;
		} finally {
			$reader->close();
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
		}
	}

	/**
	 * Extract a single item without deserializing source metadata.
	 *
	 * @param SimpleXMLElement $node Item.
	 * @param SimpleXMLElement $wp WordPress fields.
	 * @param array            $namespaces XML namespaces.
	 * @return array Record.
	 */
	private function post( $node, $wp, $namespaces ) {
		$post = array(
			'post_title' => (string) $node->title,
			'guid'       => (string) $node->guid,
			'postmeta'   => array(),
			'terms'      => array(),
			'comments'   => array(),
		);
		foreach ( array( 'post_id', 'post_type', 'post_date', 'post_date_gmt', 'post_name', 'post_parent', 'menu_order', 'post_password', 'status', 'comment_status', 'ping_status', 'is_sticky', 'attachment_url' ) as $key ) {
			$post[ $key ] = (string) $wp->$key;
		}
		$dc                   = $node->children( 'http://purl.org/dc/elements/1.1/' );
		$content              = $node->children( 'http://purl.org/rss/1.0/modules/content/' );
		$excerpt              = $node->children( isset( $namespaces['excerpt'] ) ? $namespaces['excerpt'] : 'http://wordpress.org/export/1.2/excerpt/' );
		$post['post_author']  = (string) $dc->creator;
		$post['post_content'] = (string) $content->encoded;
		$post['post_excerpt'] = (string) $excerpt->encoded;
		foreach ( $node->category as $term ) {
			$attributes      = $term->attributes();
			$post['terms'][] = array(
				'taxonomy' => (string) $attributes['domain'],
				'slug'     => (string) $attributes['nicename'],
				'name'     => (string) $term,
			);
		}
		foreach ( $wp->postmeta as $meta ) {
			$key = (string) $meta->meta_key;
			if ( ! isset( $post['postmeta'][ $key ] ) ) {
				$post['postmeta'][ $key ] = array();
			}
			$post['postmeta'][ $key ][] = (string) $meta->meta_value;
		}
		foreach ( $wp->comment as $comment ) {
			$row = array();
			foreach ( array( 'comment_id', 'comment_author', 'comment_author_email', 'comment_author_IP', 'comment_author_url', 'comment_date', 'comment_date_gmt', 'comment_content', 'comment_approved', 'comment_type', 'comment_parent', 'comment_user_id' ) as $key ) {
				$row[ $key ] = (string) $comment->$key;
			}
			$post['comments'][] = $row;
		}
		return $post;
	}

	/**
	 * Report an invalid XML export.
	 *
	 * @return WP_Error Invalid XML error.
	 */
	private function invalid() {
		return new WP_Error( 'calendarp_wxr', __( 'Invalid or unsupported WordPress XML. DTDs, entities and malformed exports are not accepted.', 'calendar-plus' ) );
	}
}
