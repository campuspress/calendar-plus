<?php
/**
 * Calendar Plus: public/templates/content/pagination.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;

if ( $wp_query->max_num_pages <= 1 ) {
	return;
}

?>

<?php do_action( 'calendarp_before_pagination' ); ?>

<nav class="calendarp-pagination row">
	<?php
	$calendarp_pagination_args = apply_filters(
		'calendarp_pagination_args',
		array(
			'base'      => esc_url_raw( str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ) ),
			'format'    => '',
			'add_args'  => '',
			'current'   => max( 1, get_query_var( 'paged' ) ),
			'total'     => $wp_query->max_num_pages,
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
			'type'      => 'array',
			'end_size'  => 3,
			'mid_size'  => 3,
		)
	);

	$calendarp_pagination = paginate_links( $calendarp_pagination_args );

	?>
	<ul class="pagination large-12 columns">
		<?php foreach ( $calendarp_pagination as $calendarp_page ) : ?>
			<?php $calendarp_current = strpos( $calendarp_page, 'current' ) !== false ? 'current' : ''; ?>
			<?php if ( $calendarp_current ) : ?>
				<li class="current">
					<a href=""><?php echo wp_kses_post( $calendarp_page ); ?></a>
				</li>
			<?php else : ?>
				<li><?php echo wp_kses_post( $calendarp_page ); ?></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'calendarp_after_pagination' ); ?>
