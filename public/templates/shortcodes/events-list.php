<?php
/**
 * Modern events list shortcode template
 * 
 * @var array $template_data {
 *    @type bool $featured_image
 *    @type bool $display_location
 *    @type bool $display_excerpt
 * }
 * @var array $event_groups
 */

foreach ( $event_groups as $events_by_date ) {
	foreach ( $events_by_date as $date => $events ) {
		$month_name = mysql2date( 'M', $date, true );
		$day        = mysql2date( 'd', $date, true );
		?>
		<div class="cal-plus-events-list">
			<?php include( calendarp_locate_template( 'shortcodes/events-list-item.php' ) ); ?>
		</div>
		<?php
	}
}
