<?php
/**
 * Calendar Plus: public/integration/themes/sixteen.php.
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
		<div id="primary" class="content-area">
			<main id="main" class="site-main" role="main">
	<?php
}

/**
 * Handle calendarp after content.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_after_content() {
	?>
			</main>
		</div>
	<?php
}
