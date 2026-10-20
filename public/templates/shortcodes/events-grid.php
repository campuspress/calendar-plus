<?php
/**
 * Calendar Plus: public/templates/shortcodes/events-grid.php.
 *
 * @package Calendar_Plus
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Preserve the existing overridable event-item template context (date, day, events, is_inline, month_name).
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calendar render context.
 *
 * @var array $event_groups
 * @var array $template_data {
		@param int $column_size
 *      @param int $columns
 * }
 */
$is_inline = false;

foreach ( $event_groups as $calendarp_events_by_date ) {
	$calendarp_items_in_row  = 0;
	$calendarp_items_counter = 0;
	$calendarp_total_events  = array_sum( array_map( 'count', $calendarp_events_by_date ) );

	foreach ( $calendarp_events_by_date as $date => $calendarp_group ) {

		foreach ( $calendarp_group as $calendarp_event ) {

			$events             = array( $calendarp_event );
			$month_name         = mysql2date( 'M', $date, true );
			$day                = mysql2date( 'd', $date, true );
			$calendarp_row_ends = 1 === ( $calendarp_items_in_row + 1 ) / $template_data['columns'];
			?>
			<?php
			if ( 0 === $calendarp_items_in_row ) {
				?>
				<div class="calendarp-grid-row columns">
				<?php
			}
			++$calendarp_items_in_row;
			++$calendarp_items_counter;
			?>
					<div class="calendarp column large-<?php echo esc_html( $template_data['column_size'] ); ?>">
						<?php include calendarp_locate_template( 'shortcodes/events-list-item.php' ); ?>
					</div>
			<?php
			if ( $calendarp_row_ends || $calendarp_total_events === $calendarp_items_counter ) {
				?>
				</div>
				<?php
				$calendarp_items_in_row = 0;
			}
		}
	}
}
