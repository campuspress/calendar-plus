<?php

/**
 * @var $event           Calendar_Plus_Event
 * @var $exclusions      array
 * @var $standard_rules  array
 * @var $recurring_rules array
 * @var $datespan_rules  array
 * @var $calendar        array
 * @var $meta_box_slug   string
 */

$event_type_options = array(
	'regular'   => __( 'Regular event', 'calendar-plus' ),
	'recurrent' => __( 'Recurring event', 'calendar-plus' ),
	'datespan'  => __( 'Multi-day event', 'calendar-plus' ),
);

$duration_options = array( 'from', 'until' );
$time_duration_labels = array( __( 'Starts at', 'calendar-plus' ), __( 'Ends at', 'calendar-plus' ) );
$date_duration_labels = array( __( 'Starts on', 'calendar-plus' ), __( 'Ends on', 'calendar-plus' ) );
$short_time_duration_labels = array( __( 'Starts at', 'calendar-plus' ), _x( 'to', 'time duration sep', 'calendar-plus' ) );

$week_numbers = array(
	1 => __( '1<sup>st</sup>', 'calendar-plus' ),
	2 => __( '2<sup>nd</sup>', 'calendar-plus' ),
	3 => __( '3<sup>rd</sup>', 'calendar-plus' ),
	4 => __( '4<sup>th</sup>', 'calendar-plus' ),
	5 => __( '5<sup>th</sup>', 'calendar-plus' ),
);

$weekdays = array();

/** @var WP_Locale $wp_locale */
global $wp_locale;

for ( $day_index = 1; $day_index <= 7; $day_index++ ) {
	$weekdays[ $day_index ] = $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( 7 === $day_index ? 0 : $day_index ) );
}

?>

