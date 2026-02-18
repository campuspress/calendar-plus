<?php
/*
 * Legacy theme Integration method.
 * This file is used to display the single events page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>


<?php get_header( 'calendar-plus' ); ?>

<?php do_action( 'calendarp_before_content' ); ?>

<div class="calendarp calendarp-single">

	<div class="calendarp-breadcrumbs row">
		<div class="columns large-12">
			<?php echo calendarp_breadcrumbs(); ?>
		</div>
	</div>

	<?php if ( have_posts() ) : ?>

		<?php
        while ( have_posts() ) :
			the_post();
			?>

			<?php calendarp_get_template_part( 'content', 'event' ); ?>

		<?php endwhile; ?>

	<?php endif; ?>
</div>

<?php do_action( 'calendarp_after_content' ); ?>

<?php do_action( 'calendarp_sidebar' ); ?>

<?php do_action( 'calendarp_after_sidebar' ); ?>

<?php get_footer( 'calendar-plus' ); ?>
