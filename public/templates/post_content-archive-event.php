<?php
/**
 * The template used in modern theme integration method.
 * Used to replace post_content for events archive pages.
 *
 * @package CalendarPlus
 * @subpackage Templates
 */
?>

<?php do_action( 'calendarp_post_content_after_page_title' ); ?>

<?php if ( have_posts() ) : ?>

	<?php do_action( 'calendarp_post_content_before_events_loop' ); ?>

	<?php
	while ( have_posts() ) :
		the_post();
		?>

		<?php 
		
		calendarp_get_template_part( 'post_content-content', 'event' ); ?>

	<?php endwhile; ?>

	<?php do_action( 'calendarp_post_content_after_events_loop' ); ?>

<?php else : ?>

	<p class="calendarp-warning"><?php _e( 'No events were found.', 'calendar-plus' ); ?></p>

<?php endif; ?>