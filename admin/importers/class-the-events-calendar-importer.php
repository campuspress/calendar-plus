<?php

include_once 'parsers.php';

class Calendar_Plus_The_Events_Calendar_Importer extends WP_Importer {

	public $max_wxr_version = 1.2;
	public $id = 0;
	public $fetch_attachments = true;

	public function dispatch() {
		$step = ! empty( $_GET['step'] ) ? absint( $_GET['step'] ) : 0;

		?>
		<div class="wrap">

		</div>
		<?php

		$this->header();

		$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
		switch ( $step ) {
			case 0:
				$this->greet();
				break;
			case 1:
				check_admin_referer( 'import-upload' );
				if ( $this->handle_upload() ) {
					$this->import_options();
				}
				break;
			case 2:
				check_admin_referer( 'import-calendarp-the-events-calendar' );
				$this->fetch_attachments = ( ! empty( $_POST['fetch_attachments'] ) && $this->allow_fetch_attachments() );
				$this->id = (int) $_POST['import_id'];
				$file = get_attached_file( $this->id );
				set_time_limit( 0 );
				$this->import( $file );
				break;
		}

		$this->footer();
	}

	private function header() {
		?>
		<div class="wrap">
		<h2><?php _e( 'Import data from The Events Calendar Plugin to Calendar+', 'calendar-plus' ); ?></h2>
		<?php
	}

	private function footer() {
		?>
		</div>
		<?php
	}

	private function greet() {
		echo '<div class="narrow">';
		echo '<p>' . __( 'Howdy! Upload your WordPress eXtended RSS (WXR) file and we&#8217;ll import the events into this site.', 'calendar-plus' ) . '</p>';
		echo '<p>' . __( 'Choose a WXR (.xml) file to upload, then click Upload file and import.', 'calendar-plus' ) . '</p>';
		wp_import_upload_form( 'admin.php?import=calendar-plus-the-events-calendar&amp;step=1' );
		echo '</div>';
	}

	private function handle_upload() {
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'calendar-plus' ) . '</strong><br />';
			echo esc_html( $file['error'] ) . '</p>';

