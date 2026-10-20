<?php
/**
 * Event feed dynamic block.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer validates and escapes every field.
echo Calendar_Plus_Event_Feed::render( $attributes );
