<?php

$nonce_action = calendarp_get_settings_slug() . '_import';

$settings_prefix = calendarp_get_settings_slug();
$settings = calendarp_get_settings();

?>

<h2><?php esc_html_e( 'Import iCal File', 'calendar-plus' ); ?></h2>

<p><?php esc_html_e( 'Import events from an iCal (.ics) file.', 'calendar-plus' ); ?></p>

<form action="<?php echo self_admin_url( 'options.php' ); ?>" method="post" enctype="multipart/form-data">
	<?php settings_fields( calendarp_get_settings_slug() ); ?>
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="ical-file"><?php _e( 'File (.ics)', 'calendar-plus' ); ?></label>
			</th>
			<td>
				<input type="file" name="ical-file" id="ical-file">
				<p class="description"><?php _e( 'Events are imported based on their UID fields. If the same UID already exists in Calendar+, the event will be updated instead of created again.', 'calendarp' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label><?php esc_html_e( 'Options', 'calendar-plus' ); ?></label>
			</th>
			<td>
				<label for="import-recurring">
					<input type="checkbox"
						name="<?php echo esc_attr( $settings_prefix ); ?>[import-recurring]"
						id="import-recurring" value="1" />
					<?php _e( 'Import recurring events?', 'calendar-plus' ); ?>
				</label>
				<p class="description"><?php _e( 'By default, only the first recurring event instance will be imported. Selecting this option will import all recurring events as dedicated event instances.', 'calendarp' ); ?></p>
			</td>
		</tr>
	</table>

	<?php submit_button( esc_html__( 'Upload file and import', 'calendar-plus' ), 'primary', $settings_prefix . '[submit-ical-import]' ); ?>
</form>

<h2><?php esc_html_e( 'Synchronise Remote Feeds', 'calendar-plus' ); ?></h2>

<p><?php esc_html_e( 'Monitor one or more iCal or RSS feeds, adding new events to the calendar and updating existing events when the feeds are updated.', 'calendar-plus' ); ?></p>

<div id="col-container" class="wp-clearfix">
	<div id="col-left">
		<div class="col-wrap">
			<?php

			$users = get_users();
			$categories = get_terms( [ 'taxonomy' => 'calendar_event_category', 'hide_empty' => false ] );
			$current_user = wp_get_current_user();

			if ( false !== ( $feeds = get_option( 'calendar_plus_ical_feeds', false ) ) ) {
				delete_option( 'calendar_plus_ical_feeds' );
				add_option( 'calendar_plus_remote_feeds', $feeds );
			}

			$feeds = get_option( 'calendar_plus_remote_feeds', array() );

			$current_feed = array(
				'name'           => '',
				'source'         => '',
				'type'           => '',
				'author'         => 0,
				'category'       => 0,
				'status'         => '',
				'exclude_past'   => '',
				'keep_updated'  => '',
			);

			if ( isset( $_GET['edit_remote_feed'] ) && is_numeric( $_GET['edit_remote_feed'] ) ) {
				$feed_id = intval( $_GET['edit_remote_feed'] );

				if ( isset( $feeds[ $feed_id ] ) ) {
					$current_feed = array_merge( $current_feed, $feeds[ $feed_id ] );
					$current_feed['id'] = $feed_id;
				}
			}

			?>

			<h2><?php

				if ( isset( $current_feed['id'] ) ) {
					esc_html_e( 'Edit Feed', 'calendar-plus' );
					printf(
						' <a href="%s" class="page-title-action" style="float: right;">%s</a>',
						esc_url( remove_query_arg( 'edit_remote_feed' ) ),
						esc_html__( 'Cancel', 'calendar-plus' )
					);
				} else {
					esc_html_e( 'Add New Feed', 'calendar-plus' );
				}
				?></h2>

			<form method="post" action="<?php echo esc_url( self_admin_url( 'options.php' ) ); ?>">
				<?php

				settings_fields( calendarp_get_settings_slug() );

				if ( isset( $current_feed['id'] ) ) {
					printf( '<input type="hidden" name="remote_feed_id" value="%d">', $current_feed['id'] );
				}

				?>

				<table class="form-table">
					<tbody>
					<tr>
						<th>
							<label for="remote-feed-name"><?php esc_html_e( 'Name', 'calendar-plus' ); ?></label>
						</th>
						<td>
							<input name="remote_feed[name]" id="remote-feed-name" type="text" size="40"
							       value="<?php echo esc_attr( $current_feed['name'] ); ?>">
							<p class="description">
								<?php esc_html_e( 'Enter a memorable label for this feed.', 'calendar-plus' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="remote-feed-source">
								<?php esc_html_e( 'Source', 'calendar-plus' ); ?>
							</label>
						</th>
						<td>
							<input name="remote_feed[source]" id="remote-feed-source" type="url" size="40"
							       value="<?php echo esc_attr( $current_feed['source'] ); ?>" required="required">
							<p class="description">
								<?php esc_html_e( 'Enter the full URL to the remote feed.', 'calendar-plus' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="remote-feed-type">
								<?php esc_html_e( 'Type', 'calendar-plus' ); ?>
							</label>
						</th>
						<td>
							<select id="remote-feed-type" name="remote_feed[type]" required="required">
								<option value="ical"<?php checked( $current_feed['type'], 'ical' ); ?>>
									<?php esc_html_e( 'iCal (.ics)', 'calendar-plus' ); ?>
								</option>
								<option value="rss"<?php checked( $current_feed['type'], 'rss' ); ?>>
									<?php esc_html_e( 'RSS (.rss)', 'calendar-plus' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Choose whether the source is an iCal or RSS feed.', 'calendar-plus' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="remote-feed-author"><?php esc_html_e( 'Author', 'calendar-plus' ); ?></label>
						</th>
						<td>
							<select id="remote-feed-author" name="remote_feed[author]">
								<?php
								$current_author = $current_feed['author'] ? $current_feed['author'] : $current_user->ID;

								/** @var WP_User $user */
								foreach ( $users as $user ) {
									printf(
										'<option value="%d"%s>%s</option>',
										$user->ID,
										selected( $user->ID, $current_author, false ),
										esc_html( $user->display_name )
									);
								}
								?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Assign all imported events to this user.', 'calendar-plus' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="remote-feed-category">
								<?php esc_html_e( 'Category', 'calendar-plus' ); ?>
							</label>
						<td>
							<select id="remote-feed-category" name="remote_feed[category]">
								<option value="0" selected="selected">
									<?php esc_html_e( 'none', 'calendar-plus' ); ?>
								</option>

								<?php
								/** @var WP_Term $category */
								foreach ( $categories as $category ) {
									printf(
										'<option value="%d"%s>%s</option>',
										$category->term_id,
										selected( $category->term_id, $current_feed['category'], false ),
										esc_html( $category->name )
									);
								}
								?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Assign all imported events to this category.', 'calendar-plus' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="remote-feed-status">
								<?php esc_html_e( 'Publish Events', 'calendar-plus' ); ?>
							</label>
						<td>
							<select id="remote-feed-status" name="remote_feed[status]">
								<?php

								$options = array(
									'publish' => __( 'Publish Immediately', 'calendar-plus' ),
									'draft'   => __( 'Save as Draft', 'calendar-plus' ),
									'pending' => __( 'Pending Review', 'calendar-plus' ),
								);

								foreach ( $options as $option => $label ) {
									printf( '<option value="%s"%s>%s</option>',
										esc_attr( $option ),
										selected( $option, $current_feed['status'], false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Decide what publication status newly-created events should have.', 'calendar-plus' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="remote-feed-exclude-past">
								<?php esc_html_e( 'Exclude Past Events', 'calendar-plus' ); ?>
							</label>
						<td>
							<select id="remote-feed-exclude-past" name="remote_feed[exclude_past]">
								<?php

								$options = array(
									''   => __( 'No', 'calendar-plus' ),
									'-1' => __( 'Yes', 'calendar-plus' ),
								);

								foreach ( $options as $option => $label ) {
									printf( '<option value="%s"%s>%s</option>',
										esc_attr( $option ),
										selected( $option, $current_feed['exclude_past'], false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Decide if past events should be excluded from import.', 'calendar-plus' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="remote-feed-keep-updated">
								<?php esc_html_e( 'Keep Events Updated', 'calendar-plus' ); ?>
							</label>
						<td>
							<select id="remote-feed-keep-updated" name="remote_feed[keep_updated]">
								<?php
								if( empty( $current_feed['keep_updated'] ) ) {
									$current_feed['keep_updated'] = $current_feed['type'] === 'rss' ? '0' : '1';
								}
								

								$options = array(
									'0'   => __( 'No', 'calendar-plus' ),
									'1' => __( 'Yes', 'calendar-plus' ),
								);

								foreach ( $options as $option => $label ) {
									printf( '<option value="%s"%s>%s</option>',
										esc_attr( $option ),
										selected( $option, $current_feed['keep_updated'], false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Decide if synced events should be updated after the change in feed.', 'calendar-plus' ); ?>
							</p>
						</td>
					</tr>
					</tbody>
				</table>

				<?php submit_button(
					isset( $current_feed['id'] ) ? __( 'Update', 'calendar-plus' ) : __( 'Add new feed', 'calendar-plus' ),
					'primary', $settings_prefix . '[submit-add-remote-feed]'
				); ?>

			</form>
		</div>
	</div>
	<div id="col-right">
		<div class="col-wrap">
			<h2><?php esc_html_e( 'Active Feeds', 'calendar-plus' ); ?></h2>

			<table class="wp-list-table widefat striped fixed">
				<thead>
				<tr>
					<?php

					$columns = array(
						__( 'Feed Information', 'calendar-plus' ),
						__( 'Last Synchronised', 'calendar-plus' ),
						__( 'Feed Source', 'calendar-plus' ),
					);

					foreach ( $columns as $column ) {
						printf( '<th scope="col" class="manage-column">%s</th>', esc_html( $column ) );
					}

					?>
				</tr>
				</thead>
				<tbody>
				<?php
				if ( empty( $feeds ) ) {
					?>
					<tr>
						<td colspan="<?php echo count( $columns ); ?>">
							<?php esc_html_e( 'There are no active feeds.', 'calendar-plus' ); ?>
						</td>
					</tr>
					<?php
				} else {
					foreach ( $feeds as $i => $feed ) {
						$delete_url = add_query_arg( [ 'delete_remote_feed' => $i, '_wpnonce' => wp_create_nonce( $nonce_action ) ] );

						?>

						<tr>
							<td>
								<div class="row-actions alignright">
									<span class="edit">
										<a href="<?php echo esc_url( add_query_arg( 'edit_remote_feed', $i ) ); ?>"><?php
											esc_html_e( 'Edit', 'calendar-plus' ); ?>
										</a>
									</span>
									|
									<span class="delete">
										<a href="<?php echo esc_url( $delete_url ); ?>"><?php
											esc_html_e( 'Delete', 'calendar-plus' ); ?>
										</a>
									</span>
								</div>

								<strong><?php echo esc_html( $feed['name'] ); ?></strong>

								<p class="ical-feed-meta-info">
									<?php
									$feed['status'] = isset( $feed['status'] ) ? $feed['status'] : 'publish';

									if ( 'draft' === $feed['status'] ) {
										$strings = array(
											__( 'Save events as <span>drafts</span>.', 'calendar-plus' ),
											/* translators: %s: author display name */
											__( 'Save events as <span>drafts</span> on behalf of <span>%s</span>.', 'calendar-plus' ),
											/* translators: 1: category name, 2: author display name */
											__( 'Save events as <span>drafts</span> in the <span>%1$s</span> category on behalf of <span>%2$s</span>.', 'calendar-plus' ),
										);

									} elseif ( 'pending' === $feed['status'] ) {
										$strings = array(
											__( 'Save events as <span>pending review</span>.', 'calendar-plus' ),
											/* translators: %s: author display name */
											__( 'Save events as <span>pending review</span> on behalf of <span>%s</span>.', 'calendar-plus' ),
											/* translators: 1: author display name, 2: category name */
											__( 'Save events as <span>pending review</span> in the <span>%1$s</span> category on behalf of <span>%2$s</span>.', 'calendar-plus' ),
										);

									} else {
										$strings = array(
											__( 'Publish events <span>immediately</span>.', 'calendar-plus' ),
											/* translators: %s: author display name */
											__( 'Publish events <span>immediately</span> on behalf of <span>%s</span>.', 'calendar-plus' ),
											/* translators: 1: author display name, 2: category name */
											__( 'Publish events <span>immediately</span> in the <span>%1$s</span> category on behalf of <span>%2$s</span>.', 'calendar-plus' ),
										);
									}

									$author = get_user_by( 'ID', $feed['author'] );
									$category = '';

									if ( $feed['category'] ) {

										/** @var WP_Term $event_category */
										foreach ( $categories as $event_category ) {
											if ( $event_category->term_id === $feed['category'] ) {
												$category = $event_category->name;
												break;
											}
										}
									}

									if ( $category && $author ) {
										$string = sprintf( $strings[2], $event_category->name, $author->display_name );
									} elseif ( $author ) {
										$string = sprintf( $strings[1], $author->display_name );
									} else {
										$string = $strings[0];
									}

									echo wp_kses( $string, [ 'span' => [] ] );

									?>
								</p>

							</td>
							<td>
								<p><?php

									$last_sync = $feed['last_sync'];

									if ( empty( $last_sync ) ) {
										esc_html_e( 'This feed has not been synchronised yet.', 'calendar-plus' );
									} else {

										switch ( $last_sync['status'] ) {
											case 'request_error':
												esc_html_e( 'There was an error retrieving the feed from the source', 'calendar-plus' );
												break;
											case 'import_error':
												esc_html_e( 'There was an error parsing the feed', 'calendar-plus' );
												break;
											case 'error':
											case 'request_error':
											case 'parse_error':
												esc_html_e( 'There was an error fetching and parsing the feed', 'calendar-plus' );
												break;
											case 'complete_empty':
												esc_html_e( 'The feed contained no events', 'calendar-plus' );
												break;
											case 'complete':
												/* translators: amount of events */
												printf( esc_html__( 'Successfully imported %d events', 'calendar-plus' ),
													is_array( $last_sync['events'] ) ? count( $last_sync['events'] ) : $last_sync['events']
												);
										}

										echo '<br>';

										/* translators: 1: formatted time, 2: formatted date */
										printf( _x( 'at %1$s on %2$s.', 'time and date sep', 'calendar-plus' ),
											date_i18n( get_option( 'time_format' ), $last_sync['time'] ),
											date_i18n( get_option( 'date_format' ), $last_sync['time'] )
										);
									}

									?></p>
							</td>
							<td><?php echo esc_url( $feed['source'] ); ?></td>
						</tr>

						<?php
					}
				}

				?>
				</tbody>
			</table>

			<form method="post" action="<?php echo esc_attr( self_admin_url( 'options.php' ) ); ?>">
				<?php

				settings_fields( calendarp_get_settings_slug() );
				if ( isset( $settings['feed_sync_interval'] ) ) {
					$current_interval = $settings['feed_sync_interval'];
				} elseif ( isset( $settings['ical_sync_interval'] ) ) {
					$current_interval = $settings['ical_sync_interval'];
				} else {
					$current_interval = 'hourly';
				}

				$interval_options = array(
					'hourly'     => __( 'every hour', 'calendar-plus' ),
					'twicedaily' => __( 'twice every day', 'calendar-plus' ),
					'daily'      => __( 'once every day', 'calendar-plus' ),
				);

				?>

				<p class="alignright">
					<label for="feed-sync-interval">
						<?php esc_html_e( 'Check for new events', 'calendar-plus' ); ?>
					</label>

					<select id="feed-sync-interval" name="<?php echo $settings_prefix; ?>[feed_sync_interval]">
						<?php foreach ( $interval_options as $interval => $label ) { ?>
							<option value="<?php echo esc_attr( $interval ); ?>"<?php selected( $interval, $current_interval ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php } ?>
					</select>

					<?php
					submit_button(
						esc_html__( 'Update interval settings', 'calendar-plus' ),
						'secondary',
						$settings_prefix . '[submit-feed-sync-interval]',
						false
					);
					?>

				</p>
			</form>
		</div>
	</div>
</div>

