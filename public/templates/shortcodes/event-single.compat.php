<div>
	<div class="calendarp-event-shortcode-header" style="margin-bottom: 20px">

		<div class="event-meta">
			<?php if ( $date = calendarp_event_human_read_dates( 'date', $event->ID ) ) : ?>
				<div class="event-meta-item event-dates">
					<span class="dashicons dashicons-calendar-alt"></span><?php echo $date; ?>
				</div>
			<?php endif; ?>

			<?php if ( $recurrence = calendarp_event_human_read_dates( 'recurrence', $event->ID ) ) : ?>
				<div class="event-meta-item event-recurrence">
					<span class="dashicons dashicons-update"></span><?php echo $recurrence; ?>
				</div>
			<?php endif; ?>

			<?php if ( $time = calendarp_event_human_read_dates( 'time', $event->ID ) ) : ?>
				<div class="event-meta-item event-time">
					<span class="dashicons dashicons dashicons-clock"></span><?php echo $time; ?>
				</div>
			<?php endif; ?>

			<div class="event-meta-item event-categories">
				<?php calendarp_event_categories_list( $event->ID ); ?>
			</div>

			<div class="event-meta-item event-ical" style="overflow: hidden">
				<?php calendarp_event_ical_file_button( $event->ID ); ?>
			</div>
		</div>
	</div>
	<div class="calendarp-event-shortcode-content">
		<?php do_action( 'calendarp_content_event_content', $event ); ?>
	</div>
</div>
