<?php
/**
 * Calendar Plus: public/integration/plugins/divi-builder.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', 'calendarp_divi_styles_fix' );

/**
 * Handle calendarp divi styles fix.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_divi_styles_fix() {
	?>
	<style id="calendarp_divi_fix">
		.calendar-plus #calendar-plus-single-event {
			color:#333 !important;
			position: absolute !important;
			padding:2rem !important;
			background:#FFF !important;
			border:1px solid #DADADA !important;
		}
	</style>
	<?php
}