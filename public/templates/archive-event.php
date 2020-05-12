<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php get_header( 'calendar-plus' ); ?>

	<?php do_action( 'calendarp_before_content' ); ?>

	<div class="calendarp">

		<?php do_action( 'calendarp_before_page_title' ); ?>

		<h1 class="<?php echo apply_filters( 'calendarp_page_archive_event_title_class', 'page-title' ); ?>"><?php echo esc_html( calendarp_get_template_title() ); ?></h1>

		<?php do_action( 'calendarp_after_page_title' ); ?>

		<?php if ( have_posts() ) : ?>

			<?php do_action( 'calendarp_before_events_loop' ); ?>

			<?php
            while ( have_posts() ) :
				the_post();
				?>

				<?php calendarp_get_template_part( 'content', 'event' ); ?>

			<?php endwhile; ?>

			<?php do_action( 'calendarp_after_events_loop' ); ?>

		<?php else : ?>

			<p class="calendarp-warning"><?php _e( 'No events were found.', 'calendar-plus' ); ?></p>

		<?php endif; ?>

	</div>

	<?php do_action( 'calendarp_after_content' ); ?>

	<?php do_action( 'calendarp_sidebar' ); ?>

	<?php do_action( 'calendarp_after_sidebar' ); ?>

<?php get_footer( 'calendar-plus' ); ?>
