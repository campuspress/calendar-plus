<?php
require_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';
?>

<div class="event-meta-item" style="overflow: hidden">
    <?php _e( 'Add to', 'calendar-plus' ); ?>:
    <?php calendarp_event_add_to_calendars_links( get_the_ID() ); ?>
</div>