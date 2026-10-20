<?php
/**
 * Calendar Plus: public/integration/themes/apostrophe.php.
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
		<section id="primary" class="content-area">
			<main id="content" role="main" class="site-content">
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
		</section>
	<?php
}
