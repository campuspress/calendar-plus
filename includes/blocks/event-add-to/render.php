<?php
/**
 * Context-aware event field.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shared renderer escapes each field.
echo Calendar_Plus_Event_Display::field( 'add-to', $attributes, $block );
