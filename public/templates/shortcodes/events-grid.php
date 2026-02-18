<?php
/**
 * Modern events grid shortcode template
 * 
 * @var array $event_groups
 * @var array $template_data {
 *      @type int $column_size
 *      @type int $columns
 *      @type bool $featured_image
 *      @type bool $display_location
 *      @type bool $display_excerpt
 * }
 */

foreach ( $event_groups as $events_by_date ) {
	$items_in_row  = 0;
	$items_counter = 0;
	$total_events  = array_sum( array_map( "count", $events_by_date ) );

	foreach ( $events_by_date as $date => $group ) {
		foreach ( $group as $event ) {
			$events     = array( $event );
			$month_name = mysql2date( 'M', $date, true );
			$day        = mysql2date( 'd', $date, true );
			$row_ends   = 1 === ( $items_in_row + 1 ) / $template_data['columns'];
			
			if ( 0 === $items_in_row ) {
				?>
				<div class="cal-plus-events-grid__row">
				<?php
			}
			
			$items_in_row++;
			$items_counter++;
			?>
			<div class="cal-plus-events-grid__item" style="flex: 0 0 <?php echo esc_attr( 100 / $template_data['columns'] ); ?>%;">
				<?php include( calendarp_locate_template( 'shortcodes/events-list-item.php' ) ); ?>
			</div>
			<?php
			
			if ( $row_ends || $total_events === $items_counter ) {
				?>
				</div>
				<?php
				$items_in_row = 0;
			}
		}
	}
}
