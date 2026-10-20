<?php
/**
 * Media handling for the Calendar Plus TEC importer.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Imports validated media and rewrites known WordPress media references. */
class Calendar_Plus_TEC_Media {
	/**
	 * Validate native URLs and reject non-public IPv4 ranges on foreign origins.
	 * The configured site origin retains WordPress's trusted same-origin policy.
	 *
	 * @param string $url Candidate HTTP media URL.
	 * @return bool Whether this URL can be requested.
	 */
	public static function allowed_url( $url ) {
		if ( ! wp_http_validate_url( $url ) ) {
			return false;
		}
		$parts = wp_parse_url( $url );
		$home  = wp_parse_url( home_url() );
		$port  = $parts['port'] ?? ( 'https' === $parts['scheme'] ? 443 : 80 );
		$local = $home['port'] ?? ( 'https' === $home['scheme'] ? 443 : 80 );
		if ( strtolower( $parts['host'] ) === strtolower( $home['host'] ) && $port === $local && $parts['scheme'] === $home['scheme'] ) {
			return true;
		}
		$ip = gethostbyname( trim( $parts['host'], '.' ) );
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return false;
		}
		$bytes = array_map( 'intval', explode( '.', $ip ) );
		// Additional IANA shared, documentation, benchmarking and multicast ranges.
		return ! (
			( 100 === $bytes[0] && $bytes[1] >= 64 && $bytes[1] <= 127 ) ||
			( 192 === $bytes[0] && 0 === $bytes[1] && in_array( $bytes[2], array( 0, 2 ), true ) ) ||
			( 192 === $bytes[0] && 88 === $bytes[1] && 99 === $bytes[2] ) ||
			( 198 === $bytes[0] && in_array( $bytes[1], array( 18, 19 ), true ) ) ||
			( 198 === $bytes[0] && 51 === $bytes[1] && 100 === $bytes[2] ) ||
			( 203 === $bytes[0] && 0 === $bytes[1] && 113 === $bytes[2] ) ||
			$bytes[0] >= 224
		);
	}

	/**
	 * Bound block parsing before recursively examining untrusted markup.
	 *
	 * @param string $content Source markup.
	 * @return true|WP_Error Whether this content is within the block budget.
	 */
	public static function validate_content( $content ) {
		if ( preg_match_all( '/<!--\s+wp:/', $content ) > 2000 ) {
			return new WP_Error( 'calendarp_blocks', __( 'Content exceeds the 2,000 block limit.', 'calendar-plus' ) );
		}
		$stack = array( array( parse_blocks( $content ), 0 ) );
		$count = 0;
		while ( $stack ) {
			$entry = array_pop( $stack );
			foreach ( $entry[0] as $block ) {
				if ( ++$count > 2000 || $entry[1] > 20 ) {
					return new WP_Error( 'calendarp_block_depth', __( 'Content exceeds the block count or nesting limit.', 'calendar-plus' ) );
				}
				if ( $block['innerBlocks'] ) {
					$stack[] = array( $block['innerBlocks'], $entry[1] + 1 );
				}
			}
		}
		return true;
	}

	/**
	 * Find media URLs in HTML attributes, without evaluating content.
	 *
	 * @param string $content Saved HTML.
	 * @return string[] URLs.
	 */
	public static function urls( $content ) {
		$urls      = array();
		$processor = new WP_HTML_Tag_Processor( $content );
		while ( $processor->next_tag() ) {
			foreach ( array( 'src', 'href', 'poster', 'data-src', 'srcset', 'data-srcset' ) as $attribute ) {
				$value = $processor->get_attribute( $attribute );
				if ( ! is_string( $value ) ) {
					continue;
				}
				if ( false !== strpos( $attribute, 'srcset' ) ) {
					foreach ( explode( ',', $value ) as $candidate ) {
						$parts  = preg_split( '/\s+/', trim( $candidate ) );
						$urls[] = $parts[0];
					}
				} else {
					$urls[] = $value;
				}
			}
		}
		self::block_urls( parse_blocks( $content ), $urls );
		return array_values( array_unique( $urls ) );
	}

	/**
	 * Identify remote media which could not be remapped successfully.
	 *
	 * @param string $content Content.
	 * @param array  $urls Successful URL mappings.
	 * @return int Unresolved media reference count.
	 */
	public static function unresolved( $content, $urls ) {
		$references = array();
		$tags       = new WP_HTML_Tag_Processor( $content );
		while ( $tags->next_tag() ) {
			if ( 'A' === $tags->get_tag() ) {
				$href = $tags->get_attribute( 'href' );
				if ( is_string( $href ) && wp_check_filetype( (string) wp_parse_url( $href, PHP_URL_PATH ) )['type'] ) {
					$references[] = $href;
				}
			}
			if ( ! in_array( $tags->get_tag(), array( 'IMG', 'VIDEO', 'AUDIO', 'SOURCE' ), true ) ) {
				continue;
			}
			foreach ( array( 'src', 'poster', 'data-src' ) as $attribute ) {
				$value = $tags->get_attribute( $attribute );
				if ( is_string( $value ) ) {
					$references[] = $value;
				}
			}
		}
		self::block_urls( parse_blocks( $content ), $references );
		$count = 0;
		foreach ( array_unique( $references ) as $url ) {
			if ( preg_match( '~^(?:https?:)?//~i', $url ) && ! isset( $urls[ $url ] ) && ! in_array( $url, $urls, true ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Collect core block URLs, including URLs without saved HTML.
	 *
	 * @param array $blocks Blocks.
	 * @param array $urls Collected URLs.
	 *
	 * @return mixed Calendar operation result.
	 */
	private static function block_urls( $blocks, &$urls ) {
		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], array( 'core/image', 'core/gallery', 'core/cover', 'core/media-text', 'core/file', 'core/audio', 'core/video' ), true ) ) {
				foreach ( array( 'url', 'href', 'mediaUrl', 'poster' ) as $attribute ) {
					if ( isset( $block['attrs'][ $attribute ] ) && is_string( $block['attrs'][ $attribute ] ) ) {
						$urls[] = $block['attrs'][ $attribute ];
					}
				}
			}
			self::block_urls( $block['innerBlocks'], $urls );
		}
	}

	/**
	 * Normalize a URL for lookup; also recognize WordPress resized filenames.
	 *
	 * @param string $url URL.
	 * @return string Lookup URL.
	 */
	public static function key( $url ) {
		$url = preg_replace( '/[?#].*$/', '', html_entity_decode( $url, ENT_QUOTES, 'UTF-8' ) );
		return preg_replace( '/-\d+x\d+(?=\.[a-z0-9]+$)/i', '', $url );
	}

	/**
	 * Recognize aliases explicitly supplied by an attachment's URL and GUID.
	 *
	 * @param array  $post Source attachment.
	 * @param string $source Source blog URL.
	 * @return string[] Exact normalized aliases; never guess another site's path.
	 */
	public static function source_keys( $post, $source ) {
		$keys = array( self::key( $post['attachment_url'] ) );
		$guid = $post['guid'] ?? '';
		if ( $guid && wp_parse_url( $guid, PHP_URL_HOST ) === wp_parse_url( $source, PHP_URL_HOST ) ) {
			$keys[] = self::key( $guid );
		}
		return array_unique( $keys );
	}

	/**
	 * Download one file using WordPress HTTP and media APIs.
	 *
	 * @param array  $post Source media record.
	 * @param string $identity Persistent source identity.
	 * @return int|WP_Error Attachment ID.
	 */
	public function import( $post, $identity ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error( 'calendarp_media_permission', __( 'You cannot upload media.', 'calendar-plus' ) );
		}
		$url   = esc_url_raw( $post['attachment_url'], array( 'http', 'https' ) );
		$parts = wp_parse_url( $url );
		if ( ! $url || ! $parts || isset( $parts['user'] ) || isset( $parts['pass'] ) || ! self::allowed_url( $url ) ) {
			return new WP_Error( 'calendarp_media_url', __( 'The media URL is not a permitted public HTTP URL.', 'calendar-plus' ) );
		}
		if ( is_multisite() && upload_is_user_over_quota( false ) ) {
			return new WP_Error( 'calendarp_quota', __( 'The site upload quota has been reached.', 'calendar-plus' ) );
		}
		$limit    = min( 5 * MB_IN_BYTES, wp_max_upload_size() );
		$filename = sanitize_file_name( wp_basename( $parts['path'] ?? '' ) );
		$type     = wp_check_filetype( $filename, get_allowed_mime_types() );
		if ( ! $type['type'] || $limit < 1 ) {
			return new WP_Error( 'calendarp_media_type', __( 'This media filename or type is not allowed.', 'calendar-plus' ) );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$temp = wp_tempnam( $filename );
		if ( ! $temp ) {
			return new WP_Error( 'calendarp_media_temp', __( 'WordPress could not prepare the media upload.', 'calendar-plus' ) );
		}
		try {
			$deadline = microtime( true ) + 10;
			for ( $redirects = 0; $redirects <= 3; ++$redirects ) {
				if ( ! self::allowed_url( $url ) ) {
					return new WP_Error( 'calendarp_media_url', __( 'The media URL is not a permitted public HTTP URL.', 'calendar-plus' ) );
				}
				$remaining = $deadline - microtime( true );
				if ( $remaining <= 0 ) {
					return new WP_Error( 'calendarp_media_timeout', __( 'The media download exceeded its time budget.', 'calendar-plus' ) );
				}
				$response = wp_safe_remote_get(
					$url,
					array(
						'stream'              => true,
						'filename'            => $temp,
						'limit_response_size' => $limit + 1,
						'redirection'         => 0,
					)
				);
				if ( is_wp_error( $response ) || ! in_array( wp_remote_retrieve_response_code( $response ), array( 301, 302, 303, 307, 308 ), true ) ) {
					break;
				}
				$location = wp_remote_retrieve_header( $response, 'location' );
				if ( 3 === $redirects || ! is_string( $location ) || ! $location ) {
					return new WP_Error( 'calendarp_media_redirect', __( 'The media redirect is missing or exceeds the redirect limit.', 'calendar-plus' ) );
				}
				$url = WP_Http::make_absolute_url( $location, $url );
			}
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return new WP_Error( 'calendarp_media_http', __( 'The source did not return the media file successfully.', 'calendar-plus' ) );
			}
			$size = wp_filesize( $temp );
			if ( ! $size || $size > $limit ) {
				return new WP_Error( 'calendarp_media_size', __( 'The media is empty or exceeds the 5 MB/site upload limit.', 'calendar-plus' ) );
			}
			$checked = wp_check_filetype_and_ext( $temp, $filename, get_allowed_mime_types() );
			if ( ! $checked['ext'] || ! $checked['type'] ) {
				return new WP_Error( 'calendarp_media_mime', __( 'The file does not match an allowed media type.', 'calendar-plus' ) );
			}
			if ( 0 === strpos( $checked['type'], 'image/' ) ) {
				$dimensions = wp_getimagesize( $temp );
				if ( ! $dimensions || $dimensions[0] * $dimensions[1] > 16000000 ) {
					return new WP_Error( 'calendarp_media_pixels', __( 'This image is invalid or exceeds 16 megapixels.', 'calendar-plus' ) );
				}
			}
			$post_id = media_handle_sideload(
				array(
					'name'     => $filename,
					'tmp_name' => $temp,
				),
				0,
				null,
				array(
					'post_author'  => absint( $post['destination_author'] ?? get_current_user_id() ),
					'post_title'   => sanitize_text_field( $post['post_title'] ),
					'post_content' => wp_kses_post( $post['post_content'] ),
					'post_excerpt' => wp_kses_post( $post['post_excerpt'] ),
					'meta_input'   => array( '_calendarp_tec_source' => $identity ),
				)
			);
			if ( ! is_wp_error( $post_id ) && ! empty( $post['postmeta']['_wp_attachment_image_alt'][0] ) ) {
				update_post_meta( $post_id, '_wp_attachment_image_alt', sanitize_text_field( $post['postmeta']['_wp_attachment_image_alt'][0] ) );
			}
			return $post_id;
		} finally {
			wp_delete_file( $temp );
		}
	}

	/**
	 * Rewrite HTML and known core-block media references using successful maps.
	 *
	 * @param string $content Original content.
	 * @param array  $urls Exact source URL to destination URL map.
	 * @param array  $ids Source attachment ID to destination ID map.
	 * @param bool   $preserve_ids Whether IDs already belong to the destination.
	 * @return string Rewritten content.
	 */
	public static function rewrite( $content, $urls, $ids, $preserve_ids = false ) {
		$blocks  = parse_blocks( $content );
		$content = serialize_blocks( self::blocks( $blocks, $urls, $ids, $preserve_ids ) );
		$tags    = new WP_HTML_Tag_Processor( $content );
		while ( $tags->next_tag() ) {
			foreach ( array( 'src', 'href', 'poster', 'data-src' ) as $attribute ) {
				$value = $tags->get_attribute( $attribute );
				if ( is_string( $value ) && isset( $urls[ $value ] ) ) {
					$tags->set_attribute( $attribute, $urls[ $value ] );
				}
			}
			foreach ( array( 'srcset', 'data-srcset' ) as $attribute ) {
				$value = $tags->get_attribute( $attribute );
				if ( ! is_string( $value ) ) {
					continue;
				}
				$candidates = explode( ',', $value );
				$changed    = false;
				foreach ( $candidates as &$candidate ) {
					$parts = preg_split( '/\s+/', trim( $candidate ), 2 );
					if ( isset( $urls[ $parts[0] ] ) ) {
						$candidate = $urls[ $parts[0] ] . ( isset( $parts[1] ) ? ' ' . $parts[1] : '' );
						$changed   = true;
					}
				}
				unset( $candidate );
				if ( $changed ) {
					// Source size descriptors cannot describe the new full-sized files.
					$tags->remove_attribute( $attribute );
					$tags->remove_attribute( 'sizes' );
				}
			}
			$class = $tags->get_attribute( 'class' );
			if ( is_string( $class ) && ! $preserve_ids ) {
				$tags->set_attribute(
					'class',
					preg_replace_callback(
						'/\bwp-image-(\d+)\b/',
						static function ( $matches ) use ( $ids ) {
							return isset( $ids[ $matches[1] ] ) ? 'wp-image-' . $ids[ $matches[1] ] : '';
						},
						$class
					)
				);
			}
		}
		if ( $preserve_ids ) {
			return $tags->get_updated_html();
		}
		return preg_replace_callback(
			'/\[gallery\b([^\]]*)\]/',
			static function ( $matches ) use ( $ids ) {
				return preg_replace_callback(
					'/\bids=([\'"])([\d,\s]+)\1/',
					static function ( $attribute ) use ( $ids ) {
						$mapped = array();
						foreach ( explode( ',', $attribute[2] ) as $id ) {
							if ( isset( $ids[ trim( $id ) ] ) ) {
								$mapped[] = $ids[ trim( $id ) ];
							}
						}
						return 'ids="' . ( $mapped ? implode( ',', $mapped ) : '0' ) . '"';
					},
					$matches[0]
				);
			},
			$tags->get_updated_html()
		);
	}

	/**
	 * Recursively map attributes of known core media blocks.
	 *
	 * @param array $blocks Parsed blocks.
	 * @param array $urls URL map.
	 * @param array $ids ID map.
	 * @param bool  $preserve_ids Whether IDs already belong to the destination.
	 * @return array Blocks.
	 */
	private static function blocks( $blocks, $urls, $ids, $preserve_ids = false ) {
		foreach ( $blocks as &$block ) {
			if ( $preserve_ids && 'core/image' === $block['blockName'] && ! isset( $block['attrs']['id'] ) ) {
				// A failed first transfer removed the foreign ID. Recover only an owned URL.
				$tags = new WP_HTML_Tag_Processor( $block['innerHTML'] );
				if ( $tags->next_tag( 'IMG' ) ) {
					$url = $tags->get_attribute( 'src' );
					$url = is_string( $url ) ? ( $urls[ $url ] ?? $url ) : '';
					if ( $url && in_array( $url, $urls, true ) ) {
						$id = attachment_url_to_postid( $url );
						if ( $id && in_array( $id, array_map( 'absint', $ids ), true ) && wp_attachment_is_image( $id ) && wp_get_attachment_url( $id ) === $url ) {
							$block['attrs']['id'] = $id;
							// Restore the native image class removed with the failed foreign ID.
							if ( 1 === count( $block['innerContent'] ) && is_string( $block['innerContent'][0] ) && ! $tags->has_class( 'wp-image-' . $id ) ) {
								$class = $tags->get_attribute( 'class' );
								if ( ! is_string( $class ) || ! preg_match( '/\bwp-image-\d+\b/', $class ) ) {
									$tags->add_class( 'wp-image-' . $id );
									$block['innerHTML']       = $tags->get_updated_html();
									$block['innerContent'][0] = $block['innerHTML'];
								}
							}
						}
					}
				}
			}
			if ( in_array( $block['blockName'], array( 'core/image', 'core/gallery', 'core/cover', 'core/media-text', 'core/file', 'core/audio', 'core/video' ), true ) ) {
				foreach ( $preserve_ids ? array() : array( 'id', 'mediaId' ) as $key ) {
					if ( isset( $block['attrs'][ $key ] ) ) {
						if ( is_scalar( $block['attrs'][ $key ] ) && isset( $ids[ $block['attrs'][ $key ] ] ) ) {
							$block['attrs'][ $key ] = $ids[ $block['attrs'][ $key ] ];
						} else {
							unset( $block['attrs'][ $key ] );
						}
					}
				}
				if ( ! $preserve_ids && isset( $block['attrs']['ids'] ) && is_array( $block['attrs']['ids'] ) ) {
					$mapped = array();
					foreach ( $block['attrs']['ids'] as $source_id ) {
						if ( is_scalar( $source_id ) && isset( $ids[ $source_id ] ) ) {
							$mapped[] = $ids[ $source_id ];
						}
					}
					$block['attrs']['ids'] = $mapped;
				}
				foreach ( array( 'url', 'href', 'mediaUrl', 'poster' ) as $key ) {
					if ( isset( $block['attrs'][ $key ] ) && is_string( $block['attrs'][ $key ] ) && isset( $urls[ $block['attrs'][ $key ] ] ) ) {
						$block['attrs'][ $key ] = $urls[ $block['attrs'][ $key ] ];
					}
				}
			}
			$block['innerBlocks'] = self::blocks( $block['innerBlocks'], $urls, $ids, $preserve_ids );
		}
		unset( $block );
		return $blocks;
	}
}