<div class="calendarp">

	<select name="event_details[_recurrence]" id="event-recurring">
		<?php
		foreach ( $event_type_options as $option => $label ) {

			printf(
				'<option value="%s"%s>%s</option>',
				$option,
				selected( $option, $event->get_event_type(), false ),
				esc_html( $label )
			);

		}
		?>
	</select>

	<div id="event-dates">
		<?php if ( $calendar ) : ?>
			<p>
				<strong><?php _e( 'Rules', 'calendar-plus' ); ?>:</strong>
				<code><?php echo calendarp_get_human_read_dates( $event->ID ); ?></code>
			</p>
			<p>
				<label for="edit-dates">
					<input type="checkbox" id="edit-dates" name="event_details[edit_dates]" value="true">
					<?php esc_html_e( 'Edit rules', 'calendar-plus' ); ?>

					<span class="description">
						<?php

						/* translators: 1: link to calendar admin menu, 2: title text */
						printf( __( '(if you have set custom dates in <a href="%1$s" title="%2$s">Calendar Page</a> these will be deleted)', 'calendar-plus' ),
							esc_url( admin_url( 'edit.php?post_type=calendar_event&page=calendar-plus-calendar' ) ),
							esc_html__( 'Calendar page', 'calendar-plus' )
						);

						?>
					</span>

				</label>
			</p>
		<?php else : ?>
			<input type="hidden" name="event_details[edit_dates]" value="true">
		<?php endif; ?>

		<div id="event-dates-regular" class="event-dates-option hidden">

			<p>
				<label>
					<input type="checkbox" name="event_details[all_day_event]" id="event-dates-regular-time-all-day-event"<?php checked( $event->is_all_day_event() ); ?>>
					<?php esc_html_e( 'All day event', 'calendar-plus' ); ?>
				</label>
			</p>

			<?php foreach ( $standard_rules as $rule_n => $standard_date ) : ?>

				<div id="event-regular-date-<?php echo $rule_n + 1; ?>" class="event-regular-date-item" style="display: block;">
					<h3>
						<span class="dashicons dashicons-calendar-alt"></span>
						<?php esc_html_e( 'Date', 'calendar-plus' ); ?> |
						<a href="#" class="remove-link remove-regular-event" style="display: none;" data-regular-date-id="<?php echo $rule_n + 1; ?>">
							<?php esc_html_e( 'Remove date', 'calendar-plus' ); ?>
						</a>
					</h3>

					<p>
						<label class="standard-from-date-label"><?php _e( 'Date', 'calendar-plus' ); ?></label>
						<input type="text" class="datepicker" name="event_details[from_date][]"
						       value="<?php echo $standard_date['from_date']; ?>">
					</p>

					<p class="event-regular-time">
						<?php
						foreach ( $duration_options as $i => $option ) {
							?>

							<label class="standard-<?php echo $option; ?>-time-label"><?php echo esc_html( $short_time_duration_labels[ $i ] ); ?></label>
							<?php
							calendarp_time_selector(
								array(
									'selected'     => $standard_date["{$option}_time"],
									'hours_name'   => "event_details[{$option}_time_hour][]",
									'minutes_name' => "event_details[{$option}_time_minute][]",
									'am_pm_name'   => "event_details[{$option}_time_am_pm][]",
								)
							);
						}
						?>
					</p>
				</div>
			<?php endforeach; ?>
			<input type="hidden" name="standard-dates-index" id="standard-dates-index" value="<?php echo count( $standard_rules ); ?>">

			<button class="button" id="add-regular-event"><?php esc_html_e( 'Add new date', 'calendar-plus' ); ?></button>

			<div id="event-regular-date-template">
				<h3>
					<span class="dashicons dashicons-calendar-alt"></span>
					<?php esc_html_e( 'Date', 'calendar-plus' ); ?> |
					<small>
						<a href="#" class="remove-link remove-regular-event">
							<?php esc_html_e( 'Remove date', 'calendar-plus' ); ?>
						</a>
					</small>
				</h3>

				<p>
					<label class="standard-from-date-label"><?php _e( 'Date', 'calendar-plus' ); ?></label>
					<input type="text" class="standard-dates-datepicker" name="event_details[from_date][]" value="">
				</p>

				<p class="event-regular-time">
					<?php
					foreach ( $duration_options as $i => $option ) {
						?>

						<label class="standard-<?php echo $option; ?>-time-label"><?php echo esc_html( $short_time_duration_labels[ $i ] ); ?></label>
						<?php
						calendarp_time_selector(
							array(
								'hours_name'   => "event_details[{$option}_time_hour][]",
								'minutes_name' => "event_details[{$option}_time_minute][]",
								'am_pm_name'   => "event_details[{$option}_time_am_pm][]",
							)
						);
					}
					?>
				</p>
			</div>
		</div>

		<div id="event-dates-recurrent" class="event-dates-option hidden">

			<div id="event-dates-recurrent-dates" class="event-dates-recurrent-subsection">
				<h3>
					<span class="dashicons dashicons-calendar-alt"></span> <?php _e( 'Date range', 'calendar-plus' ); ?>
				</h3>

				<?php
				$labels = array( _x( 'From', 'Details metabox: From date', 'calendar-plus' ), _x( 'To', 'Details metabox: To date', 'calendar-plus' ) );

				foreach ( $duration_options as $i => $option ) {
					?>

					<p>
						<label class="recurring-dates-<?php echo $option; ?>-label" for="recurring-<?php echo $option; ?>-date"><?php echo esc_html( $labels[ $i ] ); ?></label>
						<input type="text" class="datepicker" id="recurring-<?php echo $option; ?>-date" name="event_details[recurring][<?php echo $option; ?>_date]"
						       value="<?php echo esc_attr( $recurring_rules['dates'][ $option ] ); ?>">
					</p>

				<?php } ?>
			</div>

			<div id="event-dates-recurrent-frequency" class="event-dates-recurrent-subsection">
				<h3>
					<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Recurrence', 'calendar-plus' ); ?>
				</h3>

				<?php $every = $recurring_rules['every']['every']; ?>

				<label class="recurring-every-label" for="recurring-frequency-every">
					<?php echo esc_html_x( 'Every', 'Details metabox: Every number of days/weeks', 'calendar-plus' ); ?>
				</label>

				<?php $classes = 'recurring-every-day recurring-every-week recurring-every-month recurring-every-year'; ?>
				<span class="recurring-every-container <?php echo $classes; ?>">
					<input type="number" class="small-text recurring-every-field-<?php echo $classes; ?>" id="recurring-every"
					       name="event_details[recurring][every]" value="<?php echo esc_attr( $every ); ?>">
				</span>

				<span class="recurring-every-container recurring-every-dow">
				<?php foreach ( $weekdays as $day_index => $day_name ) : ?>
					<label>
						<input type="checkbox" class="recurring-every-field recurring-every-field-dow" name="event_details[recurring][every][]"
						       value="<?php echo $day_index; ?>"<?php checked( is_array( $every ) && in_array( $day_index, $every ) ); ?>>
						<?php echo $day_name; ?>
					</label>
				<?php endforeach; ?>
				</span>

				<div class="recurring-every-container recurring-every-dom">
					<table>

						<?php

						foreach ( $week_numbers as $week => $label ) {
							$selected_days = isset( $every[ $week ] ) && is_array( $every[ $week ] ) ? $every[ $week ] : array();

							?>

							<tr>
								<th><?php echo $label; ?></th>

								<?php foreach ( $weekdays as $day_index => $day_name ) { ?>
									<td>
										<label>
											<input type="checkbox" class="recurring-every-field recurring-every-field-dom"
											       name="event_details[recurring][every][<?php echo $week; ?>][]"
											       value="<?php echo $day_index; ?>"
												<?php
												checked( in_array( $day_index, $selected_days ) );
												?>
											>
											<?php
											echo $day_name;
											?>
										</label>
									</td>
								<?php } ?>

							</tr>

						<?php } ?>

					</table>
				</div>


				<select name="event_details[recurring][what]" id="recurring-frequency-every">
					<?php
					$options = array(
						'day'  => __( 'Day(s)', 'calendar-plus' ),
						'week' => __( 'Week(s)', 'calendar-plus' ),
						'year' => __( 'Year(s)', 'calendar-plus' ),
						'dow'  => __( 'Days of the week', 'calendar-plus' ),
						'dom'  => __( 'Days of the month', 'calendar-plus' ),
					);

					foreach ( $options as $option => $label ) {
						?>
						<option value="<?php echo $option; ?>" <?php selected( $recurring_rules['every']['what'], $option ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php } ?>
				</select>
			</div>
			<div id="event-dates-recurrent-time" class="event-dates-recurrent-subsection">
				<h3>
					<span class="dashicons dashicons-clock"></span> <?php _e( 'Time', 'calendar-plus' ); ?>
				</h3>

				<?php foreach ( $duration_options as $i => $option ) { ?>

					<p class="recurring-times-from">
						<label class="recurring-times-<?php echo $option; ?>-label" for="event_details[recurring][<?php echo $option; ?>_time_hour]"><?php echo esc_html( $time_duration_labels[ $i ] ); ?></label>
						<?php
						calendarp_time_selector(
							array(
								'selected'     => $recurring_rules['times'][ $option ],
								'hours_name'   => "event_details[recurring][{$option}_time_hour]",
								'minutes_name' => "event_details[recurring][{$option}_time_minute]",
								'am_pm_name'   => "event_details[recurring][{$option}_time_am_pm]",
							)
						);
						?>
					</p>
				<?php } ?>

				<p>
					<label>
						<input type="checkbox" name="event_details[recurring][all_day_event]" id="event-dates-recurrent-time-all-day-event"<?php checked( $event->is_all_day_event() ); ?>>
						<?php _e( 'All day event', 'calendar-plus' ); ?>
					</label>
				</p>
			</div>

			<div id="event-dates-recurrent-exclusions" class="event-dates-recurrent-subsection">
				<h3>
					<span class="dashicons dashicons-dismiss"></span> <?php _e( 'Excluded dates', 'calendar-plus' ); ?>
				</h3>
				<div id="event-dates-recurrent-exclusions-list">

					<?php foreach ( $exclusions as $i => $exclusion ) : ?>
						<div id="recurring-exclusions-<?php echo $i + 1; ?>" class="recurring-excluded-date">
							<input type="text" class="recurring-exclusions-datepicker datepicker" name="event_details[recurring][exclusions][]" value="<?php echo esc_attr( $exclusion['date'] ); ?>">
							<a href="#" class="remove-link remove-exclusions-date dashicons dashicons-no-alt" data-excluded-date-id="<?php echo $i + 1; ?>"></a>
						</div>
					<?php endforeach; ?>

					<input type="hidden" name="recurring-exclusions-index" id="recurring-exclusions-index" value="<?php echo count( $exclusions ); ?>">
				</div>
				<div id="event-dates-recurrent-exclusions-date-template" class="hidden recurring-excluded-date">
					<input type="text" class="recurring-exclusions-datepicker" name="event_details[recurring][exclusions][]" value="">
					<a href="#" class="remove-link dashicons remove-exclusions-date dashicons-no-alt"></a>
				</div>
				<button class="button" id="event-dates-recurrent-exclusions-add"><?php _e( 'Add excluded date', 'calendar-plus' ); ?></button>
			</div>
		</div>

		<div id="event-dates-datespan" class="event-dates-option hidden">
			<p>
				<label>
					<input type="checkbox" name="event_details[datespan][all_day_event]" id="event-dates-datespan-time-all-day-event"<?php checked( $event->is_all_day_event() ); ?>>
					<?php _e( 'All day event', 'calendar-plus' ); ?>
				</label>
			</p>

			<?php foreach ( $duration_options as $i => $option ) { ?>
				<p>
					<label for="event_details[datespan][<?php echo $option; ?>_date]" class="datespan-<?php echo $option; ?>-date-label"><?php echo esc_html( $date_duration_labels[ $i ] ); ?></label>
					<input type="text" class="datepicker" id="event_details[datespan][<?php echo $option; ?>_date]" name="event_details[datespan][<?php echo $option; ?>_date]"
					       value="<?php echo esc_attr( $datespan_rules["{$option}_date"] ); ?>">
				</p>
			<?php } ?>

			<p class="event-datespan-time">
				<?php
				foreach ( $duration_options as $i => $option ) {
					?>

					<label class="datespan-<?php echo $option; ?>-time-label"><?php echo esc_html( $time_duration_labels[ $i ] ); ?></label>

					<?php
					calendarp_time_selector(
						array(
							'selected'     => $datespan_rules["{$option}_time"],
							'hours_name'   => "event_details[datespan][{$option}_time_hour]",
							'minutes_name' => "event_details[datespan][{$option}_time_minute]",
							'am_pm_name'   => "event_details[datespan][{$option}_time_am_pm]",
						)
					);
				}
				?>
			</p>
		</div>
	</div>
</div>

<?php

do_action( "calendarp_{$meta_box_slug}_meta_box", $event );
wp_nonce_field( "calendarp_{$meta_box_slug}_meta_box", "calendarp_{$meta_box_slug}_nonce" );

?>

<script>
	jQuery(document).ready(function ($) {
		var type = '<?php echo $event->get_event_type(); ?>';
		var args = {
			selector: $('#event-recurring'),
			type: 'general' === type ? 'regular' : type,
			disabled: '<?php echo $calendar ? 1 : 0; ?>'
		};
		CPeventDetailsMetabox = new window.CalendarPlusAdmin.misc.eventDetailsMetabox(args);

		$('.calendarp .datepicker').datepicker({
			dateFormat: 'yy-mm-dd'
		});
	});
</script>
