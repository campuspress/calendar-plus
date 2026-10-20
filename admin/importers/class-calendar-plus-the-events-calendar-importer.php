<?php
/**
 * WordPress XML importer for The Events Calendar.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-calendar-plus-tec-wxr-reader.php';
require_once __DIR__ . '/class-calendar-plus-tec-media.php';
require_once __DIR__ . '/class-calendar-plus-tec-details.php';
require_once __DIR__ . '/class-calendar-plus-tec-preview.php';

/** Imports TEC records using resumable, site- and user-scoped jobs. */
class Calendar_Plus_The_Events_Calendar_Importer extends WP_Importer {
	/**
	 * Uploaded WXR attachment ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Check permissions on every entry point, including resumed requests.
	 *
	 * @return bool Whether the user can manage an import.
	 */
	public function can_import() {
		foreach ( array( 'import', 'manage_options', 'manage_calendar_plus', 'edit_others_calendar_events', 'publish_calendar_events', 'edit_others_calendar_locations', 'publish_calendar_locations', 'manage_calendar_event_terms' ) as $capability ) {
			if ( ! current_user_can( $capability ) ) {
				return false;
			}
		}
		return class_exists( 'WP_HTML_Tag_Processor' );
	}

	/**
	 * Handle dispatch.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function dispatch() {
		if ( ! $this->can_import() ) {
			wp_die( esc_html__( 'You cannot import Calendar Plus events, or this WordPress version is unsupported.', 'calendar-plus' ) );
		}
		$step = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 0;
		echo '<div class="wrap"><h1>' . esc_html__( 'Import The Events Calendar into Calendar Plus', 'calendar-plus' ) . '</h1>';
		if ( 1 === $step ) {
			check_admin_referer( 'import-upload' );
			$result = $this->upload_exports();
			if ( is_wp_error( $result ) ) {
				$this->notice( $result->get_error_message() );
			} else {
				$this->options( $result );
			}
		} elseif ( 2 === $step ) {
			check_admin_referer( 'calendarp-tec-start' );
			$this->id = isset( $_POST['import_id'] ) ? absint( $_POST['import_id'] ) : 0;
			$data     = $this->staged_data();
			if ( is_wp_error( $data ) ) {
				$this->notice( $data->get_error_message() );
			} else {
				$selection = $this->save_selection( $data );
				if ( is_wp_error( $selection ) ) {
					$this->notice( $selection->get_error_message() );
				} elseif ( empty( $_POST['commit_import'] ) ) {
					$preview_page = isset( $_POST['preview_page'] ) ? absint( $_POST['preview_page'] ) : 0;
					$this->options( $data, $preview_page );
				} else {
					$mapped = isset( $_POST['user_map'] ) && is_array( $_POST['user_map'] ) ? array_map( 'absint', wp_unslash( $_POST['user_map'] ) ) : array();
					$result = $this->start( $data, $mapped, ! empty( $_POST['fetch_attachments'] ), ! empty( $_POST['repair_media'] ), ! empty( $_POST['enrich_details'] ), $selection );
					if ( is_wp_error( $result ) ) {
						$this->notice( $result->get_error_message() );
					} else {
						$this->progress( $result, true );
					}
				}
			}
		} else {
			$job = get_option( $this->job_key() );
			if ( $job ) {
				$this->progress( $job );
			}
			if ( ! $job || $job['done'] ) {
				Calendar_Plus_TEC_Preview::upload_form();
			}
		}
		echo '</div>';
	}

	/**
	 * Stage WordPress-managed uploads; no event or media imports occur here.
	 *
	 * @return array|WP_Error Combined data.
	 */
	private function upload_exports() {
		check_admin_referer( 'import-upload' );
		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error( 'calendarp_upload', __( 'You cannot upload an export file.', 'calendar-plus' ) );
		}
		$uploads  = array();
		$original = $_FILES;
		try {
			foreach ( array( 'import', 'venues_file', 'organisers_file' ) as $field ) {
				if ( empty( $original[ $field ] ) ) {
					continue;
				}
				if ( ! is_array( $original[ $field ] ) ) {
					return new WP_Error( 'calendarp_upload', __( 'An upload field is malformed.', 'calendar-plus' ) );
				}
				if ( isset( $original[ $field ]['error'] ) && UPLOAD_ERR_NO_FILE === (int) $original[ $field ]['error'] ) {
					continue;
				}
				foreach ( array( 'name', 'type', 'tmp_name', 'error', 'size' ) as $attribute ) {
					if ( ! isset( $original[ $field ][ $attribute ] ) || ! is_scalar( $original[ $field ][ $attribute ] ) ) {
						return new WP_Error( 'calendarp_upload', __( 'An upload field is malformed.', 'calendar-plus' ) );
					}
				}
				// WordPress owns validation and storage; its importer API expects this field name.
				$_FILES['import'] = $original[ $field ];
				$upload           = wp_import_handle_upload();
				if ( isset( $upload['error'] ) ) {
					return new WP_Error( 'calendarp_upload', $upload['error'] );
				}
				$uploads[] = absint( $upload['id'] );
				update_post_meta( $upload['id'], '_calendarp_tec_upload_owner', get_current_user_id() );
			}
			$this->id = $uploads[0] ?? 0;
			$reader   = new Calendar_Plus_TEC_WXR_Reader();
			$data     = $reader->parse_files( array_map( 'get_attached_file', $uploads ) );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			$events = array_filter(
				$data['posts'],
				static function ( $post ) {
					return 'tribe_events' === $post['post_type'];
				}
			);
			if ( ! $events ) {
				return new WP_Error( 'calendarp_empty', __( 'Include the events export as well as its companion files.', 'calendar-plus' ) );
			}
			$selected  = array();
			$locations = array();
			foreach ( $events as $event ) {
				$venue  = absint( $this->meta( $event, '_EventVenueID' ) );
				$chosen = $venue ? $this->location_choice( $data['base_url'], $venue ) : 0;
				if ( $chosen ) {
					$locations[ $venue ] = $chosen;
				}
				if ( ! is_wp_error( $this->preview_schedule( $event ) ) ) {
					$selected[] = absint( $event['post_id'] );
				}
			}
			update_post_meta( $this->id, '_calendarp_tec_uploads', $uploads );
			update_post_meta(
				$this->id,
				'_calendarp_tec_plan',
				array(
					'expires'   => time() + HOUR_IN_SECONDS,
					'hash'      => hash( 'sha256', wp_json_encode( $data ) ),
					'events'    => $selected,
					'locations' => $locations,
				)
			);
			return $data;
		} finally {
			$_FILES = $original;
			if ( ! $this->id || ! get_post_meta( $this->id, '_calendarp_tec_plan', true ) ) {
				foreach ( $uploads as $upload_id ) {
					wp_import_cleanup( $upload_id );
				}
			}
		}
	}

	/**
	 * Reparse only attachments owned by the current user and verify the plan.
	 *
	 * @return array|WP_Error Validated source.
	 */
	private function staged_data() {
		$plan    = get_post_meta( $this->id, '_calendarp_tec_plan', true );
		$uploads = get_post_meta( $this->id, '_calendarp_tec_uploads', true );
		if ( ! is_array( $plan ) || $plan['expires'] < time() || ! is_array( $uploads ) || count( $uploads ) > 3 ) {
			return new WP_Error( 'calendarp_plan', __( 'This preview has expired. Upload the exports again.', 'calendar-plus' ) );
		}
		foreach ( $uploads as $upload_id ) {
			if ( 'attachment' !== get_post_type( $upload_id ) || (int) get_post_meta( $upload_id, '_calendarp_tec_upload_owner', true ) !== get_current_user_id() ) {
				return new WP_Error( 'calendarp_owner', __( 'This import file does not belong to you.', 'calendar-plus' ) );
			}
		}
		$reader = new Calendar_Plus_TEC_WXR_Reader();
		$data   = $reader->parse_files( array_map( 'get_attached_file', $uploads ) );
		if ( ! is_wp_error( $data ) && ! hash_equals( $plan['hash'], hash( 'sha256', wp_json_encode( $data ) ) ) ) {
			return new WP_Error( 'calendarp_changed', __( 'The source files changed after preview. Upload them again.', 'calendar-plus' ) );
		}
		return $data;
	}

	/**
	 * Save selections only for the server-rendered page and valid Locations.
	 *
	 * @param array $data Parsed source.
	 * @return array|WP_Error Selected events and reviewed Location choices.
	 */
	private function save_selection( $data ) {
		check_admin_referer( 'calendarp-tec-start' );
		$plan   = get_post_meta( $this->id, '_calendarp_tec_plan', true );
		$events = array_values(
			array_filter(
				$data['posts'],
				static function ( $post ) {
					return 'tribe_events' === $post['post_type'];
				}
			)
		);
		$page   = isset( $_POST['current_page'] ) ? absint( $_POST['current_page'] ) : 0;
		$shown  = array_slice( $events, $page * 20, 20 );
		$ids    = isset( $_POST['event_ids'] ) && is_array( $_POST['event_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['event_ids'] ) ) : array();
		foreach ( $shown as $event ) {
			$id             = absint( $event['post_id'] );
			$plan['events'] = array_values( array_diff( $plan['events'], array( $id ) ) );
			if ( in_array( $id, $ids, true ) && ! is_wp_error( $this->preview_schedule( $event ) ) ) {
				$plan['events'][] = $id;
			}
		}
		$locations = isset( $_POST['location_map'] ) && is_array( $_POST['location_map'] ) ? array_map( 'absint', wp_unslash( $_POST['location_map'] ) ) : array();
		foreach ( $shown as $event ) {
			$venue = absint( $this->meta( $event, '_EventVenueID' ) );
			if ( $venue && isset( $locations[ $venue ] ) ) {
				$id = $locations[ $venue ];
				if ( $id && ( 'calendar_location' !== get_post_type( $id ) || 'trash' === get_post_status( $id ) || ! current_user_can( 'edit_post', $id ) || ( in_array( $event['status'], array( 'publish', 'future' ), true ) && 'publish' !== get_post_status( $id ) ) ) ) {
					return new WP_Error( 'calendarp_location', __( 'A selected Location is unavailable or cannot be edited.', 'calendar-plus' ) );
				}
				$plan['locations'][ $venue ] = $id;
			}
		}
		$plan['options'] = array(
			'fetch'  => ! empty( $_POST['fetch_attachments'] ),
			'repair' => ! empty( $_POST['repair_media'] ),
			'enrich' => ! empty( $_POST['enrich_details'] ),
		);
		$plan['authors'] = isset( $_POST['user_map'] ) && is_array( $_POST['user_map'] ) ? array_map( 'absint', wp_unslash( $_POST['user_map'] ) ) : array();
		update_post_meta( $this->id, '_calendarp_tec_plan', $plan );
		return $plan;
	}

	/**
	 * Parse an export without executing or deserializing its contents.
	 *
	 * @param string $file WordPress-managed filename.
	 * @return array|WP_Error Parsed data.
	 */
	public function parse( $file ) {
		$reader = new Calendar_Plus_TEC_WXR_Reader();
		return $reader->parse( $file );
	}

	/**
	 * Render author and media choices.
	 *
	 * @param array $data Export data.
	 * @param int   $preview_page Requested preview page.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function options( $data, $preview_page = 0 ) {
		$plan    = get_post_meta( $this->id, '_calendarp_tec_plan', true );
		$choices = $plan['options'] ?? array(
			'fetch'  => true,
			'repair' => false,
			'enrich' => true,
		);
		echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?import=calendar-plus-the-events-calendar&step=2' ) ) . '">';
		wp_nonce_field( 'calendarp-tec-start' );
		echo '<input type="hidden" name="import_id" value="' . esc_attr( $this->id ) . '">';
		echo '<p>' . esc_html__( 'Authors default to your account. You can assign them to existing members of this site. No accounts or roles will be created.', 'calendar-plus' ) . '</p>';
		$users = get_users(
			array(
				'number'  => 100,
				'fields'  => array( 'ID', 'display_name' ),
				'blog_id' => get_current_blog_id(),
			)
		);
		foreach ( array_slice( array_values( $data['authors'] ), 0, 100 ) as $index => $author ) {
			$name = 'calendarp-tec-author-' . $index;
			echo '<p><label for="' . esc_attr( $name ) . '">' . esc_html( $author['author_display_name'] ) . '</label> ';
			echo '<select id="' . esc_attr( $name ) . '" name="user_map[' . esc_attr( $index ) . ']"><option value="0">' . esc_html__( 'Use my account', 'calendar-plus' ) . '</option>';
			foreach ( $users as $user ) {
				echo '<option value="' . esc_attr( $user->ID ) . '"' . selected( $plan['authors'][ $index ] ?? 0, $user->ID, false ) . '>' . esc_html( $user->display_name ) . '</option>';
			}
			echo '</select></p>';
		}
		echo '<p><label><input type="checkbox" name="fetch_attachments" value="1"' . checked( $choices['fetch'], true, false ) . '> ' . esc_html__( 'Download and import related media (maximum 5 MB per file).', 'calendar-plus' ) . '</label></p>';
		echo '<p><label><input type="checkbox" name="repair_media" value="1"' . checked( $choices['repair'], true, false ) . '> ' . esc_html__( 'Repair media on existing imported events only. No new events, locations or terms will be created. Legacy matches must have a unique title/date and unchanged content; event details will be preserved.', 'calendar-plus' ) . '</label></p>';
		echo '<p><label><input type="checkbox" name="enrich_details" value="1"' . checked( $choices['enrich'], true, false ) . '> ' . esc_html__( 'Update missing Locations, tags and imported details on previously imported events. Preserve schedules and local edits.', 'calendar-plus' ) . '</label></p>';
		Calendar_Plus_TEC_Preview::render( $this, $data, $preview_page );
		submit_button( __( 'Start import', 'calendar-plus' ), 'primary', 'commit_import' );
		echo '</form>';
	}

	/**
	 * Start a bounded job; persist small queue chunks outside autoloaded options.
	 *
	 * @param array $data Parsed export.
	 * @param array $authors Author selection by export author index.
	 * @param bool  $fetch Whether to download media.
	 * @param bool  $repair Whether to repair unique legacy matches.
	 * @param bool  $enrich Whether to enrich previously imported events.
	 * @param array $selection Optional reviewed event and Location selection.
	 * @return array|WP_Error Job header.
	 */
	public function start( $data, $authors = array(), $fetch = true, $repair = false, $enrich = false, $selection = array() ) {
		if ( ! $this->can_import() || ( $fetch && ! current_user_can( 'upload_files' ) ) ) {
			return new WP_Error( 'calendarp_import_permission', __( 'You cannot start this import.', 'calendar-plus' ) );
		}
		$old = get_option( $this->job_key() );
		if ( $old && ! $old['done'] ) {
			return new WP_Error( 'calendarp_running', __( 'Resume or cancel your existing import before starting another.', 'calendar-plus' ) );
		}
		$job = array(
			'token'         => wp_generate_uuid4(),
			'owner'         => get_current_user_id(),
			'upload'        => $this->id,
			'uploads'       => get_post_meta( $this->id, '_calendarp_tec_uploads', true ) ? get_post_meta( $this->id, '_calendarp_tec_uploads', true ) : array( $this->id ),
			'enrich'        => $enrich && ! $repair,
			'selection'     => $selection,
			'updated'       => 0,
			'related'       => array(),
			'source'        => untrailingslashit( $data['base_url'] ),
			'cursor'        => 0,
			'total'         => 0,
			'done'          => false,
			'fetch'         => $fetch,
			'repair'        => $repair,
			'authors'       => array(),
			'posts'         => array(),
			'media'         => array(),
			'urls'          => array(),
			'created'       => 0,
			'reused'        => 0,
			'failed'        => 0,
			'warnings'      => array(),
			'warning_count' => 0,
		);
		foreach ( array_values( $data['authors'] ) as $index => $author ) {
			$user_id = ! empty( $authors[ $index ] ) ? absint( $authors[ $index ] ) : get_current_user_id();
			if ( ! get_userdata( $user_id ) || ( is_multisite() && ! is_user_member_of_blog( $user_id ) ) ) {
				return new WP_Error( 'calendarp_author', __( 'An author selection is not a member of this site.', 'calendar-plus' ) );
			}
			$job['authors'][ $author['author_login'] ] = $user_id;
		}
		$queue = $this->queue( $data, $job );
		if ( is_wp_error( $queue ) ) {
			return $queue;
		}
		$job['total'] = count( $queue );
		if ( ! $job['total'] ) {
			return new WP_Error( 'calendarp_empty', __( 'No TEC events were found in this export.', 'calendar-plus' ) );
		}
		if ( count( $queue ) > 4000 ) {
			return new WP_Error( 'calendarp_tasks', __( 'Too many related records. Split the export before importing.', 'calendar-plus' ) );
		}
		if ( ! add_option(
			'calendarp_tec_active_job',
			array(
				'owner' => $job['owner'],
				'token' => $job['token'],
			),
			'',
			false
		) ) {
			return new WP_Error( 'calendarp_site_running', __( 'Another import is active on this site. Its owner must resume or cancel it first.', 'calendar-plus' ) );
		}
		$chunks = array_chunk( $queue, 5 );
		foreach ( $chunks as $index => $chunk ) {
			if ( ! add_option( $this->chunk_key( $job, $index ), $chunk, '', false ) ) {
				foreach ( array_keys( $chunks ) as $cleanup_index ) {
					delete_option( $this->chunk_key( $job, $cleanup_index ) );
				}
				$this->release_site_lock( $job );
				return new WP_Error( 'calendarp_queue', __( 'The import queue could not be saved.', 'calendar-plus' ) );
			}
		}
		if ( ! update_option( $this->job_key(), $job, false ) ) {
			foreach ( array_keys( $chunks ) as $index ) {
				delete_option( $this->chunk_key( $job, $index ) );
			}
			$this->release_site_lock( $job );
			return new WP_Error( 'calendarp_job_save', __( 'The import job could not be saved. Upload the export again.', 'calendar-plus' ) );
		}
		return $job;
	}

	/**
	 * Build a dependency-ordered queue and include referenced/shared attachments.
	 *
	 * @param array $data Parsed export.
	 * @param array $job Job header, receiving initial warnings.
	 * @return array|WP_Error Tasks.
	 */
	private function queue( $data, &$job ) {
		$events      = array();
		$venues      = array();
		$attachments = array();
		$organizers  = array();
		$urls        = array();
		$ids         = array();
		foreach ( $data['posts'] as $post ) {
			$valid_content = Calendar_Plus_TEC_Media::validate_content( $post['post_content'] . ' ' . $post['post_excerpt'] );
			if ( is_wp_error( $valid_content ) ) {
				return $valid_content;
			}
			$id = absint( $post['post_id'] );
			if ( ! $id || isset( $ids[ $id ] ) ) {
				return new WP_Error( 'calendarp_ids', __( 'The export contains missing or duplicate record IDs.', 'calendar-plus' ) );
			}
			$ids[ $id ] = true;
			if ( 'tribe_events' === $post['post_type'] ) {
				$events[ $id ] = $post;
			} elseif ( 'tribe_venue' === $post['post_type'] ) {
				$venues[ $id ] = $post;
			} elseif ( 'tribe_organizer' === $post['post_type'] ) {
				$organizers[ $id ] = $post;
			} else {
				$post['attachment_url'] = $post['attachment_url'] ? $post['attachment_url'] : $post['guid'];
				$attachments[ $id ]     = $post;
				foreach ( Calendar_Plus_TEC_Media::source_keys( $post, $job['source'] ) as $key ) {
					$urls[ $key ] = $id;
				}
			}
		}
		if ( ! $events ) {
			return array();
		}
		if ( isset( $job['selection']['events'] ) ) {
			$events = array_intersect_key( $events, array_flip( $job['selection']['events'] ) );
		}
		if ( $job['repair'] ) {
			foreach ( $events as $id => $event ) {
				$match = $this->existing( $this->identity( $job, 'event', $id ), 'calendar_event' );
				if ( ! $match ) {
					$match = $this->legacy( $event );
				}
				if ( ! $match || is_wp_error( $match ) ) {
					unset( $events[ $id ] );
				}
			}
		}
		if ( ! $events ) {
			return new WP_Error( 'calendarp_no_matches', __( 'No selected events can be imported or repaired. Review the preview and import mode.', 'calendar-plus' ) );
		}
		$records        = array_column( $data['posts'], null, 'post_id' );
		$needed         = array();
		$public_venues  = array();
		$allowed_venues = array();
		foreach ( $events as $event ) {
			$venue_id = absint( $this->meta( $event, '_EventVenueID' ) );
			$needed[] = $venue_id;
			if ( in_array( $event['status'], array( 'publish', 'future' ), true ) ) {
				$public_venues[ $venue_id ] = true;
			}
			$prepared = Calendar_Plus_TEC_Preview::prepare( $event, $records );
			if ( $prepared['venue_record'] ) {
				$allowed_venues[] = $venue_id;
			}
		}
		$venues = array_intersect_key( $venues, array_flip( $allowed_venues ) );
		foreach ( $job['selection']['locations'] ?? array() as $source_id => $chosen ) {
			if ( $chosen && in_array( (int) $source_id, $needed, true ) ) {
				if ( 'calendar_location' !== get_post_type( $chosen ) || 'trash' === get_post_status( $chosen ) || ! current_user_can( 'edit_post', $chosen ) || ( isset( $public_venues[ $source_id ] ) && 'publish' !== get_post_status( $chosen ) ) ) {
					return new WP_Error( 'calendarp_location', __( 'A selected Location is unavailable or cannot be edited.', 'calendar-plus' ) );
				}
				$job['posts'][ $source_id ] = $chosen;
			}
		}
		if ( ! $job['repair'] ) {
			foreach ( array_unique( $needed ) as $source_id ) {
				$mapped = $this->location_choice( $job['source'], $source_id );
				if ( $mapped && ! isset( $job['posts'][ $source_id ] ) && ! array_key_exists( $source_id, $job['selection']['locations'] ?? array() ) ) {
					if ( isset( $public_venues[ $source_id ] ) && 'publish' !== get_post_status( $mapped ) ) {
						return new WP_Error( 'calendarp_location_privacy', __( 'A published event cannot be mapped to a non-public Location. Review the Location choice.', 'calendar-plus' ) );
					}
					$job['posts'][ $source_id ] = $mapped;
				}
			}
		}
		$selected = array();
		$owners   = $job['repair'] ? $events : $events + $venues;
		if ( ! $job['repair'] ) {
			foreach ( $events as $event ) {
				$prepared = Calendar_Plus_TEC_Preview::prepare( $event, $records );
				foreach ( $prepared['organizer_records'] as $organizer ) {
					$owners[ $organizer['post_id'] ] = $organizer;
				}
			}
		}
		foreach ( $attachments as $id => $post ) {
			if ( isset( $owners[ absint( $post['post_parent'] ) ] ) ) {
				$selected[ $id ] = $post;
			}
		}
		$references = array();
		foreach ( $owners as $post ) {
			$thumbnail = absint( $this->meta( $post, '_thumbnail_id' ) );
			if ( $thumbnail && isset( $attachments[ $thumbnail ] ) ) {
				$selected[ $thumbnail ] = $attachments[ $thumbnail ];
			} elseif ( $thumbnail ) {
				$this->warning( $job, __( 'A featured image record is missing from the export.', 'calendar-plus' ) );
			}
			$content = $post['post_content'] . ' ' . $post['post_excerpt'];
			preg_match_all( '/(?:wp-image-|"(?:id|mediaId)"\s*:\s*)(\d+)/', $content, $matches );
			preg_match_all( '/"ids"\s*:\s*\[([\d,\s]+)\]|\[gallery[^\]]*\bids=[\x22\x27]([\d,\s]+)[\x22\x27]/', $content, $groups );
			foreach ( array_merge( $groups[1], $groups[2] ) as $group ) {
				$matches[1] = array_merge( $matches[1], array_map( 'trim', explode( ',', $group ) ) );
			}
			foreach ( $matches[1] as $id ) {
				if ( isset( $attachments[ $id ] ) ) {
					$selected[ $id ] = $attachments[ $id ];
				}
			}
			foreach ( Calendar_Plus_TEC_Media::urls( $content ) as $url ) {
				$resolved = $this->resolve_url( $url, $job['source'] );
				$key      = Calendar_Plus_TEC_Media::key( $resolved );
				if ( isset( $urls[ $key ] ) ) {
					$id                 = $urls[ $key ];
					$selected[ $id ]    = $attachments[ $id ];
					$references[ $url ] = $id;
				} elseif ( $this->source_image( $resolved, $job['source'] ) ) {
					$id                 = 'url-' . hash( 'sha256', $key );
					$selected[ $id ]    = array(
						'post_id'        => $id,
						'post_parent'    => 0,
						'post_title'     => wp_basename( wp_parse_url( $resolved, PHP_URL_PATH ) ),
						'post_content'   => '',
						'post_excerpt'   => '',
						'postmeta'       => array(),
						'attachment_url' => $resolved,
					);
					$references[ $url ] = $id;
				}
				if ( count( $selected ) > 1000 ) {
					return new WP_Error( 'calendarp_media_limit', __( 'Too many related media files. Split the export before importing.', 'calendar-plus' ) );
				}
			}
		}
		$aliases_by_id = array();
		foreach ( $references as $url => $source_id ) {
			$aliases_by_id[ $source_id ][] = $url;
		}
		$queue = $job['repair'] ? array() : $this->terms( $this->required_terms( $data['terms'], $events ), $job );
		if ( ! $job['repair'] ) {
			foreach ( $job['selection']['locations'] ?? array() as $source_id => $chosen ) {
				if ( in_array( (int) $source_id, $needed, true ) && (int) get_option( 'calendarp_tec_location_' . $this->identity( $job, 'location', $source_id ) ) !== (int) $chosen ) {
					$queue[] = array(
						'type' => 'location_link',
						'data' => array(
							'post_id'        => $source_id,
							'destination_id' => $chosen,
							'public_event'   => isset( $public_venues[ $source_id ] ),
						),
					);
				}
			}
		}
		$related = array();
		if ( ! $job['repair'] ) {
			foreach ( $events as $event ) {
				$prepared = Calendar_Plus_TEC_Preview::prepare( $event, $records );
				foreach ( array_merge( $prepared['venue_record'] ? array( $prepared['venue_record'] ) : array(), $prepared['organizer_records'] ) as $record ) {
					$related[ $record['post_id'] ] = $record;
				}
			}
		}
		foreach ( $related as $source_id => $record ) {
			$job['related'][] = $source_id;
			$queue[]          = array(
				'type' => 'related',
				'data' => $record,
			);
		}
		foreach ( $selected as $id => $post ) {
			$aliases = array_merge( array( $post['attachment_url'] ), Calendar_Plus_TEC_Media::source_keys( $post, $job['source'] ), $aliases_by_id[ $id ] ?? array() );
			$queue[] = array(
				'type'    => 'media',
				'data'    => $post,
				'aliases' => array_unique( $aliases ),
			);
		}
		foreach ( $job['repair'] ? array() : $venues as $post ) {
			$chosen = $job['posts'][ $post['post_id'] ] ?? 0;
			if ( $chosen ) {
				if ( 'calendar_location' !== get_post_type( $chosen ) || 'trash' === get_post_status( $chosen ) || ! current_user_can( 'edit_post', $chosen ) ) {
					return new WP_Error( 'calendarp_location', __( 'A selected Location is unavailable or cannot be edited.', 'calendar-plus' ) );
				}
				$job['posts'][ $post['post_id'] ] = $chosen;
				continue;
			}
			$queue[] = array(
				'type' => 'location',
				'data' => $post,
			);
		}
		foreach ( $events as $post ) {
			$post               = Calendar_Plus_TEC_Preview::prepare( $post, $records );
			$post['organizers'] = array();
			foreach ( Calendar_Plus_TEC_Preview::issues( $post, $records ) as $issue ) {
				$this->warning( $job, $post['post_title'] . ': ' . $issue );
			}
			foreach ( array_unique( $post['postmeta']['_EventOrganizerID'] ?? array() ) as $organizer_id ) {
				if ( isset( $organizers[ $organizer_id ] ) ) {
					$organizer = $organizers[ $organizer_id ];
					if ( ! in_array( $organizer['status'], array( 'publish', $post['status'] ), true ) ) {
						continue;
					}

					$post['organizers'][] = array(
						'name' => sanitize_text_field( $organizer['post_title'] ),
						'meta' => $this->details( $organizer ),
					);
				}
			}
			if ( ! $job['repair'] ) {
				$post['dependency_ids'] = array_column( $post['organizer_records'], 'post_id' );
				if ( $post['venue_record'] ) {
					$post['dependency_ids'][] = $post['venue_record']['post_id'];
				}
				unset( $post['organizers'] );
			}
			unset( $post['organizer_records'], $post['venue_record'] );
			$queue[] = array(
				'type' => 'event',
				'data' => $post,
			);
		}
		foreach ( $selected as $post ) {
			if ( ! empty( $post['post_parent'] ) ) {
				$queue[] = array(
					'type' => 'parent',
					'data' => $post,
				);
			}
		}
		return $queue;
	}

	/**
	 * Keep selected event terms and their ancestors within the source budget.
	 *
	 * @param array $terms Source term definitions.
	 * @param array $events Selected source events.
	 * @return array Required definitions.
	 */
	private function required_terms( $terms, $events ) {
		$definitions = array();
		foreach ( $terms as $term ) {
			$taxonomy = $this->taxonomy( $term['taxonomy'] );
			if ( $taxonomy ) {
				$definitions[ $taxonomy . ':' . $term['slug'] ] = $term;
			}
		}
		$pending = array();
		foreach ( $events as $event ) {
			foreach ( $event['terms'] as $term ) {
				$pending[] = $this->taxonomy( $term['taxonomy'] ) . ':' . $term['slug'];
			}
		}
		$required = array();
		while ( $pending ) {
			$key = array_pop( $pending );
			if ( isset( $required[ $key ] ) || ! isset( $definitions[ $key ] ) ) {
				continue;
			}
			$term             = $definitions[ $key ];
			$required[ $key ] = $term;
			if ( $term['parent'] ) {
				$pending[] = $this->taxonomy( $term['taxonomy'] ) . ':' . $term['parent'];
			}
		}
		return array_values( $required );
	}

	/**
	 * Sort category parents ahead of children, preserving descriptions and tags.
	 *
	 * @param array $terms Source terms.
	 * @param array $job Job header.
	 * @return array Tasks.
	 */
	private function terms( $terms, &$job ) {
		$pending = array();
		foreach ( $terms as $term ) {
			$taxonomy = $this->taxonomy( $term['taxonomy'] );
			if ( $taxonomy ) {
				$term['taxonomy']                           = $taxonomy;
				$pending[ $taxonomy . ':' . $term['slug'] ] = $term;
			}
		}
		$tasks = array();
		while ( $pending ) {
			$progress = false;
			foreach ( $pending as $key => $term ) {
				if ( $term['parent'] && isset( $pending[ $term['taxonomy'] . ':' . $term['parent'] ] ) ) {
					continue;
				}
				$tasks[]  = array(
					'type' => 'term',
					'data' => $term,
				);
				$progress = true;
				unset( $pending[ $key ] );
			}
			if ( ! $progress ) {
				$this->warning( $job, __( 'Cyclic category parents were skipped.', 'calendar-plus' ) );
				break;
			}
		}
		return $tasks;
	}

	/**
	 * Handle ajax batch.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function ajax_batch() {
		check_ajax_referer( 'calendarp-tec-batch', 'nonce' );
		$token  = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$result = ! empty( $_POST['cancel'] ) ? $this->cancel( $token ) : $this->run_batch( $token );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		wp_send_json_success( $this->summary( $result ) );
	}

	/**
	 * Process at most five tasks and one media file in a request.
	 *
	 * @param string $token Job token.
	 * @return array|WP_Error Updated job.
	 */
	public function run_batch( $token ) {
		$job = $this->authorized_job( $token );
		if ( is_wp_error( $job ) || $job['done'] ) {
			return $job;
		}
		$lock = $this->job_key() . '_lock';
		if ( get_option( $lock ) && get_option( $lock ) < time() - 300 ) {
			delete_option( $lock );
		}
		if ( ! add_option( $lock, time(), '', false ) ) {
			return new WP_Error( 'calendarp_locked', __( 'An import request is already running. Wait for it to finish.', 'calendar-plus' ) );
		}
		try {
			$started = microtime( true );
			$media   = 0;
			for ( $count = 0; $count < 5 && $job['cursor'] < $job['total']; ++$count ) {
				$chunk_index = (int) floor( $job['cursor'] / 5 );
				$chunk       = get_option( $this->chunk_key( $job, $chunk_index ) );
				$task        = $chunk[ $job['cursor'] % 5 ] ?? null;
				if ( ! $task ) {
					return new WP_Error( 'calendarp_queue_missing', __( 'The saved import queue is missing. Cancel and upload the export again.', 'calendar-plus' ) );
				}
				if ( 'media' === $task['type'] && $media ) {
					break;
				}
				$media += 'media' === $task['type'] ? 1 : 0;
				$result = $this->task( $task, $job );
				if ( is_wp_error( $result ) ) {
					++$job['failed'];
					$this->warning( $job, sanitize_text_field( $task['data']['post_title'] ?? $task['type'] ) . ': ' . $result->get_error_message() );
				}
				++$job['cursor'];
				if ( ! update_option( $this->job_key(), $job, false ) ) {
					return new WP_Error( 'calendarp_checkpoint', __( 'Progress could not be saved. Retry the import.', 'calendar-plus' ) );
				}
				if ( 0 === $job['cursor'] % 5 || $job['cursor'] === $job['total'] ) {
					delete_option( $this->chunk_key( $job, $chunk_index ) );
				}
				if ( microtime( true ) - $started >= 8 ) {
					break;
				}
			}
			if ( $job['cursor'] === $job['total'] ) {
				$job['done'] = true;
				$this->release_site_lock( $job );
				if ( $job['upload'] ) {
					foreach ( $job['uploads'] ?? array( $job['upload'] ) as $upload_id ) {
						wp_import_cleanup( $upload_id );
					}
				}
				$this->cleanup_related( $job );
				$job['posts']   = array();
				$job['media']   = array();
				$job['urls']    = array();
				$job['authors'] = array();
				update_option( $this->job_key(), $job, false );
			}
			return $job;
		} finally {
			delete_option( $lock );
		}
	}

	/**
	 * Perform one task; WordPress APIs own all writes and invalidation.
	 *
	 * @param array $task Task.
	 * @param array $job Job header.
	 * @return true|WP_Error Result.
	 */
	private function task( $task, &$job ) {
		$post = $task['data'];
		if ( 'related' === $task['type'] ) {
			update_option( 'calendarp_tec_related_' . $job['token'] . '_' . $post['post_id'], $post, false );
			return true;
		}
		if ( 'event' === $task['type'] && ! $job['repair'] ) {
			$records = array();
			foreach ( $post['dependency_ids'] ?? array() as $source_id ) {
				$record = get_option( 'calendarp_tec_related_' . $job['token'] . '_' . $source_id );
				if ( ! is_array( $record ) ) {
					return new WP_Error( 'calendarp_related', __( 'A saved dependency is unavailable. Cancel and upload the exports again.', 'calendar-plus' ) );
				}
				$records[ $source_id ] = $record;
			}
			unset( $post['dependency_ids'] );
			$post               = Calendar_Plus_TEC_Preview::prepare( $post, $records );
			$post['organizers'] = array();
			foreach ( $post['organizer_records'] as $organizer ) {
				$post['organizers'][] = array(
					'name' => sanitize_text_field( $organizer['post_title'] ),
					'meta' => $this->details( $organizer ),
				);
			}
		}
		if ( 'location_link' === $task['type'] ) {
			$id = absint( $post['destination_id'] );
			if ( ! $id ) {
				delete_option( 'calendarp_tec_location_' . $this->identity( $job, 'location', $post['post_id'] ) );
				return true;
			}
			if ( 'calendar_location' !== get_post_type( $id ) || 'trash' === get_post_status( $id ) || ! current_user_can( 'edit_post', $id ) || ( ! empty( $post['public_event'] ) && 'publish' !== get_post_status( $id ) ) ) {
				unset( $job['posts'][ $post['post_id'] ] );
				return new WP_Error( 'calendarp_location', __( 'A selected Location is unavailable or cannot be edited.', 'calendar-plus' ) );
			}
			update_option( 'calendarp_tec_location_' . $this->identity( $job, 'location', $post['post_id'] ), $id, false );
			return true;
		}
		if ( 'term' === $task['type'] ) {
			return $this->term( $post );
		}
		if ( 'media' === $task['type'] ) {
			if ( ! $job['fetch'] ) {
				return true;
			}
			$identity = $this->identity( $job, 'media', $post['post_id'] );
			$post_id  = $this->existing( $identity, 'attachment' );
			if ( ! $post_id ) {
				$post['destination_author'] = $job['authors'][ $post['post_author'] ?? '' ] ?? get_current_user_id();
				$media                      = new Calendar_Plus_TEC_Media();
				$post_id                    = $media->import( $post, $identity );
				if ( is_wp_error( $post_id ) ) {
					return $post_id;
				}
				++$job['created'];
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) || 'trash' === get_post_status( $post_id ) ) {
					return new WP_Error( 'calendarp_media_edit', __( 'A matched attachment is unavailable or cannot be edited.', 'calendar-plus' ) );
				}
				++$job['reused'];
			}
			$url = wp_get_attachment_url( $post_id );
			if ( ! $url ) {
				return new WP_Error( 'calendarp_missing_media', __( 'An imported attachment no longer has a usable URL.', 'calendar-plus' ) );
			}
			$job['media'][ $post['post_id'] ] = $post_id;
			foreach ( $task['aliases'] as $alias ) {
				$job['urls'][ $alias ] = $url;
			}
			return true;
		}
		if ( 'parent' === $task['type'] ) {
			$id     = $job['media'][ $post['post_id'] ] ?? 0;
			$parent = $job['posts'][ $post['post_parent'] ] ?? 0;
			if ( $id && $parent && ! wp_get_post_parent_id( $id ) ) {
				$result = wp_update_post(
					array(
						'ID'          => $id,
						'post_parent' => $parent,
					),
					true
				);
				return is_wp_error( $result ) ? $result : true;
			}
			return true;
		}
		return $this->record( $post, $task['type'], $job );
	}

	/**
	 * Create or reuse an event/location without arbitrary metadata replay.
	 *
	 * @param array  $post Source post.
	 * @param string $type Record type.
	 * @param array  $job Job header.
	 * @return true|WP_Error Result.
	 */
	private function record( $post, $type, &$job ) {
		$post_type   = 'event' === $type ? 'calendar_event' : 'calendar_location';
		$identity    = $this->identity( $job, $type, $post['post_id'] );
		$post_id     = $this->existing( $identity, $post_type );
		$hash_record = $post;
		unset( $hash_record['venue_record'], $hash_record['organizer_records'] );
		$hash    = hash( 'sha256', wp_json_encode( $hash_record ) );
		$content = wp_kses_post( $post['post_content'] );
		$excerpt = wp_kses_post( $post['post_excerpt'] );
		$legacy  = false;
		if ( ! in_array( $post['status'], array( 'publish', 'draft', 'pending', 'future', 'private', 'trash' ), true ) ) {
			return new WP_Error( 'calendarp_status', __( 'An event/location with an unsupported publication status was skipped.', 'calendar-plus' ) );
		}
		if ( ! $post_id && 'event' === $type ) {
			$match = $this->legacy( $post );
			if ( $match ) {
				if ( ! $job['repair'] || is_wp_error( $match ) ) {
					return new WP_Error( 'calendarp_legacy', __( 'A possible legacy import exists. Enable media repair for an unchanged unique match, or review the event manually.', 'calendar-plus' ) );
				}
				$post_id = $match;
				$legacy  = true;
			}
		}
		if ( $post_id ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return new WP_Error( 'calendarp_edit', __( 'You cannot edit a matched destination record.', 'calendar-plus' ) );
			}
			$saved = get_post( $post_id );
			if ( 'event' === $type && ! empty( $job['enrich'] ) && ! $legacy ) {
				$job['posts'][ $post['post_id'] ] = $post_id;
				++$job['reused'];
				return $this->enrich( $post_id, $post, $job );
			}
			if ( ! $legacy ) {
				$unchanged = get_post_meta( $post_id, '_calendarp_tec_import_hash', true ) === $hash && get_post_meta( $post_id, '_calendarp_tec_content_hash', true ) === hash( 'sha256', $saved->post_content . $saved->post_excerpt );
				$complete  = 'complete' === get_post_meta( $post_id, '_calendarp_tec_state', true );
				if ( ! $unchanged ) {
					if ( ! $unchanged ) {
						$this->warning( $job, __( 'A changed source/destination record was preserved without overwriting it.', 'calendar-plus' ) );
					}
					$job['posts'][ $post['post_id'] ] = $post_id;
					++$job['reused'];
					return true;
				}
				// Completed, unchanged records only receive media repairs, never schedule/status changes.
				$legacy = $complete || $job['repair'];
			}
			$content = $saved->post_content;
			$excerpt = $saved->post_excerpt;
			++$job['reused'];
		} else {
			if ( $job['repair'] ) {
				return new WP_Error( 'calendarp_repair_missing', __( 'No unchanged, unique imported event matched this record. Media repair does not create new events.', 'calendar-plus' ) );
			}
			if ( 'event' === $type ) {
				$schedule = $this->schedule( $post );
				if ( is_wp_error( $schedule ) ) {
					return $schedule;
				}
			}
			$post_id = wp_insert_post(
				wp_slash(
					array(
						'post_type'      => $post_type,
						'post_status'    => 'draft',
						'post_title'     => sanitize_text_field( $post['post_title'] ),
						'post_content'   => $content,
						'post_excerpt'   => $excerpt,
						'post_author'    => $job['authors'][ $post['post_author'] ] ?? get_current_user_id(),
						'post_name'      => sanitize_title( $post['post_name'] ),
						'post_date'      => $post['post_date'],
						'post_date_gmt'  => $post['post_date_gmt'],
						'post_password'  => $post['post_password'],
						'comment_status' => 'open' === $post['comment_status'] ? 'open' : 'closed',
						'ping_status'    => 'closed',
						'menu_order'     => (int) $post['menu_order'],
						'meta_input'     => array(
							'_calendarp_tec_source'       => $identity,
							'_calendarp_tec_import_hash'  => $hash,
							'_calendarp_tec_content_hash' => hash( 'sha256', $content . $excerpt ),
							'_calendarp_tec_state'        => 'pending',
						),
					)
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
			++$job['created'];
		}
		$job['posts'][ $post['post_id'] ] = $post_id;
		if ( ! $legacy ) {
			$result = 'event' === $type ? $this->event_details( $post_id, $post, $job ) : $this->location_details( $post_id, $post );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$this->comments( $post_id, $post, $job );
		}
		if ( $job['fetch'] ) {
			$unresolved = Calendar_Plus_TEC_Media::unresolved( $content . ' ' . $excerpt, $job['urls'] );
			if ( $unresolved ) {
				/* translators: %d: Number of unresolved remote media references. */
				$this->warning( $job, $post['post_title'] . ': ' . sprintf( __( '%d media references remain on the source/external site. Review missing attachment records, unavailable files or third-party media.', 'calendar-plus' ), $unresolved ) );
			}
			$content   = Calendar_Plus_TEC_Media::rewrite( $content, $job['urls'], $job['media'], $legacy && get_post_meta( $post_id, '_calendarp_tec_source', true ) === $identity );
			$excerpt   = Calendar_Plus_TEC_Media::rewrite( $excerpt, $job['urls'], $job['media'], $legacy && get_post_meta( $post_id, '_calendarp_tec_source', true ) === $identity );
			$thumbnail = absint( $this->meta( $post, '_thumbnail_id' ) );
			if ( isset( $job['media'][ $thumbnail ] ) ) {
				$current_thumbnail = get_post_thumbnail_id( $post_id );
				if ( ! $legacy || ! $current_thumbnail || ! wp_attachment_is_image( $current_thumbnail ) ) {
					set_post_thumbnail( $post_id, $job['media'][ $thumbnail ] );
				} elseif ( (int) $current_thumbnail !== $job['media'][ $thumbnail ] ) {
					$this->warning( $job, $post['post_title'] . ': ' . __( 'An existing featured image was preserved. Review it manually if the old importer copied a foreign image ID.', 'calendar-plus' ) );
				}
			}
		}
		if ( ! $legacy && 'event' === $type ) {
			$proposal = Calendar_Plus_TEC_Details::propose( $post, $content, get_post_meta( $post_id, '_calendarp_tec_section_hash', true ), $job['urls'], $job['media'] );
			if ( is_wp_error( $proposal ) ) {
				return $proposal;
			}
			$content = $proposal['content'];
		}
		$status = $legacy ? get_post_status( $post_id ) : $post['status'];
		if ( ! $legacy && 'event' === $type && ( in_array( strtolower( $this->meta( $post, '_EventHideFromUpcoming' ) ), array( 'yes', 'true', '1' ), true ) || in_array( $this->meta( $post, '_tribe_events_status' ), array( 'canceled', 'cancelled', 'postponed' ), true ) ) ) {
			$status = 'draft';
			$this->warning( $job, __( 'A hidden, canceled or postponed event was kept as a draft for review.', 'calendar-plus' ) );
		}
		if ( $legacy && $content === $saved->post_content && $excerpt === $saved->post_excerpt && get_post_meta( $post_id, '_calendarp_tec_source', true ) === $identity ) {
			return true;
		}
		$sticky = is_sticky( $post_id );
		$result = wp_update_post(
			wp_slash(
				array(
					'ID'           => $post_id,
					'post_content' => $content,
					'post_excerpt' => $excerpt,
					'post_status'  => $status,
				)
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( $sticky || ( ! $legacy && ( '1' === $post['is_sticky'] || '1' === $this->meta( $post, '_tribe_featured' ) ) ) ) {
			stick_post( $post_id );
		}
		if ( isset( $proposal ) ) {
			update_post_meta( $post_id, '_calendarp_tec_section_hash', $proposal['receipt'] );
		}
		update_post_meta( $post_id, '_calendarp_tec_state', 'complete' );
		update_post_meta( $post_id, '_calendarp_tec_source', $identity );
		update_post_meta( $post_id, '_calendarp_tec_import_hash', $hash );
		update_post_meta( $post_id, '_calendarp_tec_content_hash', hash( 'sha256', $content . $excerpt ) );
		calendarp_delete_calendar_cache( $post_id );
		calendarp_delete_events_since_cache();
		return true;
	}

	/**
	 * Validate import eligibility before selecting a preview row.
	 *
	 * @param array $post Source event.
	 * @return array|WP_Error Supported schedule or review reason.
	 */
	public function preview_schedule( $post ) {
		if ( ! in_array( $post['status'], array( 'publish', 'draft', 'pending', 'future', 'private' ), true ) ) {
			return new WP_Error( 'calendarp_status', __( 'This source status requires manual review.', 'calendar-plus' ) );
		}
		foreach ( array( '_EventRecurrence', '_tribe_blocks_recurrence' ) as $key ) {
			$value = $this->meta( $post, $key );
			if ( $value && ! in_array( $value, array( 'a:0:{}', '[]', '{}', '0' ), true ) ) {
				return new WP_Error( 'calendarp_recurrence', __( 'A recurring series requires manual review before import.', 'calendar-plus' ) );
			}
		}
		return $this->schedule( $post );
	}

	/**
	 * Find a prior operator-approved venue crosswalk without changing it.
	 *
	 * @param string $source Source blog URL.
	 * @param int    $source_id Source venue ID.
	 * @return int Usable existing Location ID, or zero.
	 */
	public function location_choice( $source, $source_id ) {
		$key = $this->identity( array( 'source' => untrailingslashit( $source ) ), 'location', $source_id );
		$id  = absint( get_option( 'calendarp_tec_location_' . $key ) );
		return $id && 'calendar_location' === get_post_type( $id ) && 'trash' !== get_post_status( $id ) && current_user_can( 'edit_post', $id ) ? $id : 0;
	}

	/**
	 * Resolve an importer-created Location for a preview row.
	 *
	 * @param string $source Source blog URL.
	 * @param int    $source_id Source venue ID.
	 * @return int Native destination ID or zero.
	 */
	public function preview_location( $source, $source_id ) {
		return $this->existing( $this->identity( array( 'source' => untrailingslashit( $source ) ), 'location', $source_id ), 'calendar_location' );
	}

	/**
	 * Resolve a source event for the read-only preview.
	 *
	 * @param array $post Source event.
	 * @param array $data Parsed source.
	 * @return int Existing destination event ID.
	 */
	public function preview_match( $post, $data ) {
		return $this->existing( $this->identity( array( 'source' => untrailingslashit( $data['base_url'] ) ), 'event', $post['post_id'] ), 'calendar_event' );
	}

	/**
	 * Enrich importer-owned fields without regenerating a schedule or status.
	 *
	 * @param int   $id Destination event.
	 * @param array $post Prepared source event.
	 * @param array $job Import job.
	 * @return true|WP_Error Result.
	 */
	private function enrich( $id, $post, &$job ) {
		$saved = get_post( $id );
		if ( in_array( $saved->post_status, array( 'publish', 'future' ), true ) && ! in_array( $post['status'], array( 'publish', 'future' ), true ) ) {
			return new WP_Error( 'calendarp_enrich_privacy', __( 'Non-public source details cannot enrich a published destination event. Review its status manually.', 'calendar-plus' ) );
		}
		if ( 'trash' === $saved->post_status || 'complete' !== get_post_meta( $id, '_calendarp_tec_state', true ) ) {
			return new WP_Error( 'calendarp_enrich_state', __( 'Only completed, non-trashed imported events can be enriched.', 'calendar-plus' ) );
		}
		$details               = $this->details( $post );
		$details['organizers'] = $post['organizers'];
		$details['venue']      = ! empty( $post['venue_record'] ) ? $this->details( $post['venue_record'] ) : array();
		update_post_meta( $id, '_calendarp_tec_details', $details );
		$proposal = Calendar_Plus_TEC_Details::propose( $post, $saved->post_content, get_post_meta( $id, '_calendarp_tec_section_hash', true ), $job['urls'], $job['media'] );
		$changed  = false;
		if ( is_wp_error( $proposal ) ) {
			$this->warning( $job, $post['post_title'] . ': ' . $proposal->get_error_message() );
		} else {
			$content = $proposal['content'];
			if ( $job['fetch'] ) {
				$content = Calendar_Plus_TEC_Media::rewrite( $content, $job['urls'], $job['media'], true );
			}
			if ( $content !== $saved->post_content ) {
				$result = wp_update_post(
					wp_slash(
						array(
							'ID'           => $id,
							'post_content' => $content,
						)
					),
					true
				);
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				$changed = true;
			}
			update_post_meta( $id, '_calendarp_tec_section_hash', $proposal['receipt'] );
		}
		$venue    = absint( $this->meta( $post, '_EventVenueID' ) );
		$location = $job['posts'][ $venue ] ?? 0;
		$current  = (int) get_post_meta( $id, '_location_id', true );
		$owned    = (int) get_post_meta( $id, '_calendarp_tec_location_id', true );
		if ( $location && 'calendar_location' === get_post_type( $location ) && 'trash' !== get_post_status( $location ) && current_user_can( 'edit_post', $location ) && ( ! in_array( $saved->post_status, array( 'publish', 'future' ), true ) || 'publish' === get_post_status( $location ) ) ) {
			if ( ( ! $current && ! $owned ) || ( $owned && $current === $owned ) ) {
				if ( $current !== (int) $location ) {
					update_post_meta( $id, '_location_id', $location );
					$changed = true;
				}
				update_post_meta( $id, '_calendarp_tec_location_id', $location );
			} elseif ( $current !== (int) $location ) {
				$this->warning( $job, $post['post_title'] . ': ' . __( 'Your existing Location choice was preserved.', 'calendar-plus' ) );
			}
		}
		foreach ( $post['terms'] as $term ) {
			$taxonomy = $this->taxonomy( $term['taxonomy'] );
			if ( $taxonomy ) {
				$result = $this->term(
					array(
						'taxonomy'    => $taxonomy,
						'slug'        => $term['slug'],
						'name'        => $term['name'],
						'parent'      => '',
						'description' => '',
					)
				);
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				$found = term_exists( $term['slug'], $taxonomy );
				if ( $found && ! has_term( (int) $found['term_id'], $taxonomy, $id ) ) {
					$result = wp_set_object_terms( $id, array( (int) $found['term_id'] ), $taxonomy, true );
					if ( is_wp_error( $result ) ) {
						return $result;
					}
					$changed = true;
				}
			}
		}
		if ( $job['fetch'] ) {
			$thumbnail = absint( $this->meta( $post, '_thumbnail_id' ) );
			if ( ! get_post_thumbnail_id( $id ) && isset( $job['media'][ $thumbnail ] ) ) {
				set_post_thumbnail( $id, $job['media'][ $thumbnail ] );
				$changed = true;
			}
		}
		if ( $changed ) {
			++$job['updated'];
			calendarp_delete_calendar_cache( $id );
			calendarp_delete_events_since_cache();
		}
		return true;
	}

	/**
	 * Validate and convert a single TEC event interval.
	 *
	 * @param array $post Source event.
	 * @return array|WP_Error Schedule.
	 */
	public function schedule( $post ) {
		$all_day = in_array( strtolower( $this->meta( $post, '_EventAllDay' ) ), array( 'yes', '1', 'true' ), true );
		$zone    = $this->meta( $post, '_EventTimezone' );
		try {
			if ( preg_match( '/^UTC([+-]\d{1,2}(?:\.\d+)?)$/', $zone, $match ) ) {
				$minutes = (int) round( (float) $match[1] * 60 );
				$zone    = sprintf( '%s%02d:%02d', $minutes < 0 ? '-' : '+', (int) floor( abs( $minutes ) / 60 ), abs( $minutes ) % 60 );
			}
			$timezone = $zone ? new DateTimeZone( $zone ) : wp_timezone();
			$dates    = array();
			foreach ( array( 'Start', 'End' ) as $part ) {
				$value = $this->meta( $post, '_Event' . $part . 'Date' );
				$date  = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $value, $timezone );
				if ( ! $date || $date->format( 'Y-m-d H:i:s' ) !== $value ) {
					return new WP_Error( 'calendarp_date', __( 'An event has missing or invalid dates and was not published.', 'calendar-plus' ) );
				}
				if ( ! $all_day ) {
					$utc = $this->meta( $post, '_Event' . $part . 'DateUTC' );
					if ( $utc ) {
						$instant = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $utc, new DateTimeZone( 'UTC' ) );
						if ( ! $instant || $instant->format( 'Y-m-d H:i:s' ) !== $utc || $instant->setTimezone( $timezone )->format( 'Y-m-d H:i:s' ) !== $value ) {
							return new WP_Error( 'calendarp_utc', __( 'An event has inconsistent local and UTC dates. Review its timezone before importing.', 'calendar-plus' ) );
						}
						$date = $instant;
					}
					$date = $date->setTimezone( wp_timezone() );
				}
				$dates[] = $date;
			}
		} catch ( Exception $exception ) {
			return new WP_Error( 'calendarp_timezone', __( 'An event timezone is invalid.', 'calendar-plus' ) );
		}
		if ( $dates[0]->diff( $dates[1] )->days > 366 ) {
			return new WP_Error( 'calendarp_duration', __( 'Events spanning more than 366 days require manual review.', 'calendar-plus' ) );
		}
		if ( $dates[1] < $dates[0] ) {
			return new WP_Error( 'calendarp_interval', __( 'An event ends before it starts.', 'calendar-plus' ) );
		}
		if ( ! $all_day && $dates[1]->getTimestamp() > $dates[0]->getTimestamp() && $dates[1]->format( 'Y-m-d H:i' ) <= $dates[0]->format( 'Y-m-d H:i' ) ) {
			return new WP_Error( 'calendarp_precision', __( 'This interval cannot be represented safely with Calendar Plus local, minute-based times. Review its daylight-saving transition or seconds.', 'calendar-plus' ) );
		}
		$rule = array(
			'rule_type'  => $dates[0]->format( 'Y-m-d' ) === $dates[1]->format( 'Y-m-d' ) ? 'standard' : 'datespan',
			'from_date'  => $dates[0]->format( 'Y-m-d' ),
			'until_date' => $dates[1]->format( 'Y-m-d' ),
			'from_time'  => $all_day ? '00:00' : $dates[0]->format( 'H:i' ),
			'until_time' => $all_day ? '23:59' : $dates[1]->format( 'H:i' ),
		);
		return array(
			'rule'    => $rule,
			'all_day' => $all_day,
		);
	}

	/**
	 * Apply native event fields and retain safe source detail for review.
	 *
	 * @param int   $id Destination ID.
	 * @param array $post Source post.
	 * @param array $job Job header.
	 * @return true|WP_Error Result.
	 */
	private function event_details( $id, $post, &$job ) {
		$details               = $this->details( $post );
		$details['organizers'] = $post['organizers'];
		update_post_meta( $id, '_calendarp_tec_details', $details );
		$schedule = $this->schedule( $post );
		if ( is_wp_error( $schedule ) ) {
			return $schedule;
		}
		$recurrence       = $this->meta( $post, '_EventRecurrence' );
		$block_recurrence = $this->meta( $post, '_tribe_blocks_recurrence' );
		if ( ( $recurrence && ! in_array( $recurrence, array( 'a:0:{}', '[]', '{}', '0' ), true ) ) || ( $block_recurrence && ! in_array( $block_recurrence, array( 'a:0:{}', '[]', '{}', '0' ), true ) ) ) {
			return new WP_Error( 'calendarp_recurrence', __( 'A recurring event requires manual review; its first interval was not published as a complete series.', 'calendar-plus' ) );
		}
		update_post_meta( $id, '_all_day', $schedule['all_day'] );
		calendarp_update_event_type( $id, 'datespan' === $schedule['rule']['rule_type'] ? 'datespan' : '' );
		calendarp_generate_event_rules_and_dates( $id, array( $schedule['rule'] ) );
		$event = calendarp_get_event( $id );
		if ( ! $event || ! $event->get_dates_list() || ! calendar_plus()->generator->get_all_event_dates( $id ) || ! $event->get_min_date() ) {
			return new WP_Error( 'calendarp_schedule', __( 'The destination schedule could not be verified. The event remains a draft.', 'calendar-plus' ) );
		}
		$venue = absint( $this->meta( $post, '_EventVenueID' ) );
		if ( $venue && isset( $job['posts'][ $venue ] ) && 'calendar_location' === get_post_type( $job['posts'][ $venue ] ) && 'trash' !== get_post_status( $job['posts'][ $venue ] ) && current_user_can( 'edit_post', $job['posts'][ $venue ] ) && ( ! in_array( $post['status'], array( 'publish', 'future' ), true ) || 'publish' === get_post_status( $job['posts'][ $venue ] ) ) ) {
			update_post_meta( $id, '_location_id', $job['posts'][ $venue ] );
			update_post_meta( $id, '_calendarp_tec_location_id', $job['posts'][ $venue ] );
		} elseif ( $venue ) {
			$this->warning( $job, __( 'An event venue is missing from the export or failed to import.', 'calendar-plus' ) );
		}
		foreach ( $post['terms'] as $term ) {
			$taxonomy = $this->taxonomy( $term['taxonomy'] );
			if ( ! $taxonomy ) {
				continue;
			}
			$result = $this->term(
				array(
					'taxonomy'    => $taxonomy,
					'slug'        => $term['slug'],
					'name'        => $term['name'],
					'parent'      => '',
					'description' => '',
				)
			);
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$found  = term_exists( $term['slug'], $taxonomy );
			$result = wp_set_object_terms( $id, array( (int) $found['term_id'] ), $taxonomy, true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		$details               = $this->details( $post );
		$details['organizers'] = $post['organizers'];
		update_post_meta( $id, '_calendarp_tec_details', $details );
		if ( preg_match( '/<!--\s+wp:(?:tribe|tec)\//', $post['post_content'] ) || $this->meta( $post, '_tribe_events_status' ) || $this->meta( $post, '_EventHideFromUpcoming' ) ) {
			$this->warning( $job, __( 'An event contains TEC-specific blocks or details with no native Calendar Plus display. Source details were retained for review.', 'calendar-plus' ) );
		}
		return true;
	}

	/**
	 * Map venue address fields without invoking geocoding services.
	 *
	 * @param int   $id Location ID.
	 * @param array $post Venue.
	 * @return true Result.
	 */
	private function location_details( $id, $post ) {
		foreach ( array(
			'_VenueAddress' => '_address',
			'_VenueCity'    => '_city',
			'_VenueZip'     => '_postcode',
		) as $source => $destination ) {
			update_post_meta( $id, $destination, sanitize_text_field( $this->meta( $post, $source ) ) );
		}
		$state = $this->meta( $post, '_VenueStateProvince' );
		if ( ! $state ) {
			$state = $this->meta( $post, '_VenueState' );
		}
		if ( ! $state ) {
			$state = $this->meta( $post, '_VenueProvince' );
		}
		update_post_meta( $id, '_state', sanitize_text_field( $state ) );
		$country   = $this->meta( $post, '_VenueCountry' );
		$countries = calendarp_get_countries();
		$code      = isset( $countries[ strtoupper( $country ) ] ) ? strtoupper( $country ) : array_search( $country, $countries, true );
		update_post_meta( $id, '_country', $code ? $code : '' );
		update_post_meta( $id, '_location_type', 'standard' );
		update_post_meta( $id, '_calendarp_tec_details', $this->details( $post ) );
		return true;
	}

	/**
	 * Import comments idempotently and then resolve reply parents.
	 *
	 * @param int   $id Destination post ID.
	 * @param array $post Source post.
	 * @param array $job Job header.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function comments( $id, $post, &$job ) {
		$mapped = array();
		foreach ( $post['comments'] as $comment ) {
			$key   = $this->identity( $job, 'comment', $comment['comment_id'] );
			$found = get_comments(
				array(
					'post_id'    => $id,
					'meta_key'   => '_calendarp_tec_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Bounded provenance lookup; no custom SQL allowed.
					'meta_value' => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Bounded to this post and one comment.
					'number'     => 1,
					'fields'     => 'ids',
					'status'     => 'all',
				)
			);
			if ( $found ) {
				$mapped[ $comment['comment_id'] ] = (int) $found[0];
				continue;
			}
			$approved   = in_array( $comment['comment_approved'], array( '0', '1', 'spam', 'trash' ), true ) ? $comment['comment_approved'] : '0';
			$comment_id = wp_insert_comment(
				array(
					'comment_post_ID'      => $id,
					'comment_author'       => sanitize_text_field( $comment['comment_author'] ),
					'comment_author_email' => sanitize_email( $comment['comment_author_email'] ),
					'comment_author_url'   => esc_url_raw( $comment['comment_author_url'] ),
					'comment_content'      => wp_kses_post( $comment['comment_content'] ),
					'comment_date'         => $comment['comment_date'],
					'comment_date_gmt'     => $comment['comment_date_gmt'],
					'comment_approved'     => $approved,
					'comment_type'         => 'comment',
					'user_id'              => 0,
					'comment_meta'         => array( '_calendarp_tec_source' => $key ),
				)
			);
			if ( $comment_id ) {
				$mapped[ $comment['comment_id'] ] = $comment_id;
			} else {
				$this->warning( $job, __( 'A comment could not be imported.', 'calendar-plus' ) );
			}
		}
		foreach ( $post['comments'] as $comment ) {
			if ( isset( $mapped[ $comment['comment_id'] ], $mapped[ $comment['comment_parent'] ] ) && $comment['comment_id'] !== $comment['comment_parent'] ) {
				wp_update_comment(
					array(
						'comment_ID'     => $mapped[ $comment['comment_id'] ],
						'comment_parent' => $mapped[ $comment['comment_parent'] ],
					)
				);
			}
		}
	}

	/**
	 * Resolve/create one term without modifying an existing shared term.
	 *
	 * @param array $term Term.
	 * @return true|WP_Error Result.
	 */
	private function term( $term ) {
		if ( term_exists( $term['slug'], $term['taxonomy'] ) ) {
			return true;
		}
		$parent = $term['parent'] ? term_exists( $term['parent'], $term['taxonomy'] ) : null;
		$result = wp_insert_term(
			sanitize_text_field( $term['name'] ),
			$term['taxonomy'],
			array(
				'slug'        => sanitize_title( $term['slug'] ),
				'description' => wp_kses_post( $term['description'] ),
				'parent'      => is_array( $parent ) ? (int) $parent['term_id'] : 0,
			)
		);
		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Find a unique, unchanged event created by the legacy importer.
	 *
	 * @param array $post Source event.
	 * @return int|WP_Error|false Match.
	 */
	private function legacy( $post ) {
		$found = get_posts(
			array(
				'post_type'      => 'calendar_event',
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private', 'trash' ),
				'title'          => $post['post_title'],
				'date_query'     => array(
					array(
						'column'    => 'post_date',
						'after'     => $post['post_date'],
						'before'    => $post['post_date'],
						'inclusive' => true,
					),
				),
				'posts_per_page' => 2,
			)
		);
		if ( ! $found ) {
			return false;
		}
		$content = wp_kses_post( html_entity_decode( $post['post_content'], ENT_QUOTES, 'UTF-8' ) );
		$excerpt = wp_kses_post( html_entity_decode( $post['post_excerpt'], ENT_QUOTES, 'UTF-8' ) );
		if ( 1 !== count( $found ) || $found[0]->post_content !== $content || $found[0]->post_excerpt !== $excerpt || get_post_meta( $found[0]->ID, '_calendarp_tec_source', true ) ) {
			return new WP_Error( 'calendarp_ambiguous', __( 'A possible legacy import is ambiguous or has been edited.', 'calendar-plus' ) );
		}
		return $found[0]->ID;
	}

	/**
	 * Find an existing provenance match, never an unrelated title match.
	 *
	 * @param string $identity Source identity.
	 * @param string $post_type Destination type.
	 * @return int ID or zero.
	 */
	private function existing( $identity, $post_type ) {
		$ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => array( 'inherit', 'publish', 'draft', 'pending', 'future', 'private', 'trash' ),
				'meta_key'       => '_calendarp_tec_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Bounded provenance lookup through WordPress APIs.
				'meta_value'     => $identity, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- One result per source identity.
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Retain only known scalar TEC metadata, never deserialize source objects.
	 *
	 * @param array $post Source post.
	 * @return array Details.
	 */
	private function details( $post ) {
		$result = array();
		foreach ( $post['postmeta'] as $key => $values ) {
			if ( preg_match( '/^_(?:Event|Venue|Organizer)[A-Za-z]+$|^_tribe_(?:events_status(?:_reason)?|featured)$/', $key ) ) {
				$result[ $key ] = array_map( 'sanitize_textarea_field', $values );
			}
		}
		return $result;
	}

	/**
	 * Cancel pending work without deleting imported user content.
	 *
	 * @param string $token Job token.
	 * @return array|WP_Error Job.
	 */
	public function cancel( $token ) {
		$job = $this->authorized_job( $token );
		if ( is_wp_error( $job ) || $job['done'] ) {
			return $job;
		}
		$lock = get_option( $this->job_key() . '_lock' );
		if ( $lock && $lock > time() - 300 ) {
			return new WP_Error( 'calendarp_locked', __( 'Wait for the current request before canceling.', 'calendar-plus' ) );
		}
		$chunk_count = (int) ceil( $job['total'] / 5 );
		for ( $index = (int) floor( $job['cursor'] / 5 ); $index < $chunk_count; ++$index ) {
			delete_option( $this->chunk_key( $job, $index ) );
		}
		if ( $job['upload'] ) {
			foreach ( $job['uploads'] ?? array( $job['upload'] ) as $upload_id ) {
					wp_import_cleanup( $upload_id );
			}
		}
		$this->cleanup_related( $job );
		$this->release_site_lock( $job );
		delete_option( $this->job_key() );
		delete_option( $this->job_key() . '_lock' );
		$job['done'] = true;
		return $job;
	}

	/**
	 * Delete temporary dependency records owned by this completed/canceled job.
	 *
	 * @param array $job Job header.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function cleanup_related( $job ) {
		foreach ( $job['related'] ?? array() as $source_id ) {
			delete_option( 'calendarp_tec_related_' . $job['token'] . '_' . $source_id );
		}
	}

	/**
	 * Release only the site lease owned by this job.
	 *
	 * @param array $job Job header.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function release_site_lock( $job ) {
		$active = get_option( 'calendarp_tec_active_job' );
		if ( is_array( $active ) && isset( $active['token'] ) && hash_equals( $active['token'], $job['token'] ) ) {
			delete_option( 'calendarp_tec_active_job' );
		}
	}

	/**
	 * Authenticate a saved job against the current site/user and permissions.
	 *
	 * @param string $token Job token.
	 * @return array|WP_Error Job.
	 */
	private function authorized_job( $token ) {
		$job = get_option( $this->job_key() );
		if ( ! $this->can_import() || ! $job || get_current_user_id() !== $job['owner'] || ! hash_equals( $job['token'], $token ) || ( $job['fetch'] && ! current_user_can( 'upload_files' ) ) ) {
			return new WP_Error( 'calendarp_job_permission', __( 'This import is unavailable or does not belong to you.', 'calendar-plus' ) );
		}
		return $job;
	}

	/**
	 * Display accessible, resumable import progress.
	 *
	 * @param array $job Job.
	 * @param bool  $redirect Whether to continue on the canonical GET screen.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function progress( $job, $redirect = false ) {
		wp_enqueue_script( 'calendarp-tec-importer', calendarp_get_plugin_url() . 'admin/js/tec-importer.js', array(), calendarp_get_version(), true );
		wp_localize_script(
			'calendarp-tec-importer',
			'calendarpTECImport',
			array(
				'redirect' => $redirect ? admin_url( 'admin.php?import=calendar-plus-the-events-calendar' ) : '',
				'url'      => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'calendarp-tec-batch' ),
				'token'    => $job['token'],
				'summary'  => $this->summary( $job ),
				'error'    => __( 'The request failed. You can resume after checking the connection.', 'calendar-plus' ),
				'complete' => __( 'Import processing finished. Review the counts and warnings below.', 'calendar-plus' ),
				'labels'   => array(
					'created'  => __( 'Created records', 'calendar-plus' ),
					'reused'   => __( 'Reused records', 'calendar-plus' ),
					'updated'  => __( 'Updated events', 'calendar-plus' ),
					'failed'   => __( 'Failed tasks', 'calendar-plus' ),
					'warnings' => __( 'Warnings', 'calendar-plus' ),
				),
			)
		);
		echo '<div id="calendarp-tec-progress"><p role="status" aria-live="polite"></p><progress max="' . esc_attr( $job['total'] ) . '" value="' . esc_attr( $job['cursor'] ) . '" aria-label="' . esc_attr__( 'Import progress', 'calendar-plus' ) . '"></progress><ul></ul>';
		if ( ! $job['done'] ) {
			echo '<p><button type="button" class="button button-primary" data-import-start>' . esc_html__( 'Start / resume', 'calendar-plus' ) . '</button> <button type="button" class="button" data-import-cancel>' . esc_html__( 'Cancel remaining work', 'calendar-plus' ) . '</button></p>';
		}
		echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=calendar_event' ) ) . '">' . esc_html__( 'View imported events', 'calendar-plus' ) . '</a></p></div>';
	}

	/**
	 * Return bounded user-visible progress without private payloads.
	 *
	 * @param array $job Job.
	 * @return array Summary.
	 */
	private function summary( $job ) {
		return array_intersect_key( $job, array_flip( array( 'cursor', 'total', 'done', 'created', 'reused', 'updated', 'failed', 'warning_count', 'warnings' ) ) );
	}

	/**
	 * Add a bounded, sanitized warning for the authorized importer screen.
	 *
	 * @param array  $job Job.
	 * @param string $message Message.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function warning( &$job, $message ) {
		++$job['warning_count'];
		if ( count( $job['warnings'] ) < 100 ) {
			$job['warnings'][] = sanitize_text_field( $message );
		}
	}

	/**
	 * Display an escaped error message.
	 *
	 * @param string $message Error.
	 *
	 * @return mixed Calendar operation result.
	 */
	private function notice( $message ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Get the current user's site-scoped job option.
	 *
	 * @return string Option name.
	 */
	private function job_key() {
		return 'calendarp_tec_import_' . get_current_user_id();
	}

	/**
	 * Get a queue option name.
	 *
	 * @param array $job Job.
	 * @param int   $index Chunk number.
	 * @return string Name.
	 */
	private function chunk_key( $job, $index ) {
		return 'calendarp_tec_queue_' . $job['token'] . '_' . $index;
	}

	/**
	 * Build a provenance key; WordPress option/meta APIs scope it to this site.
	 *
	 * @param array  $job Job.
	 * @param string $type Source type.
	 * @param string $id Source ID.
	 * @return string Key.
	 */
	private function identity( $job, $type, $id ) {
		return hash( 'sha256', $job['source'] . '|' . $type . '|' . $id );
	}

	/**
	 * Retrieve the first scalar source metadata value.
	 *
	 * @param array  $post Source post.
	 * @param string $key Key.
	 * @return string Value.
	 */
	private function meta( $post, $key ) {
		return $post['postmeta'][ $key ][0] ?? '';
	}

	/**
	 * Map only the supported event taxonomies.
	 *
	 * @param string $taxonomy Source taxonomy.
	 * @return string Destination taxonomy or empty string.
	 */
	private function taxonomy( $taxonomy ) {
		$map = array(
			'tribe_events_cat' => 'calendar_event_category',
			'post_tag'         => 'calendar_event_tag',
			'tag'              => 'calendar_event_tag',
		);
		return $map[ $taxonomy ] ?? '';
	}

	/**
	 * Resolve source-relative URLs for dependency lookup.
	 *
	 * @param string $url Source URL.
	 * @param string $base Source site URL.
	 * @return string Absolute URL.
	 */
	private function resolve_url( $url, $base ) {
		if ( 0 === strpos( $url, '//' ) ) {
			return wp_parse_url( $base, PHP_URL_SCHEME ) . ':' . $url;
		}
		if ( 0 === strpos( $url, '/' ) ) {
			return wp_parse_url( $base, PHP_URL_SCHEME ) . '://' . wp_parse_url( $base, PHP_URL_HOST ) . $url;
		}
		return $url;
	}

	/**
	 * Allow fallback downloads only for source-site WordPress upload images.
	 *
	 * @param string $url Image URL.
	 * @param string $base Source URL.
	 * @return bool Whether a fallback task is permitted.
	 */
	private function source_image( $url, $base ) {
		return wp_parse_url( $url, PHP_URL_HOST ) === wp_parse_url( $base, PHP_URL_HOST ) && false !== strpos( (string) wp_parse_url( $url, PHP_URL_PATH ), '/wp-content/uploads/' ) && (bool) preg_match( '/\.(?:jpe?g|png|gif|webp|avif)$/i', (string) wp_parse_url( $url, PHP_URL_PATH ) );
	}
}
