<?php

function calendarp_before_content() {
	$template = get_option( 'template' );
	?>
		<div id="primary" class="site-content">
			<div id="content" role="main" class="site-content calendar-theme-<?php echo esc_attr( $template ); ?>">
	<?php
}
