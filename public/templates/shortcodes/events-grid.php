<?php
/**
 * @var array $event_groups
 * @var array $template_data {
		@param int $column_size
 *      @param int $columns
 * }
 */
$is_inline = false;

foreach ( $event_groups as $events_by_date ) {
	$items_in_row = 0;

	foreach ( $events_by_date as $date => $group ) {

        foreach ( $group as $event ) {

            $events     = array( $event );
            $month_name = mysql2date( 'M', $date, true );
            $day        = mysql2date( 'd', $date, true );

            ?>
            <?php if( $items_in_row === 0 ) {
                ?>
                <div class="calendarp-grid-row columns">
                <?php
            }
            $items_in_row++;
            ?>
                    <div class="calendarp column large-<?php echo $template_data['column_size']; ?>">
                        <?php include( calendarp_locate_template( 'shortcodes/events-list-item.php' ) ); ?>
                    </div>
            <?php if( $items_in_row === $template_data['columns'] ) {
                ?>
                </div>
                <?php
                $items_in_row = 0;
            }
        }
	}
}
