<?php
/**
 * Render an events showcase block.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes values and uses core block wrappers.
echo Calendar_Plus_Events_Showcase::shell( $attributes, $content, $block );
