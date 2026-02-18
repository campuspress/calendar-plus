<?php
/**
 * Modern widget template: This Month Events
 * 
 * @var array  $args     Widget display arguments (before_widget, after_widget, etc.)
 * @var array  $instance Widget settings
 * @var array  $events   Array of event objects for this month
 */
?>

<?php echo $args['before_widget']; ?>

<?php if ( ! empty( $instance['title'] ) ) : ?>
	<?php echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; ?>
<?php endif; ?>

<?php if ( ! empty( $events ) ) : ?>
	<ul class="cal-plus-widget-list cal-plus-widget-list--this-month">
		<?php foreach ( $events as $event ) : ?>
			<li class="cal-plus-widget-list__item">
				<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>" 
				   class="cal-plus-widget-list__link"
				   title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'calendar-plus' ), get_the_title( $event->ID ) ) ); ?>">
					<?php echo esc_html( get_the_title( $event->ID ) ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
<?php else : ?>
	<p class="cal-plus-widget__no-events">
		<strong><?php esc_html_e( 'There are no events for this month.', 'calendar-plus' ); ?></strong>
	</p>
<?php endif; ?>

<div class="cal-plus-widget__footer">
	<?php calendarp_events_permalink(); ?>
</div>

<?php echo $args['after_widget']; ?>
