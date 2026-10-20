<?php
/**
 * Calendar Plus: public/templates/shortcodes/events-list.php.
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
 * @var array $template_data {
 *    @type bool $featured_image
 * }
 */
$is_inline = true;

foreach ( $event_groups as $calendarp_events_by_date ) {
	foreach ( $calendarp_events_by_date as $date => $events ) {
		$month_name = mysql2date( 'M', $date, true );
		$day        = mysql2date( 'd', $date, true );

		?>
		<div class="calendarp">
			<?php include calendarp_locate_template( 'shortcodes/events-list-item.php' ); ?>
		</div>
		<?php
	}
}
