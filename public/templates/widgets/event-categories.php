<?php
/**
 * Modern widget template: Event Categories
 * 
 * @var array  $args          Widget display arguments
 * @var array  $instance      Widget settings
 * @var array  $category_args Arguments for wp_list_categories
 */
?>

<?php echo $args['before_widget']; ?>

<?php if ( ! empty( $instance['title'] ) ) : ?>
	<?php echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; ?>
<?php endif; ?>

<ul class="cal-plus-widget-categories">
	<?php wp_list_categories( $category_args ); ?>
</ul>

<div class="cal-plus-widget__footer">
	<?php calendarp_events_permalink(); ?>
</div>

<?php echo $args['after_widget']; ?>
