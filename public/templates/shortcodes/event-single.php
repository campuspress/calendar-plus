<div class="calendarp-event-shortcode">
	<div class="calendarp-event-shortcode-header">
		<?php echo calendarp_get_the_event_thumbnail( $event->ID, 'event_thumbnail' ); ?>
		<h3>
			<a href="<?php echo esc_attr( get_permalink( $event->ID ) ); ?>"
				title="<?php esc_attr( sprintf( __( 'Permalink to %s', 'calendar-plus' ), get_permalink() ) ); ?>">
				<?php echo get_the_title( $event->ID ); ?>
			</a>
		</h3>

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

			<div class="event-meta-item event-ical">
				<?php calendarp_event_ical_file_button( $event->ID ); ?>
			</div>
		</div>
	</div>
	<div class="calendarp-event-shortcode-content">
		<?php do_action( 'calendarp_content_event_content', $event ); ?>
	</div>
</div>
