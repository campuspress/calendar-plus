<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;

if ( $wp_query->max_num_pages <= 1 ) {
	return;
}

$pagination_nav_classes = apply_filters( 'calendarp_pos_content_pagination_nav_classes', array( 'cal-plus-pagination', 'pagination' ) );
$pagination_links_classes = apply_filters( 'calendarp_pos_content_pagination_links_classes', array( 'cal-plus-pagination__links', 'nav-links' ) );
?>

<?php do_action( 'calendarp_post_content_before_pagination' ); ?>

<nav class="<?php echo implode( ' ', $pagination_nav_classes ); ?>" aria-label="<?php _e( 'Events Pagination', 'calendar-plus' ); ?>">
	<?php
	$pagination_args = apply_filters( 'calendarp_pagination_args', array(
		'base'      => esc_url_raw( str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ) ),
		'format'    => '',
		'add_args'  => '',
		'current'   => max( 1, get_query_var( 'paged' ) ),
		'total'     => $wp_query->max_num_pages,
		'prev_text' => 'Previous',
		'next_text' => 'Next',
		'type'      => 'array',
		'end_size'  => 3,
		'mid_size'  => 3,
	) );

	$pagination = paginate_links( $pagination_args );
	?>

	<div class="<?php echo implode( ' ', $pagination_links_classes ); ?>">
		<?php foreach ( $pagination as $page ) : ?>
			<?php echo $page; ?>
		<?php endforeach; ?>
	</div>
</nav>

<?php do_action( 'calendarp_post_content_after_pagination' ); ?>
