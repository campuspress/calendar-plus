<?php
/**
 * Event meta information list (reusable partial)
 * 
 * @var int  $event_id Event ID
 * @var bool $show_add_to_calendar Optional. Show add to calendar links. Default true.
 */

$event_id = isset( $args['event_id'] ) ? $args['event_id'] : get_the_ID();
$show_add_to_calendar = isset( $args['show_add_to_calendar'] ) ? $args['show_add_to_calendar'] : true;
?>

<ul class="cal-plus-data-list cal-plus-event__meta">
	<?php
	$event_dates = calendarp_event_human_read_dates( 'date', $event_id );
	if ( ! empty( $event_dates ) ) :
		?>
		<li class="cal-plus-event__meta-item cal-plus-event__meta-item--dates">
			<span class="cal-plus-event__meta-item-icon dashicons dashicons-calendar-alt" aria-hidden="true"></span> 
			<span class="cal-plus-event__meta-item-text"><?php echo esc_html( $event_dates ); ?></span>
		</li>
		<?php 
	endif; 
	?>

	<?php
	$event_recurrence = calendarp_event_human_read_dates( 'recurrence', $event_id );
	if ( ! empty( $event_recurrence ) ) :
		?>
		<li class="cal-plus-event__meta-item cal-plus-event__meta-item--recurrence">
			<span class="cal-plus-event__meta-item-icon dashicons dashicons-update" aria-hidden="true"></span> 
			<span class="cal-plus-event__meta-item-text"><?php echo esc_html( $event_recurrence ); ?></span>
		</li>
	<?php endif; ?>

	<?php
	$event_time = calendarp_event_human_read_dates( 'time', $event_id );
	if ( ! empty( $event_time ) ) :
		?>
		<li class="cal-plus-event__meta-item cal-plus-event__meta-item--time">
			<span class="cal-plus-event__meta-item-icon dashicons dashicons-clock" aria-hidden="true"></span> 
			<span class="cal-plus-event__meta-item-text"><?php echo esc_html( $event_time ); ?></span>
		</li>
		<?php 
	endif; 
	?>

	<?php if ( has_term( '', 'calendar_event_category', $event_id ) ) : ?>
		<li class="cal-plus-event__meta-item cal-plus-event__meta-item--categories">
			<?php calendarp_event_categories_list( $event_id ); ?>
		</li>
	<?php endif; ?>

	<?php if ( $show_add_to_calendar ) : ?>
		<li class="cal-plus-event__meta-item cal-plus-event__meta-item--add-to-calendar">
			<span class="cal-plus-event__meta-item-text">
				<?php esc_html_e( 'Add to', 'calendar-plus' ); ?>: 
				<?php calendarp_event_add_to_calendars_links( $event_id ); ?>
			</span>
		</li>
	<?php endif; ?>
</ul>
