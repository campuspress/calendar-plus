<?php

function calendarp_before_content() {
	?>
	<div id="content" class="clearfix calendar-theme-attorney">
	<div id="main" class="col620 clearfix" role="main">
	<?php
}

function calendarp_after_content() {
	?>
	</div>
	<?php
}

add_action( 'calendarp_sidebar', 'calendarp_attorney_after_sidebar', 50 );
function calendarp_attorney_after_sidebar() {
	?>
    </div>
    <?php

}

add_action( 'wp_enqueue_scripts', 'calendarp_attorney_enqueue_styles' );
function calendarp_attorney_enqueue_styles() {
	wp_enqueue_style(
		'attorney-calendarp',
		calendarp_get_plugin_url() . 'public/integration/themes/css/attorney.css',
		[], calendarp_get_version()
	);
}
