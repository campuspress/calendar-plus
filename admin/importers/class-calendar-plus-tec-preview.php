<?php
/**
 * Read-only, paginated TEC import review screens.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Displays source mappings without creating destination content. */
class Calendar_Plus_TEC_Preview {
	/**
	 * Handle upload form.
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function upload_form() {
		echo '<p>' . esc_html__( 'Upload an events XML export and optional venue and organiser XML exports from the same site. Review the mappings before importing. Combined maximum: 8 MB and 1,000 related records. Source media must remain accessible during import.', 'calendar-plus' ) . '</p>';
		echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin.php?import=calendar-plus-the-events-calendar&step=1' ) ) . '">';
		wp_nonce_field( 'import-upload' );
		foreach ( array(
			'import'          => __( 'Events XML', 'calendar-plus' ),
			'venues_file'     => __( 'Venues XML (optional)', 'calendar-plus' ),
			'organisers_file' => __( 'Organisers XML (optional)', 'calendar-plus' ),
		) as $name => $label ) {
			echo '<p><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label> <input type="file" accept=".xml" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . ( 'import' === $name ? ' required' : '' ) . '></p>';
		}
		submit_button( __( 'Upload files and preview', 'calendar-plus' ) );
		echo '</form>';
	}

	/**
	 * Prepare source dependencies without changing their original record IDs.
	 *
	 * @param array $post Source event.
	 * @param array $records Source records indexed by ID.
	 * @return array Prepared event.
	 */
	public static function prepare( $post, $records ) {
		$venue                     = absint( Calendar_Plus_TEC_Details::value( $post, '_EventVenueID' ) );
		$post['venue_record']      = isset( $records[ $venue ] ) && 'tribe_venue' === $records[ $venue ]['post_type'] ? $records[ $venue ] : array();
		$post['organizer_records'] = array();
		if ( $post['venue_record'] && ! in_array( $post['venue_record']['status'], array( 'publish', $post['status'] ), true ) ) {
			$post['venue_record'] = array();
		}
		foreach ( array_unique( $post['postmeta']['_EventOrganizerID'] ?? array() ) as $id ) {
			if ( isset( $records[ $id ] ) && 'tribe_organizer' === $records[ $id ]['post_type'] && in_array( $records[ $id ]['status'], array( 'publish', $post['status'] ), true ) ) {
				$post['organizer_records'][] = $records[ $id ];
			}
		}
		return $post;
	}

	/**
	 * Explain missing dependencies and questionable public details precisely.
	 *
	 * @param array $post Source event.
	 * @param array $records Source records indexed by ID.
	 * @return string[] Review messages.
	 */
	public static function issues( $post, $records ) {
		$issues = array();
		$ready  = self::prepare( $post, $records );
		$venue  = Calendar_Plus_TEC_Details::value( $post, '_EventVenueID' );
		if ( $venue && '0' !== $venue && ! $ready['venue_record'] ) {
			/* translators: %s: Source venue record ID. */
			$issues[] = sprintf( __( 'Venue record %s is missing or unavailable for this event status.', 'calendar-plus' ), $venue );
		}
		foreach ( array_unique( $post['postmeta']['_EventOrganizerID'] ?? array() ) as $id ) {
			if ( $id && '0' !== $id && ( ! isset( $records[ $id ] ) || 'tribe_organizer' !== $records[ $id ]['post_type'] || ! in_array( $records[ $id ]['status'], array( 'publish', $post['status'] ), true ) ) ) {
				/* translators: %s: Source organiser record ID. */
				$issues[] = sprintf( __( 'Organiser record %s is missing or unavailable for this event status.', 'calendar-plus' ), $id );
			}
		}
		$checks   = $ready['organizer_records'];
		$checks[] = $post;
		if ( $ready['venue_record'] ) {
			$checks[] = $ready['venue_record'];
			if ( ! Calendar_Plus_TEC_Details::value( $ready['venue_record'], '_VenueAddress' ) ) {
				$issues[] = __( 'This venue has no street address; retain its available details without inventing an address or map.', 'calendar-plus' );
			}
		}
		foreach ( $checks as $record ) {
			foreach ( array( '_VenueURL', '_OrganizerWebsite', '_EventURL' ) as $key ) {
				$value = Calendar_Plus_TEC_Details::value( $record, $key );
				if ( $value && ! Calendar_Plus_TEC_Details::website( $value ) ) {
					/* translators: %s: Unusable website value from the source. */
					$issues[] = sprintf( __( 'Review this website; it will not be linked automatically: %s', 'calendar-plus' ), $value );
				}
			}
			$email = Calendar_Plus_TEC_Details::value( $record, '_OrganizerEmail' );
			if ( $email && ! is_email( $email ) ) {
				$issues[] = __( 'An organiser email is invalid and will not be displayed automatically.', 'calendar-plus' );
			}
		}
		return array_values( array_unique( $issues ) );
	}

