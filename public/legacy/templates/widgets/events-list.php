<?php
/**
 * Legacy Events List Widget Template
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
?>

<ul class="events-list-widget">
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

			<li class="event">
				<ul>
					<?php foreach ( $output as $field => $content ) : ?>
						<?php
						if ( ! $content ) {
							continue;
						}

						if ( ! $linked ) {
							$content = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $event->ID ) ), $content );
							$linked = true;
						}
						?>
						<li class="event-<?php echo esc_attr( $field ); ?>"><?php echo $content; ?></li>
					<?php endforeach; ?>
				</ul>
			</li>
		<?php endforeach; ?>
	<?php endforeach; ?>
</ul>

<p><?php echo calendarp_events_permalink( false ); ?></p>
