<?php
/**
 * Calendar Plus: admin/views/event-details-meta-box.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Calendar render context.
 *
 * @var $event           Calendar_Plus_Event
 * @var $exclusions      array
 * @var $standard_rules  array
 * @var $recurring_rules array
 * @var $datespan_rules  array
 * @var $calendar        array
 * @var $meta_box_slug   string
 */

$calendarp_event_type_options = array(
	'regular'   => __( 'Regular event', 'calendar-plus' ),
	'recurrent' => __( 'Recurring event', 'calendar-plus' ),
	'datespan'  => __( 'Multi-day event', 'calendar-plus' ),
);

$calendarp_duration_options           = array( 'from', 'until' );
$calendarp_time_duration_labels       = array( __( 'Starts at', 'calendar-plus' ), __( 'Ends at', 'calendar-plus' ) );
$calendarp_date_duration_labels       = array( __( 'Starts on', 'calendar-plus' ), __( 'Ends on', 'calendar-plus' ) );
$calendarp_short_time_duration_labels = array( __( 'Starts at', 'calendar-plus' ), _x( 'to', 'time duration sep', 'calendar-plus' ) );

$calendarp_week_numbers = array(
	1 => __( '1<sup>st</sup>', 'calendar-plus' ),
	2 => __( '2<sup>nd</sup>', 'calendar-plus' ),
	3 => __( '3<sup>rd</sup>', 'calendar-plus' ),
	4 => __( '4<sup>th</sup>', 'calendar-plus' ),
	5 => __( '5<sup>th</sup>', 'calendar-plus' ),
);

$calendarp_weekdays = array();

/** Calendar render context. @var WP_Locale $wp_locale */
global $wp_locale;

for ( $calendarp_day_index = 1; $calendarp_day_index <= 7; $calendarp_day_index++ ) {
	$calendarp_weekdays[ $calendarp_day_index ] = $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( 7 === $calendarp_day_index ? 0 : $calendarp_day_index ) );
}

?>