	/**
	 * Render twenty rows with source/final body and mapping details.
	 *
	 * @param Calendar_Plus_The_Events_Calendar_Importer $importer Importer.
	 * @param array                                      $data Source export.
	 * @param int                                        $page Requested page.
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function render( $importer, $data, $page = 0 ) {
		wp_enqueue_style( 'calendarp-tec-importer', calendarp_get_plugin_url() . 'admin/css/tec-importer.css', array(), calendarp_get_version() );
		$plan    = get_post_meta( $importer->id, '_calendarp_tec_plan', true );
		$records = array_column( $data['posts'], null, 'post_id' );
		$events  = array_values(
			array_filter(
				$data['posts'],
				static function ( $post ) {
					return 'tribe_events' === $post['post_type'];
				}
			)
		);
		$pages   = (int) ceil( count( $events ) / 20 );
		$page    = min( $page, max( 0, $pages - 1 ) );
		$choices = get_posts(
			array(
				'post_type'      => 'calendar_location',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		echo '<h2>' . esc_html__( 'Review event mappings', 'calendar-plus' ) . '</h2>';
		/* translators: 1: Source event count, 2: Selected event count. */
		echo '<p>' . esc_html( sprintf( __( '%1$d source events; %2$d selected. Invalid schedules are excluded. Media availability is checked during transfer. No events, Locations or terms have been created by this preview.', 'calendar-plus' ), count( $events ), count( $plan['events'] ) ) ) . '</p>';
		echo '<input type="hidden" name="current_page" value="' . esc_attr( $page ) . '"><table class="widefat striped calendarp-tec-preview"><thead><tr>';
		foreach ( array( __( 'Select', 'calendar-plus' ), __( 'Event and dates', 'calendar-plus' ), __( 'Venue → Location', 'calendar-plus' ), __( 'Organisers', 'calendar-plus' ), __( 'Review details', 'calendar-plus' ) ) as $heading ) {
			echo '<th scope="col">' . esc_html( $heading ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		$shown_venues = array();
		foreach ( array_slice( $events, $page * 20, 20 ) as $post ) {
			$post     = self::prepare( $post, $records );
			$id       = absint( $post['post_id'] );
			$schedule = $importer->preview_schedule( $post );
			$existing = $importer->preview_match( $post, $data );
			$issues   = self::issues( $post, $records );
			if ( $existing ) {
				$current_location = (int) get_post_meta( $existing, '_location_id', true );
				$owned_location   = (int) get_post_meta( $existing, '_calendarp_tec_location_id', true );
				if ( ( $current_location && $current_location !== $owned_location ) || ( ! $current_location && $owned_location ) ) {
					$issues[] = __( 'Your local Location choice will be preserved.', 'calendar-plus' );
				}
			}
			$current  = $existing ? get_post_field( 'post_content', $existing ) : wp_kses_post( $post['post_content'] );
			$proposal = Calendar_Plus_TEC_Details::propose( $post, $current, $existing ? get_post_meta( $existing, '_calendarp_tec_section_hash', true ) : '' );
			if ( is_wp_error( $proposal ) ) {
				$issues[] = $proposal->get_error_message();
			}
			echo '<tr class="calendarp-tec-event-row"><td><input type="checkbox" name="event_ids[]" value="' . esc_attr( $id ) . '" aria-label="' . esc_attr( $post['post_title'] ) . '"' . checked( in_array( $id, $plan['events'], true ), true, false ) . disabled( is_wp_error( $schedule ), true, false ) . '></td><td><strong>' . esc_html( $post['post_title'] ) . '</strong><br>' . esc_html( $id ) . '<br>';
			echo esc_html( is_wp_error( $schedule ) ? $schedule->get_error_message() : implode( ' → ', array( Calendar_Plus_TEC_Details::value( $post, '_EventStartDate' ), Calendar_Plus_TEC_Details::value( $post, '_EventEndDate' ) ) ) . ' (' . Calendar_Plus_TEC_Details::value( $post, '_EventTimezone' ) . ')' );
			echo '<br>' . esc_html( $existing ? __( 'Existing import: preserve schedule and local edits', 'calendar-plus' ) : __( 'New event', 'calendar-plus' ) ) . '</td><td>';
			$venue = absint( Calendar_Plus_TEC_Details::value( $post, '_EventVenueID' ) );
			echo esc_html( $post['venue_record']['post_title'] ?? ( $venue ? $venue : __( 'None supplied', 'calendar-plus' ) ) );
			if ( $venue && ! isset( $shown_venues[ $venue ] ) ) {
				$shown_venues[ $venue ] = true;
				echo '<br><select name="location_map[' . esc_attr( $venue ) . ']" aria-label="' . esc_attr__( 'Destination Location for this source venue', 'calendar-plus' ) . '"><option value="0">' . esc_html__( 'Create/reuse by source ID', 'calendar-plus' ) . '</option>';
				$available = $choices;
				$chosen_id = $plan['locations'][ $venue ] ?? 0;
				if ( $chosen_id && ! in_array( $chosen_id, array_column( $available, 'ID' ), true ) ) {
					$chosen_post = get_post( $chosen_id );
					if ( $chosen_post && 'calendar_location' === $chosen_post->post_type ) {
						$available[] = $chosen_post;
					}
				}
				foreach ( $available as $choice ) {
					if ( current_user_can( 'edit_post', $choice->ID ) ) {
						echo '<option value="' . esc_attr( $choice->ID ) . '"' . selected( $plan['locations'][ $venue ] ?? 0, $choice->ID, false ) . '>' . esc_html( $choice->post_title ) . '</option>';
					}
				}
				echo '</select>';
				$matched = $chosen_id ? $chosen_id : $importer->preview_location( $data['base_url'], $venue );
				echo '<p>' . esc_html( $matched ? __( 'Reuse Location', 'calendar-plus' ) . ': ' . get_the_title( $matched ) : ( $post['venue_record'] ? __( 'Create a new Location', 'calendar-plus' ) : __( 'Supply the venue export or choose an existing Location.', 'calendar-plus' ) ) ) . '</p>';
			}
			echo '</td><td>' . esc_html( implode( ', ', array_column( $post['organizer_records'], 'post_title' ) ) ) . '</td><td>' . esc_html( $issues || is_wp_error( $schedule ) ? __( 'Review warnings below', 'calendar-plus' ) : __( 'Source details resolved', 'calendar-plus' ) ) . '</td></tr>';
			echo '<tr class="calendarp-tec-detail-row"><td colspan="5"><details id="calendarp-tec-details-' . esc_attr( $id ) . '"><summary>' . esc_html__( 'View fields, warnings and proposed body', 'calendar-plus' ) . ': ' . esc_html( $post['post_title'] ) . '</summary>';
			foreach ( $issues as $issue ) {
				echo '<p>' . esc_html( $issue ) . '</p>';
			}
			$fields = array(
				__( 'Source status', 'calendar-plus' ) => $post['status'],
				__( 'Destination timezone', 'calendar-plus' ) => wp_timezone_string(),
				__( 'All-day event', 'calendar-plus' ) => ! is_wp_error( $schedule ) && $schedule['all_day'] ? __( 'Yes', 'calendar-plus' ) : __( 'No', 'calendar-plus' ),
				__( 'Website', 'calendar-plus' )       => Calendar_Plus_TEC_Details::value( $post, '_EventURL' ),
				__( 'Cost', 'calendar-plus' )          => Calendar_Plus_TEC_Details::value( $post, '_EventCost' ),
				__( 'Currency', 'calendar-plus' )      => Calendar_Plus_TEC_Details::value( $post, '_EventCurrencySymbol' ),
				__( 'Event Tags and categories', 'calendar-plus' ) => implode(
					', ',
					array_column(
						array_filter(
							$post['terms'],
							static function ( $term ) {
								return in_array( $term['taxonomy'], array( 'post_tag', 'tag', 'tribe_events_cat' ), true ); }
						),
						'name'
					)
				),
				__( 'Excerpt', 'calendar-plus' )       => $post['post_excerpt'],
				__( 'Comments', 'calendar-plus' )      => count( $post['comments'] ),
			);
			if ( ! is_wp_error( $schedule ) ) {
				$fields[ __( 'Destination interval', 'calendar-plus' ) ] = $schedule['rule']['from_date'] . ' ' . $schedule['rule']['from_time'] . ' → ' . $schedule['rule']['until_date'] . ' ' . $schedule['rule']['until_time'];
			}
			$thumbnail = absint( Calendar_Plus_TEC_Details::value( $post, '_thumbnail_id' ) );
			$fields[ __( 'Featured image', 'calendar-plus' ) ]                            = $records[ $thumbnail ]['attachment_url'] ?? __( 'None supplied', 'calendar-plus' );
			$fields[ __( 'Files and media referenced in description', 'calendar-plus' ) ] = implode( "\n", Calendar_Plus_TEC_Media::urls( $post['post_content'] . ' ' . $post['post_excerpt'] ) );
			echo '<dl>';
			foreach ( $fields as $label => $value ) {
				echo '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( '' === (string) $value ? __( 'None supplied', 'calendar-plus' ) : $value ) . '</dd>';
			}
			echo '</dl>';
			echo '<p>' . esc_html__( 'Current description', 'calendar-plus' ) . '</p><textarea class="large-text" rows="6" readonly aria-label="' . esc_attr__( 'Current description', 'calendar-plus' ) . '">' . esc_textarea( $current ) . '</textarea>';
			$preview_html = is_wp_error( $proposal ) ? $current : $proposal['content'];
			$allowed_html = array_fill_keys( array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'blockquote', 'a' ), array() );
			echo '<div class="calendarp-tec-body-preview">' . wp_kses( $preview_html, $allowed_html ) . '</div>';
			echo '<p>' . esc_html__( 'Proposed description before media transfer', 'calendar-plus' ) . '</p><textarea class="large-text" rows="6" readonly aria-label="' . esc_attr__( 'Proposed description', 'calendar-plus' ) . '">' . esc_textarea( is_wp_error( $proposal ) ? $current : $proposal['content'] ) . '</textarea></details></td></tr>';
		}
		echo '</tbody></table><p>';
		for ( $index = 0; $index < $pages; ++$index ) {
			echo '<button type="submit" class="button" name="preview_page" value="' . esc_attr( $index ) . '"' . disabled( $index, $page, false ) . '>' . esc_html( $index + 1 ) . '</button> ';
		}
		echo '</p>';
	}
}