			return false;
		} elseif ( ! file_exists( $file['file'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'calendar-plus' ) . '</strong><br />';
			printf( __( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'calendar-plus' ), esc_html( $file['file'] ) );
			echo '</p>';

			return false;
		}

		$this->id = (int) $file['id'];
		$import_data = $this->parse( $file['file'] );
		if ( is_wp_error( $import_data ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'calendar-plus' ) . '</strong><br />';
			echo esc_html( $import_data->get_error_message() ) . '</p>';

			return false;
		}

		$this->version = $import_data['version'];
		if ( $this->version > $this->max_wxr_version ) {
			echo '<div class="error"><p><strong>';
			printf( __( 'This WXR file (version %s) may not be supported by this version of the importer. Please consider updating.', 'calendar-plus' ), esc_html( $import_data['version'] ) );
			echo '</strong></p></div>';
		}

		$this->get_authors_from_import( $import_data );

		return true;
	}

	/**
	 * Parse a WXR file
	 *
	 * @param string $file Path to WXR file for parsing
	 *
	 * @return array Information gathered from the WXR file
	 */
	function parse( $file ) {
		$parser = new WXR_Parser();
		$data = $parser->parse( $file );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Only keep the Events types
		$posts_ids = array();
		$events = array();
		$venues = array();
		if ( ! empty( $data['posts'] ) ) {
			foreach ( $data['posts'] as $post ) {
				if ( 'tribe_events' === $post['post_type'] ) {

					if ( ! empty( $post['terms'] ) ) {
						$post_terms = array();
						foreach ( $post['terms'] as $post_term ) {
							if ( 'tribe_events_cat' === $post_term['domain'] ) {
								$post_term['domain'] = 'calendar_event_category';
								$post_terms[] = $post_term;
							}
						}
						$post['terms'] = $post_terms;
					}

					$post['post_type'] = 'calendar_event';
					$events[] = $post;
					$events_ids[] = $post['post_id'];
				}
				if ( 'tribe_venue' === $post['post_type'] ) {
					$post['post_type'] = 'calendar_location';
					$venues[] = $post;
					$posts_ids[] = $post['post_id'];
				}
			}
		}

		// Only keep attachments whose parents are Events
		if ( ! empty( $data['posts'] ) ) {
			$attachments = array();
			foreach ( $data['posts'] as $post ) {
				if ( 'attachment' === $post['post_type'] && in_array( $post['post_parent'], $events_ids ) ) {
					$attachments[] = $post;
				}
			}
		}

		$terms = array();
		if ( ! empty( $data['terms'] ) ) {
			foreach ( $data['terms'] as $term ) {
				if ( 'tribe_events_cat' === $term['term_taxonomy'] ) {
					$term['term_taxonomy'] = 'calendar_event_category';
					$terms[] = $term;
				}
			}
		}

		$data['posts'] = array_merge( $events, $venues, $attachments );
		$data['terms'] = $terms;

		// No need of tags/categories
		$data['tags'] = array();
		$data['categories'] = array();

		return $data;

	}

	function get_authors_from_import( $import_data ) {
		foreach ( $import_data['posts'] as $post ) {
			$login = sanitize_user( $post['post_author'], true );
			if ( empty( $login ) ) {
				printf( __( 'Failed to import author %s. Their posts will be attributed to the current user.', 'calendar-plus' ), esc_html( $post['post_author'] ) );
				echo '<br />';
				continue;
			}

			if ( ! isset( $this->authors[ $login ] ) ) {
				$this->authors[ $login ] = array(
					'author_login'        => $login,
					'author_display_name' => $post['post_author'],
				);
			}
		}
	}

	function import_options() {
		$j = 0;
		?>
		<form action="<?php echo admin_url( 'admin.php?import=calendar-plus-the-events-calendar&amp;step=2' ); ?>" method="post">
			<?php wp_nonce_field( 'import-calendarp-the-events-calendar' ); ?>
			<input type="hidden" name="import_id" value="<?php echo $this->id; ?>" />

			<?php if ( ! empty( $this->authors ) ) : ?>
				<h3><?php _e( 'Assign Authors', 'calendar-plus' ); ?></h3>
				<p><?php _e( 'To make it easier for you to edit and save the imported content, you may want to reassign the author of the imported item to an existing user of this site. For example, you may want to import all the entries as <code>admin</code>s entries.', 'calendar-plus' ); ?></p>
				<?php if ( $this->allow_create_users() ) : ?>
					<p><?php printf( __( 'If a new user is created by WordPress, a new password will be randomly generated and the new user&#8217;s role will be set as %s. Manually changing the new user&#8217;s details will be necessary.', 'calendar-plus' ), esc_html( get_option( 'default_role' ) ) ); ?></p>
				<?php endif; ?>
				<ol id="authors">
					<?php foreach ( $this->authors as $author ) : ?>
						<li><?php $this->author_select( $j++, $author ); ?></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<?php if ( $this->allow_fetch_attachments() ) : ?>
				<h3><?php _e( 'Import Attachments', 'calendar-plus' ); ?></h3>
				<p>
					<input type="checkbox" value="1" name="fetch_attachments" id="import-attachments" />
					<label for="import-attachments"><?php _e( 'Download and import file attachments', 'calendar-plus' ); ?></label>
				</p>
			<?php endif; ?>

			<?php submit_button( __( 'Submit', 'calendar-plus' ), 'secondary', '' ); ?>
		</form>
		<?php
	}

	function allow_fetch_attachments() {
		return true;
	}


	function allow_create_users() {
		return true;
	}

	function author_select( $n, $author ) {
		_e( 'Import author:', 'calendar-plus' );
		echo ' <strong>' . esc_html( $author['author_display_name'] );
		if ( '1.0' != $this->version ) {
			echo ' (' . esc_html( $author['author_login'] ) . ')';
		}
		echo '</strong><br />';

		if ( '1.0' != $this->version ) {
			echo '<div style="margin-left:18px">';
		}

		$create_users = $this->allow_create_users();
		if ( $create_users ) {
			if ( '1.0' != $this->version ) {
				_e( 'or create new user with login name:', 'calendar-plus' );
				$value = '';
			} else {
				_e( 'as a new user:', 'calendar-plus' );
				$value = esc_attr( sanitize_user( $author['author_login'], true ) );
			}

			echo ' <input type="text" name="user_new[' . $n . ']" value="' . $value . '" /><br />';
		}

		if ( ! $create_users && '1.0' == $this->version ) {
			_e( 'assign posts to an existing user:', 'calendar-plus' );
		} else {
			_e( 'or assign posts to an existing user:', 'calendar-plus' );
		}
		wp_dropdown_users(
			array(
				'name'            => "user_map[$n]",
				'multi'           => true,
				'show_option_all' => __(
					'- Select -',
					'calendar-plus'
				),
			)
		);
		echo '<input type="hidden" name="imported_authors[' . $n . ']" value="' . esc_attr( $author['author_login'] ) . '" />';

		if ( '1.0' != $this->version ) {
			echo '</div>';
		}
	}

	function import( $file ) {
		add_filter( 'import_post_meta_key', array( $this, 'is_valid_meta_key' ) );
		add_filter( 'http_request_timeout', array( &$this, 'bump_request_timeout' ) );

		$this->import_start( $file );

		$this->get_author_mapping();

		wp_suspend_cache_invalidation( true );
		$this->process_terms();
		$this->process_posts();
		wp_suspend_cache_invalidation( false );

		// update incorrect/missing information in the DB
		//$this->backfill_parents();
		//$this->backfill_attachment_urls();
		//      $this->remap_featured_images();

		$this->import_end();
	}

	public function import_start( $file ) {
		if ( ! is_file( $file ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'calendar-plus' ) . '</strong><br />';
			echo __( 'The file does not exist, please try again.', 'calendar-plus' ) . '</p>';
			$this->footer();
			die();
		}

		$import_data = $this->parse( $file );

		if ( is_wp_error( $import_data ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'calendar-plus' ) . '</strong><br />';
			echo esc_html( $import_data->get_error_message() ) . '</p>';
			$this->footer();
			die();
		}

		$this->version = $import_data['version'];
		$this->get_authors_from_import( $import_data );
		$this->posts = $import_data['posts'];
		$this->terms = $import_data['terms'];
		$this->base_url = esc_url( $import_data['base_url'] );

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );

	}

	function import_end() {
		wp_import_cleanup( $this->id );

		wp_cache_flush();
		foreach ( get_taxonomies() as $tax ) {
			delete_option( "{$tax}_children" );
			_get_term_hierarchy( $tax );
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		echo '<p>' . __( 'All done.', 'calendar-plus' ) . ' <a href="' . admin_url() . '">' . __( 'Have fun!', 'calendar-plus' ) . '</a>' . '</p>';
		echo '<p>' . __( 'Remember to update the passwords and roles of imported users.', 'calendar-plus' ) . '</p>';

	}

	/**
	 * Create new posts based on import information
	 *
	 * Posts marked as having a parent which doesn't exist will become top level items.
	 * Doesn't create a new post if: the post type doesn't exist, the given post ID
	 * is already noted as imported or a post with the same title and date already exists.
	 * Note that new/updated terms, comments and meta are imported for the last of the above.
	 */
	function process_posts() {

		foreach ( $this->posts as $post ) {

			if ( ! post_type_exists( 'calendar_event' ) ) {
				printf(
					__( 'Failed to import &#8220;%1$s&#8221;: Invalid post type %2$s', 'calendar-plus' ),
					esc_html( $post['post_title'] ),
					esc_html( $post['post_type'] )
				);
				echo '<br />';
				continue;
			}

			if ( isset( $this->processed_posts[ $post['post_id'] ] ) && ! empty( $post['post_id'] ) ) {
				continue;
			}

			if ( 'auto-draft' === $post['status'] ) {
				continue;
			}

			$post_type_object = get_post_type_object( $post['post_type'] );

			$post_exists = post_exists( $post['post_title'], '', $post['post_date'] );
			if ( $post_exists && get_post_type( $post_exists ) == $post['post_type'] ) {
				printf( __( '%1$s &#8220;%2$s&#8221; already exists.', 'calendar-plus' ), $post_type_object->labels->singular_name, esc_html( $post['post_title'] ) );
				echo '<br />';
				$comment_post_id = $post_id = $post_exists;
			} else {
				$post_parent = (int) $post['post_parent'];
				if ( $post_parent ) {
					// if we already know the parent, map it to the new local ID
					if ( isset( $this->processed_posts[ $post_parent ] ) ) {
						$post_parent = $this->processed_posts[ $post_parent ];
						// otherwise record the parent for later
					} else {
						$this->post_orphans[ intval( $post['post_id'] ) ] = $post_parent;
						$post_parent = 0;
					}
				}

				// map the post author
				$author = sanitize_user( $post['post_author'], true );
				if ( isset( $this->author_mapping[ $author ] ) ) {
					$author = $this->author_mapping[ $author ];
				} else {
					$author = (int) get_current_user_id();
				}

				$post_content = '';
				if ( ! empty( $post['post_content'] ) ) {
					$post_content = html_entity_decode( $post['post_content'] );
					$post_content = wp_kses_post( $post_content );
				}

				$post_excerpt = '';
				if ( ! empty( $post['post_excerpt'] ) ) {
					$post_excerpt = html_entity_decode( $post['post_excerpt'] );
					$post_excerpt = wp_kses_post( $post_excerpt );
				}

				$postdata = array(
					'import_id'      => $post['post_id'],
					'post_author'    => $author,
					'post_date'      => $post['post_date'],
					'post_date_gmt'  => $post['post_date_gmt'],
					'post_content'   => $post_content,
					'post_excerpt'   => $post_excerpt,
					'post_title'     => $post['post_title'],
					'post_status'    => $post['status'],
					'post_name'      => $post['post_name'],
					'comment_status' => $post['comment_status'],
					'ping_status'    => $post['ping_status'],
					'guid'           => $post['guid'],
					'post_parent'    => $post_parent,
					'menu_order'     => $post['menu_order'],
					'post_type'      => $post['post_type'],
					'post_password'  => $post['post_password'],
				);

				$original_post_id = $post['post_id'];

				if ( 'attachment' == $postdata['post_type'] ) {
					$remote_url = ! empty( $post['attachment_url'] ) ? $post['attachment_url'] : $post['guid'];

					// try to use _wp_attached file for upload folder placement to ensure the same location as the export site
					// e.g. location is 2003/05/image.jpg but the attachment post_date is 2010/09, see media_handle_upload()
					$postdata['upload_date'] = $post['post_date'];
					if ( isset( $post['postmeta'] ) ) {
						foreach ( $post['postmeta'] as $meta ) {
							if ( '_wp_attached_file' === $meta['key'] ) {
								if ( preg_match( '%^[0-9]{4}/[0-9]{2}%', $meta['value'], $matches ) ) {
									$postdata['upload_date'] = $matches[0];
								}
								break;
							}
						}
					}

					$comment_post_id = $post_id = $this->process_attachment( $postdata, $remote_url );
				} else {
					$comment_post_id = $post_id = wp_insert_post( $postdata, true );
				}

				if ( is_wp_error( $post_id ) ) {
					printf(
						__( 'Failed to import %1$s &#8220;%2$s&#8221;', 'calendar-plus' ),
						$post_type_object->labels->singular_name,
						esc_html( $post['post_title'] )
					);
					if ( defined( 'IMPORT_DEBUG' ) && IMPORT_DEBUG ) {
						echo ': ' . $post_id->get_error_message();
					}
					echo '<br />';
					continue;
				}

				if ( 1 == $post['is_sticky'] ) {
					stick_post( $post_id );
				}
			}

			// map pre-import ID to local ID
			$this->processed_posts[ intval( $post['post_id'] ) ] = (int) $post_id;

			if ( ! isset( $post['terms'] ) ) {
				$post['terms'] = array();
			}

			// add categories, tags and other terms
			if ( ! empty( $post['terms'] ) ) {
				$terms_to_set = array();
				foreach ( $post['terms'] as $term ) {
					// back compat with WXR 1.0 map 'tag' to 'post_tag'
					$taxonomy = ( 'tag' == $term['domain'] ) ? 'post_tag' : $term['domain'];
					$term_exists = term_exists( $term['slug'], $taxonomy );
					$term_id = is_array( $term_exists ) ? $term_exists['term_id'] : $term_exists;
					if ( ! $term_id ) {
						$t = wp_insert_term( $term['name'], $taxonomy, array( 'slug' => $term['slug'] ) );
						if ( ! is_wp_error( $t ) ) {
							$term_id = $t['term_id'];
						} else {
							printf( __( 'Failed to import %1$s %2$s', 'calendar-plus' ), esc_html( $taxonomy ), esc_html( $term['name'] ) );
							if ( defined( 'IMPORT_DEBUG' ) && IMPORT_DEBUG ) {
								echo ': ' . $t->get_error_message();
							}
							echo '<br />';
							continue;
						}
					}
					$terms_to_set[ $taxonomy ][] = intval( $term_id );
				}

				foreach ( $terms_to_set as $tax => $ids ) {
					$tt_ids = wp_set_post_terms( $post_id, $ids, $tax );
				}
				unset( $post['terms'], $terms_to_set );
			}

			if ( ! isset( $post['comments'] ) ) {
				$post['comments'] = array();
			}

			// add/update comments
			if ( ! empty( $post['comments'] ) ) {
				$num_comments = 0;
				$inserted_comments = array();
				foreach ( $post['comments'] as $comment ) {
					$comment_id = $comment['comment_id'];
					$newcomments[ $comment_id ]['comment_post_ID'] = $comment_post_id;
					$newcomments[ $comment_id ]['comment_author'] = $comment['comment_author'];
					$newcomments[ $comment_id ]['comment_author_email'] = $comment['comment_author_email'];
					$newcomments[ $comment_id ]['comment_author_IP'] = $comment['comment_author_IP'];
					$newcomments[ $comment_id ]['comment_author_url'] = $comment['comment_author_url'];
					$newcomments[ $comment_id ]['comment_date'] = $comment['comment_date'];
					$newcomments[ $comment_id ]['comment_date_gmt'] = $comment['comment_date_gmt'];
					$newcomments[ $comment_id ]['comment_content'] = $comment['comment_content'];
					$newcomments[ $comment_id ]['comment_approved'] = $comment['comment_approved'];
					$newcomments[ $comment_id ]['comment_type'] = $comment['comment_type'];
					$newcomments[ $comment_id ]['comment_parent'] = $comment['comment_parent'];
					$newcomments[ $comment_id ]['commentmeta'] = isset( $comment['commentmeta'] ) ? $comment['commentmeta'] : array();
					if ( isset( $this->processed_authors[ $comment['comment_user_id'] ] ) ) {
						$newcomments[ $comment_id ]['user_id'] = $this->processed_authors[ $comment['comment_user_id'] ];
					}
				}
				ksort( $newcomments );

				foreach ( $newcomments as $key => $comment ) {
					// if this is a new post we can skip the comment_exists() check
					if ( ! $post_exists || ! comment_exists( $comment['comment_author'], $comment['comment_date'] ) ) {
						if ( isset( $inserted_comments[ $comment['comment_parent'] ] ) ) {
							$comment['comment_parent'] = $inserted_comments[ $comment['comment_parent'] ];
						}
						$comment = wp_filter_comment( $comment );
						$inserted_comments[ $key ] = wp_insert_comment( $comment );

						foreach ( $comment['commentmeta'] as $meta ) {
							$value = maybe_unserialize( $meta['value'] );
							add_comment_meta( $inserted_comments[ $key ], $meta['key'], $value );
						}

						$num_comments++;
					}
				}
				unset( $newcomments, $inserted_comments, $post['comments'] );
			}

			// CHANGE THE META!!!!

			if ( ! isset( $post['postmeta'] ) ) {
				$post['postmeta'] = array();
			}

			// add/update post meta
			if ( ! empty( $post['postmeta'] ) ) {

				$event_details = array();

				$date_start = false;
				$date_end = false;
				$all_day_event = false;
				foreach ( $post['postmeta'] as $meta ) {
					$key = $meta['key'];
					$value = false;

					if ( '_edit_last' == $key ) {
						if ( isset( $this->processed_authors[ intval( $meta['value'] ) ] ) ) {
							$value = $this->processed_authors[ intval( $meta['value'] ) ];
						} else {
							$key = false;
						}
					}

					if ( '_EventStartDate' === $key ) {
						$date_start = $meta['value'];
						continue;
					}

					if ( '_EventEndDate' === $key ) {
						$date_end = $meta['value'];
						continue;
					}

					if ( '_EventVenueID' === $key ) {
						$location_id = absint( $meta['value'] );
						continue;
					}

					if ( '_EventAllDay' === $key ) {
						$all_day_event = 'yes' === $meta['value'] ? true : false;
						continue;
					}

					if ( $key ) {
						// export gets meta straight from the DB so could have a serialized string
						if ( ! $value ) {
							$value = maybe_unserialize( $meta['value'] );
						}

						add_post_meta( $post_id, $key, $value );

						// if the post has a featured image, take note of this in case of remap
						if ( '_thumbnail_id' == $key ) {
							$this->featured_images[ $post_id ] = (int) $value;
						}
					}
				}

				// Process the dates
				if ( ! empty( $date_start ) && ! empty( $date_end ) ) {
					$from_date  = mysql2date( 'Y-m-d', $date_start );
					$until_date = mysql2date( 'Y-m-d', $date_end );

					if ( $from_date != $until_date ) {
						$event_details['recurrence'] = 'datespan';

						$event_details['datespan'] = array(
							'from_date'  => $from_date,
							'until_date' => $until_date,
						);
						if ( ! $all_day_event ) {
							$event_details['datespan']['from_time_hour']    = mysql2date( 'H', $date_start );
							$event_details['datespan']['from_time_minute']  = mysql2date( 'i', $date_start );
							$event_details['datespan']['until_time_hour']   = mysql2date( 'H', $date_end );
							$event_details['datespan']['until_time_minute'] = mysql2date( 'i', $date_end );
						} else {
							$event_details['datespan']['all_day_event'] = true;
						}
					} else {
						$event_details = array(
							'recurrence' => 'regular',
							'from_date'  => array( $from_date ),
							'until_date' => array( $from_date ),
						);

						if ( ! $all_day_event ) {
							$event_details['from_time_hour']    = array( mysql2date( 'H', $date_start ) );
							$event_details['from_time_minute']  = array( mysql2date( 'i', $date_start ) );
							$event_details['until_time_hour']   = array( mysql2date( 'H', $date_end ) );
							$event_details['until_time_minute'] = array( mysql2date( 'i', $date_end ) );
						} else {
							$event_details['all_day_event'] = true;
						}
					}
				}

				if( $event_details ) {
					calendar_plus_save_event_details( $post_id, $event_details );
				}
			}
		}

		unset( $this->posts );
	}

	/**
	 * If fetching attachments is enabled then attempt to create a new attachment
	 *
	 * @param array  $post Attachment post details from WXR
	 * @param string $url URL to fetch attachment from
	 *
	 * @return int|WP_Error Post ID on success, WP_Error otherwise
	 */
	function process_attachment( $post, $url ) {
		if ( ! $this->fetch_attachments ) {
			return new WP_Error(
				'attachment_processing_error',
				__( 'Fetching attachments is not enabled', 'calendar-plus' )
			);
		}

		// if the URL is absolute, but does not contain address, then upload it assuming base_site_url
		if ( preg_match( '|^/[\w\W]+$|', $url ) ) {
			$url = rtrim( $this->base_url, '/' ) . $url;
		}

		$upload = $this->fetch_remote_file( $url, $post );
		if ( is_wp_error( $upload ) ) {
			return $upload;
		}

		if ( $info = wp_check_filetype( $upload['file'] ) ) {
			$post['post_mime_type'] = $info['type'];
		} else {
			return new WP_Error( 'attachment_processing_error', __( 'Invalid file type', 'calendar-plus' ) );
		}

		$post['guid'] = $upload['url'];

		// as per wp-admin/includes/upload.php
		$post_id = wp_insert_attachment( $post, $upload['file'] );
		wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $upload['file'] ) );

		// remap resized image URLs, works by stripping the extension and remapping the URL stub.
		if ( preg_match( '!^image/!', $info['type'] ) ) {
			$parts = pathinfo( $url );
			$name = basename( $parts['basename'], ".{$parts['extension']}" ); // PATHINFO_FILENAME in PHP 5.2

			$parts_new = pathinfo( $upload['url'] );
			$name_new = basename( $parts_new['basename'], ".{$parts_new['extension']}" );

			$this->url_remap[ $parts['dirname'] . '/' . $name ] = $parts_new['dirname'] . '/' . $name_new;
		}

		return $post_id;
	}

	/**
	 * Attempt to download a remote file attachment
	 *
	 * @param string $url URL of item to fetch
	 * @param array  $post Attachment details
	 *
	 * @return array|WP_Error Local file location details on success, WP_Error otherwise
	 */
	function fetch_remote_file( $url, $post ) {
		// extract the file name and extension from the url
		$file_name = basename( $url );

		// get placeholder file in the upload dir with a unique, sanitized filename
		$upload = wp_upload_bits( $file_name, 0, '', $post['upload_date'] );
		if ( $upload['error'] ) {
			return new WP_Error( 'upload_dir_error', $upload['error'] );
		}

		// fetch the remote url and write it to the placeholder file
		$headers = wp_get_http( $url, $upload['file'] );

		// request failed
		if ( ! $headers ) {
			@unlink( $upload['file'] );

			return new WP_Error( 'import_file_error', __( 'Remote server did not respond', 'calendar-plus' ) );
		}

		// make sure the fetch was successful
		if ( '200' != $headers['response'] ) {
			@unlink( $upload['file'] );

			return new WP_Error( 'import_file_error', sprintf( __( 'Remote server returned error response %1$d %2$s', 'calendar-plus' ), esc_html( $headers['response'] ), get_status_header_desc( $headers['response'] ) ) );
		}

		$filesize = filesize( $upload['file'] );

		if ( isset( $headers['content-length'] ) && $filesize != $headers['content-length'] ) {
			@unlink( $upload['file'] );

			return new WP_Error( 'import_file_error', __( 'Remote file is incorrect size', 'calendar-plus' ) );
		}

		if ( 0 == $filesize ) {
			@unlink( $upload['file'] );

			return new WP_Error( 'import_file_error', __( 'Zero size file downloaded', 'calendar-plus' ) );
		}

		$max_size = (int) $this->max_attachment_size();
		if ( ! empty( $max_size ) && $filesize > $max_size ) {
			@unlink( $upload['file'] );

			return new WP_Error( 'import_file_error', sprintf( __( 'Remote file is too large, limit is %s', 'calendar-plus' ), size_format( $max_size ) ) );
		}

		// keep track of the old and new urls so we can substitute them later
		$this->url_remap[ $url ] = $upload['url'];
		$this->url_remap[ $post['guid'] ] = $upload['url']; // r13735, really needed?
		// keep track of the destination if the remote url is redirected somewhere else
		if ( isset( $headers['x-final-location'] ) && $headers['x-final-location'] != $url ) {
			$this->url_remap[ $headers['x-final-location'] ] = $upload['url'];
		}

		return $upload;
	}

	/**
	 * Map old author logins to local user IDs based on decisions made
	 * in import options form. Can map to an existing user, create a new user
	 * or falls back to the current user in case of error with either of the previous
	 */
	function get_author_mapping() {
		if ( ! isset( $_POST['imported_authors'] ) ) {
			return;
		}

		$create_users = $this->allow_create_users();

		foreach ( (array) $_POST['imported_authors'] as $i => $old_login ) {
			// Multisite adds strtolower to sanitize_user. Need to sanitize here to stop breakage in process_posts.
			$santized_old_login = sanitize_user( $old_login, true );
			$old_id = isset( $this->authors[ $old_login ]['author_id'] ) ? intval( $this->authors[ $old_login ]['author_id'] ) : false;

			if ( ! empty( $_POST['user_map'][ $i ] ) ) {
				$user = get_userdata( intval( $_POST['user_map'][ $i ] ) );
				if ( isset( $user->ID ) ) {
					if ( $old_id ) {
						$this->processed_authors[ $old_id ] = $user->ID;
					}
					$this->author_mapping[ $santized_old_login ] = $user->ID;
				}
			} elseif ( $create_users ) {
				if ( ! empty( $_POST['user_new'][ $i ] ) ) {
					$user_id = wp_create_user( $_POST['user_new'][ $i ], wp_generate_password() );
				} elseif ( '1.0' != $this->version ) {
					$user_data = array(
						'user_login'   => $old_login,
						'user_pass'    => wp_generate_password(),
						'user_email'   => isset( $this->authors[ $old_login ]['author_email'] ) ? $this->authors[ $old_login ]['author_email'] : '',
						'display_name' => $this->authors[ $old_login ]['author_display_name'],
						'first_name'   => isset( $this->authors[ $old_login ]['author_first_name'] ) ? $this->authors[ $old_login ]['author_first_name'] : '',
						'last_name'    => isset( $this->authors[ $old_login ]['author_last_name'] ) ? $this->authors[ $old_login ]['author_last_name'] : '',
					);
					$user_id = wp_insert_user( $user_data );
				}

				if ( ! is_wp_error( $user_id ) ) {
					if ( $old_id ) {
						$this->processed_authors[ $old_id ] = $user_id;
					}
					$this->author_mapping[ $santized_old_login ] = $user_id;
				} else {
					printf( __( 'Failed to create new user for %s. Their posts will be attributed to the current user.', 'calendar-plus' ), esc_html( $this->authors[ $old_login ]['author_display_name'] ) );
					if ( defined( 'IMPORT_DEBUG' ) && IMPORT_DEBUG ) {
						echo ' ' . $user_id->get_error_message();
					}
					echo '<br />';
				}
			}

			// failsafe: if the user_id was invalid, default to the current user
			if ( ! isset( $this->author_mapping[ $santized_old_login ] ) ) {
				if ( $old_id ) {
					$this->processed_authors[ $old_id ] = (int) get_current_user_id();
				}
				$this->author_mapping[ $santized_old_login ] = (int) get_current_user_id();
			}
		}
	}

	/**
	 * Create new terms based on import information
	 *
	 * Doesn't create a term its slug already exists
	 */
	function process_terms() {
		if ( empty( $this->terms ) ) {
			return;
		}

		foreach ( $this->terms as $term ) {
			// if the term already exists in the correct taxonomy leave it alone
			$term_id = term_exists( $term['slug'], $term['term_taxonomy'] );
			if ( $term_id ) {
				if ( is_array( $term_id ) ) {
					$term_id = $term_id['term_id'];
				}
				if ( isset( $term['term_id'] ) ) {
					$this->processed_terms[ intval( $term['term_id'] ) ] = (int) $term_id;
				}
				continue;
			}

			if ( empty( $term['term_parent'] ) ) {
				$parent = 0;
			} else {
				$parent = term_exists( $term['term_parent'], $term['term_taxonomy'] );
				if ( is_array( $parent ) ) {
					$parent = $parent['term_id'];
				}
			}
			$description = isset( $term['term_description'] ) ? $term['term_description'] : '';
			$termarr     = array(
				'slug'        => $term['slug'],
				'description' => $description,
				'parent'      => intval( $parent ),
			);

			$id = wp_insert_term( $term['term_name'], $term['term_taxonomy'], $termarr );
			if ( ! is_wp_error( $id ) ) {

				if ( isset( $term['term_id'] ) ) {
					$this->processed_terms[ intval( $term['term_id'] ) ] = $id['term_id'];
				}
			} else {
				printf( __( 'Failed to import %1$s %2$s', 'calendar-plus' ), esc_html( $term['term_taxonomy'] ), esc_html( $term['term_name'] ) );
				if ( defined( 'IMPORT_DEBUG' ) && IMPORT_DEBUG ) {
					echo ': ' . $id->get_error_message();
				}
				echo '<br />';
				continue;
			}
		}

		unset( $this->terms );
	}

	/**
	 * Decide what the maximum file size for downloaded attachments is.
	 * Default is 0 (unlimited), can be filtered via import_attachment_size_limit
	 *
	 * @return int Maximum attachment file size to import
	 */
	function max_attachment_size() {
		return 0;
	}

}
