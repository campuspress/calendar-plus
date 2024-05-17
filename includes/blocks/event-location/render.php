<?php
require_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';

$location = calendarp_the_event_location();
if( $location ) {
    $location = $location->get_full_address();
    if( $location ) { 
        $location = '<span class="calendarp-block-event-location">' . $location . '</span>';
    }
}

echo $location;
