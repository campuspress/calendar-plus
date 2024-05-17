<?php
require_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';

$date = calendarp_get_human_read_dates( get_the_ID() );
if( $date ) {
    $date = '<span class="calendarp-block-event-date">' . $date . '</span>';
}

echo $date;
