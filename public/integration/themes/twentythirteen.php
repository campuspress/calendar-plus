<?php
/**
 * Calendar Plus: public/integration/themes/twentythirteen.php.
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
function calendarp_before_content() { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Universal.NamingConventions.NoReservedKeywordParameterNames.classFound -- Preserve this existing public API or native callback signature; compatibility includes parameter names and the historical class/property name.
	$template = get_option( 'template' );
	?>
		<div id="primary" class="content-area">
			<div id="content" role="main" class="site-content calendar-theme-<?php echo esc_attr( $template ); ?>">
	<?php
}

add_action( 'calendarp_before_page_title', 'calendarp_twentythirteen_before_elements' );
/**
 * Handle calendarp twentythirteen before elements.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_twentythirteen_before_elements() {
	?>
	<article class="page type-page status-publish hentry">

		<header class="entry-header">
	<?php
}

add_action( 'calendarp_after_page_title', 'calendarp_twentythirteen_after_elements' );
/**
 * Handle calendarp twentythirteen after elements.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_twentythirteen_after_elements() {
	?>
			</header>
			<div class="entry-content"> </div>
		</article>
	<?php
}

add_filter( 'calendarp_page_archive_event_title_class', 'calendarp_twentythirteen_archive_event_title_class' );
/**
 * Handle calendarp twentythirteen archive event title class.
 *
 * @param mixed $class Class.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_twentythirteen_archive_event_title_class( $class ) {
	return 'entry-title';
}


add_action( 'calendarp_event_content_footer_class', 'calendarp_twentythirteen_footer_class' );
/**
 * Handle calendarp twentythirteen footer class.
 *
 * @param mixed $class Class.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_twentythirteen_footer_class( $class ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound -- Preserve this existing public API or native callback signature; compatibility includes parameter names and the historical class/property name.
	return $class . ' entry-meta';
}
