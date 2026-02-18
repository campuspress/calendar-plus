<?php
/**
 * Modern events list item template
 * 
 * @var Calendar_Plus_Event[] $events
 * @var string $month_name
 * @var string $day
 * @var array $template_data
 */
?>
<article class="cal-plus-event-list-item">
	<div class="cal-plus-event-list-item__date">
		<?php if ( 'sticky' !== $date ) : ?>
			<time class="cal-plus-event-list-item__date-month" aria-label="<?php echo esc_attr( mysql2date( 'F', $date, true ) ); ?>">
				<?php echo esc_html( $month_name ); ?>
			</time>
			<time class="cal-plus-event-list-item__date-day">
				<?php echo esc_html( $day ); ?>
			</time>
		<?php endif; ?>
	</div>

	<div class="cal-plus-event-list-item__content">
		<?php
		/** @var Calendar_Plus_Event $event */
		foreach ( $events as $event ) :
			?>
			<div class="cal-plus-event-list-item__event">
				<h3 class="cal-plus-event-list-item__title">
					<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>">
						<?php echo esc_html( get_the_title( $event->ID ) ); ?>
					</a>
				</h3>

				<div class="cal-plus-event-list-item__meta">
					<?php echo calendarp_get_human_read_dates( $event->ID ); ?>
				</div>

				<?php if ( $template_data['display_location'] && $event->get_location() ) : ?>
					<address class="cal-plus-event-list-item__location">
						<span class="dashicons dashicons-location" aria-hidden="true"></span>
						<?php echo esc_html( $event->get_location()->get_full_address() ); ?>
					</address>
				<?php endif; ?>

				<?php if ( $template_data['display_excerpt'] && has_excerpt( $event->get_post() ) ) : ?>
					<div class="cal-plus-event-list-item__excerpt">
						<?php echo apply_filters( 'the_excerpt', get_the_excerpt( $event->get_post() ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $template_data['featured_image'] && has_post_thumbnail( $event->ID ) ) : ?>
					<div class="cal-plus-event-list-item__thumbnail">
						<?php echo get_the_post_thumbnail( $event->ID, 'medium' ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</article>