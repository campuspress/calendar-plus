<?php
add_action( 'wp_head', 'calendarp_divi_styles_fix' );

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