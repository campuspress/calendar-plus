<?php
/**
 * Legacy widget template: This Week Events
 * 
 * @var array  $args     Widget display arguments
 * @var array  $instance Widget settings
 * @var array  $events   Array of event objects for this week
 */
?>

<?php echo $args['before_widget']; ?>
<?php echo $args['before_title']; ?>
<?php echo $instance['title']; ?>
<?php echo $args['after_title']; ?>

<?php if ( ! empty( $events ) ) : ?>
	<ul class="this-week-events-list">
		<?php foreach ( $events as $event ) : ?>
			<li><a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'calendar-plus' ), get_the_title( $event->ID ) ) ); ?>"><?php echo get_the_title( $event->ID ); ?></a></li>
		<?php endforeach; ?>
	</ul>
<?php else : ?>
	<strong><?php _e( 'There are no events for this week.', 'calendar-plus' ); ?></strong>
<?php endif; ?>

<br/>
<?php calendarp_events_permalink(); ?>

<?php echo $args['after_widget']; ?>
