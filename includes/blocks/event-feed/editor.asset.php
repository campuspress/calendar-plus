<?php
/**
 * Calendar Plus: includes/blocks/event-feed/editor.asset.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- WordPress requires the .asset.php companion filename.
/**
 * Editor dependencies.
 *
 * @package Calendar_Plus
 */

return array(
	'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n', 'wp-api-fetch', 'wp-data' ),
	'version'      => '2.2.22',
);
