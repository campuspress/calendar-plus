<?php
add_action( 'admin_print_styles', 'calendarp_event_datepicker_fix' );

function calendarp_event_datepicker_fix() {
	$screen = get_current_screen();
	if (
		$screen &&
		'post' === $screen->base &&
		'calendar_event' === $screen->post_type
	) {
		echo '<style>
				.ui-datepicker-calendar thead th{
					background: #ffffff !important;
					color: #444 !important;
				}
				#ui-datepicker-div .ui-datepicker-header{
					 background-color: #34495e !important;
				}

				#ui-datepicker-div .ui-datepicker-year,
				#ui-datepicker-div .ui-datepicker-month {
					color: #dadada !important;
				}
			  </style>';
	}
}