<div class="calendarp">

	<select name="event_details[_recurrence]" id="event-recurring">
		<?php
		foreach ( $calendarp_event_type_options as $calendarp_option => $calendarp_label ) {

			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $calendarp_option ),
				selected( $calendarp_option, $event->get_event_type(), false ),
				esc_html( $calendarp_label )
			);

		}
		?>
	</select>

	<div id="event-dates">
		<?php if ( $calendar ) : ?>
			<p>
				<strong><?php esc_html_e( 'Rules', 'calendar-plus' ); ?>:</strong>
				<code><?php echo wp_kses_post( calendarp_get_human_read_dates( $event->ID ) ); ?></code>
			</p>
			<p>
				<label for="edit-dates">
					<input type="checkbox" id="edit-dates" name="event_details[edit_dates]" value="true">
					<?php esc_html_e( 'Edit rules', 'calendar-plus' ); ?>

					<span class="description">
						<?php

						/* translators: 1: link to calendar admin menu, 2: title text */
						printf(
							wp_kses_post( /* translators: 1: calendar admin URL, 2: link title. */ __( '(if you have set custom dates in <a href="%1$s" title="%2$s">Calendar Page</a> these will be deleted)', 'calendar-plus' ) ),
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

			<?php foreach ( $standard_rules as $calendarp_rule_n => $calendarp_standard_date ) : ?>

				<div id="event-regular-date-<?php echo esc_html( $calendarp_rule_n + 1 ); ?>" class="event-regular-date-item" style="display: block;">
					<h3>
						<span class="dashicons dashicons-calendar-alt"></span>
						<?php esc_html_e( 'Date', 'calendar-plus' ); ?> |
						<a href="#" class="remove-link remove-regular-event" style="display: none;" data-regular-date-id="<?php echo esc_attr( $calendarp_rule_n + 1 ); ?>">
							<?php esc_html_e( 'Remove date', 'calendar-plus' ); ?>
						</a>
					</h3>

					<p>
						<label class="standard-from-date-label"><?php esc_html_e( 'Date', 'calendar-plus' ); ?></label>
						<input type="text" class="datepicker" name="event_details[from_date][]"
								value="<?php echo esc_attr( $calendarp_standard_date['from_date'] ); ?>">
					</p>

					<p class="event-regular-time">
						<?php
						foreach ( $calendarp_duration_options as $calendarp_i => $calendarp_option ) {
							?>

							<label class="standard-<?php echo esc_html( $calendarp_option ); ?>-time-label"><?php echo esc_html( $calendarp_short_time_duration_labels[ $calendarp_i ] ); ?></label>
							<?php
							calendarp_time_selector(
								array(
									'selected'     => $calendarp_standard_date[ "{$calendarp_option}_time" ],
									'hours_name'   => "event_details[{$calendarp_option}_time_hour][]",
									'minutes_name' => "event_details[{$calendarp_option}_time_minute][]",
									'am_pm_name'   => "event_details[{$calendarp_option}_time_am_pm][]",
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
					<label class="standard-from-date-label"><?php esc_html_e( 'Date', 'calendar-plus' ); ?></label>
					<input type="text" class="standard-dates-datepicker" name="event_details[from_date][]" value="">
				</p>

				<p class="event-regular-time">
					<?php
					foreach ( $calendarp_duration_options as $calendarp_i => $calendarp_option ) {
						?>

						<label class="standard-<?php echo esc_html( $calendarp_option ); ?>-time-label"><?php echo esc_html( $calendarp_short_time_duration_labels[ $calendarp_i ] ); ?></label>
						<?php
						calendarp_time_selector(
							array(
								'hours_name'   => "event_details[{$calendarp_option}_time_hour][]",
								'minutes_name' => "event_details[{$calendarp_option}_time_minute][]",
								'am_pm_name'   => "event_details[{$calendarp_option}_time_am_pm][]",
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
					<span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'Date range', 'calendar-plus' ); ?>
				</h3>

				<?php
				$calendarp_labels = array( _x( 'From', 'Details metabox: From date', 'calendar-plus' ), _x( 'To', 'Details metabox: To date', 'calendar-plus' ) );

				foreach ( $calendarp_duration_options as $calendarp_i => $calendarp_option ) {
					?>

					<p>
						<label class="recurring-dates-<?php echo esc_html( $calendarp_option ); ?>-label" for="recurring-<?php echo esc_html( $calendarp_option ); ?>-date"><?php echo esc_html( $calendarp_labels[ $calendarp_i ] ); ?></label>
						<input type="text" class="datepicker" id="recurring-<?php echo esc_html( $calendarp_option ); ?>-date" name="event_details[recurring][<?php echo esc_html( $calendarp_option ); ?>_date]"
								value="<?php echo esc_attr( $recurring_rules['dates'][ $calendarp_option ] ); ?>">
					</p>

				<?php } ?>
			</div>

			<div id="event-dates-recurrent-frequency" class="event-dates-recurrent-subsection">
				<h3>
					<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Recurrence', 'calendar-plus' ); ?>
				</h3>

				<?php $calendarp_every = $recurring_rules['every']['every']; ?>

				<label class="recurring-every-label" for="recurring-frequency-every">
					<?php echo esc_html_x( 'Every', 'Details metabox: Every number of days/weeks', 'calendar-plus' ); ?>
				</label>

				<?php $calendarp_classes = 'recurring-every-day recurring-every-week recurring-every-month recurring-every-year'; ?>
				<span class="recurring-every-container <?php echo esc_html( $calendarp_classes ); ?>">
					<input type="number" class="small-text recurring-every-field-<?php echo esc_html( $calendarp_classes ); ?>" id="recurring-every"
							name="event_details[recurring][every]" value="<?php echo esc_attr( $calendarp_every ); ?>">
				</span>

				<span class="recurring-every-container recurring-every-dow">
				<?php foreach ( $calendarp_weekdays as $calendarp_day_index => $calendarp_day_name ) : ?>
					<label>
						<input type="checkbox" class="recurring-every-field recurring-every-field-dow" name="event_details[recurring][every][]"
								value="<?php echo esc_attr( $calendarp_day_index ); ?>"<?php checked( is_array( $calendarp_every ) && in_array( $calendarp_day_index, $calendarp_every, true ) ); ?>>
						<?php echo esc_html( $calendarp_day_name ); ?>
					</label>
				<?php endforeach; ?>
				</span>

				<div class="recurring-every-container recurring-every-dom">
					<table>

						<?php

						foreach ( $calendarp_week_numbers as $calendarp_week => $calendarp_label ) {
							$calendarp_selected_days = isset( $calendarp_every[ $calendarp_week ] ) && is_array( $calendarp_every[ $calendarp_week ] ) ? $calendarp_every[ $calendarp_week ] : array();

							?>

							<tr>
								<th><?php echo esc_html( $calendarp_label ); ?></th>

								<?php foreach ( $calendarp_weekdays as $calendarp_day_index => $calendarp_day_name ) { ?>
									<td>
										<label>
											<input type="checkbox" class="recurring-every-field recurring-every-field-dom"
													name="event_details[recurring][every][<?php echo esc_html( $calendarp_week ); ?>][]"
													value="<?php echo esc_attr( $calendarp_day_index ); ?>"
												<?php
												checked( in_array( $calendarp_day_index, $calendarp_selected_days, true ) );
												?>
											>
											<?php
											echo esc_html( $calendarp_day_name );
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
					$calendarp_options = array(
						'day'  => __( 'Day(s)', 'calendar-plus' ),
						'week' => __( 'Week(s)', 'calendar-plus' ),
						'year' => __( 'Year(s)', 'calendar-plus' ),
						'dow'  => __( 'Days of the week', 'calendar-plus' ),
						'dom'  => __( 'Days of the month', 'calendar-plus' ),
					);

					foreach ( $calendarp_options as $calendarp_option => $calendarp_label ) {
						?>
						<option value="<?php echo esc_attr( $calendarp_option ); ?>" <?php selected( $recurring_rules['every']['what'], $calendarp_option ); ?>>
							<?php echo esc_html( $calendarp_label ); ?>
						</option>
					<?php } ?>
				</select>
			</div>
			<div id="event-dates-recurrent-time" class="event-dates-recurrent-subsection">
				<h3>
					<span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Time', 'calendar-plus' ); ?>
				</h3>

				<?php foreach ( $calendarp_duration_options as $calendarp_i => $calendarp_option ) { ?>

					<p class="recurring-times-from">
						<label class="recurring-times-<?php echo esc_html( $calendarp_option ); ?>-label" for="event_details[recurring][<?php echo esc_html( $calendarp_option ); ?>_time_hour]"><?php echo esc_html( $calendarp_time_duration_labels[ $calendarp_i ] ); ?></label>
						<?php
						calendarp_time_selector(
							array(
								'selected'     => $recurring_rules['times'][ $calendarp_option ],
								'hours_name'   => "event_details[recurring][{$calendarp_option}_time_hour]",
								'minutes_name' => "event_details[recurring][{$calendarp_option}_time_minute]",
								'am_pm_name'   => "event_details[recurring][{$calendarp_option}_time_am_pm]",
							)
						);
						?>
					</p>
				<?php } ?>

				<p>
					<label>
						<input type="checkbox" name="event_details[recurring][all_day_event]" id="event-dates-recurrent-time-all-day-event"<?php checked( $event->is_all_day_event() ); ?>>
						<?php esc_html_e( 'All day event', 'calendar-plus' ); ?>
					</label>
				</p>
			</div>

			<div id="event-dates-recurrent-exclusions" class="event-dates-recurrent-subsection">
				<h3>
					<span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Excluded dates', 'calendar-plus' ); ?>
				</h3>
				<div id="event-dates-recurrent-exclusions-list">

					<?php foreach ( $exclusions as $calendarp_i => $calendarp_exclusion ) : ?>
						<div id="recurring-exclusions-<?php echo esc_html( $calendarp_i + 1 ); ?>" class="recurring-excluded-date">
							<input type="text" class="recurring-exclusions-datepicker datepicker" name="event_details[recurring][exclusions][]" value="<?php echo esc_attr( $calendarp_exclusion['date'] ); ?>">
							<a href="#" class="remove-link remove-exclusions-date dashicons dashicons-no-alt" data-excluded-date-id="<?php echo esc_attr( $calendarp_i + 1 ); ?>"></a>
						</div>
					<?php endforeach; ?>

					<input type="hidden" name="recurring-exclusions-index" id="recurring-exclusions-index" value="<?php echo count( $exclusions ); ?>">
				</div>
				<div id="event-dates-recurrent-exclusions-date-template" class="hidden recurring-excluded-date">
					<input type="text" class="recurring-exclusions-datepicker" name="event_details[recurring][exclusions][]" value="">
					<a href="#" class="remove-link dashicons remove-exclusions-date dashicons-no-alt"></a>
				</div>
				<button class="button" id="event-dates-recurrent-exclusions-add"><?php esc_html_e( 'Add excluded date', 'calendar-plus' ); ?></button>
			</div>
		</div>

		<div id="event-dates-datespan" class="event-dates-option hidden">
			<p>
				<label>
					<input type="checkbox" name="event_details[datespan][all_day_event]" id="event-dates-datespan-time-all-day-event"<?php checked( $event->is_all_day_event() ); ?>>
					<?php esc_html_e( 'All day event', 'calendar-plus' ); ?>
				</label>
			</p>

			<?php foreach ( $calendarp_duration_options as $calendarp_i => $calendarp_option ) { ?>
				<p>
					<label for="event_details[datespan][<?php echo esc_html( $calendarp_option ); ?>_date]" class="datespan-<?php echo esc_html( $calendarp_option ); ?>-date-label"><?php echo esc_html( $calendarp_date_duration_labels[ $calendarp_i ] ); ?></label>
					<input type="text" class="datepicker" id="event_details[datespan][<?php echo esc_html( $calendarp_option ); ?>_date]" name="event_details[datespan][<?php echo esc_html( $calendarp_option ); ?>_date]"
							value="<?php echo esc_attr( $datespan_rules[ "{$calendarp_option}_date" ] ); ?>">
				</p>
			<?php } ?>

			<p class="event-datespan-time">
				<?php
				foreach ( $calendarp_duration_options as $calendarp_i => $calendarp_option ) {
					?>

					<label class="datespan-<?php echo esc_html( $calendarp_option ); ?>-time-label"><?php echo esc_html( $calendarp_time_duration_labels[ $calendarp_i ] ); ?></label>

					<?php
					calendarp_time_selector(
						array(
							'selected'     => $datespan_rules[ "{$calendarp_option}_time" ],
							'hours_name'   => "event_details[datespan][{$calendarp_option}_time_hour]",
							'minutes_name' => "event_details[datespan][{$calendarp_option}_time_minute]",
							'am_pm_name'   => "event_details[datespan][{$calendarp_option}_time_am_pm]",
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
		var type = '<?php echo esc_attr( $event->get_event_type() ); ?>';
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
