<?php
/**
 * Legacy widget template: Event Categories
 * 
 * @var array  $args          Widget display arguments
 * @var array  $instance      Widget settings
 * @var array  $category_args Arguments for wp_list_categories
 */
?>

<?php echo $args['before_widget']; ?>
<?php echo $args['before_title']; ?>
<?php echo $instance['title']; ?>
<?php echo $args['after_title']; ?>

<ul>
	<?php wp_list_categories( $category_args ); ?>
</ul>

<br/>
<?php calendarp_events_permalink(); ?>

<?php echo $args['after_widget']; ?>
