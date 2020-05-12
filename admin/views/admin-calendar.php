<div class="calendarp-calendar-backdrop"></div>

<h2 class="nav-tab-wrapper">
	<?php foreach ( $tabs as $key => $label ) : ?>
		<?php
			$url = remove_query_arg(
				array(
					'calendar_day',
					'calendar_month',
					'calendar_year',
				)
			);
		?>
		<a href="<?php echo esc_url( add_query_arg( 'tab', $key, $url ) ); ?>" class="nav-tab <?php echo $current_tab === $key ? 'nav-tab-active' : ''; ?>"><?php echo $label; ?></a>
	<?php endforeach; ?>
</h2>

<div class="wrap">
	
	<?php $calendar_renderer->calendar_slots( $calendar_cells_week, $current_tab, calendarp_get_calendar_event_template(), calendarp_get_calendar_event_popup_template() ); ?>
	<?php echo $the_calendar; ?>

	<form action="" id="calendarp-controls-form" method="post">
		<?php echo $calendar_renderer->controls(); ?>
	</form>
</div>

<?php $calendar_cells_ids_js = json_encode( $calendar_cells_ids ); ?>
<script>
	calendarCellsIds = <?php echo $calendar_cells_ids_js; ?>;
	calendarMode = '<?php echo $current_tab; ?>';
	jQuery(document).ready(function($) {
		

		var calendarpCalendar = new CalendarPlusAdmin.misc.Calendar( '<?php echo $current_tab; ?>' );

		var calendarpForm = $('#calendarp-controls-form');
		var calendarpHeading = $('.calendarp_calendar .heading-row' );

		calendarpHeading.css( 'float', 'left' );
		calendarpForm.remove().css( 'float', 'right' ).insertAfter( calendarpHeading );

	});
</script>




