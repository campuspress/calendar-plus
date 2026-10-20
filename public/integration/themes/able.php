<?php
/**
 * Calendar Plus: public/integration/themes/able.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Handle calendarp before content.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_before_content() {
	$template = get_option( 'template' );
	?>
	<div id="primary" class="site-content">
		<div id="content" role="main" class="calendar-theme-<?php echo esc_attr( $template ); ?>"">

	<?php
}

/**
 * Handle calendarp after content.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_after_content() {
	?>
		</div>
	</div>
	<?php
}

