<?php
/**
 * Calendar Plus: public/integration/themes/twentyfifteen.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


add_action( 'calendarp_before_page_title', 'calendarp_twentyfifteen_before_elements' );
/**
 * Handle calendarp twentyfifteen before elements.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_twentyfifteen_before_elements() { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Universal.NamingConventions.NoReservedKeywordParameterNames.classFound -- Preserve this existing public API or native callback signature; compatibility includes parameter names and the historical class/property name.
	?>
	<article class="page type-page status-publish hentry">

		<header class="entry-header">
	<?php
}

add_action( 'calendarp_after_page_title', 'calendarp_twentyfifteen_after_elements' );
/**
 * Handle calendarp twentyfifteen after elements.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_twentyfifteen_after_elements() {
	?>
			</header>
			<div class="entry-content"> </div>
		</article>
	<?php
}

add_filter( 'calendarp_page_archive_event_title_class', 'calendarp_twentyfifteen_archive_event_title_class' );
/**
 * Handle calendarp twentyfifteen archive event title class.
 *
 * @param mixed $class Class.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_twentyfifteen_archive_event_title_class( $class ) {
	return 'entry-title';
}

/**
 * Handle calendarp before content.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_before_content() {
	$template = get_option( 'template' );
	?>
		<div id="primary" class="content-area">
			<main id="main" class="site-main calendar-theme-<?php echo esc_attr( $template ); ?>" role="main">
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
