<?php
/**
 * Calendar Plus: public/templates/shortcodes/event-single.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="calendarp-event-shortcode">
	<div class="calendarp-event-shortcode-header">
		<?php


		echo wp_kses_post( calendarp_get_the_event_thumbnail( $event->ID, 'event_thumbnail' ) );
		?>
		<h3>
			<a href="<?php echo esc_attr( get_permalink( $event->ID ) ); ?>"
				title="
				<?php
				esc_attr(
					sprintf( /* translators: %s: event title or URL. */
						__( 'Permalink to %s', 'calendar-plus' ),
						get_permalink()
					)
				);
				?>
	">
				<?php echo esc_html( get_the_title( $event->ID ) ); ?>
			</a>
		</h3>

		<div class="event-meta">
			<?php $calendarp_date = calendarp_event_human_read_dates( 'date', $event->ID ); ?>
<?php if ( $calendarp_date ) : ?>
				<div class="event-meta-item event-dates">
					<span class="dashicons dashicons-calendar-alt"></span><?php echo esc_html( $calendarp_date ); ?>
				</div>
			<?php endif; ?>

			<?php $calendarp_recurrence = calendarp_event_human_read_dates( 'recurrence', $event->ID ); ?>
<?php if ( $calendarp_recurrence ) : ?>
				<div class="event-meta-item event-recurrence">
					<span class="dashicons dashicons-update"></span><?php echo esc_html( $calendarp_recurrence ); ?>
				</div>
			<?php endif; ?>

			<?php $calendarp_time = calendarp_event_human_read_dates( 'time', $event->ID ); ?>
<?php if ( $calendarp_time ) : ?>
				<div class="event-meta-item event-time">
					<span class="dashicons dashicons dashicons-clock"></span><?php echo esc_html( $calendarp_time ); ?>
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
