<?php

function calendarp_before_content() {
	$template = get_option( 'template' );
	?>
	<div id="primary" class="site-content">
		<div id="content" role="main" class="calendar-theme-<?php echo esc_attr( $template ); ?>"">

	<?php
}

function calendarp_after_content() {
	?>
		</div>
	</div>
	<?php
}

