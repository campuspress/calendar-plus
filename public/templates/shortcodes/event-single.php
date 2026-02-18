<div class="cal-plus-event cal-plus-event--shortcode">
	<?php if ( has_post_thumbnail( $event->ID ) ) : ?>
		<div class="cal-plus-event__thumbnail">
			<?php echo calendarp_get_the_event_thumbnail( $event->ID, 'event_thumbnail' ); ?>
		</div>
	<?php endif; ?>

	<h3 class="cal-plus-event__title">
		<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>">
			<?php echo esc_html( get_the_title( $event->ID ) ); ?>
		</a>
	</h3>

	<?php calendarp_get_template( 'partials/event-meta.php', array( 'event_id' => $event->ID ) ); ?>

	<div class="cal-plus-event__content">
		<?php do_action( 'calendarp_content_event_content', $event ); ?>
	</div>
</div>
