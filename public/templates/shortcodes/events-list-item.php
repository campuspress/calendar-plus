<?php
/**
 * Shared list and grid event item.
 *
 * @package Calendar_Plus
 * @var bool $is_inline
 * @var Calendar_Plus_Event[] $events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="calendarp_calendar agenda-minified-calendar">
	<div class="calendarp-date-item row">
		<div class="calendarp-date<?php echo $is_inline ? ' large-2 columns' : ''; ?> text-center">
			<?php if ( 'sticky' !== $date ) : ?>
				<div class="calendarp-date-month"><?php echo esc_html( $month_name ); ?></div>
				<div class="calendarp-date-day"><?php echo esc_html( $day ); ?></div>
			<?php endif; ?>
		</div>
		<div class="calendarp-events<?php echo $is_inline ? ' large-10 columns' : ''; ?>">
			<?php
			// Each row is a native Calendar_Plus_Event.
			foreach ( $events as $calendarp_event ) :
				?>
				<div class="calendar-event">
					<h3>
						<a href="<?php echo esc_url( get_permalink( $calendarp_event->ID ) ); ?>">
							<?php echo esc_html( get_the_title( $calendarp_event->ID ) ); ?>
						</a>
					</h3>
					<div class="calendarp-event-meta">
						<?php echo wp_kses_post( calendarp_get_human_read_dates( $calendarp_event->ID ) ); ?>
					</div>
					<?php
					if ( $template_data['display_location'] && $calendarp_event->get_location() ) :
						?>
						<div class="calendarp-even-location">
							<p><?php echo esc_html( $calendarp_event->get_location()->get_full_address() ); ?></p>
						</div>
						<?php
					endif;
					?>
					<?php
					if ( $template_data['display_excerpt'] && has_excerpt( $calendarp_event->get_post() ) ) :
						?>
						<div class="calendarp-event-excerpt">
							<p>
							<?php
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve WordPress core excerpt filters.
							echo wp_kses_post( apply_filters( 'the_excerpt', get_the_excerpt( $calendarp_event->get_post() ) ) );
							?>
						</p>
						</div>
						<?php
					endif;
					?>
					<?php
					if ( $template_data['featured_image'] && has_post_thumbnail( $calendarp_event->ID ) ) :
						?>
						<div class="calendarp-event-thumbnail">
							<?php echo wp_kses_post( get_the_post_thumbnail( $calendarp_event->ID, 'medium_large', array( 'sizes' => '(max-width: 600px) 100vw, 640px' ) ) ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
