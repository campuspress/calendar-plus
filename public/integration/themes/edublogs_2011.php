<?php
/**
 * Calendar Plus: public/integration/themes/edublogs_2011.php.
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
	?>
	<div id="primary" class="content calendar-theme-edublogs_2011">
		<div id="content" role="main" >
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
