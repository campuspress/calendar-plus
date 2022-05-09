<?php
$is_inline = true;

foreach ( $event_groups as $events_by_date ) {
	foreach ( $events_by_date as $date => $events ) {
		$month_name = mysql2date( 'M', $date, true );
		$day        = mysql2date( 'd', $date, true );

		?>
        <div class="calendarp">
            <?php include( calendarp_locate_template( 'shortcodes/events-list-item.php' ) ); ?>
        </div>
		<?php
	}
}
