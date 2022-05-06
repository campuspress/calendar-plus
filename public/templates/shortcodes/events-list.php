<?php
calendarp_get_template_part( 'content/event-list-group-selector' );

foreach ( $event_groups as $events_by_date ) {
	foreach ( $events_by_date as $date => $events ) {
		$month_name = mysql2date( 'M', $date, true );
		$day        = mysql2date( 'd', $date, true );

		?>
		<div class="calendarp">
			<div class="calendarp_calendar agenda-minified-calendar">
				<div class="calendarp-date-item row">
					<div class="calendarp-date large-2 columns text-center">
						<?php if ( 'sticky' !== $date ) : ?>
							<div class="calendarp-date-month"><?php echo $month_name; ?></div>
							<div class="calendarp-date-day"><?php echo $day; ?></div>
						<?php endif; ?>
					</div>
					<div class="calendarp-events large-10 columns">
						<?php
						/** @var Calendar_Plus_Event $event */
						foreach ( $events as $event ) :
							?>
							<div class="calendar-event">
								<h3>
									<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>">
										<?php echo get_the_title( $event->ID ); ?>
									</a>
								</h3>
								<div class="calendarp-event-meta">
									<?php echo calendarp_get_human_read_dates( $event->ID ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
