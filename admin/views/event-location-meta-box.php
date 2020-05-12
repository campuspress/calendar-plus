<?php

/**
 * @var int                 $location_id
 * @var string              $meta_box_slug
 * @var Calendar_Plus_Event $event
 */

calendarp_locations_dropdown(
	array(
		'name'     => 'event_location[_location_id]',
		'id'       => 'event-location',
		'selected' => $location_id,
	)
);

?>

<div id="selected-location"></div>
<div id="location-search"></div>

<?php

do_action( 'calendarp_' . $meta_box_slug . '_meta_box', $event );

wp_nonce_field( 'calendarp_' . $meta_box_slug . '_meta_box', 'calendarp_' . $meta_box_slug . '_nonce' );
