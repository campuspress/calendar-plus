<?php

/**
 * @var string              $current_location
 * @var string              $meta_box_slug
 * @var Calendar_Plus_Event $event
 */
?>
<div id="location-search"></div>
<script id="location-search-result-template" type="text/html">
    <a href="#" data-location-id="<%= id %>" class="location-result">[<%= id %>] <%= title %> (<%= slug %>)</a>
</script>
<script id="location-search-template" type="text/html">
    <div id="location-results"></div>
    <div id="location-search">
        <div class="spinner"></div>
        <input type="text" id="location-search-string" class="components-text-control__input" value="<%= title %>" aria-label="Location search">
        <input type="hidden" name="event_location[_location_id]" id="event_location" value="<%= id %>">
    </div>
</script>

<script>
	(function( $ ) {
		var selectedLocation    = <?php echo $current_location; ?>;
		const search = new window.CalendarPlusAdmin.misc.eventLocationSearch()
        search.init({
            model: selectedLocation
        });
    })(jQuery);
</script>
<?php

do_action( 'calendarp_' . $meta_box_slug . '_meta_box', $event );

wp_nonce_field( 'calendarp_' . $meta_box_slug . '_meta_box', 'calendarp_' . $meta_box_slug . '_nonce' );
