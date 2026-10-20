<?php
/**
 * Calendar Plus: admin/views/admin-calendar.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="calendarp-calendar-backdrop"></div>

<h2 class="nav-tab-wrapper">
	<?php


	foreach ( $tabs as $calendarp_key => $calendarp_label ) :
		?>
			<?php
			$calendarp_url = remove_query_arg(
				array(
					'calendar_day',
					'calendar_month',
					'calendar_year',
				)
			);
			?>
		<a href="<?php echo esc_url( add_query_arg( 'tab', $calendarp_key, $calendarp_url ) ); ?>" class="nav-tab <?php echo esc_attr( $current_tab === $calendarp_key ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $calendarp_label ); ?></a>
		<?php endforeach; ?>
</h2>

<div class="wrap">

	<?php $calendar_renderer->calendar_slots( $calendar_cells_week, $current_tab, calendarp_get_calendar_event_template(), calendarp_get_calendar_event_popup_template() ); ?>
	<?php echo wp_kses( $the_calendar, calendarp_get_allowed_calendar_html() ); ?>

	<form action="" id="calendarp-controls-form" method="get">
		<?php $calendar_renderer->controls(); ?>
	</form>
</div>


<script>
	calendarCellsIds =
	<?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- A JSON value in JavaScript, with every HTML-sensitive character hex-encoded.
	echo wp_json_encode( array_map( 'absint', $calendar_cells_ids ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
	?>
	;
	calendarMode = '<?php echo esc_js( $current_tab ); ?>';
	jQuery(document).ready(function($) {


		var calendarpCalendar = new CalendarPlusAdmin.misc.Calendar( '<?php echo esc_js( $current_tab ); ?>' );

		var calendarpForm = $('#calendarp-controls-form');
		var calendarpHeading = $('.calendarp_calendar .heading-row' );

		calendarpHeading.css( 'float', 'left' );
		calendarpForm.remove().css( 'float', 'right' ).insertAfter( calendarpHeading );

	});
</script>



