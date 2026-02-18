<?php
/**
 * Modern Events List Widget Template
 *
 * @var array $args Widget display arguments
 * @var array $instance Widget settings
 * @var array $events_by_date Events grouped by date
 * @var bool $display_name Display event name
 * @var bool $display_desc Display event description
 * @var bool $display_date Display event date
 * @var bool $display_time Display event time
 * @var bool $display_cats Display event categories
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $events_by_date ) ) {
	echo '<p class="cal-plus-widget__no-events">' . __( 'No events found.', 'calendar-plus' ) . '</p>';
	return;
}
?>

<ul class="cal-plus-widget-list cal-plus-widget-list--events">
	<?php foreach ( $events_by_date as $date => $events ) : ?>
		<?php foreach ( $events as $event ) : ?>
			<?php
			$output = array();

			if ( $display_name ) {
				$output['name'] = get_the_title( $event->ID );
			}

			if ( $display_time || $display_date ) {
				$dates = calendarp_get_human_read_dates( $event->ID, 'array' );

				if ( $display_time && $display_date && $dates['date'] && $dates['time'] ) {
					$output['date event-time'] = sprintf(
						_x( '%1$s at %2$s', 'time and date sep', 'calendar-plus' ),
						$dates['date'], $dates['time']
					);

				} elseif ( $display_time && $dates['time'] ) {
					$output['time'] = $dates['time'];
				} elseif ( $display_date && $dates['date'] ) {
					$output['date'] = $dates['date'];
				}
			}

			if ( $display_desc ) {
				$output['description'] = get_the_excerpt( $event->ID );
			}

			if ( $display_cats && $cats = get_the_term_list( $event->ID, 'calendar_event_category' ) ) {
				$output['category'] = sprintf( __( 'Posted in %s', 'calendar-plus' ), $cats );
			}

			if ( empty( $output ) ) {
				continue;
			}

			$linked = false;
			?>

			<li class="cal-plus-widget-list__item cal-plus-widget-list__item--event">
				<ul class="cal-plus-event-fields">
					<?php foreach ( $output as $field => $content ) : ?>
						<?php
						if ( ! $content ) {
							continue;
						}

						if ( ! $linked ) {
							$content = sprintf( '<a href="%s" class="cal-plus-widget-list__link">%s</a>', esc_url( get_permalink( $event->ID ) ), $content );
							$linked = true;
						}

						$field_class = 'cal-plus-event-fields__' . esc_attr( str_replace( ' ', '-', $field ) );
						?>
						<li class="<?php echo $field_class; ?>"><?php echo $content; ?></li>
					<?php endforeach; ?>
				</ul>
			</li>
		<?php endforeach; ?>
	<?php endforeach; ?>
</ul>

<p class="cal-plus-widget__footer">
	<?php echo calendarp_events_permalink( false ); ?>
</p>
