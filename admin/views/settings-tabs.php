<?php
/**
 * Calendar Plus: admin/views/settings-tabs.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2 class="nav-tab-wrapper">
	<?php


	foreach ( $tabs as $calendarp_key => $calendarp_label ) :
		?>
		<a href="<?php echo esc_url( add_query_arg( 'tab', $calendarp_key ) ); ?>" class="nav-tab <?php echo esc_html( $current_tab === $calendarp_key ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $calendarp_label ); ?></a>
		<?php endforeach; ?>
</h2>
