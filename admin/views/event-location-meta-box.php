<?php
/**
 * Calendar Plus: admin/views/event-location-meta-box.php.
 *
 * @package Calendar_Plus
 */

// phpcs:disable Generic.PHP.DisallowAlternativePHPTags.MaybeASPOpenTagFound -- Escaped Underscore client templates use angle-percent-minus delimiters; these are not PHP ASP tags.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Calendar render context.
 *
 * @var string              $current_location
 * @var string              $meta_box_slug
 * @var Calendar_Plus_Event $event
 */
?>
<div id="location-search"></div>
<script id="location-search-result-template" type="text/html">
	<a href="#" data-location-id="<%- id %>" class="location-result">[<%- id %>] <%- title %> (<%- slug %>)</a>
</script>
<script id="location-search-template" type="text/html">
	<div id="location-results"></div>
	<div id="location-search-inner">
		<div class="spinner"></div>
		<input type="text" id="location-search-string" class="components-text-control__input" value="<%- title %>" aria-label="Location search">
		<input type="hidden" name="event_location[_location_id]" id="event_location" value="<%- id %>">
	</div>
</script>

<script>
	jQuery(function( $ ) {
		var selectedLocation    =
		<?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- A JavaScript value with every HTML-sensitive character hex-encoded, not HTML text.
		echo wp_json_encode( json_decode( $current_location ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		?>
	;
		const search = new window.CalendarPlusAdmin.misc.eventLocationSearch()
		search.init({
			model: selectedLocation
		});
	});
</script>
<?php

// phpcs:enable Generic.PHP.DisallowAlternativePHPTags

do_action( 'calendarp_' . $meta_box_slug . '_meta_box', $event );

wp_nonce_field( 'calendarp_' . $meta_box_slug . '_meta_box', 'calendarp_' . $meta_box_slug . '_nonce' );
